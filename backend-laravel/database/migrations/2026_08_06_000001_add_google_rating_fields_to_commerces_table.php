<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commerces', function (Blueprint $table) {
            // Note moyenne et volume total d'avis remontes par l'API Google Places
            // (accessibles meme sans le champ "reviews" detaille, qui necessite
            // un niveau de facturation superieur - cf. commande FetchGoogleReviews)
            $table->float('google_rating')->nullable()->after('trustpilot_slug');
            $table->integer('google_rating_count')->nullable()->after('google_rating');
            $table->timestamp('google_synced_at')->nullable()->after('google_rating_count');
        });
    }

    public function down(): void
    {
        Schema::table('commerces', function (Blueprint $table) {
            $table->dropColumn(['google_rating', 'google_rating_count', 'google_synced_at']);
        });
    }
};