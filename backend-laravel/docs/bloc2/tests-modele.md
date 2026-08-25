# Tests automatisés du modèle d’intelligence artificielle — ReviewPro

## 1. Objectif

Les tests automatisés vérifient que le dataset, le modèle, les métadonnées, les métriques et l’API FastAPI respectent les règles définies pour ReviewPro.

Ils permettent d’éviter l’intégration ou la livraison d’un modèle invalide, incompatible ou insuffisamment performant.

## 2. Outils utilisés

| Outil | Version | Utilisation |
|---|---:|---|
| pytest | 8.4.2 | Exécution des tests Python |
| pytest-cov | 6.3.0 | Mesure de la couverture |
| HTTPX | 0.28.1 | Test de l’API FastAPI |
| scikit-learn | 1.6.1 | Calcul des métriques |
| FastAPI TestClient | Version liée à FastAPI | Requêtes HTTP sans serveur externe |

Les dépendances sont enregistrées dans `requirements-ai.txt`.

## 3. Organisation

```text
pytest.ini
tests_ai/
├── __init__.py
├── conftest.py
├── test_api.py
├── test_dataset.py
├── test_model.py
└── test_quality.py
```

Le fichier `conftest.py` centralise :

- les chemins des fichiers ;
- la liste des catégories autorisées ;
- le chargement du dataset ;
- le chargement des prédictions de validation croisée ;
- le chargement des métadonnées ;
- le chargement du modèle Joblib.

Les fixtures utilisent une portée de session afin de ne charger les fichiers qu’une seule fois.

## 4. Configuration de pytest

Le fichier `pytest.ini` indique :

- que les tests se trouvent dans `tests_ai` ;
- que les fichiers commencent par `test_` ;
- que les fonctions commencent par `test_` ;
- que les marqueurs non déclarés sont interdits ;
- qu’un marqueur `quality` identifie les seuils de qualité du modèle.

## 5. Tests du dataset

Le fichier `test_dataset.py` vérifie :

| Règle | Résultat attendu |
|---|---|
| Fichier présent | `reviews_ai_macro_120.csv` existe |
| Colonnes | `id`, `text`, `fine_label`, `macro_label` |
| Nombre de lignes | 120 |
| Valeurs obligatoires | Aucune valeur vide |
| Unicité | 120 textes normalisés uniques |
| Catégories | Exactement cinq catégories autorisées |
| Distribution | Identique aux métadonnées |
| Longueur | Entre 3 et 5 000 caractères |

Ces tests empêchent l’entraînement ou la validation sur un dataset incomplet ou incompatible avec l’API.

## 6. Tests du modèle et des métadonnées

Le fichier `test_model.py` vérifie :

- la présence du modèle Joblib ;
- la présence des métadonnées JSON ;
- l’empreinte SHA-256 du dataset ;
- le nom du modèle ;
- le nombre de lignes d’entraînement ;
- la version de scikit-learn ;
- la liste des classes ;
- la validité du seuil ;
- l’activation de la vérification humaine ;
- la stabilité d’une prédiction répétée ;
- la présence d’un score par classe ;
- l’absence de scores non numériques ;
- la validité de la marge.

### Contrôle d’intégrité

Le test recalcule :

```text
SHA-256 du fichier reviews_ai_macro_120.csv
```

Cette valeur doit correspondre à :

```text
bfbbc21836f5a1d1b69a840e88568358d1d9abd828ebdf4b27aa681050206f12
```

Une modification non documentée du dataset provoque donc un échec.

## 7. Tests de l’API FastAPI

Le fichier `test_api.py` utilise `TestClient` pour vérifier les endpoints sans démarrer Uvicorn.

### Endpoint `/health`

Le test vérifie :

- le code HTTP 200 ;
- le statut `ok` ;
- le nom du modèle ;
- les 120 lignes d’entraînement ;
- les cinq classes ;
- le seuil de `0,30`.

### Endpoint `/predict`

Le contrat JSON doit contenir exactement :

```text
category
label
decision_score
margin
threshold
needs_review
ranking
```

Les tests vérifient aussi :

- que la catégorie est autorisée ;
- que le libellé est présent ;
- que `needs_review` est un booléen ;
- que les cinq catégories apparaissent dans le classement ;
- que les scores sont triés du plus élevé au plus faible ;
- que la première catégorie correspond à la prédiction ;
- que la marge correspond aux deux meilleurs scores ;
- que le statut de vérification applique correctement le seuil.

### Validation des entrées

L’API doit refuser avec le code HTTP 422 :

- un texte vide ;
- un texte de un caractère ;
- un texte de deux caractères ;
- un texte supérieur à 5 000 caractères ;
- une requête sans champ `text`.

## 8. Tests des métriques de validation

Le fichier `test_quality.py` utilise :

```text
storage/app/ai/supervised_macro_svm_cv_predictions_120.csv
```

