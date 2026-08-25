from collections import Counter

from .conftest import ALLOWED_LABELS, DATASET_PATH


def test_dataset_exists():
    assert DATASET_PATH.is_file()


def test_dataset_contains_expected_columns(dataset_rows):
    assert dataset_rows
    assert {"id", "text", "fine_label", "macro_label"}.issubset(
        dataset_rows[0]
    )


def test_dataset_contains_120_rows(dataset_rows):
    assert len(dataset_rows) == 120


def test_required_values_are_not_empty(dataset_rows):
    for row in dataset_rows:
        assert row["id"].strip()
        assert row["text"].strip()
        assert row["fine_label"].strip()
        assert row["macro_label"].strip()


def test_texts_are_unique(dataset_rows):
    normalized = [" ".join(row["text"].lower().split()) for row in dataset_rows]
    assert len(normalized) == len(set(normalized))


def test_macro_labels_are_authorized(dataset_rows):
    labels = {row["macro_label"] for row in dataset_rows}
    assert labels == ALLOWED_LABELS


def test_class_distribution_matches_metadata(dataset_rows, metadata):
    actual = Counter(row["macro_label"] for row in dataset_rows)
    assert dict(actual) == metadata["class_distribution"]


def test_text_length_is_compatible_with_api(dataset_rows):
    assert min(len(row["text"].strip()) for row in dataset_rows) >= 3
    assert max(len(row["text"]) for row in dataset_rows) <= 5000
