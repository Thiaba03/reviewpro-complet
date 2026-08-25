# Collecte automatisée et traçabilité — ReviewPro

## 1. Objectif

ReviewPro utilise plusieurs méthodes de collecte afin d’alimenter une base de données consacrée aux avis et aux plaintes portant sur des produits électroniques.

Le processus doit permettre :

- de collecter automatiquement les données ;
- d’identifier leur provenance ;
- de contrôler chaque ligne ;
- d’écarter les données hors cible ;
- de détecter les doublons ;
- de conserver les erreurs d’exécution ;
- de rendre les résultats reproductibles.

## 2. Sources utilisées

| Code | Source | Type | Licence ou cadre d’utilisation |
|---|---|---|---|
| `kaggle_datafiniti_amazon_reviews` | Datafiniti Consumer Reviews of Amazon Products | Fichier de données | CC BY-NC-SA 4.0 |
| `webscraper_test_laptops` | Web Scraper Test Sites — Laptops | Scraping | Site pédagogique de démonstration |
| `trustpilot` | Avis initiaux de démonstration | Données de démonstration | Quatre avis présents avant l’import principal |

Les métadonnées de provenance sont conservées dans la table `data_sources`, notamment :

- le code technique ;
- le nom de la source ;
- le type de collecte ;
- l’URL d’origine ;
- la licence ;
- la date de vérification des conditions ;
- les notes relatives au RGPD.

## 3. Import du fichier Datafiniti

Le fichier principal traité est :

`Datafiniti_Amazon_Consumer_Reviews_of_Amazon_Products_May19.csv`

Le lot principal a produit les résultats suivants :

| Indicateur | Nombre |
|---|---:|
| Lignes lues | 28 332 |
| Avis importés | 16 175 |
| Lignes hors cible ignorées | 12 088 |
| Doublons détectés | 69 |
| Lignes rejetées pour corruption | 0 |

Le contrôle du lot respecte l’équation suivante :

**28 332 lignes lues = 16 175 lignes importées + 12 088 lignes hors cible + 69 doublons**

Les lignes hors cible correspondent principalement à des produits qui ne respectaient pas le périmètre électronique défini pour ReviewPro.

## 4. Résultat final des avis

La base contient 16 200 avis :

| Source | Lot | Nombre d’avis |
|---|---:|---:|
| Datafiniti | 2 | 21 |
| Datafiniti | 3 | 16 175 |
| Trustpilot | Sans lot d’import | 4 |
| **Total** | | **16 200** |

Les 21 premières lignes Datafiniti proviennent d’une première exécution réussie limitée. L’import principal a ensuite ajouté 16 175 avis supplémentaires sans réimporter les doublons déjà présents.

## 5. Collecte par scraping

Une seconde méthode de collecte a été mise en œuvre sur la page pédagogique « Web Scraper Test Sites — Laptops ».

Cette source permet de démontrer une extraction automatisée depuis une page web sans collecter de données personnelles réelles.

### Résultats des exécutions

| Lot | Statut | Lignes lues | Relevés stockés | Produits distincts |
|---:|---|---:|---:|---:|
| 4 | Échec | 1 | 0 | 0 |
| 5 | Terminé | 6 | 6 | 6 |
| 6 | Terminé | 117 | 117 | 117 |

Le lot 5 correspond à une première collecte limitée servant à valider le fonctionnement du script.

Le lot 6 correspond à la collecte complète. Il a créé 117 relevés associés à 117 produits distincts en environ 30 secondes.

Les informations collectées sont enregistrées dans `product_snapshots` :

- produit concerné ;
- prix ;
- devise ;
- note moyenne ;
- nombre d’avis affiché ;
- description ;
- URL de la page source ;
- date et heure de collecte ;
- lot d’import associé.

## 6. Gestion des erreurs

ReviewPro conserve également les tentatives ayant échoué :

| Lot | Source | Résultat |
|---:|---|---|
| 1 | Datafiniti | Échec après la lecture de 8 344 lignes |
| 4 | Scraping pédagogique | Échec après une première tentative |

Ces incidents restent enregistrés avec :

- le statut `failed` ;
- la date de démarrage ;
- la date de fin ;
- le nombre de lignes déjà lues ;
- un emplacement prévu pour le message d’erreur.

Cette stratégie garantit la traçabilité du processus et facilite la recherche de la cause d’un incident.

## 7. Contrôle des doublons

Deux mécanismes contribuent au contrôle des doublons :

- `file_checksum` identifie le fichier traité ;
- `content_hash` représente le contenu normalisé d’un avis.

Lors de l’import principal, 69 doublons ont été détectés. Ils n’ont pas été ajoutés une nouvelle fois dans la base.

Le rapprochement entre les avis et les produits utilise également les identifiants provenant de la source d’origine.

## 8. Reproductibilité

Chaque exécution produit un enregistrement dans `import_batches`.

Cet enregistrement permet de retrouver :

- la source utilisée ;
- le fichier traité ;
- les paramètres d’exécution ;
- le nombre de lignes lues ;
- le nombre de lignes importées ;
- le nombre de doublons ;
- le nombre de lignes ignorées ou rejetées ;
- le statut final ;
- les dates de début et de fin.

Cette organisation permet de relancer une collecte et de comparer son résultat avec les exécutions précédentes.

## 9. Respect des données et des sources

Les données collectées sont limitées aux informations nécessaires au projet :

- informations sur les produits ;
- contenu des avis ;
- notes ;
- dates ;
- informations techniques de provenance.

L’identité réelle des auteurs n’est pas nécessaire à l’analyse des plaintes. Les données peuvent donc être anonymisées avant leur exploitation.

La source Datafiniti possède une licence documentée. Le scraping est effectué sur un site conçu spécifiquement pour les exercices de scraping.

## 10. Correspondance avec les compétences RNCP

Cette réalisation apporte les preuves suivantes :

- automatisation de l’extraction depuis un fichier de données ;
- automatisation de l’extraction depuis une page web ;
- suivi de plusieurs sources ;
- filtrage des lignes hors cible ;
- détection des doublons ;
- homogénéisation avant stockage ;
- journalisation des exécutions et des incidents ;
- conservation de la provenance et des conditions d’utilisation.