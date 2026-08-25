<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Lot ayant créé cet avis.
            // Nullable pour conserver les quatre anciens avis.
            $table->foreignId('import_batch_id')
                ->nullable()
                ->after('commerce_id')
                ->constrained('import_batches')
                ->restrictOnDelete();

            // Langue normalisée : fr, en, de...
            $table->string('language', 10)
                ->nullable()
                ->after('content');

            // Empreinte du contenu utilisée pour détecter les doublons.
            $table->string('content_hash', 64)
                ->nullable()
                ->after('language')
                ->index();

            // Indique si les informations personnelles ont été anonymisées.
            $table->boolean('is_anonymized')
                ->default(false)
                ->after('auteur');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['import_batch_id']);
            $table->dropIndex(['content_hash']);

            $table->dropColumn([
                'import_batch_id',
                'language',
                'content_hash',
                'is_anonymized',
            ]);
        });
    }
};