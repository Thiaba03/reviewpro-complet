# Veille technique et réglementaire — ReviewPro

## 1. Objectif

La veille permet de maintenir le service d’intelligence artificielle de ReviewPro en phase avec les évolutions techniques, réglementaires et de sécurité.

Elle doit notamment permettre de :

- choisir des outils encore maintenus et compatibles ;
- détecter les vulnérabilités et les changements de dépendances ;
- suivre les règles applicables aux données personnelles et à l’intelligence artificielle ;
- formuler des recommandations concrètes pour le projet ;
- conserver une trace des sources consultées et des décisions prises.

## 2. Périmètre de la veille

La veille est organisée autour de cinq axes :

| Axe | Sujets surveillés |
|---|---|
| Modèles et machine learning | classification de textes, scikit-learn, Transformers, métriques et dérive |
| API et déploiement | FastAPI, Laravel, Docker, disponibilité et performances |
| MLOps | tests, versionnement, intégration continue, livraison et monitorage |
| Sécurité | sécurité des API, dépendances, secrets et fichiers de modèles |
| Réglementation | RGPD, recommandations de la CNIL et règlement européen sur l’IA |

## 3. Organisation du travail de veille

### 3.1 Rôles proposés

Dans un contexte collectif, les rôles peuvent être répartis ainsi :

| Rôle | Responsabilité |
|---|---|
| Référente technique | Bibliothèques Python, FastAPI, modèles et performances |
| Référente réglementaire | CNIL, RGPD et règlement européen sur l’IA |
| Référente sécurité | OWASP, dépendances, secrets et vulnérabilités |
| Relecteur ou tuteur | Validation des sources et des recommandations |

Dans le cadre du projet individuel, l’étudiante centralise la veille. Une relecture par un tuteur, un formateur ou un autre étudiant doit être consignée pour démontrer la dimension collaborative.

### 3.2 Cycle de veille

1. Sélectionner les sources officielles ou reconnues.
2. Collecter les nouveautés importantes.
3. Vérifier la date, l’auteur et la fiabilité de chaque information.
4. Résumer les conséquences possibles pour ReviewPro.
5. Partager le résultat avec le tuteur ou le groupe projet.
6. Valider une recommandation.
7. Enregistrer la décision et son état d’application.

### 3.3 Fréquence

| Fréquence | Action |
|---|---|
| Chaque semaine | Vérifier les alertes de sécurité et les versions critiques |
| Chaque mois | Mettre à jour le tableau de veille et partager les changements |
| Avant une livraison | Vérifier les dépendances, les tests et les obligations réglementaires |
| Après un incident | Rechercher une correction et documenter la solution |

## 4. Critères de sélection des sources

Une source est retenue lorsqu’elle répond à plusieurs critères :

- source officielle ou documentation de l’éditeur ;
- contenu daté et identifiable ;
- rapport direct avec les technologies du projet ;
- recommandations vérifiables ;
- absence de conflit d’intérêts évident ;
- possibilité de comparer l’information avec une autre source fiable.

Les articles non officiels peuvent aider à découvrir un sujet, mais ils ne sont pas utilisés seuls pour justifier une décision importante.

## 5. Sources retenues

| Source | Domaine | Utilisation dans ReviewPro |
|---|---|---|
| CNIL | RGPD et IA | Minimisation, finalité, sécurité et droits des personnes |
| Commission européenne | Règlement européen sur l’IA | Classification des risques, transparence, supervision humaine et documentation |
| EUR-Lex | Texte juridique officiel | Consultation du règlement (UE) 2024/1689 |
| scikit-learn | Machine learning | Entraînement, évaluation, persistance et compatibilité du modèle |
| Hugging Face | Modèles Transformers | Utilisation et limites du zero-shot |
| FastAPI | API et déploiement | Validation, documentation OpenAPI et exploitation du service |
| OWASP | Sécurité des API | Authentification, limitation des ressources et configuration |
| Docker | Packaging | Reproductibilité de l’environnement du modèle |
| GitHub Actions | CI/CD | Automatisation des tests et de la livraison |

## 6. Veille technique

### 6.1 Persistance du modèle scikit-learn

