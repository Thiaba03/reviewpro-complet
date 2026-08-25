<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Les champs name, slug, country et website_url
        // sont déjà créés dans create_brands_table.
    }

    public function down(): void
    {
        // Aucun changement à annuler.
    }
};