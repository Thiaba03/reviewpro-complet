import csv
from collections import Counter
from pathlib import Path

import numpy as np
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import accuracy_score, classification_report
from sklearn.model_selection import StratifiedKFold, cross_val_predict
from sklearn.pipeline import FeatureUnion, Pipeline


INPUT_PATH = Path("storage/app/ai/reviews_ai_macro_120.csv")
OUTPUT_PATH = Path(
    "storage/app/ai/supervised_macro_cv_predictions_120.csv"
)

with INPUT_PATH.open(encoding="utf-8", newline="") as file:
    rows = list(csv.DictReader(file))

texts = [row["text"] for row in rows]
true_labels = [row["macro_label"] for row in rows]
classes = sorted(set(true_labels))

features = FeatureUnion([
    (
        "words",
        TfidfVectorizer(
            lowercase=True,
            ngram_range=(1, 2),
            min_df=1,
            max_df=0.95,
            sublinear_tf=True,
            max_features=10000,
        ),
    ),
    (
        "characters",
        TfidfVectorizer(
            analyzer="char_wb",
            lowercase=True,
            ngram_range=(3, 5),
            min_df=2,
            sublinear_tf=True,
            max_features=15000,
        ),
    ),
])

model = Pipeline([
    ("features", features),
    (
        "classifier",
        LogisticRegression(
            max_iter=2000,
            class_weight="balanced",
            C=2.0,
            random_state=42,
        ),
    ),
])

cv = StratifiedKFold(
    n_splits=5,
    shuffle=True,
    random_state=42,
)

fold_numbers = np.zeros(len(rows), dtype=int)

for fold, (_, test_indices) in enumerate(cv.split(texts, true_labels), 1):
    fold_numbers[test_indices] = fold

print("Évaluation supervisée avec validation croisée...")
probabilities = cross_val_predict(
    model,
    texts,
    true_labels,
    cv=cv,
    method="predict_proba",
)

predicted_indices = probabilities.argmax(axis=1)
predicted_labels = [classes[index] for index in predicted_indices]
confidence_scores = probabilities.max(axis=1)

accuracy = accuracy_score(true_labels, predicted_labels)

majority_count = Counter(true_labels).most_common(1)[0][1]
majority_baseline = majority_count / len(true_labels)

print("\nBASELINE CLASSE MAJORITAIRE")
print(round(majority_baseline, 4))

print("\nACCURACY MODÈLE SUPERVISÉ")
print(round(accuracy, 4))

print("\nRAPPORT PAR CATÉGORIE")
print(classification_report(
    true_labels,
    predicted_labels,
    labels=classes,
    zero_division=0,
))

output_rows = []

for index, row in enumerate(rows):
    output_rows.append({
        "id": row["id"],
        "fold": int(fold_numbers[index]),
        "true_label": true_labels[index],
        "predicted_label": predicted_labels[index],
        "score": round(float(confidence_scores[index]), 6),
        "correct": int(true_labels[index] == predicted_labels[index]),
        "text": row["text"],
    })

with OUTPUT_PATH.open("w", encoding="utf-8", newline="") as file:
    writer = csv.DictWriter(
        file,
        fieldnames=[
            "id",
            "fold",
            "true_label",
            "predicted_label",
            "score",
            "correct",
            "text",
        ],
    )
    writer.writeheader()
    writer.writerows(output_rows)

print("Prédictions enregistrées :", OUTPUT_PATH)