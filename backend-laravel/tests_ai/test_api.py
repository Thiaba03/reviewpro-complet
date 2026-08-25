import pytest
from fastapi.testclient import TestClient

from ai_service.main import app
from .conftest import ALLOWED_LABELS


client = TestClient(app)


def test_health_endpoint():
    response = client.get("/health")
    assert response.status_code == 200
    body = response.json()
    assert body["status"] == "ok"
    assert body["model"] == "review_topic_macro_svm"
    assert body["training_rows"] == 120
    assert set(body["classes"]) == ALLOWED_LABELS
    assert body["threshold"] == pytest.approx(0.3)


def test_predict_endpoint_contract():
    response = client.post(
        "/predict",
        json={"text": "The charging port is broken and the battery will not charge."},
    )
    assert response.status_code == 200
    body = response.json()
    assert set(body) == {
        "prediction_id", "category", "label", "decision_score", "margin",
        "threshold", "needs_review", "ranking",
    }
    assert isinstance(body["prediction_id"], int) and body["prediction_id"] > 0
    assert body["category"] in ALLOWED_LABELS
    assert isinstance(body["label"], str) and body["label"]
    assert isinstance(body["needs_review"], bool)
    assert len(body["ranking"]) == len(ALLOWED_LABELS)


def test_ranking_is_complete_and_sorted():
    body = client.post(
        "/predict",
        json={"text": "The tablet freezes whenever an application opens."},
    ).json()
    scores = [item["score"] for item in body["ranking"]]
    assert scores == sorted(scores, reverse=True)
    assert {item["category"] for item in body["ranking"]} == ALLOWED_LABELS
    assert body["category"] == body["ranking"][0]["category"]


def test_margin_matches_two_best_scores():
    body = client.post(
        "/predict", json={"text": "The battery only lasts fifteen minutes."}
    ).json()
    expected = body["ranking"][0]["score"] - body["ranking"][1]["score"]
    assert body["margin"] == pytest.approx(expected, abs=1e-5)
    assert body["needs_review"] == (body["margin"] < body["threshold"])


@pytest.mark.parametrize("text", ["", "a", "ab"])
def test_text_shorter_than_three_characters_is_rejected(text):
    assert client.post("/predict", json={"text": text}).status_code == 422


def test_text_longer_than_5000_characters_is_rejected():
    assert client.post("/predict", json={"text": "a" * 5001}).status_code == 422


def test_missing_text_is_rejected():
    assert client.post("/predict", json={}).status_code == 422
