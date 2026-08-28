<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Catégorie de plainte prédite par le modèle SVM (device_hardware,
            // software_ecosystem, usability, commercial_service, other_unclear).
            $table->string('category')->nullable()->after('topics');

            // Libellé lisible correspondant à la catégorie (affiché côté frontend).
            $table->string('category_label')->nullable()->after('category');

            // Marge de décision du modèle : écart entre le meilleur score et le second.
            $table->float('decision_margin')->nullable()->after('category_label');

            // true si la marge est sous le seuil : une vérification humaine est recommandée.
            $table->boolean('needs_human_review')->nullable()->after('decision_margin');

            // Identifiant de la prédiction côté service IA, utile pour envoyer
            // une correction via POST /feedback si un humain corrige la catégorie.
            $table->unsignedBigInteger('ai_prediction_id')->nullable()->after('needs_human_review');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn([
                'category',
                'category_label',
                'decision_margin',
                'needs_human_review',
                'ai_prediction_id',
            ]);
        });
    }
};