# Bloc 3 — Développer et mettre en production une application d’intelligence artificielle

## 1. Présentation du bloc

Ce dossier rassemble les réalisations et les preuves du Bloc 3 du projet ReviewPro.

ReviewPro est une application web qui centralise des avis sur des produits électroniques et aide à identifier leur principale famille de plainte. L’application ne remplace pas la décision humaine. Lorsque la prédiction du modèle est incertaine, elle indique qu’une vérification humaine est nécessaire.

Le système est composé de trois éléments :

- une interface utilisateur développée avec Vue.js ;
- une API applicative développée avec Laravel ;
- un service d’intelligence artificielle exposé avec FastAPI.

Le projet a été réalisé individuellement. Les choix, développements, tests et livraisons ont été organisés avec une démarche agile adaptée à ce contexte.

## 2. Objectif du dossier

Les documents de ce dossier permettent de démontrer les huit compétences attendues :

1. analyser le besoin d’une application intégrant un service d’intelligence artificielle ;
2. concevoir son cadre technique ;
3. coordonner sa réalisation dans un contexte agile et MLOps ;
4. développer ses composants et ses interfaces ;
5. automatiser les tests du code source ;
6. créer un processus de livraison continue ;
7. surveiller l’application et alimenter la boucle de feedback ;
8. résoudre et documenter des incidents techniques.

## 3. Documents du Bloc 3

| Document | Compétence principale | Contenu |
| --- | --- | --- |
| `analyse-besoin-specifications.md` | Analyse du besoin | Commanditaire, utilisateurs, besoins, parcours, accessibilité, critères d’acceptation, risques et faisabilité |
| `architecture-technique.md` | Conception technique | Architecture Vue–Laravel–FastAPI, flux, données, technologies, sécurité et déploiement cible |
| `gestion-projet-agile.md` | Coordination | Backlog, priorités, itérations, critères de qualité, suivi et limite du travail individuel |
| `developpement-securite-accessibilite.md` | Développement | Composants réalisés, intégration des API, validation, accessibilité, sécurité et gestion des données |
| `integration-continue-application.md` | Intégration continue | Workflows automatisés du backend, du frontend et du service IA, tests et artefacts |
| `livraison-continue-application.md` | Livraison continue | Packaging, tags, releases, empreintes SHA-256, registre de conteneurs et retour arrière |
| `monitorage-application.md` | Monitorage | Endpoint de santé, métriques, alertes, journaux, confidentialité et feedback loop |
| `resolution-incident.md` | Résolution d’incidents | Diagnostic, cause racine, correction, validation et prévention de deux incidents |

## 4. Vue générale de l’architecture

Le navigateur affiche l’interface Vue. Celle-ci appelle l’API Laravel, qui porte la logique applicative et accède à la base de données. Pour une classification, Laravel appelle le service FastAPI. FastAPI charge le modèle validé et retourne la catégorie, la marge de décision, le classement et l’indicateur de vérification humaine.

Flux principal :

1. l’utilisateur saisit un avis dans Vue ;
2. Vue envoie le texte à `POST /api/ai/predict` ;
3. Laravel valide la requête ;
4. Laravel appelle `POST /predict` sur FastAPI ;
5. le modèle calcule la prédiction et sa marge ;
6. Laravel transmet la réponse au frontend ;
7. Vue affiche le résultat et le niveau de confiance.

## 5. Correspondance entre les compétences et les preuves

### 5.1 Analyser le besoin

Le besoin identifié est d’aider une entreprise à repérer rapidement les sujets de plainte présents dans un volume important d’avis clients.

Les travaux réalisés comprennent :

- l’identification du commanditaire fictif et des utilisateurs ;
- la définition du périmètre fonctionnel ;
- la rédaction des besoins fonctionnels et non fonctionnels ;
- la description des parcours utilisateurs ;
- la définition de critères d’acceptation mesurables ;
- la prise en compte de l’utilisabilité et de l’accessibilité ;
- l’analyse de la faisabilité technique et des risques.

Preuve principale : `analyse-besoin-specifications.md`.

### 5.2 Concevoir le cadre technique

L’architecture sépare clairement les responsabilités :

