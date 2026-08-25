# Benchmark des services et modèles d’intelligence artificielle — ReviewPro

## 1. Expression du besoin

ReviewPro doit analyser le texte d’un avis client négatif afin d’identifier sa principale famille de plainte.

La fonctionnalité doit permettre aux entreprises de repérer plus facilement les problèmes les plus fréquemment signalés concernant leurs produits électroniques.

Le service d’intelligence artificielle doit :

- recevoir un avis sous forme de texte ;
- identifier sa famille principale ;
- retourner une catégorie compréhensible par l’application ;
- fournir un score permettant d’évaluer la fiabilité de la prédiction ;
- signaler les prédictions incertaines ;
- fonctionner avec Laravel et Vue à travers une API REST ;
- pouvoir être exécuté localement afin de limiter les coûts et la transmission des données.

## 2. Catégories retenues

Les douze thèmes détaillés ont été regroupés en cinq familles principales :

| Catégorie technique | Libellé fonctionnel |
|---|---|
| `software_ecosystem` | Logiciel, connexion ou compatibilité |
| `device_hardware` | Matériel, batterie, écran ou audio |
| `usability` | Utilisation et configuration |
| `commercial_service` | Prix, garantie, service ou livraison |
| `other_unclear` | Avis imprécis ou hors cible |

Ce regroupement réduit la complexité du problème et produit un résultat plus facile à interpréter dans l’application.

## 3. Jeu de données d’évaluation

Le benchmark repose sur 120 avis négatifs annotés manuellement.

| Famille | Nombre d’avis |
|---|---:|
| `software_ecosystem` | 45 |
| `device_hardware` | 36 |
| `usability` | 17 |
| `commercial_service` | 11 |
| `other_unclear` | 11 |
| **Total** | **120** |

Les données ont été nettoyées avant l’expérimentation :

- aucun texte vide ;
- aucun label vide ;
- 120 textes uniques ;
- correction des erreurs d’encodage ;
- homogénéisation des catégories ;
- suppression des informations inutiles pour l’apprentissage.

## 4. Critères de comparaison

Les solutions ont été comparées selon les critères suivants :

- accuracy ;
- F1 macro ;
- F1 pondéré ;
- capacité à reconnaître les différentes familles ;
- temps d’exécution ;
- consommation de ressources ;
- coût d’utilisation ;
- fonctionnement hors ligne ;
- simplicité d’intégration ;
- explicabilité ;
- capacité à être déployée dans une API REST.

L’accuracy mesure la proportion totale de prédictions correctes. Le F1 macro accorde la même importance à chaque catégorie. Le F1 pondéré prend en compte la taille de chaque catégorie.

## 5. Solutions étudiées

### 5.1 Modèle zero-shot multilingue

Le modèle `MoritzLaurer/mDeBERTa-v3-base-mnli-xnli` a été évalué avec la bibliothèque Transformers de Hugging Face.

L’approche zero-shot permet de classifier un texte à partir de descriptions de catégories, sans entraînement spécifique sur les données du projet.

Deux versions ont été testées :

- une classification détaillée avec douze catégories ;
- une classification simplifiée avec cinq familles principales.

### 5.2 Régression logistique supervisée

Une solution supervisée a été évaluée avec :

- une vectorisation TF-IDF ;
- des caractéristiques extraites des mots et des groupes de caractères ;
- un classificateur de régression logistique ;
- une validation croisée stratifiée à cinq plis.

### 5.3 SVM linéaire supervisé

Une troisième solution a été développée avec :

- TF-IDF sur les mots ;
- TF-IDF sur les caractères ;
- `LinearSVC` de scikit-learn ;
- validation croisée stratifiée à cinq plis ;
- entraînement final sur les 120 avis.

## 6. Résultats du benchmark

| Expérience | Tâche | Accuracy | F1 macro | F1 pondéré |
|---|---|---:|---:|---:|
| Classe majoritaire | Macro | 37,50 % | Non calculé | Non calculé |
| Zero-shot détaillé V1 | 12 catégories | 32,50 % | 0,27 | 0,32 |
| Zero-shot détaillé V2 | 12 catégories | 23,33 % | 0,21 | 0,22 |
| Zero-shot macro | 5 familles | 41,67 % | 0,33 | 0,36 |
| TF-IDF + régression logistique | 5 familles | 50,83 % | 0,28 | 0,43 |
| TF-IDF + SVM linéaire | 5 familles | 52,50 % | 0,33 | 0,47 |

## 7. Analyse des résultats

### 7.1 Zero-shot détaillé

La première version atteint une accuracy de 32,50 %. Elle reconnaît certaines plaintes liées à la batterie, au matériel et à l’audio, mais produit trop souvent les catégories `other` et `screen_display`.

