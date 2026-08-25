# Installation et configuration du service d’intelligence artificielle — ReviewPro

## 1. Objectif

Ce document décrit l’installation, le paramétrage et le démarrage du service de classification des plaintes de ReviewPro.

L’objectif est de permettre à une autre personne de reproduire l’environnement, de charger le modèle et de connecter le service d’intelligence artificielle à Laravel et à Vue.

## 2. Architecture applicative

ReviewPro utilise trois composants :

1. Vue affiche le formulaire et le résultat de l’analyse.
2. Laravel valide la requête et appelle le service d’intelligence artificielle.
3. FastAPI charge le modèle SVM et retourne la prédiction au format JSON.

Flux principal :

```text
Navigateur Vue
    → POST /api/ai/predict
Laravel
    → POST http://127.0.0.1:8001/predict
FastAPI
    → modèle SVM
FastAPI
    → réponse JSON
Laravel
    → réponse JSON
Vue
    → affichage du résultat
```

Cette séparation permet de faire évoluer le modèle indépendamment du backend Laravel.

## 3. Environnement utilisé

| Élément | Version ou valeur |
|---|---|
| Système de développement | macOS sur architecture Apple ARM64 |
| Python | 3.9.6 |
| FastAPI | 0.115.4 |
| Uvicorn | 0.30.6 |
| Pydantic | 2.13.4 |
| scikit-learn | 1.6.1 |
| Joblib | 1.5.3 |
| NumPy | 1.26.4 |
| PyTorch | 2.2.2 |
| Transformers | 4.57.2 |
| SentencePiece | 0.2.2 |
| Accélération Apple | MPS disponible |
| Backend | Laravel 12 |
| Frontend | Vue avec Vite |

## 4. Organisation des fichiers

| Fichier | Fonction |
|---|---|
| `.venv-ai/` | Environnement Python isolé |
| `requirements-ai.txt` | Versions des dépendances Python |
| `ai_service/main.py` | Application FastAPI |
| `scripts/train_macro_svm.py` | Entraînement du modèle final |
| `scripts/predict_macro_topic.py` | Prédiction en ligne de commande |
| `scripts/evaluate_supervised_macro_svm.py` | Validation croisée du SVM |
| `storage/app/ai/reviews_ai_macro_120.csv` | Dataset d’entraînement |
| `storage/app/ai/models/review_topic_macro_svm.joblib` | Modèle entraîné |
| `storage/app/ai/models/review_topic_macro_svm.metadata.json` | Métadonnées et politique de décision |
| `app/Services/AiReviewClassifier.php` | Connecteur Laravel vers FastAPI |
| `app/Http/Controllers/Api/AiPredictionController.php` | Contrôleur REST Laravel |
| `src/components/AiAnalyzer.vue` | Interface utilisateur Vue |

## 5. Création de l’environnement Python

Depuis le dossier `backend-laravel` :

```bash
python3 -m venv .venv-ai
source .venv-ai/bin/activate
python -m pip install --upgrade pip
```

L’activation est visible grâce au préfixe `(.venv-ai)` dans le terminal.

Pour quitter l’environnement :

```bash
deactivate
```

## 6. Installation des dépendances

Pour reproduire l’environnement existant :

```bash
source .venv-ai/bin/activate
python -m pip install -r requirements-ai.txt
```

Les dépendances principales sont :

```text
numpy==1.26.4
scikit-learn==1.6.1
sentencepiece==0.2.2
torch==2.2.2
transformers==4.57.2
fastapi==0.115.4
uvicorn==0.30.6
```

Le fichier complet est généré avec :

```bash
python -m pip freeze > requirements-ai.txt
```

## 7. Préparation du dataset

Le modèle utilise le fichier :

```text
storage/app/ai/reviews_ai_macro_120.csv
```

Il contient deux informations principales :

- `text` : contenu de l’avis ;
- `label` : famille de plainte attendue.

Le dataset contient 120 avis répartis en cinq familles.

Avant l’entraînement, les contrôles ont confirmé :

- aucun texte vide ;
- aucun label vide ;
- 120 textes uniques ;
- absence d’erreur d’encodage connue ;
- catégories conformes à la liste prévue.

## 8. Entraînement du modèle

Commande d’entraînement :

```bash
source .venv-ai/bin/activate
python -m py_compile scripts/train_macro_svm.py
python scripts/train_macro_svm.py
```

Le script entraîne un pipeline comprenant :

