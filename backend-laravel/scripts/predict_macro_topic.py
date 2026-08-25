import sys
from pathlib import Path

import joblib
import numpy as np


MODEL_PATH = Path(
    "storage/app/ai/models/review_topic_macro_svm.joblib"
)

DISPLAY_NAMES = {
    "device_hardware": "Matériel, batterie, écran ou audio",
    "software_ecosystem": "Logiciel, connexion ou compatibilité",
    "usability": "Utilisation et configuration",
    "commercial_service": "Prix, garantie, service ou livraison",
    "other_unclear": "Avis imprécis ou hors cible",
}

if len(sys.argv) > 1:
    text = " ".join(sys.argv[1:]).strip()
else:
    text = input("Avis à analyser : ").strip()

if not text:
    raise SystemExit("Le texte de l’avis est obligatoire.")

model = joblib.load(MODEL_PATH)

predicted_label = model.predict([text])[0]
decision_scores = model.decision_function([text])[0]
classes = model.named_steps["classifier"].classes_

ranking = np.argsort(decision_scores)[::-1]
best_score = float(decision_scores[ranking[0]])
second_score = float(decision_scores[ranking[1]])
margin = best_score - second_score

print("\nRÉSULTAT")
print("Catégorie :", predicted_label)
print("Libellé :", DISPLAY_NAMES[predicted_label])
print("Marge de décision :", round(margin, 4))

if margin < 0.30:
    print("Statut : À VÉRIFIER MANUELLEMENT")
else:
    print("Statut : PRÉDICTION EXPLOITABLE")

print("\nCLASSEMENT")
for index in ranking:
    label = classes[index]
    print(f"{decision_scores[index]: .4f} | {label}")