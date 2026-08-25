<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerces', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('categorie')->default('electromenager_electronique');
            $table->string('ville')->nullable();
            $table->string('google_place_id')->nullable()->unique();
            $table->string('trustpilot_slug')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerces');
    }
};
