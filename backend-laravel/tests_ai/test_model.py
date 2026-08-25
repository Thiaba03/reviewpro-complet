import hashlib

import numpy as np
import sklearn

from .conftest import (
    ALLOWED_LABELS,
    DATASET_PATH,
    METADATA_PATH,
    MODEL_PATH,
)


def test_model_and_metadata_files_exist():
    assert MODEL_PATH.is_file()
    assert METADATA_PATH.is_file()


def test_dataset_hash_matches_metadata(metadata):
    digest = hashlib.sha256(DATASET_PATH.read_bytes()).hexdigest()
    assert digest == metadata["training_dataset_sha256"]


def test_metadata_matches_runtime(metadata):
    assert metadata["model_name"] == "review_topic_macro_svm"
    assert metadata["training_rows"] == 120
    assert metadata["sklearn_version"] == sklearn.__version__
    assert set(metadata["classes"]) == ALLOWED_LABELS


def test_decision_policy_is_valid(metadata):
    policy = metadata["decision_policy"]
    assert 0 < policy["automatic_threshold"] < 1
    assert policy["manual_review_below_threshold"] is True
    assert policy["validation_samples"] == 120


def test_model_contains_expected_classes(trained_model):
    assert set(trained_model.classes_) == ALLOWED_LABELS


def test_model_prediction_is_deterministic(trained_model):
    text = "The charging port is broken and the battery will not charge."
    first = trained_model.predict([text])[0]
    second = trained_model.predict([text])[0]
    assert first == second == "device_hardware"


def test_decision_function_returns_one_score_per_class(trained_model):
    scores = trained_model.decision_function(
        ["The tablet freezes every time I open an application."]
    )
    assert scores.shape == (1, len(ALLOWED_LABELS))
    assert np.isfinite(scores).all()


def test_computed_margin_is_non_negative(trained_model):
    scores = trained_model.decision_function(
        ["The charging port is broken and the battery will not charge."]
    )[0]
    ordered = np.sort(scores)[::-1]
    margin = float(ordered[0] - ordered[1])
    assert margin >= 0
