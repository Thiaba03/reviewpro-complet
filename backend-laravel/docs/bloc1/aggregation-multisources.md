# Agrégation multisource — ReviewPro

## 1. Objectif

ReviewPro regroupe des données provenant de plusieurs méthodes de collecte dans un modèle relationnel commun.

Les sources utilisées sont :

- fichier CSV Datafiniti ;
- avis initiaux Trustpilot ;
- service web Google Places ;
- scraping du site pédagogique Web Scraper Test Sites.

## 2. Schéma commun des avis

Les avis provenant de structures différentes sont enregistrés dans la table reviews.

Les colonnes communes principales sont :

- content pour le texte ;
- source pour la provenance ;
- source_review_id pour identifiant externe ;
- note pour la note normalisée ;
- sentiment pour la classe positive, neutre ou négative ;
- score pour la valeur de 0 à 100 ;
- date_avis pour la date normalisée ;
- language pour la langue ;
- content_hash pour le contrôle du contenu.

Les relations facultatives permettent de conserver plusieurs formes de données :

- product_id pour un avis produit ;
- commerce_id pour un avis de commerce ;
- import_batch_id pour un avis importé par lot ;
- user_id pour un avis saisi dans application.

## 3. Résultat de agrégation des avis

| Source | Total | Avec produit | Avec commerce | Positifs | Neutres | Négatifs | Note moyenne |
|---|---:|---:|---:|---:|---:|---:|---:|
| Datafiniti | 16 196 | 16 196 | 0 | 15 099 | 664 | 433 | 4,56 |
| Trustpilot | 4 | 0 | 4 | 1 | 2 | 1 | 2,75 |
| Total | 16 200 | 16 196 | 4 | 15 100 | 666 | 434 | 4,56 |

Le tableau de bord calcule ses indicateurs à partir de cette table commune et ne contient pas les chiffres en dur.

## 4. Correspondance des formats

| Information commune | Datafiniti | Trustpilot | Google Places |
|---|---|---|---|
| Texte | reviews.text | content | reviews.text.text |
| Note | reviews.rating | note | reviews.rating |
| Date | reviews.date | date_avis | publishTime |
| Identifiant | reviews.id | identifiant local | hash déterministe |
| Produit | id ou ASIN | Non applicable | Non applicable |
| Commerce | Non applicable | commerce_id | google_place_id |

Chaque importeur transforme son format origine vers le modèle commun ReviewPro.

## 5. Règles communes appliquées

Avant le stockage, les traitements appliquent selon la source :

- nettoyage des espaces ;
- contrôle des champs obligatoires ;
- validation des notes ;
- conversion des dates ;
- calcul ou analyse du sentiment ;
- calcul du score ;
- attribution de la source ;
- création de identifiant externe ;
- détection des doublons ;
- anonymisation lorsque identité ne sert pas au traitement.

## 6. Séparation des niveaux de données

Les avis sont conservés dans reviews.

Les relevés de prix et de fiche produit obtenus par scraping sont conservés dans product_snapshots, car ils décrivent un produit à une date précise et non un avis individuel.

Les notes globales Google sont conservées dans commerces avec leur date de synchronisation.

Cette séparation évite de mélanger des données qui ne possèdent pas la même granularité.

## 7. Contrôles après agrégation

Les contrôles réalisés montrent :

- 16 200 avis dans le schéma commun ;
- aucun contenu vide ;
- aucune note hors de intervalle 1 à 5 ;
- aucun sentiment invalide ;
- aucune source manquante ;
- aucun doublon sur la combinaison source et source_review_id.

## 8. Correspondance avec la compétence RNCP

Cette réalisation démontre :

- intégration de données provenant de plusieurs sources ;
- définition de règles de correspondance ;
- homogénéisation des formats ;
- séparation des différentes granularités ;
- conservation de la provenance ;
- contrôles après agrégation ;
- mise à disposition du jeu consolidé par API REST.
