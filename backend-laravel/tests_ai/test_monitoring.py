import sqlite3

from fastapi.testclient import TestClient

from ai_service.main import MONITORING_DB_PATH, app, monitor


client = TestClient(app)


def setup_function():
    monitor.clear_for_tests()


def predict(text="The charging port is broken and the battery will not charge."):
    response = client.post("/predict", json={"text": text})
    assert response.status_code == 200
    return response.json()


def test_prediction_is_logged_without_raw_text():
    body = predict()
    with sqlite3.connect(MONITORING_DB_PATH) as connection:
        row = connection.execute(
            "SELECT id, text_hash, category FROM prediction_logs"
        ).fetchone()
        columns = {
            item[1] for item in connection.execute(
                "PRAGMA table_info('prediction_logs')"
            ).fetchall()
        }
    assert row[0] == body["prediction_id"]
    assert len(row[1]) == 64
    assert row[2] == body["category"]
    assert "text" not in columns


def test_summary_reports_predictions():
    predict()
    predict("The tablet freezes whenever I open an application.")
    summary = client.get("/monitoring/summary").json()
    assert summary["requests"] == 2
    assert summary["successes"] == 2
    assert sum(summary["categories"].values()) == 2


def test_feedback_loop_records_correction():
    body = predict()
    response = client.post(
        "/feedback",
        json={
            "prediction_id": body["prediction_id"],
            "corrected_category": body["category"],
        },
    )
    assert response.status_code == 200
    assert response.json()["is_correct"] is True
    assert client.get("/monitoring/summary").json()["feedback_accuracy"] == 1.0


def test_feedback_rejects_unknown_prediction_and_category():
    assert client.post(
        "/feedback",
        json={"prediction_id": 999999, "corrected_category": "device_hardware"},
    ).status_code == 404
    body = predict()
    assert client.post(
        "/feedback",
        json={"prediction_id": body["prediction_id"], "corrected_category": "invalid"},
    ).status_code == 422


def test_prometheus_metrics_are_exposed():
    predict()
    response = client.get("/metrics")
    assert response.status_code == 200
    assert "reviewpro_ai_predictions_total" in response.text
    assert "reviewpro_ai_prediction_latency_seconds" in response.text


def test_alerts_endpoint_and_query_validation():
    assert client.get("/monitoring/alerts").json()["status"] in {"ok", "alert"}
    assert client.get("/monitoring/summary?hours=0").status_code == 422