- Vue.js gère la présentation et les interactions ;
- Laravel gère les règles applicatives, la validation et l’accès aux données ;
- FastAPI expose le modèle d’intelligence artificielle ;
- SQLite est utilisée pour le prototype ;
- GitHub Actions automatise les contrôles et les livraisons ;
- Docker assure le packaging du service IA.

Les choix techniques, les flux, les contrats d’API, les environnements et les évolutions de production sont documentés.

Preuve principale : `architecture-technique.md`.

### 5.3 Coordonner la réalisation technique

Le projet ayant été réalisé seule, la coordination a pris la forme d’une organisation individuelle structurée :

- backlog priorisé ;
- découpage en itérations ;
- critères d’acceptation ;
- contrôles à la fin de chaque étape ;
- versionnement Git ;
- automatisation des validations ;
- documentation des décisions et des limites.

Cette organisation démontre la planification et le suivi technique. Elle ne constitue toutefois pas une preuve complète d’animation d’équipe. Une revue externe réelle reste recommandée pour renforcer cette compétence.

Preuve principale : `gestion-projet-agile.md`.

### 5.4 Développer les composants et les interfaces

Les éléments développés et intégrés comprennent :

- un tableau de bord Vue ;
- un formulaire d’analyse IA ;
- une API Laravel pour les avis et les prédictions ;
- un connecteur Laravel vers FastAPI ;
- une API FastAPI de prédiction ;
- un mécanisme de signalement des prédictions incertaines ;
- des validations côté frontend, Laravel et FastAPI ;
- des retours d’erreur compréhensibles ;
- une interface utilisable au clavier et compatible avec les technologies d’assistance.

Les tests Vitest et axe vérifient notamment le libellé du champ, son aide, l’annonce des erreurs, le focus du résultat et l’absence de violation automatique détectée.

Preuve principale : `developpement-securite-accessibilite.md`.

### 5.5 Automatiser les tests du code source

Trois chaînes d’intégration continue couvrent les composants du projet :

- Laravel : validation Composer, syntaxe PHP, migrations, tests et rapport JUnit ;
- Vue : installation reproductible, tests Vitest, contrôles d’accessibilité et build Vite ;
- IA : syntaxe Python, validation du dataset, du modèle, de l’API, du monitorage et test du conteneur.

Résultats de référence :

- Laravel : 11 tests réussis et 44 assertions après ajout du monitorage ;
- service IA : 39 tests réussis ;
- frontend : 4 tests d’accessibilité réussis et build de production valide ;
- workflow Laravel corrigé : exécution `32743765169` réussie ;
- workflow frontend : exécution `32744160943` réussie ;
- validation du monitorage Laravel : exécution `32746608577` réussie.

Preuve principale : `integration-continue-application.md`.

### 5.6 Créer une livraison continue

Les trois composants disposent d’une livraison versionnée :

- `ai-v1.0.0` pour le service et le modèle IA ;
- `backend-v1.0.0` pour Laravel ;
- `frontend-v1.0.0` pour Vue.

Les workflows exécutent les validations avant de produire les paquets. Les releases contiennent des livrables téléchargeables et des empreintes SHA-256. Le service IA est également publié sous forme d’image de conteneur dans GitHub Container Registry.

Exécutions officielles :

- backend : `32745131135` ;
- frontend : `32745628919` ;
- IA : `32605554570`.

Le projet met en œuvre une livraison continue opérationnelle. Le déploiement automatique vers un serveur public reste une évolution future.

Preuve principale : `livraison-continue-application.md`.

### 5.7 Surveiller l’application

Laravel expose `GET /api/health`, qui contrôle :

- le fonctionnement de l’application ;
- la connexion à la base de données ;
- la disponibilité du service FastAPI ;
- la version du modèle ;
- la latence du contrôle.

FastAPI fournit en complément :

- `GET /health` ;
- `GET /metrics` pour Prometheus ;
- `GET /monitoring/summary` ;
- `GET /monitoring/alerts` ;
- `POST /feedback` pour enregistrer une correction humaine.

La journalisation minimise les données : l’indisponibilité est enregistrée avec le composant et la classe d’exception, sans texte d’avis ni identité de l’utilisateur.

Preuve principale : `monitorage-application.md`.

### 5.8 Résoudre les incidents techniques

