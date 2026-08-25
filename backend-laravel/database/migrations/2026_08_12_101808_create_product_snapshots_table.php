<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_snapshots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('import_batch_id')
                ->constrained('import_batches')
                ->restrictOnDelete();

            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');

            $table->float('average_rating')->nullable();
            $table->unsignedInteger('displayed_review_count')->nullable();

            $table->text('description')->nullable();
            $table->text('source_url');

            $table->timestamp('collected_at');
            $table->timestamps();

            $table->unique(
                ['product_id', 'import_batch_id'],
                'snapshot_product_batch_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_snapshots');
    }
};