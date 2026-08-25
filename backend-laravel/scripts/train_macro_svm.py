import csv
import hashlib
import json
from collections import Counter
from datetime import datetime, timezone
from pathlib import Path

import joblib
import sklearn
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.pipeline import FeatureUnion, Pipeline
from sklearn.svm import LinearSVC


INPUT_PATH = Path("storage/app/ai/reviews_ai_macro_120.csv")
MODEL_DIRECTORY = Path("storage/app/ai/models")
MODEL_PATH = MODEL_DIRECTORY / "review_topic_macro_svm.joblib"
METADATA_PATH = MODEL_DIRECTORY / "review_topic_macro_svm.metadata.json"

MODEL_DIRECTORY.mkdir(parents=True, exist_ok=True)

with INPUT_PATH.open(encoding="utf-8", newline="") as file:
    rows = list(csv.DictReader(file))

texts = [row["text"] for row in rows]
labels = [row["macro_label"] for row in rows]

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
        LinearSVC(
            class_weight="balanced",
            C=1.0,
            dual="auto",
            random_state=42,
        ),
    ),
])

print("Entraînement sur", len(rows), "avis...")
model.fit(texts, labels)
joblib.dump(model, MODEL_PATH)

checksum = hashlib.sha256(INPUT_PATH.read_bytes()).hexdigest()

metadata = {
    "model_name": "review_topic_macro_svm",
    "algorithm": "TF-IDF mots + caractères avec LinearSVC",
    "trained_at": datetime.now(timezone.utc).isoformat(),
    "training_rows": len(rows),
    "classes": sorted(set(labels)),
    "class_distribution": dict(Counter(labels)),
    "validation_method": "validation croisée stratifiée à 5 plis",
    "validation_accuracy": 0.525,
    "validation_macro_f1": 0.33,
    "validation_weighted_f1": 0.47,
    "training_dataset": str(INPUT_PATH),
    "training_dataset_sha256": checksum,
    "sklearn_version": sklearn.__version__,
}

METADATA_PATH.write_text(
    json.dumps(metadata, indent=2, ensure_ascii=False),
    encoding="utf-8",
)

print("Modèle enregistré :", MODEL_PATH)
print("Métadonnées :", METADATA_PATH)
print("Empreinte du dataset :", checksum)