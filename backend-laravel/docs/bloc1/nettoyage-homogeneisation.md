# Nettoyage et homogénéisation — ReviewPro

## 1. Objectif

Avant leur stockage, les données sont contrôlées, nettoyées et homogénéisées afin de produire un jeu de données cohérent pour l’API et le tableau de bord.

Le traitement principal est programmé dans :

`app/Console/Commands/ImportDatafinitiReviews.php`

## 2. Contrôle de la structure CSV

Pour chaque ligne, le programme vérifie que le nombre de valeurs correspond au nombre de colonnes de l’en-tête.

Une ligne dont la structure est incorrecte est rejetée et le compteur `rows_rejected` est augmenté.

Le programme vérifie également que la création du tableau associatif avec `array_combine` a réussi.

## 3. Filtrage du périmètre

ReviewPro analyse uniquement les produits électroniques.

La catégorie est nettoyée, convertie en minuscules puis contrôlée. Une ligne ne contenant pas le terme `electronics` est enregistrée comme hors cible dans `rows_skipped`.

Pendant l’import principal :

| Indicateur | Nombre |
|---|---:|
| Lignes lues | 28 332 |
| Avis importés | 16 175 |
| Lignes hors cible | 12 088 |
| Doublons détectés | 69 |
| Lignes rejetées | 0 |

## 4. Validation des champs obligatoires

Une ligne est rejetée lorsqu’une donnée indispensable est absente :

- nom de la marque ;
- identifiant externe du produit ;
- contenu de l’avis ;
- note de l’avis.

La note doit être numérique et comprise entre 1 et 5.

## 5. Nettoyage des textes

Les champs simples sont nettoyés avec `trim`, qui retire les espaces placés au début et à la fin.

Le contenu de l’avis est traité avec `Str::squish`, qui :

- supprime les espaces inutiles ;
- remplace les successions d’espaces par un espace unique ;
- homogénéise les retours à la ligne.

## 6. Normalisation des marques

Les différentes variations de casse sont regroupées sous une valeur commune :

```php
$normalizedBrand = match (strtolower($brandName)) {
    'amazonbasics' => 'AmazonBasics',
    'amazon' => 'Amazon',
    default => Str::title($brandName),
};
```

Après normalisation :

| Marque | Produits | Avis |
|---|---:|---:|
| Amazon | 47 | 16 113 |
| AmazonBasics | 9 | 83 |

Cette règle évite de créer plusieurs marques pour `AmazonBasics`, `amazonbasics` ou d’autres variations d’écriture.

## 7. Détection des doublons

Le contenu nettoyé est converti en minuscules avant le calcul d’une empreinte SHA-256 :

```php
$contentHash = hash(
    'sha256',
    mb_strtolower($content, 'UTF-8')
);
```

L’identifiant de l’avis fourni par Datafiniti est conservé dans `source_review_id`.

Lorsque cet identifiant est absent, le programme crée un identifiant déterministe à partir :

- de l’identifiant du produit ;
- de l’empreinte du contenu ;
- de la date de l’avis.

Avant l’insertion, le programme recherche une ligne possédant la même combinaison :

- `source = kaggle_datafiniti` ;
- même `source_review_id`.

Si elle existe, la ligne n’est pas réimportée et `rows_duplicated` est augmenté.

### Contrôle réalisé dans la base

| Contrôle | Résultat |
|---|---:|
| Groupes dupliqués sur `source + source_review_id` | 0 |
| Groupes ayant le même produit et le même contenu | 2 |
| Lignes supplémentaires dans ces groupes | 2 |

Les textes identiques ne sont pas automatiquement supprimés lorsqu’ils possèdent des identifiants d’origine différents. Cela évite de supprimer deux avis distincts seulement parce que leurs auteurs ont utilisé le même texte.

## 8. Homogénéisation des dates

La date d’un avis est convertie avec `Carbon::parse`.

Si la conversion échoue, la valeur devient nulle plutôt que de provoquer l’arrêt complet de l’import.

Les dates valides sont stockées dans un format homogène dans `date_avis`.

## 9. Transformation de la note

La note est transformée en sentiment selon une règle métier explicite :

| Note | Sentiment |
|---:|---|
| 1 ou 2 | `negative` |
| 3 | `neutral` |
| 4 ou 5 | `positive` |

Un score compris entre 0 et 100 est également calculé :

**score = (note − 1) × 25**

| Note | Score |
|---:|---:|
| 1 | 0 |
| 2 | 25 |
| 3 | 50 |
| 4 | 75 |
| 5 | 100 |

## 10. Homogénéisation de la langue et de la source

Les avis Datafiniti sont enregistrés avec :

- `language = en` ;
- `source = kaggle_datafiniti`.

Ces valeurs communes facilitent les filtres et les agrégations SQL.

## 11. Anonymisation

Le nom de l’utilisateur présent dans le CSV n’est pas importé :

```php
'auteur' => null,
'is_anonymized' => true,
```

L’identité de l’auteur n’est pas nécessaire pour déterminer les plaintes les plus fréquentes.

Cette règle applique les principes de minimisation et de protection des données personnelles.

## 12. Contrôles de qualité après import

| Contrôle | Résultat |
|---|---:|
| Nombre total d’avis | 16 200 |
| Contenus vides | 0 |
| Notes hors de l’intervalle 1 à 5 | 0 |
| Sentiments invalides | 0 |
| Avis sans source | 0 |
| Avis sans produit | 4 |
| Avis Datafiniti possédant un hash | 16 196 |

Les quatre avis sans produit sont les avis Trustpilot de démonstration présents avant l’import du catalogue Datafiniti.

## 13. Correspondance avec la compétence RNCP

Le processus démontre la programmation de règles :

- de contrôle de structure ;
- de suppression logique des lignes hors cible ;
- de rejet des entrées invalides ;
- de nettoyage des textes ;
- d’homogénéisation des marques ;
- d’homogénéisation des dates et des langues ;
- de transformation des notes ;
- de détection des doublons ;
- d’anonymisation ;
- de suivi statistique des traitements.