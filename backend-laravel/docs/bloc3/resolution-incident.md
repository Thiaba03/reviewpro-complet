# Résolution d’incidents techniques — ReviewPro

## 1. Objectif

Ce document présente le diagnostic et la résolution d’incidents techniques rencontrés sur ReviewPro.

Il apporte les preuves suivantes :

- détection d’une erreur dans un environnement automatisé ;
- analyse des journaux ;
- identification de la cause racine ;
- modification du code de configuration ;
- validation locale et distante ;
- détection d’une indisponibilité applicative ;
- restauration du service ;
- documentation des solutions et des mesures préventives.

Deux incidents sont présentés.

| Incident | Nature | Résolution principale |
|---|---|---|
| Échec de l’intégration continue Laravel | Configuration et environnement | Création des répertoires avant Composer |
| Service FastAPI indisponible | Exploitation applicative | Redémarrage et contrôle du rétablissement |

## 2. Contexte technique

ReviewPro est composé de :

- une interface Vue ;
- une API Laravel ;
- une base SQLite pour le prototype ;
- une API FastAPI ;
- un modèle SVM ;
- des workflows GitHub Actions ;
- des journaux Laravel et FastAPI ;
- des endpoints de santé et de monitorage.

Le projet fonctionne localement, mais il doit également être reproductible sur une machine GitHub neuve.

## 3. Méthode de résolution

La méthode appliquée suit les étapes suivantes :

1. observer le symptôme ;
2. conserver la preuve ;
3. identifier l’étape exacte en erreur ;
4. lire les journaux pertinents ;
5. formuler une hypothèse ;
6. vérifier la cause racine ;
7. choisir une correction limitée ;
8. tester localement si possible ;
9. versionner la correction ;
10. vérifier la nouvelle exécution ;
11. documenter la prévention.

Cette méthode évite de modifier plusieurs éléments sans comprendre la cause.

# Incident 1 — Échec de l’installation Laravel en CI

## 4. Description de l’incident

Un workflow GitHub Actions a été ajouté pour automatiser :

- la validation Composer ;
- l’installation des dépendances PHP ;
- la vérification de la syntaxe ;
- l’exécution des migrations ;
- les tests Laravel ;
- la publication d’un rapport JUnit.

La première exécution a échoué pendant l’installation des dépendances.

## 5. Identification de l’exécution

Les informations de l’exécution sont :

```text
Workflow : Application Backend CI
Branche : main
Exécution : 32743052810
Job : Tester Laravel
Résultat : échec
```

Les premières étapes avaient réussi :

- préparation du job ;
- récupération des sources ;
- installation de PHP ;
- validation des fichiers Composer.

L’étape en échec était :

```text
Installer les dépendances PHP
```

## 6. Symptôme observé

GitHub Actions affichait :

```text
Process completed with exit code 1.
```

Le rapport JUnit n’était pas disponible, car l’exécution n’avait pas atteint les tests.

L’avertissement sur l’absence de rapport était une conséquence et non la cause principale.

## 7. Collecte des journaux

La commande suivante a permis d’afficher les étapes en échec :

```bash
gh run view 32743052810 \
  --repo Thiaba03/reviewpro-ai-mlops \
  --log-failed
```

La fin du journal indiquait :

```text
Generating optimized autoload files
Illuminate\Foundation\ComposerScripts::postAutoloadDump
php artisan package:discover
```

puis :

```text
The bootstrap/cache directory must be present and writable.
```

Composer avait correctement téléchargé et extrait les dépendances. L’erreur se produisait pendant le script Laravel exécuté après la génération de l’autoloader.

## 8. Analyse de la cause

Sur l’ordinateur de développement, le dossier suivant existait :

```text
bootstrap/cache
```

Laravel pouvait donc y écrire son manifeste de paquets.

Sur GitHub Actions, le dépôt était cloné dans un environnement vierge.

Git ne versionne pas les dossiers vides. Le dossier n’était donc pas présent dans la copie reconstruite par la CI.

La commande :

```text
php artisan package:discover
```

ne pouvait pas écrire dans `bootstrap/cache`.

## 9. Cause racine

La cause racine n’était pas :

- une incompatibilité PHP ;
- une dépendance Composer manquante ;
- une erreur dans `composer.lock` ;
- un problème de réseau ;
- une migration défaillante.

La cause racine était une hypothèse implicite de l’environnement local : les répertoires Laravel nécessaires existaient localement, mais leur création n’était pas automatisée dans la CI.

## 10. Impact

L’incident empêchait :

