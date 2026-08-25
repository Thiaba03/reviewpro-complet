# Modélisation des données — ReviewPro

## 1. Objectif

ReviewPro collecte, nettoie, stocke et expose des avis portant sur des produits électroniques.

La base de données a été conçue afin de :

- conserver l’origine de chaque donnée ;
- tracer chaque opération d’import ;
- éviter les doublons ;
- relier les avis aux marques et aux produits ;
- conserver les relevés historiques des produits ;
- respecter les principes de minimisation et d’anonymisation des données.

## 2. Modèle conceptuel de données — MCD

Le modèle conceptuel identifie les principales entités métier et leurs relations.

```mermaid
erDiagram
    DATA_SOURCE ||--o{ IMPORT_BATCH : produit
    IMPORT_BATCH ||--o{ REVIEW : importe
    IMPORT_BATCH ||--o{ PRODUCT_SNAPSHOT : collecte

    BRAND ||--o{ PRODUCT : possede
    PRODUCT ||--o{ REVIEW : recoit
    PRODUCT ||--o{ PRODUCT_SNAPSHOT : possede

    COMMERCE ||--o{ REVIEW : recoit
    USER ||--o{ REVIEW : redige

    ```

### Règles de gestion

1. Une source de données peut produire plusieurs lots d’import.
2. Chaque lot d’import appartient à une seule source.
3. Une marque peut posséder plusieurs produits.
4. Un produit peut recevoir plusieurs avis.
5. Un produit peut posséder plusieurs relevés réalisés à différentes dates.
6. Un lot d’import peut contenir plusieurs avis et plusieurs relevés de produits.
7. Un avis peut être associé à un produit, un commerce ou un utilisateur.
8. Certains liens sont facultatifs selon l’origine de l’avis.

Par exemple, un avis provenant du dataset Datafiniti peut être associé à un produit sans être associé à un utilisateur enregistré dans ReviewPro.

## 3. Modèle physique de données — MPD

| Table enfant | Clé étrangère | Table parent | Suppression |
|---|---|---|---|
| `import_batches` | `data_source_id` | `data_sources.id` | `RESTRICT` |
| `products` | `brand_id` | `brands.id` | `RESTRICT` |
| `reviews` | `product_id` | `products.id` | `RESTRICT` |
| `reviews` | `commerce_id` | `commerces.id` | `SET NULL` |
| `reviews` | `user_id` | `users.id` | `CASCADE` |
| `reviews` | `import_batch_id` | `import_batches.id` | `RESTRICT` |
| `product_snapshots` | `product_id` | `products.id` | `CASCADE` |
| `product_snapshots` | `import_batch_id` | `import_batches.id` | `RESTRICT` |

## 4. Signification des contraintes

### RESTRICT

La suppression est refusée lorsqu’un enregistrement est encore utilisé. Cette règle protège les sources, les lots d’import, les marques et les produits liés aux avis.

### SET NULL

Si un commerce est supprimé, l’avis est conservé, mais son champ `commerce_id` devient nul.

### CASCADE

Les enregistrements dépendants sont supprimés avec leur parent. Par exemple, les relevés historiques d’un produit sont supprimés avec celui-ci.

## 5. Traçabilité des données

La traçabilité repose sur :

- `data_sources`, qui décrit l’origine, le type et la licence de chaque source ;
- `import_batches`, qui enregistre le statut et les statistiques de chaque import ;
- `reviews.import_batch_id`, qui rattache chaque avis à son import ;
- `product_snapshots.import_batch_id`, qui rattache chaque relevé à son import ;
- `file_checksum`, qui identifie le fichier importé ;
- `content_hash`, qui contribue à détecter les avis dupliqués.

## 6. Prise en compte du RGPD

La conception applique plusieurs principes liés au RGPD :

- minimisation des données collectées ;
- possibilité d’anonymiser les avis avec `is_anonymized` ;
- documentation de la source et de sa licence ;
- traçabilité des imports ;
- absence d’identité réelle obligatoire pour les avis importés ;
- séparation des utilisateurs et du contenu des avis.

Le projet analyse principalement des avis sur des produits. L’identité réelle de l’auteur n’est pas nécessaire pour calculer les statistiques du tableau de bord.

## 7. Correspondance avec la compétence RNCP

Cette modélisation démontre :

- l’élaboration d’un modèle conceptuel ;
- sa transformation en modèle physique relationnel ;
- la définition des clés primaires et étrangères ;
- la programmation de la structure avec les migrations Laravel ;
- la mise en place de règles d’intégrité référentielle ;
- la prise en compte de la traçabilité et de la protection des données.
