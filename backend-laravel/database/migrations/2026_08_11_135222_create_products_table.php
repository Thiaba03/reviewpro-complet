<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // La marque peut être inconnue dans certaines lignes brutes.
            $table->foreignId('brand_id')
                ->nullable()
                ->constrained('brands')
                ->restrictOnDelete();

            // Plateforme d’origine de l’identifiant
            $table->string('source')->default('amazon');

            // ASIN ou identifiant produit fourni par le dataset
            $table->string('source_product_id');

            $table->string('name')->nullable();
            $table->string('category')->nullable()->index();
            $table->string('subcategory')->nullable()->index();

            $table->text('product_url')->nullable();
            $table->text('image_url')->nullable();

            $table->timestamps();

            $table->unique(
                ['source', 'source_product_id'],
                'products_source_external_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};