- la fin de `composer install` ;
- la préparation de Laravel ;
- les contrôles de syntaxe ;
- les migrations ;
- les tests ;
- la génération du rapport JUnit ;
- la validation de la version.

La CI a correctement bloqué la chaîne. Aucun livrable backend n’a été produit par cette exécution.

## 11. Correction apportée

Une nouvelle étape a été ajoutée dans :

```text
.github/workflows/application-ci.yml
```

Elle est placée avant `composer install`.

```yaml
- name: Préparer les répertoires Laravel
  run: |
    mkdir -p bootstrap/cache
    mkdir -p storage/framework/cache/data
    mkdir -p storage/framework/sessions
    mkdir -p storage/framework/views
    mkdir -p storage/logs
    chmod -R u+rwX bootstrap/cache storage
```

## 12. Justification de la correction

La correction :

- crée uniquement les dossiers attendus par Laravel ;
- fonctionne même si les dossiers existent déjà grâce à `mkdir -p` ;
- applique les droits d’écriture à l’utilisateur du job ;
- ne donne pas des permissions globales excessives comme `777` ;
- prépare également les dossiers nécessaires aux sessions, vues, caches et journaux ;
- intervient avant le script Composer qui en dépend.

## 13. Alternatives étudiées

### 13.1 Versionner artificiellement tous les dossiers

Il aurait été possible d’ajouter des fichiers `.gitignore` dans chaque dossier.

Cette solution peut être utile, mais elle ne remplace pas la préparation explicite d’un environnement de CI.

### 13.2 Donner les permissions 777

La commande suivante n’a pas été retenue :

```text
chmod -R 777
```

Elle accorde des permissions trop larges et ne correspond pas au principe du moindre privilège.

### 13.3 Ignorer les scripts Composer

L’option `--no-scripts` aurait évité `package:discover`, mais elle aurait produit une installation Laravel incomplète.

La correction retenue traite donc la véritable cause.

## 14. Vérification locale préalable

Avant la publication du workflow, plusieurs contrôles avaient été réalisés :

```text
composer.json valide
migrations exécutées sur une base temporaire
8 tests Laravel réussis
25 assertions
rapport JUnit généré
```

Ces contrôles confirmaient que le code fonctionnait localement, mais ils ne reproduisaient pas l’absence du dossier dans un clone neuf.

Cette différence justifie l’utilisation d’une CI éphémère.

## 15. Validation après correction

La nouvelle exécution porte l’identifiant :

```text
32743765169
```

Le résultat obtenu est :

```text
Workflow : Application Backend CI
Job : Tester Laravel
Résultat : succès
Durée : 25 secondes
Artefact : laravel-test-results
```

Les étapes suivantes ont toutes réussi :

- validation Composer ;
- préparation des répertoires ;
- installation des dépendances ;
- préparation de Laravel ;
- syntaxe PHP ;
- migrations ;
- tests ;
- publication du rapport.

## 16. Critères de résolution

L’incident est considéré comme résolu car :

- la cause est identifiée ;
- le workflow est corrigé ;
- la correction est versionnée ;
- la nouvelle exécution est réussie ;
- le rapport JUnit est produit ;
- les exécutions suivantes utilisent la même préparation ;
- le workflow de livraison backend réutilise la correction.

## 17. Prévention de la régression

Les mesures suivantes préviennent le retour de l’incident :

- conservation de l’étape dans le workflow ;
- exécution automatique à chaque push et pull request ;
- préparation identique dans le workflow de livraison ;
- validation de la CI avant une release ;
- documentation de la cause ;
- conservation des exécutions GitHub comme preuve.

## 18. Apprentissage issu de l’incident

Un projet peut fonctionner sur la machine du développeur et échouer dans un environnement propre.

Les dossiers, variables, permissions, caches et fichiers générés localement peuvent masquer des dépendances non documentées.

La CI a révélé cette dépendance et a permis de rendre l’installation reproductible.

# Incident 2 — Indisponibilité du service FastAPI

## 19. Objectif de la mise en situation

Le second incident vérifie que le monitorage applicatif détecte une dépendance indisponible et permet de confirmer son rétablissement.

Le processus FastAPI a été arrêté volontairement dans un environnement local contrôlé.

## 20. État normal avant l’incident

Avant l’arrêt, la route :

```text
GET /api/health
```

retournait :

```text
HTTP 200
status: ok
application: ok
database: ok
ai_service: ok
latency_ms: 20.18
```

Le modèle chargé était :

```text
review_topic_macro_svm
```

## 21. Déclenchement contrôlé

Le processus en écoute sur le port 8001 a été identifié avec :

```bash
lsof -nP -iTCP:8001 -sTCP:LISTEN
```

