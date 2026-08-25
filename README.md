# ReviewPro — Analyse intelligente des avis produits

ReviewPro est une application web permettant de centraliser, consulter et analyser des avis clients portant sur des produits électroniques.

L’application aide une entreprise à identifier les produits qui reçoivent le plus de plaintes et à classer automatiquement la plainte principale exprimée dans un avis.

Ce projet a été réalisé dans le cadre de la certification RNCP37827.

---

## 1. Objectifs du projet

ReviewPro répond à plusieurs besoins :

- centraliser des avis provenant de plusieurs sources ;
- conserver la traçabilité des données collectées ;
- consulter et filtrer les avis clients ;
- afficher des indicateurs sur la satisfaction ;
- identifier les produits qui reçoivent le plus de plaintes ;
- classifier un avis avec un modèle d’intelligence artificielle ;
- signaler les prédictions incertaines ;
- permettre une vérification humaine ;
- tester automatiquement les composants ;
- surveiller la santé de l’application et du modèle ;
- automatiser le packaging et la livraison des versions.

Le modèle ne remplace pas la décision humaine. Il fournit une première classification et demande une vérification lorsque sa marge de décision est insuffisante.

---

## 2. Fonctionnalités principales

### Tableau de bord

Le tableau de bord présente :

- le nombre total d’avis ;
- la note moyenne ;
- la répartition des avis positifs, neutres et négatifs ;
- les produits qui concentrent le plus de plaintes ;
- une représentation visuelle de la distribution des sentiments.

### Consultation des avis

L’utilisateur peut :

- consulter les avis ;
- effectuer une recherche textuelle ;
- filtrer par sentiment ;
- filtrer par note ;
- filtrer par source ;
- parcourir les résultats avec une pagination.

### Analyse par intelligence artificielle

L’utilisateur peut saisir un avis afin d’obtenir :

- la catégorie de plainte détectée ;
- le libellé compréhensible de la catégorie ;
- le score de chaque catégorie ;
- la marge de décision ;
- le classement complet des catégories ;
- une indication sur la nécessité d’une vérification humaine.

### Monitorage

L’application contrôle :

- la disponibilité de Laravel ;
- la connexion à la base SQLite ;
- la disponibilité du service FastAPI ;
- la version du modèle chargé ;
- la latence ;
- les erreurs ;
- les demandes de vérification humaine ;
- les corrections enregistrées.

---

## 3. Architecture

ReviewPro utilise une architecture séparée en trois composants.

```text
Utilisateur
    |
Frontend Vue 3
    |
API Laravel 12
    |
Service FastAPI
    |
Modèle SVM