La documentation de scikit-learn indique que les formats fondés sur `pickle`, dont `joblib`, ne doivent être chargés que depuis une source de confiance. Un fichier malveillant peut exécuter du code pendant son chargement.

Elle précise également que le chargement d’un modèle dans une version différente de scikit-learn n’est pas officiellement garanti.

#### Recommandation pour ReviewPro

- ne jamais accepter un fichier `.joblib` transmis par un utilisateur ;
- conserver le modèle dans un répertoire contrôlé ;
- enregistrer la version de scikit-learn ;
- vérifier l’empreinte SHA-256 du modèle et du dataset ;
- figer les dépendances dans `requirements-ai.txt` ;
- reconstruire le modèle lorsqu’une dépendance majeure change.

#### Application actuelle

ReviewPro conserve déjà dans les métadonnées :

- la version de scikit-learn ;
- l’empreinte SHA-256 du dataset ;
- les classes du modèle ;
- les métriques de validation ;
- le seuil de décision.

### 6.2 Déploiement FastAPI

La documentation FastAPI recommande de prendre en compte le démarrage automatique, les redémarrages, la réplication, la mémoire et la terminaison HTTPS.

#### Recommandation pour ReviewPro

- ajouter un endpoint `/health` ;
- exécuter le service dans un conteneur ;
- configurer une politique de redémarrage ;
- limiter le nombre de processus car chaque processus charge une copie du modèle ;
- mesurer la latence et les erreurs ;
- placer un reverse proxy HTTPS devant l’API en production.

#### Application actuelle

L’endpoint `/health` existe déjà. Le packaging Docker, HTTPS et le redémarrage automatique restent à mettre en place.

### 6.3 Sécurité de l’API

L’OWASP API Security Top 10 recense notamment les risques liés à l’authentification, à l’autorisation, à la consommation non limitée des ressources et aux mauvaises configurations.

#### Recommandation pour ReviewPro

- limiter la longueur du texte envoyé ;
- appliquer une limitation du nombre de requêtes ;
- protéger les routes sensibles ;
- ne pas exposer les chemins internes ni les erreurs techniques ;
- maintenir les dépendances à jour ;
- journaliser les erreurs sans enregistrer inutilement le texte complet des avis.

#### Application actuelle

La validation limite déjà le texte de l’API Laravel à 5 000 caractères et transforme l’indisponibilité du service IA en réponse HTTP 503. L’authentification et le rate limiting doivent être complétés.

### 6.4 Modèles zero-shot et modèle supervisé

Le zero-shot permet de démarrer sans données annotées, mais son coût en mémoire et son temps d’exécution sont supérieurs à ceux du SVM retenu.

#### Recommandation pour ReviewPro

- conserver le zero-shot comme solution de comparaison ;
- utiliser le SVM pour la première version opérationnelle ;
- enrichir progressivement le dataset grâce aux validations humaines ;
- réévaluer les modèles lorsque le volume annoté devient suffisant.

## 7. Veille réglementaire

### 7.1 Recommandations de la CNIL

La CNIL recommande d’appliquer les principes du RGPD dès la conception d’un système d’IA : finalité déterminée, base légale, minimisation, qualité des données, durée de conservation, sécurité, documentation et respect des droits.

#### Recommandation pour ReviewPro

- ne collecter que les données utiles à la classification ;
- ne pas conserver le nom des auteurs importés ;
- documenter la provenance et la licence des datasets ;
- définir une durée de conservation ;
- permettre la suppression ou la correction lorsqu’un droit s’applique ;
- éviter d’enregistrer le texte complet dans les journaux de monitorage ;
- réaliser une analyse d’impact si les usages futurs créent un risque élevé pour les personnes.

### 7.2 Règlement européen sur l’intelligence artificielle

Le règlement européen adopte une approche fondée sur le niveau de risque. ReviewPro classe des plaintes portant sur des produits et ne prend pas de décision concernant l’emploi, le crédit, l’éducation, la santé ou un service public essentiel.

À ce stade, ReviewPro semble relever d’un usage à risque limité ou minimal. Cette appréciation doit être réexaminée si la finalité du système change.

#### Recommandation pour ReviewPro

