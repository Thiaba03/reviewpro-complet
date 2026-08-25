# Requêtes SQL et API REST — ReviewPro

## 1. Objectif

ReviewPro met les données nettoyées à disposition des autres composants grâce à une API REST Laravel.

Les requêtes sont construites avec Eloquent et le Query Builder Laravel, puis exécutées sur SQLite.

## 2. Requêtes statistiques du tableau de bord

### Nombre total des avis

```sql
SELECT COUNT(*) AS total_reviews FROM reviews;
```

Résultat actuel : 16 200 avis.

### Note moyenne

```sql
SELECT ROUND(AVG(note), 2) AS average_rating
FROM reviews
WHERE note IS NOT NULL;
```

Résultat actuel : 4,56 sur 5.

### Répartition par sentiment

```sql
SELECT sentiment, COUNT(*) AS total
FROM reviews
WHERE sentiment IS NOT NULL
GROUP BY sentiment;
```

| Sentiment | Nombre |
|---|---:|
| Positif | 15 100 |
| Neutre | 666 |
| Négatif | 434 |

## 3. Classement des produits recevant le plus de plaintes

Cette requête joint les avis, les produits et les marques. Elle conserve les avis négatifs, regroupe les résultats par produit et retourne les dix produits les plus concernés.

```sql
SELECT
    products.id AS product_id,
    products.name AS product_name,
    brands.name AS brand_name,
    COUNT(reviews.id) AS negative_reviews
FROM reviews
JOIN products ON reviews.product_id = products.id
LEFT JOIN brands ON products.brand_id = brands.id
WHERE reviews.sentiment = 'negative'
GROUP BY products.id, products.name, brands.name
ORDER BY negative_reviews DESC
LIMIT 10;
```

## 4. Classement des marques

```sql
SELECT
    brands.id AS brand_id,
    brands.name AS brand_name,
    COUNT(reviews.id) AS negative_reviews
FROM reviews
JOIN products ON reviews.product_id = products.id
JOIN brands ON products.brand_id = brands.id
WHERE reviews.sentiment = 'negative'
GROUP BY brands.id, brands.name
ORDER BY negative_reviews DESC;
```

Ces agrégations permettent aux entreprises de repérer les produits et les marques qui concentrent le plus de plaintes.

## 5. Filtres disponibles

Le point API GET /api/reviews accepte les paramètres suivants :

| Paramètre | Règle | Utilité |
|---|---|---|
| per_page | Entier de 1 à 100 | Nombre de résultats par page |
| sentiment | positive, neutral ou negative | Filtrer par sentiment |
| note | Nombre de 1 à 5 | Filtrer par note |
| brand_id | Identifiant de marque existant | Filtrer par marque |
| product_id | Identifiant de produit existant | Filtrer par produit |
| source | Texte de 100 caractères maximum | Filtrer par source |
| search | Texte de 100 caractères maximum | Rechercher dans le contenu |

Les filtres sont appliqués uniquement lorsque le paramètre correspondant est présent.

## 6. Pagination

Par défaut, vingt avis sont retournés par page. La limite maximale est fixée à cent pour éviter une réponse JSON trop volumineuse.

Les paramètres utilisés sont conservés dans les liens de pagination avec withQueryString().

## 7. Routes REST

| Méthode | Route | Fonction |
|---|---|---|
| GET | /api/reviews | Liste filtrée et paginée |
| POST | /api/reviews | Création et analyse de texte |
| GET | /api/reviews/{review} | Consultation de détail |
| DELETE | /api/reviews/{review} | Suppression |
| GET | /api/dashboard | Statistiques agrégées |

Les réponses sont produites au format JSON afin de pouvoir être exploitées par Vue ou par un autre composant technique.

## 8. Exemples de requêtes API

Liste de vingt avis négatifs :

```text
GET /api/reviews?sentiment=negative&per_page=20
```

Filtrage par marque :

```text
GET /api/reviews?brand_id=2&per_page=20
```

Recherche textuelle :

```text
GET /api/reviews?search=charge&per_page=20
```

Consultation du tableau de bord :

```text
GET /api/dashboard
```

## 9. Tests automatisés

Les tests ReviewsApiTest vérifient notamment :

- la pagination des avis ;
- le filtre par sentiment ;
- le filtre par marque ;
- la limitation de per_page à cent.

La suite complète Laravel contient actuellement huit tests réussis et vingt-cinq assertions.

## 10. Limite technique identifiée

Lors de la création manuelle de démonstration, user_id est actuellement fixé à 1.

En production, cette valeur devra être remplacée par le compte authentifié et la route devra être protégée par un mécanisme autorisant uniquement les utilisateurs habilités.

## 11. Correspondance avec la compétence RNCP

Cette réalisation démontre :

- le développement de requêtes SQL de sélection ;
- les jointures entre plusieurs tables ;
- les agrégations avec COUNT, AVG et GROUP BY ;
- les filtres et les tris ;
- la pagination des résultats ;
- la création de routes REST ;
- la validation des paramètres ;
- la production de réponses JSON ;
- la programmation de tests automatisés pour les principaux comportements.
