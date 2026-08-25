import hashlib
import sqlite3
import threading
from datetime import datetime, timezone
from pathlib import Path
from time import perf_counter
from typing import Optional

from prometheus_client import Counter, Histogram


PREDICTIONS = Counter(
    "reviewpro_ai_predictions_total",
    "Nombre de prédictions IA",
    ["category", "needs_review", "status"],
)
ERRORS = Counter(
    "reviewpro_ai_prediction_errors_total",
    "Nombre d'erreurs de prédiction",
    ["error_type"],
)
LATENCY = Histogram(
    "reviewpro_ai_prediction_latency_seconds",
    "Latence des prédictions IA",
    buckets=(0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2, 5),
)
MARGIN = Histogram(
    "reviewpro_ai_prediction_margin",
    "Marge de décision du modèle",
    buckets=(0.05, 0.1, 0.2, 0.3, 0.5, 0.75, 1, 2, 5),
)
FEEDBACK = Counter(
    "reviewpro_ai_feedback_total",
    "Nombre de retours humains",
    ["is_correct"],
)


def utc_now() -> str:
    return datetime.now(timezone.utc).isoformat()


class PredictionMonitor:
    def __init__(self, database_path: Path, model_name: str, model_version: str):
        self.database_path = Path(database_path)
        self.database_path.parent.mkdir(parents=True, exist_ok=True)
        self.model_name = model_name
        self.model_version = model_version
        self._lock = threading.Lock()
        self._initialize()

    def _connect(self):
        connection = sqlite3.connect(self.database_path, timeout=10)
        connection.row_factory = sqlite3.Row
        connection.execute("PRAGMA foreign_keys = ON")
        return connection

    def _initialize(self):
        with self._connect() as connection:
            connection.executescript(
                """
                CREATE TABLE IF NOT EXISTS prediction_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    created_at TEXT NOT NULL,
                    text_hash TEXT NOT NULL,
                    model_name TEXT NOT NULL,
                    model_version TEXT NOT NULL,
                    category TEXT,
                    decision_score REAL,
                    margin REAL,
                    threshold REAL NOT NULL,
                    needs_review INTEGER,
                    latency_ms REAL NOT NULL,
                    status TEXT NOT NULL,
                    error_type TEXT
                );
                CREATE INDEX IF NOT EXISTS idx_prediction_logs_created_at
                    ON prediction_logs(created_at);
                CREATE TABLE IF NOT EXISTS prediction_feedback (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    prediction_id INTEGER NOT NULL UNIQUE,
                    created_at TEXT NOT NULL,
                    corrected_category TEXT NOT NULL,
                    is_correct INTEGER NOT NULL,
                    FOREIGN KEY(prediction_id) REFERENCES prediction_logs(id)
                        ON DELETE CASCADE
                );
                """
            )

    @staticmethod
    def text_hash(text: str) -> str:
        normalized = " ".join(text.strip().lower().split())
        return hashlib.sha256(normalized.encode("utf-8")).hexdigest()

    def record_success(
        self,
        text: str,
        category: str,
        decision_score: float,
        margin: float,
        threshold: float,
        needs_review: bool,
        latency_ms: float,
    ) -> int:
        with self._lock, self._connect() as connection:
            cursor = connection.execute(
                """
                INSERT INTO prediction_logs (
                    created_at, text_hash, model_name, model_version,
                    category, decision_score, margin, threshold,
                    needs_review, latency_ms, status, error_type
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'success', NULL)
                """,
                (
                    utc_now(), self.text_hash(text), self.model_name,
                    self.model_version, category, decision_score, margin,
                    threshold, int(needs_review), latency_ms,
                ),
            )
            prediction_id = int(cursor.lastrowid)
        PREDICTIONS.labels(category, str(needs_review).lower(), "success").inc()
        LATENCY.observe(latency_ms / 1000)
        MARGIN.observe(margin)
        return prediction_id

    def record_error(self, text: str, threshold: float, error: Exception, latency_ms: float):
        error_type = type(error).__name__
        with self._lock, self._connect() as connection:
            connection.execute(
                """
                INSERT INTO prediction_logs (
                    created_at, text_hash, model_name, model_version,
                    category, decision_score, margin, threshold,
                    needs_review, latency_ms, status, error_type
                ) VALUES (?, ?, ?, ?, NULL, NULL, NULL, ?, NULL, ?, 'error', ?)
                """,
                (
                    utc_now(), self.text_hash(text), self.model_name,
                    self.model_version, threshold, latency_ms, error_type,
                ),
            )
        PREDICTIONS.labels("unknown", "unknown", "error").inc()
        ERRORS.labels(error_type).inc()
        LATENCY.observe(latency_ms / 1000)

    def add_feedback(self, prediction_id: int, corrected_category: str):
        with self._lock, self._connect() as connection:
            prediction = connection.execute(
                "SELECT category, status FROM prediction_logs WHERE id = ?",
                (prediction_id,),
            ).fetchone()
            if prediction is None or prediction["status"] != "success":
                raise LookupError("Prédiction introuvable")
            is_correct = int(prediction["category"] == corrected_category)
            connection.execute(
                """
                INSERT INTO prediction_feedback (
                    prediction_id, created_at, corrected_category, is_correct
                ) VALUES (?, ?, ?, ?)
                ON CONFLICT(prediction_id) DO UPDATE SET
                    created_at = excluded.created_at,
                    corrected_category = excluded.corrected_category,
                    is_correct = excluded.is_correct
                """,
                (prediction_id, utc_now(), corrected_category, is_correct),
            )
        FEEDBACK.labels(str(bool(is_correct)).lower()).inc()
        return bool(is_correct)

    def summary(self, hours: int = 24):
        modifier = f"-{int(hours)} hours"
        with self._connect() as connection:
            row = connection.execute(
                """
                SELECT
                    COUNT(*) AS requests,
                    SUM(status = 'success') AS successes,
                    SUM(status = 'error') AS errors,
                    SUM(CASE WHEN needs_review = 1 THEN 1 ELSE 0 END) AS reviews,
                    AVG(CASE WHEN status = 'success' THEN margin END) AS avg_margin,
                    AVG(latency_ms) AS avg_latency_ms
                FROM prediction_logs
                WHERE datetime(created_at) >= datetime('now', ?)
                """,
                (modifier,),
            ).fetchone()
            feedback = connection.execute(
                """
                SELECT COUNT(*) AS total, AVG(is_correct) AS accuracy
                FROM prediction_feedback
                WHERE datetime(created_at) >= datetime('now', ?)
                """,
                (modifier,),
            ).fetchone()
            categories = connection.execute(
                """
                SELECT category, COUNT(*) AS total
                FROM prediction_logs
                WHERE status = 'success'
                  AND datetime(created_at) >= datetime('now', ?)
                GROUP BY category ORDER BY total DESC
                """,
                (modifier,),
            ).fetchall()
        requests = int(row["requests"] or 0)
        successes = int(row["successes"] or 0)
        errors = int(row["errors"] or 0)
        reviews = int(row["reviews"] or 0)
        return {
            "window_hours": hours,
            "requests": requests,
            "successes": successes,
            "errors": errors,
            "error_rate": round(errors / requests, 4) if requests else 0.0,
            "needs_review": reviews,
            "review_rate": round(reviews / successes, 4) if successes else 0.0,
            "average_margin": round(float(row["avg_margin"] or 0), 4),
            "average_latency_ms": round(float(row["avg_latency_ms"] or 0), 2),
            "feedback_count": int(feedback["total"] or 0),
            "feedback_accuracy": (
                round(float(feedback["accuracy"]), 4)
                if feedback["accuracy"] is not None else None
            ),
            "categories": {item["category"]: item["total"] for item in categories},
        }

    def alerts(self, hours: int = 24):
        summary = self.summary(hours)
        alerts = []
        if summary["requests"] >= 5 and summary["error_rate"] > 0.05:
            alerts.append({"code": "high_error_rate", "level": "critical"})
        if summary["successes"] >= 5 and summary["review_rate"] > 0.60:
            alerts.append({"code": "high_manual_review_rate", "level": "warning"})
        if summary["requests"] >= 5 and summary["average_latency_ms"] > 500:
            alerts.append({"code": "high_latency", "level": "warning"})
        if (
            summary["feedback_count"] >= 5
            and summary["feedback_accuracy"] is not None
            and summary["feedback_accuracy"] < 0.60
        ):
            alerts.append({"code": "low_feedback_accuracy", "level": "critical"})
        return {"status": "alert" if alerts else "ok", "alerts": alerts, "summary": summary}

    def clear_for_tests(self):
        with self._lock, self._connect() as connection:
            connection.execute("DELETE FROM prediction_feedback")
            connection.execute("DELETE FROM prediction_logs")


class Timer:
    def __enter__(self):
        self.started = perf_counter()
        self._elapsed_ms = None
        return self

    def __exit__(self, *_):
        self._elapsed_ms = (perf_counter() - self.started) * 1000

    @property
    def elapsed_ms(self):
        if self._elapsed_ms is not None:
            return self._elapsed_ms
        return (perf_counter() - self.started) * 1000
