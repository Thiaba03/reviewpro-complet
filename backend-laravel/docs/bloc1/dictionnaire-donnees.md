# Dictionnaire des données — ReviewPro

## 1. Objectif

Ce document décrit les données stockées par ReviewPro, leur rôle, leur format et leurs contraintes.

La base SQLite sépare :

- la provenance des données ;
- les opérations d’import ;
- le catalogue des marques et produits ;
- les avis clients ;
- les relevés historiques des produits.

## 2. Table `data_sources`

Cette table décrit les sources utilisées pour collecter les données.

| Champ | Type | Obligatoire | Description |
|---|---|---:|---|
| `id` | INTEGER | Oui | Identifiant unique de la source |
| `code` | VARCHAR | Oui | Code technique stable et unique |
| `name` | VARCHAR | Oui | Nom lisible de la source |
| `source_type` | VARCHAR | Oui | Type : fichier, API, scraping ou saisie manuelle |
| `source_url` | TEXT | Non | Adresse d’origine de la source |
| `license_name` | VARCHAR | Non | Nom de la licence d’utilisation |
| `license_url` | TEXT | Non | Adresse des conditions ou de la licence |
| `terms_checked_at` | DATETIME | Non | Date de vérification des conditions |
| `rgpd_notes` | TEXT | Non | Informations relatives au RGPD |
| `is_active` | BOOLEAN | Oui | Indique si la source est active |
| `created_at` | DATETIME | Non | Date de création |
| `updated_at` | DATETIME | Non | Date de dernière modification |

### Contraintes importantes

- `id` est la clé primaire.
- `code` doit être unique.
- Une source peut être associée à plusieurs lots d’import.

## 3. Table `import_batches`

Cette table assure la traçabilité de chaque opération d’import.

| Champ | Type | Obligatoire | Description |
|---|---|---:|---|
| `id` | INTEGER | Oui | Identifiant unique du lot |
| `data_source_id` | INTEGER | Oui | Source utilisée pour l’import |
| `original_filename` | VARCHAR | Non | Nom du fichier importé |
| `file_checksum` | VARCHAR | Non | Empreinte du fichier pour contrôler son intégrité |
| `status` | VARCHAR | Oui | État de l’import : pending, running, completed ou failed |
| `rows_read` | INTEGER | Oui | Nombre de lignes lues |
| `rows_imported` | INTEGER | Oui | Nombre de lignes importées |
| `rows_rejected` | INTEGER | Oui | Nombre de lignes rejetées |
| `rows_duplicated` | INTEGER | Oui | Nombre de doublons détectés |
| `rows_skipped` | INTEGER | Oui | Nombre de lignes ignorées |
| `parameters` | TEXT | Non | Paramètres utilisés pendant l’import |
| `error_message` | TEXT | Non | Message produit en cas d’erreur |
| `started_at` | DATETIME | Non | Date et heure de début |
| `finished_at` | DATETIME | Non | Date et heure de fin |
| `created_at` | DATETIME | Non | Date de création |
| `updated_at` | DATETIME | Non | Date de dernière modification |

### Contraintes importantes

- `id` est la clé primaire.
- `data_source_id` référence `data_sources.id`.
- La suppression d’une source utilisée est interdite par la contrainte `RESTRICT`.

## 4. Table `brands`

Cette table contient les marques des produits électroniques analysés.

| Champ | Type | Obligatoire | Description |
|---|---|---:|---|
| `id` | INTEGER | Oui | Identifiant unique de la marque |
| `name` | VARCHAR | Oui | Nom affiché de la marque |
| `slug` | VARCHAR | Oui | Nom technique utilisé dans les URL et les traitements |
| `country` | VARCHAR | Non | Pays d’origine de la marque |
| `website_url` | TEXT | Non | Site officiel de la marque |
| `created_at` | DATETIME | Non | Date de création |
| `updated_at` | DATETIME | Non | Date de dernière modification |

### Contraintes importantes

- `id` est la clé primaire.
- `name` doit être unique.
- `slug` doit être unique.
- Une marque peut posséder plusieurs produits.

## 5. Table `products`

Cette table représente le catalogue des produits électroniques.

| Champ | Type | Obligatoire | Description |
|---|---|---:|---|
| `id` | INTEGER | Oui | Identifiant unique du produit |
| `brand_id` | INTEGER | Non | Marque associée au produit |
| `source` | VARCHAR | Oui | Source dans laquelle le produit a été identifié |
| `source_product_id` | VARCHAR | Oui | Identifiant du produit dans la source d’origine |
| `name` | VARCHAR | Non | Nom commercial du produit |
| `category` | VARCHAR | Non | Catégorie principale |
| `subcategory` | VARCHAR | Non | Sous-catégorie du produit |
| `product_url` | TEXT | Non | Adresse de la fiche produit |
| `image_url` | TEXT | Non | Adresse de l’image du produit |
| `created_at` | DATETIME | Non | Date de création |
| `updated_at` | DATETIME | Non | Date de dernière modification |

### Contraintes importantes

- `id` est la clé primaire.
- `brand_id` référence `brands.id`.
- La combinaison de la source et de l’identifiant d’origine permet d’identifier un produit.
- Une marque peut posséder plusieurs produits.
- Un produit peut recevoir plusieurs avis et plusieurs relevés historiques.

## 6. Table `reviews`

Cette table centrale contient les avis clients collectés ou saisis dans l’application.

