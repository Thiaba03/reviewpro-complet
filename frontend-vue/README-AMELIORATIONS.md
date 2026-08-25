# Frontend enrichi de ReviewPro

Cette version ajoute :

- une navigation latérale responsive ;
- un tableau de bord enrichi avec indicateurs et répartition des sentiments ;
- une consultation des avis avec recherche, filtres et pagination ;
- un accès dédié à l’analyse IA existante ;
- une page de monitorage de Laravel, SQLite et FastAPI ;
- des états de chargement, d’erreur et d’absence de résultat ;
- une configuration de l’URL de l’API par variable d’environnement.

## Installation

```bash
cp .env.example .env
npm install
npm run dev -- --host=127.0.0.1 --port=5173
```

Le backend Laravel doit être disponible sur `http://127.0.0.1:8000` et le service FastAPI sur `http://127.0.0.1:8001`.

## Vérifications

```bash
npm test
npm run build
```
