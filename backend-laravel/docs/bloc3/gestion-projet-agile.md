# Gestion agile et coordination technique — ReviewPro

## 1. Objectif du document

Ce document présente l’organisation utilisée pour planifier et coordonner la réalisation technique de ReviewPro.

Le projet a été réalisé individuellement. La coordination a donc principalement porté sur :

- l’enchaînement des travaux ;
- la cohérence entre Vue, Laravel et FastAPI ;
- le suivi des priorités ;
- la qualité des livrables ;
- la gestion des versions ;
- l’automatisation MLOps.

Cette situation ne constitue pas une animation complète d’équipe. Les éléments de collaboration réelle restant à obtenir sont identifiés dans ce document.

## 2. Méthode retenue

Le projet utilise une méthode agile adaptée à une réalisation individuelle.

Le travail est organisé en itérations courtes comprenant :

1. l’identification du besoin ;
2. la définition du résultat attendu ;
3. le développement d’une petite fonctionnalité ;
4. l’exécution des tests ;
5. la correction des erreurs ;
6. la documentation ;
7. le versionnement sur GitHub.

Cette organisation permet de conserver une application fonctionnelle après chaque étape importante.

## 3. Rôles du projet

Dans le contexte de cette réalisation individuelle, plusieurs responsabilités ont été assumées par la même personne.

| Responsabilité | Activités réalisées |
|---|---|
| Commanditaire fictif | Expression du besoin métier et validation du périmètre |
| Product Owner | Priorisation des fonctionnalités et critères d’acceptation |
| Développeuse frontend | Interface Vue et accessibilité |
| Développeuse backend | API Laravel, données et connecteur IA |
| Développeuse IA | Benchmark, entraînement, FastAPI et tests du modèle |
| DevOps / MLOps | Git, GitHub Actions, Docker, packaging et release |
| Exploitation | Monitorage, alertes, feedback et diagnostic |

Cette concentration des rôles facilite les décisions, mais constitue également une limite : les décisions bénéficient de moins de contradiction et de revue externe.

## 4. Découpage du produit

Le projet est découpé en plusieurs ensembles fonctionnels.

### Épic 1 — Collecte et gestion des données

- structurer les sources ;
- importer les avis ;
- nettoyer et homogénéiser les données ;
- tracer les lots d’import ;
- mettre les données à disposition par API.

### Épic 2 — Service d’intelligence artificielle

- définir les familles de plaintes ;
- annoter le jeu d’apprentissage ;
- comparer plusieurs modèles ;
- entraîner le modèle retenu ;
- exposer le modèle avec FastAPI.

### Épic 3 — Application web

- créer le tableau de bord ;
- consulter les avis ;
- ajouter un avis ;
- intégrer l’analyse IA ;
- gérer les erreurs et le chargement ;
- appliquer les exigences d’accessibilité.

### Épic 4 — Qualité et MLOps

- automatiser les tests ;
- vérifier le dataset et le modèle ;
- packager le service ;
- construire l’image Docker ;
- publier une version ;
- surveiller le service.

## 5. Backlog priorisé

| Identifiant | Élément du backlog | Priorité | État |
|---|---|---|---|
| US-01 | Importer et tracer les avis | Haute | Terminé |
| US-02 | Consulter les statistiques | Haute | Terminé |
| US-03 | Filtrer et paginer les avis | Haute | Terminé |
| US-04 | Comparer plusieurs solutions IA | Haute | Terminé |
| US-05 | Exposer le modèle avec FastAPI | Haute | Terminé |
| US-06 | Connecter Laravel à FastAPI | Haute | Terminé |
| US-07 | Intégrer l’analyse dans Vue | Haute | Terminé |
| US-08 | Signaler les prédictions incertaines | Haute | Terminé |
| US-09 | Tester automatiquement le modèle | Haute | Terminé |
| US-10 | Monitorer les prédictions | Haute | Terminé |
| US-11 | Enregistrer le feedback humain | Haute | Terminé |
| US-12 | Construire et publier une image Docker | Haute | Terminé |
| US-13 | Tester l’accessibilité du composant IA | Haute | Terminé |
| US-14 | Étendre la CI à Laravel et au frontend | Haute | À réaliser |
| US-15 | Automatiser la livraison de l’application complète | Haute | À réaliser |
| US-16 | Ajouter l’authentification et les rôles | Moyenne | À réaliser |
| US-17 | Automatiser la conservation des données | Moyenne | À réaliser |
| US-18 | Ajouter une supervision centralisée | Moyenne | À réaliser |

## 6. Planification par itérations

### Itération 1 — Cadrage et données

Objectifs :

- clarifier le besoin ;
- choisir le domaine des produits électroniques ;
- construire le modèle de données ;
- intégrer plusieurs sources ;
- exposer les avis par API.

Résultats :

- base structurée ;
- imports traçables ;
- 16 200 avis disponibles ;
- API des avis filtrable et paginée.

