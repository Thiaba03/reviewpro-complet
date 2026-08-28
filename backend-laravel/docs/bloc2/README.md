# Dossier de preuves — Bloc 2 RNCP

## Projet

ReviewPro — Intégration d'un modèle d'intelligence artificielle de classification des plaintes dans une application de collecte d'avis produits.

## Correspondance entre compétences et preuves

| Compétence | Réalisation | Preuve |
|---|---|---|
| Veille technique et réglementaire | Sources techniques et réglementaires suivies, recommandations formulées pour le projet | veille-technique-reglementaire.md |
| Identifier des services IA préexistants (benchmark) | Comparaison de services et modèles candidats pour la classification de plaintes | benchmark-services-ia.md |
| Paramétrer un service d'intelligence artificielle | Installation, chargement du modèle SVM, configuration des connecteurs Laravel/FastAPI/Vue | installation-configuration-ia.md |
| Développer une API exposant un modèle IA | Service FastAPI (`/predict`, `/health`, `/metrics`, `/monitoring/*`, `/feedback`) | ai_service/main.py, installation-configuration-ia.md |
| Intégrer l'API dans une application (accessibilité) | Composant `AiAnalyzer.vue`, conforme WCAG/RGAA (clavier, contrastes, messages dynamiques) | accessibilite-integration-ia.md, src/components/AiAnalyzer.vue |
| Monitorer un modèle d'intelligence artificielle | `PredictionMonitor`, métriques Prometheus, alertes, boucle de feedback humain | monitorage-modele.md, ai_service/monitoring.py |
| Programmer les tests automatisés d'un modèle IA | Suite pytest (dataset, modèle, API, monitorage, qualité) | tests-modele.md, tests_ai/ |
| Créer une chaîne de livraison continue (MLOps) | Packaging Docker, validation avant publication, image de conteneur | mlops-livraison-continue.md, Dockerfile.ai |

## Documents du dossier

- veille-technique-reglementaire.md ;
- benchmark-services-ia.md ;
- installation-configuration-ia.md ;
- accessibilite-integration-ia.md ;
- monitorage-modele.md ;
- tests-modele.md ;
- mlops-livraison-continue.md.

## Principales preuves chiffrées

- 120 lignes d'entraînement pour le modèle SVM `review_topic_macro_svm` ;
- 5 catégories de classification (`device_hardware`, `software_ecosystem`, `usability`, `commercial_service`, `other_unclear`) ;
- seuil de décision automatique fixé à 0.3 ;
- 39 tests Python réussis (pytest) ;
- 4 tests d'accessibilité réussis (Vitest + axe) sur `AiAnalyzer.vue` ;
- endpoint `/health`, `/metrics`, `/monitoring/summary`, `/monitoring/alerts`, `/feedback` opérationnels.

## Composants principaux

- ai_service/main.py ;
- ai_service/monitoring.py ;
- app/Services/AiReviewClassifier.php ;
- app/Http/Controllers/Api/AiPredictionController.php ;
- src/components/AiAnalyzer.vue (frontend-vue) ;
- routes/api.php (`POST /api/ai/predict`).

## Limites déclarées

- dataset d'entraînement limité (120 lignes), performance du modèle perfectible ;
- métriques Prometheus non encore reliées à un tableau Grafana ;
- alertes non encore envoyées vers un canal externe (Slack, email) ;
- déploiement du service IA en conteneur non encore automatisé vers un serveur public.