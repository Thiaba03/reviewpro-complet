import pytest
from sklearn.metrics import accuracy_score, f1_score

from .conftest import ALLOWED_LABELS


def labels(prediction_rows):
    expected = [row["true_label"] for row in prediction_rows]
    predicted = [row["predicted_label"] for row in prediction_rows]
    return expected, predicted


def test_cross_validation_contains_120_predictions(prediction_rows):
    assert len(prediction_rows) == 120


def test_cross_validation_uses_five_folds(prediction_rows):
    assert {int(row["fold"]) for row in prediction_rows} == {1, 2, 3, 4, 5}


def test_cross_validation_labels_are_authorized(prediction_rows):
    expected, predicted = labels(prediction_rows)
    assert set(expected) == ALLOWED_LABELS
    assert set(predicted).issubset(ALLOWED_LABELS)


def test_correct_column_matches_predictions(prediction_rows):
    for row in prediction_rows:
        expected = int(row["true_label"] == row["predicted_label"])
        assert int(row["correct"]) == expected


@pytest.mark.quality
def test_minimum_accuracy_is_respected(prediction_rows):
    expected, predicted = labels(prediction_rows)
    assert accuracy_score(expected, predicted) >= 0.50


@pytest.mark.quality
def test_minimum_macro_f1_is_respected(prediction_rows):
    expected, predicted = labels(prediction_rows)
    assert f1_score(expected, predicted, average="macro") >= 0.30


@pytest.mark.quality
def test_minimum_weighted_f1_is_respected(prediction_rows):
    expected, predicted = labels(prediction_rows)
    assert f1_score(expected, predicted, average="weighted") >= 0.45


@pytest.mark.quality
def test_automatic_decisions_are_more_reliable(prediction_rows, metadata):
    threshold = metadata["decision_policy"]["automatic_threshold"]
    accepted = [row for row in prediction_rows if float(row["margin"]) >= threshold]
    coverage = len(accepted) / len(prediction_rows)
    accuracy = sum(int(row["correct"]) for row in accepted) / len(accepted)
    assert coverage >= 0.45
    assert accuracy >= 0.65
