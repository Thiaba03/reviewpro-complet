<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            //  texte vidé lors d'une anonymisation RGPD.
            $table->text('content')->nullable()->change();
        });

        Schema::table('reviews', function (Blueprint $table) {
            // Date à laquelle l'avis a été anonymisé 
            // Reste à null tant que l'avis n'a pas été purgé.
            $table->timestamp('anonymized_at')->nullable()->after('is_anonymized');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('anonymized_at');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->text('content')->nullable(false)->change();
        });
    }
};