La deuxième version, utilisant des descriptions plus longues, diminue l’accuracy à 23,33 %. Des descriptions plus détaillées ne produisent donc pas automatiquement de meilleures prédictions.

### 7.2 Zero-shot macro

Le regroupement en cinq familles améliore l’accuracy à 41,67 %. Cette solution est intéressante sans données annotées, mais nécessite un modèle volumineux et reste moins performante que les modèles supervisés.

### 7.3 Régression logistique

La régression logistique atteint une accuracy de 50,83 %. Elle dépasse le modèle zero-shot, mais ne reconnaît pas correctement les familles `commercial_service` et `other_unclear`.

### 7.4 SVM linéaire

Le SVM linéaire obtient la meilleure accuracy avec 52,50 %. Il atteint aussi :

- un F1 macro de 0,33 ;
- un F1 pondéré de 0,47 ;
- un rappel de 0,69 pour `device_hardware` ;
- un rappel de 0,76 pour `software_ecosystem`.

La catégorie `other_unclear` reste difficile à reconnaître en raison du faible nombre d’exemples et du caractère imprécis de ces avis.

## 8. Comparaison fonctionnelle

| Critère | Zero-shot mDeBERTa | Régression logistique | SVM linéaire |
|---|---|---|---|
| Entraînement spécifique | Non | Oui | Oui |
| Meilleure accuracy | 41,67 % | 50,83 % | 52,50 % |
| Fonctionnement hors ligne | Oui, après téléchargement | Oui | Oui |
| Taille approximative | 558 Mo | Faible | 896 Ko |
| Temps de prédiction | Élevé | Faible | Faible |
| Consommation mémoire | Élevée | Faible | Faible |
| Coût d’API externe | Aucun en local | Aucun | Aucun |
| Explicabilité | Faible | Moyenne | Moyenne |
| Intégration FastAPI | Possible | Simple | Simple |
| Adaptation au volume actuel | Partielle | Bonne | Bonne |

## 9. Solution préconisée

Le modèle retenu est le SVM linéaire utilisant une vectorisation TF-IDF sur les mots et les caractères.

Cette solution est recommandée parce qu’elle :

- obtient la meilleure accuracy du benchmark ;
- dépasse la baseline de la classe majoritaire ;
- est rapide à entraîner et à interroger ;
- fonctionne sans service externe payant ;
- peut être exécutée localement ;
- limite la transmission des avis à un prestataire externe ;
- produit un fichier léger ;
- peut être intégrée simplement dans FastAPI ;
- permet de retourner un score et une marge de décision.

Le modèle est enregistré dans `storage/app/ai/models/review_topic_macro_svm.joblib` et ses métadonnées dans `storage/app/ai/models/review_topic_macro_svm.metadata.json`.

## 10. Politique de décision

La différence entre les deux meilleurs scores constitue la marge de décision. Le seuil automatique retenu est `0,30`.

Lorsque la marge est inférieure à ce seuil, la prédiction nécessite une vérification humaine.

| Indicateur | Résultat estimé |
|---|---:|
| Prédictions automatiques | 62 |
| Couverture automatique | 51,7 % |
| Accuracy au-dessus du seuil | 71,0 % |
| Avis à vérifier manuellement | 58 |

Cette politique évite de présenter toutes les prédictions comme certaines.

## 11. Limites

- seulement 120 avis annotés ;
- déséquilibre entre les catégories ;
- accuracy globale encore limitée ;
- catégorie `other_unclear` non reconnue pendant la validation ;
- estimation du seuil sur un petit échantillon ;
- absence actuelle d’un jeu de test externe indépendant.

Le modèle ne doit pas remplacer une décision humaine lorsque la marge est faible.

## 12. Recommandations d’amélioration

1. Annoter davantage d’avis.
2. Atteindre au moins 100 exemples par famille.
3. Équilibrer les catégories.
4. Organiser une validation par une deuxième personne.
5. Conserver les corrections humaines.
6. Réentraîner périodiquement le modèle.
7. Mesurer les performances sur un jeu de test indépendant.
8. Surveiller la dérive des données.
9. Versionner le dataset, le modèle et les métriques.
10. Réévaluer régulièrement le seuil de décision.

## 13. Conclusion

Le benchmark montre que la solution la plus complexe n’est pas nécessairement la plus adaptée au projet.

Le modèle zero-shot est utile en l’absence de données annotées, mais le SVM linéaire est plus performant, plus léger et plus simple à intégrer dans ReviewPro.

Le SVM linéaire est donc retenu comme première version du service d’intelligence artificielle. Son utilisation est accompagnée d’un seuil de décision et d’une vérification humaine pour les prédictions incertaines.