Deux incidents complémentaires sont documentés.

Le premier est un incident réel détecté par GitHub Actions. L’installation Composer échouait car `bootstrap/cache` n’existait pas dans l’environnement propre de la CI. Le workflow a été corrigé pour créer les répertoires Laravel et définir les permissions avant l’installation. Une nouvelle exécution réussie a validé la solution.

Le second est une mise en situation contrôlée. L’arrêt de FastAPI a provoqué :

- un état `degraded` ;
- une réponse HTTP 503 de `/api/health` ;
- l’identification de `ai_service` comme indisponible ;
- un journal Laravel sans donnée personnelle.

Après redémarrage de FastAPI, l’endpoint est revenu à HTTP 200 et tous les contrôles sont repassés à l’état `ok`.

Preuve principale : `resolution-incident.md`.

## 6. Tests et résultats principaux

| Composant | Contrôle | Résultat de référence |
| --- | --- | --- |
| Laravel | Tests unitaires et fonctionnels | 11 tests, 44 assertions |
| Laravel | Migrations SQLite | Toutes les migrations réussies |
| FastAPI et modèle | Pytest | 39 tests réussis |
| Frontend Vue | Vitest et axe | 4 tests réussis |
| Frontend Vue | Build Vite | Build de production réussi |
| Application | Santé normale | HTTP 200 |
| Application | FastAPI arrêté | HTTP 503, état `degraded` |
| Application | FastAPI redémarré | HTTP 200, état `ok` |

## 7. Démonstration conseillée au jury

La démonstration peut suivre cet ordre :

1. présenter le besoin métier et l’architecture ;
2. afficher le tableau de bord ;
3. saisir un avis concernant une panne matérielle ;
4. montrer la catégorie, la marge et l’indicateur de confiance ;
5. expliquer la vérification humaine pour une prédiction incertaine ;
6. afficher `/api/health` dans l’état normal ;
7. présenter les tests Laravel, Python et Vue ;
8. montrer les workflows GitHub Actions réussis ;
9. présenter les tags et les releases ;
10. expliquer l’incident CI et l’arrêt contrôlé de FastAPI.

Il n’est pas nécessaire de provoquer une panne pendant la soutenance. Les réponses JSON, journaux et historiques GitHub Actions constituent des preuves reproductibles.

## 8. Points à expliquer avec transparence

Le projet est un prototype fonctionnel et non une plateforme publique complète.

Ses principales limites sont :

- réalisation individuelle, sans équipe projet réelle ;
- base SQLite adaptée au prototype mais pas à une forte charge ;
- absence d’authentification et d’autorisations complètes sur certains endpoints ;
- absence de HTTPS dans l’environnement local ;
- absence de limitation de débit ;
- métriques non encore reliées à un tableau Grafana ;
- alertes non encore envoyées vers un canal externe ;
- déploiement public non automatisé ;
- dataset d’apprentissage limité et performances du modèle perfectibles.

Ces limites sont connues et associées à des recommandations : PostgreSQL, HTTPS, contrôle d’accès, rate limiting, politique automatisée de conservation, Prometheus/Grafana, canal d’alerte et amélioration continue du dataset.

## 9. Dépôts et versions

- Backend Laravel, service IA et MLOps : `Thiaba03/reviewpro-ai-mlops` ;
- Frontend Vue : `Thiaba03/reviewpro-frontend` ;
- version IA : `ai-v1.0.0` ;
- version backend : `backend-v1.0.0` ;
- version frontend : `frontend-v1.0.0`.

## 10. Conclusion

Le Bloc 3 montre la réalisation complète d’une application intégrant un modèle d’intelligence artificielle, depuis l’analyse du besoin jusqu’à son exploitation technique.

ReviewPro dispose de spécifications, d’une architecture séparée, d’interfaces accessibles, de tests automatisés, de chaînes d’intégration et de livraison continues, d’un monitorage applicatif et d’une procédure de résolution d’incidents.

Les preuves sont constituées par le code, les tests, les résultats d’exécution, les artefacts, les tags, les releases, les réponses des endpoints et les journaux techniques. La principale limite à présenter honnêtement concerne la dimension collective, puisque le projet a été réalisé seule.
