<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();

            // Nom affiché : Samsung, Bosch, LG...
            $table->string('name')->unique();

            // Nom technique utilisable dans les URL : samsung, bosch, lg...
            $table->string('slug')->unique();

            $table->string('country')->nullable();
            $table->text('website_url')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};