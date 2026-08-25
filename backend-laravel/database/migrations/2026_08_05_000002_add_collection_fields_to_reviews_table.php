<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Rend user_id optionnel : les avis collectes automatiquement
            // (Google/Trustpilot) ne sont pas rattaches a un compte utilisateur.
            $table->foreignId('user_id')->nullable()->change();

            $table->foreignId('commerce_id')->nullable()->after('user_id')
                ->constrained()->nullOnDelete();

            // "manuel_utilisateur" (formulaire existant), "google", ou "trustpilot"
            $table->string('source')->default('manuel_utilisateur')->after('content');

            // Identifiant externe pour eviter les doublons lors des re-imports
            $table->string('source_review_id')->nullable()->after('source');

            $table->string('auteur')->nullable()->after('source_review_id');

            // Note laissee par le client sur la plateforme source (1 a 5)
            $table->float('note')->nullable()->after('auteur');

            // Date de l'avis original (differente de created_at qui est la date d'import)
            $table->timestamp('date_avis')->nullable()->after('note');

            $table->unique(['source', 'source_review_id']);
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique(['source', 'source_review_id']);
            $table->dropConstrainedForeignId('commerce_id');
            $table->dropColumn(['source', 'source_review_id', 'auteur', 'note', 'date_avis']);
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