### Itération 2 — Prototype IA

Objectifs :

- définir les catégories ;
- constituer un jeu annoté ;
- évaluer une première solution zero-shot ;
- établir une baseline.

Résultats :

- cinq familles de plaintes ;
- 120 textes annotés ;
- résultats d’expériences enregistrés.

### Itération 3 — Modèle supervisé et API

Objectifs :

- comparer la régression logistique et le SVM ;
- retenir une solution ;
- définir un seuil de vérification ;
- exposer le modèle par REST.

Résultats :

- SVM retenu ;
- métadonnées versionnées ;
- routes `/health` et `/predict` ;
- connecteur Laravel opérationnel.

### Itération 4 — Intégration et accessibilité

Objectifs :

- intégrer le service dans Vue ;
- afficher la fiabilité ;
- gérer les erreurs ;
- améliorer l’accessibilité.

Résultats :

- analyse visible dans l’application ;
- prédictions incertaines signalées ;
- focus et annonces accessibles ;
- quatre tests d’accessibilité réussis.

### Itération 5 — Tests et monitorage

Objectifs :

- tester les données, le modèle et l’API ;
- collecter les métriques ;
- créer la boucle de feedback ;
- détecter les incidents.

Résultats :

- 39 tests IA réussis ;
- métriques Prometheus ;
- synthèse et alertes ;
- route de feedback.

### Itération 6 — Packaging et livraison

Objectifs :

- créer un package vérifiable ;
- construire une image Docker ;
- automatiser les contrôles ;
- publier une version.

Résultats :

- workflow GitHub Actions ;
- package avec manifeste et empreintes ;
- image publiée sur GHCR ;
- release `ai-v1.0.0`.

### Itération 7 — Application complète

Objectifs restant à réaliser :

- intégrer les tests Laravel et Vue dans une CI globale ;
- construire les images de l’application ;
- automatiser une restitution sur un environnement de test ;
- étendre le monitorage à Laravel ;
- documenter un incident complet.

## 7. Définition de prêt

Un élément peut commencer lorsque :

- son besoin est compréhensible ;
- son utilisateur est identifié ;
- ses dépendances sont disponibles ;
- ses critères d’acceptation sont écrits ;
- les données ou interfaces nécessaires sont connues ;
- le résultat attendu peut être testé.

## 8. Définition de terminé

Une fonctionnalité est considérée comme terminée lorsque :

- le code est écrit ;
- le comportement principal fonctionne ;
- les entrées incorrectes sont gérées ;
- les tests associés sont réussis ;
- aucune régression connue n’est introduite ;
- la documentation nécessaire est mise à jour ;
- les secrets ne sont pas ajoutés au dépôt ;
- le code est versionné et poussé sur GitHub.

## 9. Coordination des composants

La coordination technique repose sur les contrats entre les composants.

| Producteur | Consommateur | Contrat coordonné |
|---|---|---|
| Vue | Laravel | URL `/api`, JSON, statuts HTTP et erreurs |
| Laravel | FastAPI | URL du service, timeout et contrat `/predict` |
| FastAPI | Modèle | Classes, scores, seuil et métadonnées |
| Laravel | SQLite | Migrations, modèles et relations |
| FastAPI | Monitorage | Prédictions, latence, feedback et alertes |
| GitHub Actions | Projet IA | Versions Python, dépendances, tests et Docker |

Une modification d’un contrat doit entraîner une mise à jour des tests et de la documentation correspondante.

## 10. Gestion des dépendances

Les dépendances sont décrites dans :

- `composer.json` et `composer.lock` pour Laravel ;
- `package.json` et `package-lock.json` pour Vue ;
- `requirements-ai-runtime.txt` pour le service IA ;
- `requirements-ai-test.txt` pour les tests ;
- `requirements-spark.txt` pour le traitement Big Data.

Les environnements virtuels, `node_modules`, `vendor`, les secrets et les fichiers générés ne doivent pas être versionnés.

## 11. Gestion de la qualité

Les contrôles de qualité comprennent :

- validation syntaxique ;
- tests PHPUnit ;
- tests Pytest ;
- tests Vitest ;
- contrôles axe-core ;
- vérification des empreintes ;
- contrôle des seuils de performance ;
- construction du frontend ;
- construction et test du conteneur ;
- vérification des différences Git avant commit.

## 12. Gestion des versions

Le projet utilise Git et GitHub.

Les pratiques appliquées sont :

- branche principale `main` ;
- commits décrivant les modifications ;
- dépôts séparés pour le frontend et le backend/IA ;
- tags pour les versions livrables ;
- conservation des runs GitHub Actions ;
- publication d’une release ;
- publication d’une image conteneur.

La version IA `ai-v1.0.0` constitue la première version livrable du service.

## 13. Cérémonies adaptées au projet individuel

### Planification

Au début d’une itération, les objectifs et les critères d’acceptation sont définis.

### Point quotidien individuel

