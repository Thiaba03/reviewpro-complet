<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();

            // Source utilisée pour cet import
            $table->foreignId('data_source_id')
                ->constrained('data_sources')
                ->restrictOnDelete();

            // Nom du fichier d’origine, si l’import vient d’un fichier
            $table->string('original_filename')->nullable();

            // Empreinte SHA-256 permettant d’identifier le fichier
            $table->string('file_checksum', 64)->nullable()->index();

            // pending, running, completed ou failed
            $table->string('status')->default('pending')->index();

            // Mesures de qualité de l’import
            $table->unsignedInteger('rows_read')->default(0);
            $table->unsignedInteger('rows_imported')->default(0);
            $table->unsignedInteger('rows_rejected')->default(0);
            $table->unsignedInteger('rows_duplicated')->default(0);

            // Paramètres utilisés : séparateur CSV, langue, colonnes...
            $table->json('parameters')->nullable();

            // Message général en cas d’échec
            $table->text('error_message')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};