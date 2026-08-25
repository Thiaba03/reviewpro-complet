<?php

namespace Database\Seeders;

use App\Models\Commerce;
use Illuminate\Database\Seeder;

class CommercesSeeder extends Seeder
{
    public function run(): void
    {
        $commerces = [
            [
                'nom' => 'Darty - Exemple magasin',
                'categorie' => 'electromenager_electronique',
                'ville' => 'Paris',
                'google_place_id' => null, // A remplir avec un vrai place_id
                'trustpilot_slug' => 'darty.com',
            ],
            [
                'nom' => 'Boulanger - Exemple magasin',
                'categorie' => 'electromenager_electronique',
                'ville' => 'Paris',
                'google_place_id' => null,
                'trustpilot_slug' => 'boulanger.com',
            ],
            [
                'nom' => 'Fnac - Exemple magasin',
                'categorie' => 'electromenager_electronique',
                'ville' => 'Paris',
                'google_place_id' => null,
                'trustpilot_slug' => 'fnac.com',
            ],
            [
                'nom' => 'Electrodepot - Exemple magasin',
                'categorie' => 'electromenager_electronique',
                'ville' => 'Paris',
                'google_place_id' => null,
                'trustpilot_slug' => null,
            ],
        ];

        foreach ($commerces as $data) {
            Commerce::updateOrCreate(['nom' => $data['nom']], $data);
        }
    }
}