Le PID observé était :

```text
88396
```

Le processus a été arrêté proprement avec `kill`.

Le contrôle suivant confirmait :

```text
Service IA arrêté
```

## 22. Détection par l’application

Un nouvel appel à `/api/health` a retourné :

```text
HTTP 503
status: degraded
application: ok
database: ok
ai_service: unavailable
latency_ms: 16.98
```

Le monitorage a correctement isolé FastAPI comme composant en erreur.

Laravel et SQLite n’ont pas été déclarés indisponibles.

## 23. Journal de l’incident

Laravel a enregistré :

```text
Application health check: AI service unavailable.
```

La classe de l’exception était :

```text
Illuminate\Http\Client\ConnectionException
```

Le journal ne contenait pas :

- le texte d’un avis ;
- le nom d’un auteur ;
- un identifiant utilisateur ;
- une clé d’API ;
- un secret.

## 24. Diagnostic

Les éléments suivants permettaient de conclure :

- Laravel répondait ;
- la base répondait ;
- la connexion vers le port 8001 échouait ;
- l’exception était une erreur de connexion HTTP ;
- le processus FastAPI n’écoutait plus.

La cause était donc l’arrêt du processus Uvicorn et non une défaillance du modèle ou de la base.

## 25. Résolution opérationnelle

FastAPI a été redémarré avec :

```bash
nohup .venv-ai/bin/python \
  -m uvicorn ai_service.main:app \
  --host 127.0.0.1 \
  --port 8001 \
  > storage/logs/ai-service.log 2>&1 &
```

Le nouveau processus avait le PID :

```text
4760
```

Les journaux Uvicorn indiquaient :

```text
Application startup complete.
Uvicorn running on http://127.0.0.1:8001
```

## 26. Vérification du rétablissement

Après le redémarrage, `/api/health` a retourné :

```text
HTTP 200
status: ok
application: ok
database: ok
ai_service: ok
latency_ms: 6.34
```

La réponse contenait à nouveau :

- le nom du modèle ;
- sa version ;
- l’état de la base ;
- l’état de l’application.

## 27. Critères de clôture du second incident

L’incident est clôturé car :

- le processus écoute à nouveau sur le port 8001 ;
- `/health` de FastAPI répond ;
- `/api/health` de Laravel répond HTTP 200 ;
- le modèle est identifié ;
- aucune erreur nouvelle n’apparaît au démarrage ;
- la latence redevient normale.

## 28. Amélioration du code issue du cas pratique

Avant cette mise en situation, Laravel disposait seulement de `/up`, qui ne vérifiait pas ses dépendances.

Les fichiers suivants ont été ajoutés ou modifiés :

```text
app/Http/Controllers/Api/ApplicationHealthController.php
tests/Feature/ApplicationHealthApiTest.php
routes/api.php
```

Le nouveau contrôleur :

- vérifie la base ;
- appelle FastAPI ;
- retourne un statut global ;
- mesure la latence ;
- écrit des journaux minimisés ;
- utilise HTTP 503 en cas d’incident.

La correction a donc apporté une modification durable au code de l’application, et pas uniquement une action manuelle de redémarrage.

## 29. Tests de non-régression

Trois tests ont été ajoutés :

1. application entièrement disponible ;
2. service IA indisponible ;
3. service IA dégradé.

Résultat ciblé :

```text
3 tests réussis
19 assertions
```

Résultat global :

```text
11 tests réussis
44 assertions
```

## 30. Validation en intégration continue

Le commit du monitorage a déclenché :

```text
Workflow : Application Backend CI
Exécution : 32746608577
Résultat : succès
```

La correction est donc validée dans un environnement GitHub neuf.

## 31. Prévention recommandée pour FastAPI

Dans un environnement de production, les mesures suivantes sont recommandées :

- exécuter FastAPI dans un conteneur ;
- définir une politique de redémarrage ;
- utiliser un orchestrateur ou un gestionnaire de processus ;
- configurer une sonde de disponibilité ;
- interroger `/api/health` périodiquement ;
- envoyer une notification lors d’un HTTP 503 ;
- centraliser les journaux ;
- vérifier `/metrics` après le redémarrage ;
- limiter l’accès aux endpoints administratifs.

Exemple de politique Docker cible :

```text
restart: unless-stopped
```

## 32. Procédure générale de résolution

### 32.1 Si Laravel ne répond pas

1. vérifier le processus PHP ;
2. appeler `/up` ;
3. lire `storage/logs/laravel.log` ;
4. vérifier les permissions ;
5. vérifier les variables d’environnement ;
6. redémarrer le service ;
7. exécuter les tests de santé.

