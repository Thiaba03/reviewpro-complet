<?php

namespace Database\Seeders;

use App\Models\DataSource;
use Illuminate\Database\Seeder;

class DataSourcesSeeder extends Seeder
{
    public function run(): void
    {
        DataSource::updateOrCreate(
    ['code' => 'webscraper_test_laptops'],
    [
        'name' => 'Web Scraper Test Sites - Laptops',
        'source_type' => 'scraping',
        'source_url' => 'https://webscraper.io/test-sites/e-commerce/static/computers/laptops',
        'license_name' => 'Site de démonstration pédagogique',
        'license_url' => null,
        'terms_checked_at' => '2026-08-12 00:00:00',
        'rgpd_notes' => implode(' ', [
            'Collecte limitée aux métadonnées publiques des produits.',
            'Aucun auteur, pseudonyme, email ou texte d’avis collecté.',
            'Requêtes limitées et temporisées.',
        ]),
        'is_active' => true,
    ]
);
    }
}