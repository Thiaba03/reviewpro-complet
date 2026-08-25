# Protection des données et RGPD — ReviewPro

## 1. Finalité du traitement

ReviewPro analyse des avis afin de déterminer les plaintes les plus fréquentes concernant des produits électroniques.

Les données servent à produire des statistiques, une distribution des sentiments et un classement des produits recevant le plus de plaintes.

Cette finalité ne nécessite pas de connaître identité réelle des auteurs.

## 2. Principes appliqués

- finalité déterminée ;
- minimisation des données ;
- contrôle de exactitude ;
- traçabilité de provenance ;
- anonymisation des auteurs ;
- protection des accès et des secrets.

## 3. Données traitées

| Catégorie | Exemples | Justification |
|---|---|---|
| Avis | Texte, note, date et langue | Analyse des plaintes |
| Résultats | Sentiment, score et thèmes | Statistiques |
| Produit | Marque, nom et catégorie | Classement par produit |
| Provenance | Source, lot et identifiant externe | Traçabilité |
| Identité auteur | Nom ou pseudonyme | Non nécessaire pour les imports |

## 4. Minimisation et anonymisation

Les importeurs Datafiniti et Google ne conservent plus le nom des auteurs.

Les avis importés utilisent auteur égal à null et is_anonymized égal à true.

Le fichier analytique utilisé par Spark exclut également toute colonne auteur.

Le hash du contenu sert au contrôle technique sans remplacer le texte dans les analyses.

## 5. Provenance

La table data_sources conserve :

- le code et le nom de la source ;
- le type de collecte ;
- URL origine ;
- la licence ;
- la date de vérification des conditions ;
- les notes RGPD ;
- le statut actif ou inactif.

Datafiniti est documenté avec la licence CC BY-NC-SA 4.0. Le scraping utilise un site pédagogique prévu pour les exercices.

## 6. Durée de conservation

Le prototype ne possède pas encore de suppression automatique fondée sur une durée de conservation.

Avant une mise en production, le responsable du traitement devra définir et documenter :

- la durée de conservation des avis ;
- la durée des journaux import ;
- la durée des fichiers sources ;
- la procédure de suppression ou anonymisation ;
- les exceptions nécessaires pour les statistiques anonymisées.

Cette limite est identifiée et ne doit pas être présentée comme déjà automatisée.

## 7. Sécurité

Les mesures techniques présentes comprennent :

- séparation des secrets dans le fichier .env ;
- fichier .env.example sans clé réelle ;
- validation des paramètres API ;
- limitation de la pagination à cent avis ;
- clés étrangères et contraintes intégrité ;
- contrôle des fichiers et des doublons ;
- tests automatisés Laravel ;
- sauvegarde du code avant correction.

Une clé Google Places exposée pendant les vérifications a été retirée de .env et de .env.example. Aucune autre copie locale ni historique Git local ne furent détectés.

La clé exposée doit également être révoquée dans Google Cloud. En production, les clés devront être restreintes aux API, domaines ou adresses autorisés.

## 8. Droits des personnes

Les avis importés sont anonymisés et ne nécessitent pas identification de auteur.

Pour les comptes utilisateurs et les avis saisis manuellement, une mise en production devra prévoir :

- information claire des personnes ;
- accès aux données ;
- rectification ;
- suppression ;
- gestion des demandes ;
- authentification et contrôle des autorisations.

La route de suppression existe dans API, mais elle devra être protégée par authentification avant la production.

## 9. Limites et actions restantes

| Élément | État | Action avant production |
|---|---|---|
| Anonymisation des imports | Réalisée | Maintenir les tests |
| Traçabilité des sources | Réalisée | Vérifier périodiquement les licences |
| Durée de conservation | Non automatisée | Définir et programmer la purge |
| Authentification des suppressions | Incomplète | Protéger les routes sensibles |
| Base juridique | À formaliser | Décision du responsable du traitement |
| Gestion des droits | À formaliser | Créer une procédure |

## 10. Correspondance avec la compétence RNCP

Cette conception démontre :

- prise en compte du RGPD dans le modèle ;
- minimisation des données ;
- anonymisation des auteurs ;
- traçabilité et documentation des sources ;
- séparation des secrets ;
- détection et correction de un incident de sécurité ;
- identification transparente des actions restant nécessaires avant production.
