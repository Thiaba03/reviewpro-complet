# Traitement Big Data avec Apache Spark — ReviewPro

## 1. Objectif

Le référentiel RNCP demande de démontrer une extraction et des requêtes dans un système Big Data.

ReviewPro utilise Apache Spark et Spark SQL pour traiter une exportation analytique des avis.

Le script est situé dans :

`scripts/analyze_reviews_spark.py`

## 2. Environnement technique

| Composant | Version |
|---|---|
| Architecture du Mac | ARM64 |
| Java | OpenJDK 17.0.20.1 |
| Python | 3.9.6 |
| PySpark | 3.5.7 |
| Py4J | 0.10.9.7 |

Les dépendances sont conservées dans requirements-spark.txt.

Un environnement Python séparé, .venv-spark, évite les conflits avec le service IA.

## 3. Extraction depuis SQLite

Une requête SQL joint reviews, products et brands afin de créer un jeu analytique aplati.

Le fichier produit est :

`storage/app/bigdata/reviews_export.csv`

Il contient :

- 16 200 avis ;
- une ligne de colonnes ;
- environ 5 Mo ;
- aucune identité auteur.

## 4. Chargement dans Spark

Le script crée une SparkSession nommée ReviewProBigDataAnalysis avec le mode local[*].

Le CSV est lu dans un DataFrame avec détection du schéma. Le DataFrame est ensuite enregistré comme vue temporaire reviews pour permettre les requêtes Spark SQL.

## 5. Requêtes Spark SQL

Les requêtes développées calculent :

- la répartition des avis par source ;
- la note moyenne par source ;
- la répartition par sentiment ;
- la note moyenne par sentiment ;
- les dix produits recevant le plus de plaintes ;
- le nombre de contenus vides ;
- le nombre de notes invalides ;
- le nombre de sentiments invalides.

## 6. Résultats obtenus avec Spark SQL

### Répartition par source

| Source | Total | Note moyenne |
|---|---:|---:|
| Datafiniti | 16 196 | 4,56 |
| Trustpilot | 4 | 2,75 |

### Répartition par sentiment

| Sentiment | Total | Note moyenne |
|---|---:|---:|
| Positif | 15 100 | 4,72 |
| Neutre | 666 | 3,00 |
| Négatif | 434 | 1,50 |

### Contrôles de qualité

| Contrôle | Résultat |
|---|---:|
| Lignes analysées | 16 200 |
| Contenus vides | 0 |
| Notes invalides | 0 |
| Sentiments invalides | 0 |

Les résultats Spark correspondent aux résultats obtenus directement avec SQLite et Laravel.

## 7. Stockage Big Data

Le DataFrame complet est enregistré au format Parquet avec compression Snappy.

Le format Parquet est colonnaire. Il convient aux analyses qui consultent seulement certaines colonnes et permet une lecture plus efficace que CSV pour les traitements analytiques.

Les résultats sont enregistrés dans :

`storage/app/bigdata/spark_outputs`

Ce dossier contient :

- reviews_parquet ;
- source_summary ;
- sentiment_summary ;
- top_complaint_products.

Le script relit ensuite le fichier Parquet et vérifie que les 16 200 lignes sont toujours présentes.

## 8. Reproductibilité

La commande suivante relance le traitement :

```bash
source .venv-spark/bin/activate
python scripts/analyze_reviews_spark.py
```

Le journal complet est conservé dans storage/app/bigdata/spark_run.log.

## 9. Limites de la démonstration

Le jeu actuel contient 16 200 avis et reste de taille modeste. Spark est exécuté en mode local sur un seul ordinateur et non sur un cluster distribué.

Cette démonstration ne prétend donc pas représenter une infrastructure Big Data de production.

Elle prouve cependant :

- utilisation du moteur Apache Spark ;
- programmation de requêtes Spark SQL ;
- manipulation de DataFrames ;
- utilisation du format Parquet ;
- possibilité de transférer le même script vers un cluster Spark, Kubernetes ou un service cloud.

## 10. Correspondance avec la compétence RNCP

Cette réalisation apporte une preuve technique de :

- extraction SQL depuis SQLite ;
- chargement dans un moteur Big Data ;
- requêtes avec Spark SQL ;
- agrégations distribuables ;
- contrôles de qualité ;
- stockage au format Parquet ;
- génération automatique de résultats analytiques ;
- documentation honnête des limites et de la faisabilité.