| Champ | Type | Obligatoire | Description |
|---|---|---:|---|
| `id` | INTEGER | Oui | Identifiant unique de l’avis |
| `user_id` | INTEGER | Non | Utilisateur ayant saisi l’avis |
| `product_id` | INTEGER | Non | Produit concerné par l’avis |
| `commerce_id` | INTEGER | Non | Commerce éventuellement concerné |
| `import_batch_id` | INTEGER | Non | Lot ayant importé l’avis |
| `content` | TEXT | Oui | Texte de l’avis |
| `sentiment` | VARCHAR | Non | Sentiment calculé : positive, neutral ou negative |
| `score` | INTEGER | Non | Score d’analyse de l’avis |
| `topics` | TEXT | Non | Thèmes détectés dans l’avis |
| `source` | VARCHAR | Oui | Origine de l’avis |
| `source_review_id` | VARCHAR | Non | Identifiant de l’avis dans sa source |
| `auteur` | VARCHAR | Non | Nom ou pseudonyme éventuellement fourni |
| `note` | FLOAT | Non | Note donnée au produit |
| `date_avis` | DATETIME | Non | Date de publication de l’avis |
| `language` | VARCHAR | Non | Langue détectée ou fournie |
| `content_hash` | VARCHAR | Non | Empreinte du texte utilisée pour détecter les doublons |
| `is_anonymized` | BOOLEAN | Oui | Indique si l’avis a été anonymisé |
| `created_at` | DATETIME | Non | Date de création dans ReviewPro |
| `updated_at` | DATETIME | Non | Date de dernière modification |

### Contraintes importantes

- `id` est la clé primaire.
- `product_id` référence `products.id`.
- `commerce_id` référence `commerces.id`.
- `user_id` référence `users.id`.
- `import_batch_id` référence `import_batches.id`.
- `content` est obligatoire.
- `content_hash` contribue à la détection des doublons.
- Les relations avec un produit, un commerce ou un utilisateur peuvent être facultatives selon la source.

### Protection des données

L’analyse statistique ne nécessite pas de connaître l’identité réelle de l’auteur. Les champs `auteur` et `user_id` ne doivent donc être conservés que lorsqu’ils sont nécessaires et justifiés.

Le champ `is_anonymized` permet de tracer l’application d’un traitement d’anonymisation.

## 7. Table `product_snapshots`

Cette table conserve les relevés successifs observés sur les fiches produits. Elle permet de suivre l’évolution d’un produit sans remplacer les anciennes valeurs.

| Champ | Type | Obligatoire | Description |
|---|---|---:|---|
| `id` | INTEGER | Oui | Identifiant unique du relevé |
| `product_id` | INTEGER | Oui | Produit concerné |
| `import_batch_id` | INTEGER | Oui | Lot de collecte ayant créé le relevé |
| `price` | NUMERIC | Non | Prix observé |
| `currency` | VARCHAR | Oui | Devise du prix, USD par défaut |
| `average_rating` | FLOAT | Non | Note moyenne affichée |
| `displayed_review_count` | INTEGER | Non | Nombre d’avis affiché sur la source |
| `description` | TEXT | Non | Description observée |
| `source_url` | TEXT | Oui | Adresse de la page collectée |
| `collected_at` | DATETIME | Oui | Date et heure de la collecte |
| `created_at` | DATETIME | Non | Date de création |
| `updated_at` | DATETIME | Non | Date de dernière modification |

### Contraintes importantes

- `id` est la clé primaire.
- `product_id` référence `products.id`.
- `import_batch_id` référence `import_batches.id`.
- `source_url` et `collected_at` sont obligatoires.
- La suppression d’un produit entraîne la suppression de ses relevés.

## 8. Table `commerces`

Cette table contient les commerces utilisés par les premiers avis de démonstration et par certaines sources externes.

| Champ | Type | Obligatoire | Description |
|---|---|---:|---|
| `id` | INTEGER | Oui | Identifiant unique du commerce |
| `nom` | VARCHAR | Oui | Nom du commerce |
| `categorie` | VARCHAR | Oui | Catégorie du commerce |
| `ville` | VARCHAR | Non | Ville du commerce |
| `google_place_id` | VARCHAR | Non | Identifiant Google Places |
| `trustpilot_slug` | VARCHAR | Non | Identifiant utilisé sur Trustpilot |
| `google_rating` | FLOAT | Non | Note moyenne Google |
| `google_rating_count` | INTEGER | Non | Nombre de notes Google |
| `google_synced_at` | DATETIME | Non | Date de dernière synchronisation Google |
| `created_at` | DATETIME | Non | Date de création |
| `updated_at` | DATETIME | Non | Date de dernière modification |

### Contraintes importantes

- `id` est la clé primaire.
- Un commerce peut être associé à plusieurs avis.
- Si un commerce est supprimé, ses avis sont conservés avec un `commerce_id` nul.

## 9. Tables techniques Laravel

Les tables suivantes sont utilisées par le framework et ne font pas partie directement du modèle métier de ReviewPro :

- `cache` et `cache_locks` ;
- `jobs`, `job_batches` et `failed_jobs` ;
- `sessions` ;
- `migrations` ;
- `password_reset_tokens`.

La table `users` gère les comptes de l’application. Elle peut être reliée aux avis saisis manuellement, mais les avis importés ne nécessitent pas obligatoirement de compte utilisateur.

## 10. Synthèse

Le dictionnaire démontre que chaque donnée possède :

- une définition précise ;
- un type adapté ;
- une règle indiquant si elle est obligatoire ;
- des contraintes d’intégrité ;
- un rôle dans la collecte, la traçabilité ou l’analyse ;
- une justification métier ou réglementaire.