Il vérifie :

- la présence de 120 prédictions ;
- l’utilisation de cinq plis ;
- la validité des labels attendus et prédits ;
- la cohérence de la colonne `correct` ;
- l’accuracy minimale ;
- le F1 macro minimal ;
- le F1 pondéré minimal ;
- la meilleure fiabilité des décisions automatiques.

## 9. Seuils de qualité

| Métrique | Seuil bloquant | Résultat observé | Statut |
|---|---:|---:|---|
| Accuracy | ≥ 0,50 | 0,525 | Conforme |
| F1 macro | ≥ 0,30 | 0,33 | Conforme |
| F1 pondéré | ≥ 0,45 | 0,47 | Conforme |
| Couverture automatique au seuil 0,30 | ≥ 45 % | 51,7 % | Conforme |
| Accuracy des décisions automatiques | ≥ 65 % | 71,0 % | Conforme |

Ces seuils sont volontairement proches des performances actuelles. Ils empêchent une régression importante sans présenter le modèle comme plus performant qu’il ne l’est.

Ils devront être relevés après l’ajout de nouvelles données annotées.

## 10. Commandes d’exécution

Activation de l’environnement :

```bash
source .venv-ai/bin/activate
```

Contrôle de la syntaxe :

```bash
python -m py_compile \
  tests_ai/conftest.py \
  tests_ai/test_dataset.py \
  tests_ai/test_model.py \
  tests_ai/test_api.py \
  tests_ai/test_quality.py
```

Exécution normale :

```bash
python -m pytest -v
```

Exécution avec couverture :

```bash
python -m pytest \
  --cov=ai_service \
  --cov-report=term-missing \
  --cov-report=xml:storage/app/ai/pytest-coverage.xml
```

Enregistrement de la preuve :

```bash
python -m pytest -v \
| tee storage/app/ai/pytest-model-results.txt
```

## 11. Résultats obtenus

```text
33 tests réussis
0 test en échec
Durée : 0,78 seconde
Couverture des lignes de ai_service : 100 %
```

La couverture des branches apparaît à 0 % parce que la mesure des branches n’a pas été activée. Elle ne représente pas un échec de la suite de tests.

Les preuves sont enregistrées dans :

```text
storage/app/ai/pytest-model-results.txt
storage/app/ai/pytest-coverage.xml
```

## 12. Tests d’intégration Laravel

Commande :

```bash
php artisan test
```

Résultat :

```text
8 tests réussis
25 assertions réussies
Durée : 0,23 seconde
```

Les tests Laravel vérifient notamment :

- une classification réussie ;
- la présence obligatoire du texte ;
- la réponse HTTP 503 lorsque FastAPI est indisponible ;
- la pagination des avis ;
- le filtre par sentiment ;
- le filtre par marque ;
- la limite maximale de pagination.

## 13. Test de construction du frontend

Commande :

```bash
npm run build
```

Résultat :

```text
70 modules transformés
Build Vite réussi en 267 ms
```

Cette vérification confirme que l’intégration de l’interface IA ne bloque pas la construction du frontend.

## 14. Validation avant intégration

Un modèle peut être intégré uniquement si :

1. le dataset satisfait tous les contrôles ;
2. l’empreinte correspond aux métadonnées ;
3. le modèle contient les cinq classes ;
4. tous les tests Python réussissent ;
5. les seuils de qualité sont atteints ;
6. les tests Laravel réussissent ;
7. le frontend compile ;
8. les versions du modèle et des dépendances sont enregistrées.

Un échec doit interrompre la future chaîne d’intégration continue.

## 15. Limites et travaux complémentaires

La suite actuelle valide le dataset final, le modèle entraîné, l’évaluation et l’API.

Les travaux complémentaires sont :

- exécuter ces tests automatiquement dans la CI ;
- tester l’entraînement dans un environnement temporaire ;
- activer la couverture des branches ;
- ajouter un jeu de test indépendant ;
- tester des caractères inhabituels et plusieurs langues ;
- tester la charge et le temps de réponse ;
- augmenter le nombre d’exemples par classe.

## 16. Correspondance avec la compétence RNCP

Cette réalisation démontre la programmation de règles automatiques pour :

- valider le jeu de données ;
- contrôler la préparation et les formats ;
- vérifier les artefacts d’entraînement ;
- contrôler l’intégrité du dataset ;
- évaluer le modèle ;
- valider des seuils de qualité ;
- vérifier le contrat de l’API ;
- empêcher l’intégration d’un modèle non conforme.

La future chaîne CI exécutera ces règles à chaque modification.

## 17. Conclusion

La suite de tests fournit une preuve reproductible de la qualité technique actuelle du service d’intelligence artificielle.

Elle ne garantit pas que le modèle est parfait, mais elle garantit qu’une régression importante, une modification inattendue du dataset ou une rupture du contrat API sera détectée automatiquement.
