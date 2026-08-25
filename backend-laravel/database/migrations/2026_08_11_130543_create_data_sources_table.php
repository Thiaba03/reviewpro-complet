<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_sources', function (Blueprint $table) {
            $table->id();

            // Identifiant technique stable : kaggle_amazon_fr, google_places...
            $table->string('code')->unique();

            // Nom compréhensible affiché dans la documentation
            $table->string('name');

            // Valeurs prévues : dataset, api, scraping, manual
            $table->string('source_type');

            // Page d’origine du dataset, de l’API ou du site
            $table->text('source_url')->nullable();

            // Informations permettant de justifier le droit d’utilisation
            $table->string('license_name')->nullable();
            $table->text('license_url')->nullable();

            // Date à laquelle les conditions ont été vérifiées
            $table->timestamp('terms_checked_at')->nullable();

            // Notes concernant les données personnelles et le RGPD
            $table->text('rgpd_notes')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_sources');
    }
};