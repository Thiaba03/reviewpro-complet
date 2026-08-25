<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('reviews', function (Blueprint $table) {
        $table->id();

        // Lien vers l'utilisateur (obligatoire)
        $table->foreignId('user_id')->constrained()->onDelete('cascade');

        // Le texte de l'avis
        $table->text('content');

        // Résultats de l'IA (peuvent être vides au début, donc nullable)
        $table->string('sentiment')->nullable(); // positive, neutral, negative
        $table->integer('score')->nullable();    // 0 à 100
        $table->json('topics')->nullable();      // ["prix", "livraison"]

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