Un point court peut répondre à trois questions :

- qu’est-ce qui a été terminé ?
- quel est le prochain objectif ?
- quel problème bloque l’avancement ?

### Revue

À la fin de l’itération, le résultat est démontré et comparé aux critères d’acceptation.

### Rétrospective

Les difficultés sont recensées afin de modifier la méthode de travail de l’itération suivante.

## 14. Exemple de rétrospective

### Ce qui a bien fonctionné

- développement progressif ;
- tests rapides ;
- séparation entre Laravel et FastAPI ;
- conservation des métriques et des résultats ;
- publication automatique du service IA.

### Difficultés rencontrées

- migrations contenant des colonnes dupliquées ;
- confusion entre plusieurs versions du frontend ;
- démarrage simultané de trois serveurs ;
- installation de Java et Spark ;
- port déjà utilisé ;
- service Laravel temporairement indisponible ;
- risque de versionner une clé Google.

### Améliorations décidées

- exécuter toute la suite de tests après une migration ;
- conserver une seule version officielle de chaque dépôt ;
- utiliser des routes de santé ;
- documenter les commandes de démarrage ;
- supprimer les secrets des fichiers exemples ;
- automatiser davantage de contrôles dans la CI.

## 15. Gestion des risques projet

| Risque | Probabilité | Impact | Mesure prévue |
|---|---|---|---|
| Rupture du contrat API | Moyenne | Élevé | Tests de contrat |
| Régression du modèle | Moyenne | Élevé | Seuils qualité dans Pytest |
| Indisponibilité d’un service | Moyenne | Élevé | Health checks et erreur 503 |
| Secret versionné | Faible | Élevé | `.gitignore` et contrôle avant commit |
| Données personnelles dans les logs | Moyenne | Élevé | Empreinte sans texte brut |
| Frontend non accessible | Moyenne | Moyen | Tests axe et vérification clavier |
| Dépendance à une seule personne | Élevée | Élevé | Documentation reproductible et revue externe |

## 16. Outils de coordination recommandés

Pour rendre la conduite agile visible au jury, le projet peut utiliser :

- GitHub Issues pour les tâches ;
- GitHub Projects pour le tableau Kanban ;
- les pull requests pour les revues ;
- les commentaires GitHub pour les décisions ;
- les releases pour les livraisons ;
- les Actions pour la qualité ;
- les documents `docs/bloc3` pour les décisions et les preuves.

## 17. Tableau Kanban recommandé

Les colonnes proposées sont :

```text
Backlog | À faire | En cours | À vérifier | Terminé
```

Chaque carte doit préciser :

- le besoin ;
- la priorité ;
- les critères d’acceptation ;
- le composant concerné ;
- les tests à exécuter ;
- la preuve attendue.

## 18. Limite concernant le travail collectif

ReviewPro a été développé individuellement. Il serait incorrect d’indiquer qu’une équipe a été coordonnée pendant le développement.

La compétence de coordination est partiellement démontrée par :

- la coordination des composants techniques ;
- la planification des itérations ;
- le backlog ;
- la gestion de la qualité ;
- les versions et les livraisons ;
- la documentation permettant une reprise du projet.

La dimension collaborative doit être complétée par une activité réelle, par exemple :

- une revue du backlog avec un formateur ;
- une revue technique avec une camarade ;
- une pull request relue par une autre personne ;
- une démonstration suivie de remarques ;
- un compte rendu de décision après cet échange.

## 19. Preuve collaborative à compléter

Cette section doit être remplie uniquement après un échange réel.

| Information | Valeur à compléter |
|---|---|
| Personne | À compléter |
| Rôle | À compléter |
| Date | À compléter |
| Mode d’échange | À compléter |
| Élément présenté | À compléter |
| Remarque reçue | À compléter |
| Décision prise | À compléter |
| Preuve conservée | Capture, commentaire ou compte rendu |

Il ne faut pas inventer les informations de cette section.

## 20. Correspondance avec la compétence

Cette organisation démontre :

- la planification d’une réalisation technique ;
- la priorisation d’un backlog ;
- le découpage en itérations ;
- la coordination de plusieurs composants ;
- la définition de règles de qualité ;
- l’intégration de la conduite de projet avec le MLOps ;
- le suivi des risques et des incidents ;
- la conservation des preuves de versionnement et de livraison.

La preuve d’animation d’un travail collectif reste à compléter par un échange réel.

## 21. Conclusion

La réalisation de ReviewPro a été organisée de manière progressive, avec des objectifs, des critères d’acceptation, des tests et des livraisons versionnées.

Cette organisation a permis de coordonner Vue, Laravel, FastAPI, le modèle, les données et la chaîne MLOps. La documentation facilite la reprise du projet par une autre personne.

La principale limite est l’absence d’une équipe pendant la réalisation. Une revue externe réelle devra être organisée et conservée comme preuve afin de renforcer la dimension collaborative attendue par la compétence.