- informer l’utilisateur qu’une classification est produite par une IA ;
- afficher les prédictions incertaines ;
- conserver une supervision humaine ;
- documenter la finalité, le dataset, les métriques et les limites ;
- journaliser la version du modèle utilisée ;
- permettre la correction des résultats ;
- réévaluer le niveau de risque en cas de changement d’usage.

Le champ `needs_review` et le seuil de décision de `0,30` contribuent déjà à la supervision humaine.

## 8. Tableau de veille et recommandations

| Date | Sujet | Information retenue | Recommandation | Priorité | État |
|---|---|---|---|---|---|
| 23/08/2026 | Persistance scikit-learn | Un fichier joblib non fiable peut exécuter du code | Contrôler la provenance et vérifier le hash | Haute | Partiellement appliqué |
| 23/08/2026 | Compatibilité du modèle | Le chargement entre versions différentes n’est pas garanti | Figer les versions et reconstruire après mise à jour majeure | Haute | Appliqué |
| 23/08/2026 | Déploiement FastAPI | Le service doit gérer démarrage, redémarrage, mémoire et HTTPS | Conteneuriser et ajouter une politique de redémarrage | Haute | À faire |
| 23/08/2026 | Sécurité API | La consommation non limitée constitue un risque | Ajouter rate limiting et authentification | Haute | À faire |
| 23/08/2026 | CNIL et IA | Les principes RGPD doivent être appliqués dès la conception | Minimiser les journaux et définir une durée de conservation | Haute | Partiellement appliqué |
| 23/08/2026 | Règlement IA | Documentation et supervision humaine améliorent la confiance | Conserver le seuil et la validation humaine | Haute | Appliqué |
| 23/08/2026 | Qualité ML | 120 avis sont insuffisants pour stabiliser toutes les classes | Enrichir et rééquilibrer le dataset | Haute | En cours |

## 9. Partage et validation collective

Le résultat de la veille doit être présenté au tuteur ou au groupe projet. Chaque échange doit produire une trace courte :

| Date | Participants | Sujet | Décision | Responsable | Échéance |
|---|---|---|---|---|---|
| À renseigner | Étudiante + tuteur/relecteur | Validation des recommandations initiales | À renseigner après l’échange | À renseigner | À renseigner |

Cette ligne devra être complétée avec une réunion ou une relecture réellement effectuée. Une capture, un compte rendu ou un commentaire de validation pourra être ajouté au rapport comme preuve du travail collectif.

## 10. Décisions retenues pour le projet

Les premières décisions issues de la veille sont :

1. conserver le SVM local pour la première version ;
2. vérifier l’intégrité du modèle avant son chargement ;
3. figer les dépendances Python ;
4. ne pas journaliser inutilement les textes complets ;
5. ajouter des métriques de latence, d’erreur et d’incertitude ;
6. maintenir une vérification humaine sous le seuil de 0,30 ;
7. ajouter des tests du dataset et du modèle dans la CI ;
8. conteneuriser FastAPI pour la livraison ;
9. protéger les routes et limiter le nombre de requêtes ;
10. réévaluer le modèle avec davantage de données annotées.

## 11. Liens officiels consultés

- CNIL, recommandations pour le développement des systèmes d’IA : https://www.cnil.fr/en/ai-system-development-cnils-recommendations-to-comply-gdpr
- Commission européenne, règlement européen sur l’IA : https://digital-strategy.ec.europa.eu/en/policies/regulatory-framework-ai
- EUR-Lex, règlement (UE) 2024/1689 : https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32024R1689
- scikit-learn, persistance des modèles : https://scikit-learn.org/stable/model_persistence.html
- FastAPI, concepts de déploiement : https://fastapi.tiangolo.com/deployment/concepts/
- OWASP, API Security Top 10 2023 : https://owasp.org/API-Security/editions/2023/en/0x11-t10/
- Hugging Face, documentation Transformers : https://huggingface.co/docs/transformers/

## 12. Conclusion

La veille conduit à des recommandations directement applicables à ReviewPro. Elle ne consiste pas uniquement à accumuler des liens : chaque information est reliée à une décision, une priorité et un état d’application.

La dimension collective devra être finalisée par une relecture réelle du tableau et par la conservation d’une preuve de l’échange.
