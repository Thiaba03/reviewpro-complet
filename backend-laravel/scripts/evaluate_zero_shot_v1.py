import csv
from pathlib import Path

import torch
from sklearn.metrics import accuracy_score, classification_report
from transformers import pipeline


INPUT_PATH = Path("storage/app/ai/reviews_ai_dataset_120.csv")
OUTPUT_PATH = Path("storage/app/ai/zero_shot_predictions_v2_120.csv")
MODEL_NAME = "MoritzLaurer/mDeBERTa-v3-base-mnli-xnli"


LABELS = {
    "battery_charging": "a battery life or charging problem",
    "hardware_failure": "a broken component or hardware failure",
    "screen_display": "a screen, picture quality, or touchscreen problem",
    "software_performance": "slow performance, freezing, crashing, or software malfunction",
    "connectivity": "a Wi-Fi, internet, or Bluetooth connection problem",
    "usability_setup": "difficult setup, navigation, controls, advertisements, or ease of use",
    "support_warranty": "a customer service, repair, return, or warranty problem",
    "delivery_packaging": "a delivery, shipping, packaging, or missing item problem",
    "price_value": "poor value, hidden fees, storage cost, or excessive price",
    "audio_quality": "a speaker, volume, or sound quality problem",
    "compatibility": "missing or incompatible applications, formats, services, or features",
    "out_of_scope": "not a complaint about the electronic product",
    "other": "a vague product complaint with no specific problem",
}


with INPUT_PATH.open(encoding="utf-8", newline="") as file:
    rows = list(csv.DictReader(file))

device = torch.device("mps") if torch.backends.mps.is_available() else -1

print("Modèle :", MODEL_NAME)
print("Appareil :", device)
print("Avis à analyser :", len(rows))

classifier = pipeline(
    "zero-shot-classification",
    model=MODEL_NAME,
    device=device,
)

candidate_labels = list(LABELS.values())
description_to_label = {
    description: label
    for label, description in LABELS.items()
}

results = []

for index, row in enumerate(rows, start=1):
    prediction = classifier(
        row["text"],
        candidate_labels,
        multi_label=False,
        hypothesis_template="The main complaint can be described as {}.",
    )

    predicted_description = prediction["labels"][0]
    predicted_label = description_to_label[predicted_description]
    score = float(prediction["scores"][0])

    results.append({
        "id": row["id"],
        "true_label": row["label"],
        "predicted_label": predicted_label,
        "score": round(score, 6),
        "correct": int(predicted_label == row["label"]),
        "text": row["text"],
    })

    if index % 10 == 0 or index == len(rows):
        print(f"Progression : {index}/{len(rows)}")

with OUTPUT_PATH.open("w", encoding="utf-8", newline="") as file:
    writer = csv.DictWriter(
        file,
        fieldnames=[
            "id",
            "true_label",
            "predicted_label",
            "score",
            "correct",
            "text",
        ],
    )
    writer.writeheader()
    writer.writerows(results)

true_labels = [row["true_label"] for row in results]
predicted_labels = [row["predicted_label"] for row in results]
evaluated_labels = sorted(set(true_labels))

print("\nACCURACY")
print(round(accuracy_score(true_labels, predicted_labels), 4))

print("\nRAPPORT PAR CATÉGORIE")
print(classification_report(
    true_labels,
    predicted_labels,
    labels=evaluated_labels,
    zero_division=0,
))

print("Prédictions enregistrées :", OUTPUT_PATH)