- TF-IDF sur les mots ;
- TF-IDF sur les caractères ;
- classificateur `LinearSVC`.

Deux fichiers sont produits :

```text
storage/app/ai/models/review_topic_macro_svm.joblib
storage/app/ai/models/review_topic_macro_svm.metadata.json
```

Le modèle produit actuellement un fichier d’environ 896 Ko.

## 9. Métadonnées du modèle

Le fichier de métadonnées contient :

- le nom du modèle ;
- l’algorithme ;
- la date d’entraînement ;
- le nombre de lignes ;
- la liste et la distribution des classes ;
- la méthode de validation ;
- l’accuracy et les scores F1 ;
- le chemin et l’empreinte SHA-256 du dataset ;
- la version de scikit-learn ;
- le seuil de décision ;
- un avertissement sur la petite taille de l’échantillon.

La présence de ces informations contribue à la traçabilité du modèle.

## 10. Politique de décision

Le modèle retourne un score pour chaque famille. Les scores sont classés du plus élevé au plus faible.

La marge correspond à la différence entre les deux meilleurs scores :

```text
marge = meilleur score - deuxième meilleur score
```

Le seuil est lu dans les métadonnées :

```text
automatic_threshold = 0.30
```

Lorsque la marge est inférieure à `0,30`, la réponse contient :

```json
{
  "needs_review": true
}
```

La prédiction doit alors être vérifiée par une personne.

## 11. Test du modèle en ligne de commande

Exemple matériel :

```bash
python scripts/predict_macro_topic.py \
"The charging port is broken and the battery will not charge."
```

Résultat attendu :

```text
Catégorie : device_hardware
Statut : PRÉDICTION EXPLOITABLE
```

Exemple logiciel :

```bash
python scripts/predict_macro_topic.py \
"The tablet freezes every time I open an application."
```

Cette prédiction peut être classée dans `software_ecosystem`, mais sa faible marge entraîne une vérification humaine.

## 12. Configuration de FastAPI

L’application est définie dans :

```text
ai_service/main.py
```

Au démarrage, elle :

1. construit les chemins du modèle et des métadonnées ;
2. charge le fichier Joblib ;
3. charge le fichier JSON ;
4. lit le seuil automatique ;
5. prépare les libellés fonctionnels.

La requête de prédiction est contrôlée par Pydantic :

```text
longueur minimale : 3 caractères
longueur maximale : 5 000 caractères
```

## 13. Endpoints FastAPI

### GET `/health`

Vérifie que le service et le modèle sont disponibles.

La réponse contient notamment :

- le statut ;
- le nom du modèle ;
- le nombre de lignes d’entraînement ;
- les classes ;
- le seuil.

### POST `/predict`

Requête :

```json
{
  "text": "The charging port is broken."
}
```

Réponse :

```json
{
  "category": "device_hardware",
  "label": "Matériel, batterie, écran ou audio",
  "decision_score": 0.71,
  "margin": 1.54,
  "threshold": 0.3,
  "needs_review": false,
  "ranking": []
}
```

## 14. Démarrage de FastAPI

Dans un premier terminal :

```bash
cd ~/Downloads/reviewpro_laravel_additions/backend-laravel
source .venv-ai/bin/activate
python -m uvicorn ai_service.main:app \
  --host 127.0.0.1 \
  --port 8001
```

Le service est ensuite accessible à l’adresse :

```text
http://127.0.0.1:8001
```

La documentation OpenAPI générée par FastAPI est accessible à :

```text
http://127.0.0.1:8001/docs
```

## 15. Vérification de FastAPI

```bash
curl -sS http://127.0.0.1:8001/health \
| python3 -m json.tool
```

Test de prédiction :

```bash
curl -sS \
  -X POST \
  -H 'Content-Type: application/json' \
  -d '{"text":"The charging port is broken."}' \
  http://127.0.0.1:8001/predict \
| python3 -m json.tool
```

## 16. Configuration Laravel

Le fichier `config/services.php` contient :

```php
'reviewpro_ai' => [
    'url' => env('AI_SERVICE_URL', 'http://127.0.0.1:8001'),
    'timeout' => (int) env('AI_SERVICE_TIMEOUT', 10),
],
```

Les variables documentées dans `.env.example` sont :

```dotenv
AI_SERVICE_URL=http://127.0.0.1:8001
AI_SERVICE_TIMEOUT=10
```

