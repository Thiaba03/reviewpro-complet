# Extraction depuis un service web — Google Places

## 1. Objectif

ReviewPro contient une commande Laravel qui interroge le service web officiel Google Places afin de récupérer les informations publiques de commerces.

Fichier principal :

`app/Console/Commands/FetchGoogleReviews.php`

Commande Artisan :

```bash
php artisan reviews:fetch-google
```

## 2. Appel du service web

La commande utilise le client HTTP Laravel pour envoyer une requête GET vers API Google Places.

```text
https://places.googleapis.com/v1/places/{google_place_id}
```

La requête transmet une clé dans le header X-Goog-Api-Key et utilise X-Goog-FieldMask pour limiter les champs demandés.

Champs demandés :

- identifiant du lieu ;
- nom affiché ;
- note moyenne ;
- nombre total de notes ;
- avis disponibles.

## 3. Données réellement synchronisées

| Commerce | Note Google | Nombre de notes | Dernière synchronisation |
|---|---:|---:|---|
| Darty — exemple | 3,6 | 4 513 | 6 août 2026 |
| Boulanger — exemple | 4,3 | 1 707 | 6 août 2026 |
| Fnac — exemple | 3,9 | 19 810 | 6 août 2026 |
| Electrodepot — exemple | 4,0 | 3 767 | 6 août 2026 |

Ces valeurs sont conservées dans google_rating, google_rating_count et google_synced_at.

## 4. Traitement de la réponse JSON

Après une réponse réussie, la commande :

- lit les données JSON ;
- met à jour la note moyenne du commerce ;
- met à jour le nombre de notes ;
- mémorise la date de synchronisation ;
- parcourt les avis disponibles ;
- analyse leur sentiment ;
- insère uniquement les avis nouveaux.

Une erreur HTTP est détectée avec response->failed(). Le statut et le message sont alors affichés sans interrompre la collecte des autres commerces.

## 5. Déduplication

Le texte est nettoyé avec Str::squish puis transformé en empreinte SHA-256.

Un identifiant déterministe est calculé à partir :

- de identifiant Google du commerce ;
- du hash du contenu ;
- de la date de publication.

Avant chaque insertion, la commande recherche un avis ayant la même source et le même identifiant.

## 6. Protection des données

Le script a été corrigé afin de ne plus conserver le nom public de auteur.

Les nouveaux avis Google utilisent :

- auteur égal à null ;
- is_anonymized égal à true ;
- content_hash pour le contrôle du contenu ;
- le code de langue fourni par API.

Cette correction applique le principe de minimisation des données.

La clé Google ne doit jamais être placée dans .env.example ni affichée dans un terminal partagé. Elle doit uniquement être définie localement dans .env et protégée par les restrictions Google Cloud.

## 7. Limites connues

Google Places ne fournit pas tout historique des avis. Le service renvoie seulement une sélection limitée des avis jugés pertinents.

La disponibilité du détail des avis peut également dépendre du niveau de facturation et des champs autorisés.

Pour cette raison, Google Places est une source complémentaire. Le jeu principal des avis produits provient de Datafiniti.

## 8. Validation technique

Après la correction RGPD, la syntaxe PHP a été vérifiée et toute la suite de tests Laravel reste valide :

- 8 tests réussis ;
- 25 assertions ;
- aucune régression détectée.

## 9. Correspondance avec la compétence RNCP

Cette réalisation démontre :

- appel automatisé à un service web REST ;
- utilisation de headers authentification et de sélection de champs ;
- traitement de réponses JSON ;
- gestion des erreurs HTTP ;
- mise à jour de la base de données ;
- détection des doublons ;
- anonymisation des données ;
- documentation des limites du fournisseur.
