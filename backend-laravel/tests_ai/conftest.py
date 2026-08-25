import csv
import json
from pathlib import Path

import joblib
import pytest


ROOT = Path(__file__).resolve().parents[1]
AI_DIR = ROOT / "storage/app/ai"
DATASET_PATH = AI_DIR / "reviews_ai_macro_120.csv"
PREDICTIONS_PATH = AI_DIR / "supervised_macro_svm_cv_predictions_120.csv"
MODEL_PATH = AI_DIR / "models/review_topic_macro_svm.joblib"
METADATA_PATH = AI_DIR / "models/review_topic_macro_svm.metadata.json"

ALLOWED_LABELS = {
    "commercial_service",
    "device_hardware",
    "other_unclear",
    "software_ecosystem",
    "usability",
}


def read_csv(path: Path):
    with path.open(encoding="utf-8", newline="") as file:
        return list(csv.DictReader(file))


@pytest.fixture(scope="session")
def dataset_rows():
    return read_csv(DATASET_PATH)


@pytest.fixture(scope="session")
def prediction_rows():
    return read_csv(PREDICTIONS_PATH)


@pytest.fixture(scope="session")
def metadata():
    return json.loads(METADATA_PATH.read_text(encoding="utf-8"))


@pytest.fixture(scope="session")
def trained_model():
    return joblib.load(MODEL_PATH)