### 32.2 Si la base est indisponible

1. lire `checks.database` ;
2. vérifier `DB_CONNECTION` et `DB_DATABASE` ;
3. vérifier le fichier ou le serveur ;
4. vérifier les permissions ;
5. tester une connexion minimale ;
6. restaurer si nécessaire ;
7. rappeler `/api/health`.

### 32.3 Si FastAPI est indisponible

1. lire `checks.ai_service` ;
2. appeler directement le port 8001 ;
3. vérifier le processus ;
4. lire le journal Uvicorn ;
5. vérifier le modèle et ses métadonnées ;
6. redémarrer le service ;
7. appeler `/health` ;
8. appeler `/api/health`.

## 33. Gestion du retour arrière

Lorsqu’une correction de code provoque une régression :

1. conserver les journaux ;
2. identifier le dernier commit valide ;
3. créer une correction inverse explicite ;
4. éviter une suppression destructive de l’historique ;
5. exécuter les tests ;
6. vérifier la CI ;
7. republier une version corrective.

Pour une release, la version précédente reste disponible grâce aux tags et aux paquets GitHub.

## 34. Limites du cas pratique

| Limite | Suite recommandée |
|---|---|
| Incident FastAPI déclenché volontairement | Tester ensuite un incident réel en staging |
| Prototype local | Déployer un environnement supervisé |
| Redémarrage manuel | Automatiser le redémarrage |
| Pas de notification externe | Configurer un canal d’alerte |
| Journaux locaux | Centraliser et contrôler l’accès |
| SQLite | Utiliser PostgreSQL pour la production |

L’incident CI, lui, était un incident réel non prévu et a nécessité une correction du workflow.

## 35. Preuves à présenter au jury

Les preuves disponibles sont :

- l’exécution CI en échec `32743052810` ;
- son journal `bootstrap/cache` ;
- la modification de `application-ci.yml` ;
- l’exécution réussie `32743765169` ;
- l’artefact JUnit ;
- le contrôleur de santé ;
- les trois tests du monitorage ;
- l’exécution CI `32746608577` ;
- la réponse HTTP 200 avant incident ;
- la réponse HTTP 503 pendant l’incident ;
- le journal `ConnectionException` ;
- le journal de redémarrage Uvicorn ;
- la réponse HTTP 200 après résolution.

## 36. Démonstration orale

La présentation peut suivre cet ordre :

1. montrer l’exécution GitHub en échec ;
2. identifier l’étape Composer ;
3. ouvrir la ligne d’erreur `bootstrap/cache` ;
4. expliquer la différence entre le Mac et le clone GitHub ;
5. montrer la correction YAML ;
6. ouvrir l’exécution réussie ;
7. présenter `/api/health` dans l’état normal ;
8. arrêter FastAPI ;
9. présenter HTTP 503 et le journal ;
10. redémarrer FastAPI ;
11. présenter HTTP 200 ;
12. montrer les tests et la CI finale ;
13. conclure avec les mesures préventives.

## 37. Correspondance avec la compétence RNCP

Cette réalisation démontre :

- l’observation d’un incident technique réel ;
- l’analyse de journaux GitHub Actions ;
- l’identification d’une cause racine ;
- la correction du code de configuration ;
- le respect du principe du moindre privilège ;
- la validation de la correction dans un environnement neuf ;
- l’ajout d’un monitorage applicatif ;
- la détection d’une dépendance indisponible ;
- l’utilisation d’un code HTTP adapté ;
- la journalisation sans donnée personnelle ;
- la résolution opérationnelle d’un incident ;
- la vérification du retour à la normale ;
- l’ajout de tests de non-régression ;
- la validation de ces tests par la CI ;
- la documentation d’une procédure de diagnostic ;
- la formulation de mesures préventives.

## 38. Conclusion

Les deux incidents montrent deux dimensions complémentaires de la maintenance de ReviewPro.

L’échec `bootstrap/cache` a révélé une dépendance cachée à l’environnement local. La correction du workflow a rendu l’installation Laravel reproductible et a été vérifiée par une nouvelle exécution réussie.

L’arrêt de FastAPI a ensuite démontré le fonctionnement du monitorage applicatif. Laravel a localisé la dépendance indisponible, retourné HTTP 503 et produit un journal minimisé. Le redémarrage a permis de rétablir HTTP 200.

Les solutions sont intégrées au code, testées automatiquement, versionnées et documentées. Elles renforcent le fonctionnement opérationnel de l’application et réduisent le risque de réapparition des mêmes incidents.
