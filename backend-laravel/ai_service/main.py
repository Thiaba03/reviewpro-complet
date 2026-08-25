import json
import os
from pathlib import Path

import joblib
import numpy as np
from fastapi import FastAPI, HTTPException, Query
from fastapi.responses import Response
from prometheus_client import CONTENT_TYPE_LATEST, generate_latest
from pydantic import BaseModel, Field

from ai_service.monitoring import PredictionMonitor, Timer


BASE_PATH = Path(__file__).resolve().parents[1]
MODEL_PATH = BASE_PATH / "storage/app/ai/models/review_topic_macro_svm.joblib"
METADATA_PATH = BASE_PATH / "storage/app/ai/models/review_topic_macro_svm.metadata.json"
MONITORING_DB_PATH = Path(
    os.getenv("REVIEWPRO_MONITORING_DB", BASE_PATH / "storage/app/ai/monitoring.sqlite")
)

DISPLAY_NAMES = {
    "device_hardware": "Matériel, batterie, écran ou audio",
    "software_ecosystem": "Logiciel, connexion ou compatibilité",
    "usability": "Utilisation et configuration",
    "commercial_service": "Prix, garantie, service ou livraison",
    "other_unclear": "Avis imprécis ou hors cible",
}

model = joblib.load(MODEL_PATH)
metadata = json.loads(METADATA_PATH.read_text(encoding="utf-8"))
threshold = metadata["decision_policy"]["automatic_threshold"]
classes = model.named_steps["classifier"].classes_
monitor = PredictionMonitor(
    MONITORING_DB_PATH,
    metadata["model_name"],
    metadata.get("trained_at", "unknown"),
)

app = FastAPI(
    title="ReviewPro AI",
    version="1.1.0",
    description="Classification et monitorage des plaintes électroniques",
)


class PredictionRequest(BaseModel):
    text: str = Field(min_length=3, max_length=5000)


class RankingItem(BaseModel):
    category: str
    label: str
    score: float


class PredictionResponse(BaseModel):
    prediction_id: int
    category: str
    label: str
    decision_score: float
    margin: float
    threshold: float
    needs_review: bool
    ranking: list[RankingItem]


class FeedbackRequest(BaseModel):
    prediction_id: int = Field(gt=0)
    corrected_category: str


@app.get("/health")
def health():
    return {
        "status": "ok",
        "model": metadata["model_name"],
        "model_version": metadata.get("trained_at", "unknown"),
        "training_rows": metadata["training_rows"],
        "classes": list(classes),
        "threshold": threshold,
    }


@app.post("/predict", response_model=PredictionResponse)
def predict(request: PredictionRequest):
    with Timer() as timer:
        try:
            decision_scores = model.decision_function([request.text])[0]
            ranking_indices = np.argsort(decision_scores)[::-1]
            best_index, second_index = ranking_indices[:2]
            category = classes[best_index]
            best_score = float(decision_scores[best_index])
            margin = best_score - float(decision_scores[second_index])
            needs_review = margin < threshold
            ranking = [
                {
                    "category": classes[index],
                    "label": DISPLAY_NAMES[classes[index]],
                    "score": round(float(decision_scores[index]), 6),
                }
                for index in ranking_indices
            ]
        except Exception as error:
            monitor.record_error(request.text, threshold, error, timer.elapsed_ms)
            raise HTTPException(status_code=500, detail="Erreur de prédiction") from error

    prediction_id = monitor.record_success(
        request.text, category, best_score, margin, threshold,
        needs_review, timer.elapsed_ms,
    )
    return {
        "prediction_id": prediction_id,
        "category": category,
        "label": DISPLAY_NAMES[category],
        "decision_score": round(best_score, 6),
        "margin": round(margin, 6),
        "threshold": threshold,
        "needs_review": needs_review,
        "ranking": ranking,
    }


@app.post("/feedback")
def feedback(request: FeedbackRequest):
    if request.corrected_category not in classes:
        raise HTTPException(status_code=422, detail="Catégorie inconnue")
    try:
        is_correct = monitor.add_feedback(
            request.prediction_id, request.corrected_category
        )
    except LookupError as error:
        raise HTTPException(status_code=404, detail=str(error)) from error
    return {"status": "recorded", "is_correct": is_correct}


@app.get("/monitoring/summary")
def monitoring_summary(hours: int = Query(default=24, ge=1, le=720)):
    return monitor.summary(hours)


@app.get("/monitoring/alerts")
def monitoring_alerts(hours: int = Query(default=24, ge=1, le=720)):
    return monitor.alerts(hours)


@app.get("/metrics", include_in_schema=False)
def metrics():
    return Response(generate_latest(), media_type=CONTENT_TYPE_LATEST)
