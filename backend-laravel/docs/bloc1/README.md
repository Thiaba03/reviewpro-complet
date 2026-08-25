# Dossier de preuves — Bloc 1 RNCP

## Projet

ReviewPro — Collecte, préparation, stockage et mise à disposition de données avis sur des produits électroniques.

## Correspondance entre compétences et preuves

| Compétence | Réalisation | Preuves |
|---|---|---|
| Automatiser extraction | CSV Datafiniti, Google Places, scraping pédagogique, export SQLite, Spark | collecte-tracabilite.md, service-web.md, big-data-spark.md |
| Développer des requêtes SQL | SQLite, Eloquent, agrégations du dashboard et Spark SQL | sql-api-rest.md, big-data-spark.md |
| Agréger et nettoyer | Filtrage électronique, validation, normalisation, déduplication et schéma commun | nettoyage-homogeneisation.md, aggregation-multisources.md |
| Créer une base RGPD | MCD, MPD, clés étrangères, dictionnaire, anonymisation et traçabilité | mcd-mpd.md, dictionnaire-donnees.md, rgpd.md |
| Développer une API REST | Liste, filtres, pagination, détail, création, suppression et dashboard JSON | sql-api-rest.md, routes/api.php, tests |

## Documents du dossier

- mcd-mpd.md ;
- dictionnaire-donnees.md ;
- collecte-tracabilite.md ;
- nettoyage-homogeneisation.md ;
- sql-api-rest.md ;
- service-web.md ;
- aggregation-multisources.md ;
- big-data-spark.md ;
- rgpd.md.

## Principales preuves chiffrées

- 28 332 lignes Datafiniti lues ;
- 12 088 lignes hors cible ;
- 69 doublons détectés pendant import ;
- 16 200 avis consolidés ;
- 117 produits collectés par scraping lors de exécution complète ;
- quatre commerces synchronisés depuis Google Places ;
- 16 200 lignes analysées et relues avec Apache Spark ;
- aucun contenu vide ;
- aucune note invalide ;
- aucun sentiment invalide ;
- huit tests Laravel et vingt-cinq assertions réussis.

## Scripts et composants principaux

- app/Console/Commands/ImportDatafinitiReviews.php ;
- app/Console/Commands/ScrapeWebScraperLaptops.php ;
- app/Console/Commands/FetchGoogleReviews.php ;
- scripts/analyze_reviews_spark.py ;
- app/Http/Controllers/Api/ReviewController.php ;
- app/Http/Controllers/Api/DashboardController.php ;
- routes/api.php.

## Limites déclarées

- Spark est exécuté localement sur un volume modeste et non sur un cluster de production ;
- la politique de conservation RGPD reste à automatiser ;
- les routes sensibles devront être protégées par authentification ;
- la base juridique et la procédure de gestion des droits devront être formalisées par le responsable du traitement.