Après une modification de la configuration :

```bash
php artisan optimize:clear
```

## 17. Connecteur Laravel

Le service `AiReviewClassifier` :

- récupère l’URL depuis la configuration ;
- supprime les espaces inutiles du texte ;
- applique un timeout ;
- envoie la requête JSON à FastAPI ;
- déclenche une exception en cas d’erreur HTTP ;
- propose une méthode de vérification de santé.

Le contrôleur `AiPredictionController` :

- valide le texte entre 3 et 5 000 caractères ;
- appelle le connecteur ;
- retourne le résultat en JSON ;
- transforme une indisponibilité en réponse HTTP 503.

Route Laravel :

```text
POST /api/ai/predict
```

## 18. Démarrage de Laravel

Dans un deuxième terminal :

```bash
cd ~/Downloads/reviewpro_laravel_additions/backend-laravel
php artisan serve --host=127.0.0.1 --port=8000
```

Test du connecteur complet :

```bash
curl -sS \
  -X POST \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{"text":"The charging port is broken."}' \
  http://127.0.0.1:8000/api/ai/predict \
| python3 -m json.tool
```

## 19. Démarrage de Vue

Dans un troisième terminal :

```bash
cd ~/Downloads/reviewpro_laravel_additions/frontend-vue
npm install
npm run dev -- --host=127.0.0.1 --port=5173
```

L’interface est accessible à :

```text
http://127.0.0.1:5173
```

Le composant `AiAnalyzer.vue` envoie le texte à Laravel, puis affiche :

- le libellé de la famille ;
- la catégorie technique ;
- la marge de décision ;
- le statut automatique ou manuel ;
- le classement complet.

## 20. Vérification des tests

Tests Laravel :

```bash
cd ~/Downloads/reviewpro_laravel_additions/backend-laravel
php artisan test
```

Résultat actuel :

```text
8 tests réussis
25 assertions réussies
```

Compilation Vue :

```bash
cd ~/Downloads/reviewpro_laravel_additions/frontend-vue
npm run build
```

## 21. Sécurité et bonnes pratiques

- ne jamais charger un fichier Joblib provenant d’une source inconnue ;
- ne pas versionner les secrets du fichier `.env` ;
- utiliser HTTPS en production ;
- limiter le nombre de requêtes ;
- protéger les routes sensibles ;
- vérifier l’empreinte du modèle ;
- ne pas journaliser inutilement le texte complet ;
- conserver la version des dépendances ;
- exécuter les tests avant chaque livraison.

## 22. Incidents courants

### Erreur de connexion au port 8001

Cause probable : FastAPI n’est pas démarré.

Solution :

```bash
source .venv-ai/bin/activate
python -m uvicorn ai_service.main:app --host 127.0.0.1 --port 8001
```

### Erreur de connexion au port 8000

Cause probable : Laravel n’est pas démarré.

Solution :

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

### Configuration Laravel non actualisée

Solution :

```bash
php artisan optimize:clear
```

### Modèle introuvable

Vérifier la présence des fichiers :

```bash
ls -lh storage/app/ai/models/
```

Puis relancer l’entraînement si nécessaire.

## 23. Limites actuelles

- environnement de développement local ;
- démarrage manuel des trois services ;
- absence actuelle de conteneur Docker ;
- absence de redémarrage automatique ;
- absence de rate limiting spécifique à l’API IA ;
- dataset d’entraînement limité à 120 avis ;
- seuil à réévaluer avec davantage de données.

Ces limites seront traitées dans les étapes de monitorage et de livraison continue.

## 24. Correspondance avec la compétence RNCP

Cette réalisation démontre :

- le suivi de la documentation technique des bibliothèques ;
- la création d’un environnement isolé ;
- l’installation de versions déterminées ;
- le chargement du modèle et de ses métadonnées ;
- le paramétrage du seuil ;
- l’exposition d’endpoints de santé et de prédiction ;
- la configuration du connecteur Laravel ;
- l’intégration avec Vue ;
- la validation des entrées et la gestion des erreurs ;
- la rédaction d’une procédure reproductible.

## 25. Conclusion

Le service d’intelligence artificielle est installé, configuré et intégré dans ReviewPro.

La séparation entre Vue, Laravel et FastAPI facilite les tests, la maintenance et le futur déploiement. Les versions, les chemins, les paramètres et la procédure de démarrage sont documentés afin de permettre la reproduction de l’environnement.
