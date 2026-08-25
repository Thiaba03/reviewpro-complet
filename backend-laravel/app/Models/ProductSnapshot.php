<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSnapshot extends Model
{
    protected $fillable = [
        'product_id',
        'import_batch_id',
        'price',
        'currency',
        'average_rating',
        'displayed_review_count',
        'description',
        'source_url',
        'collected_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'average_rating' => 'float',
        'displayed_review_count' => 'integer',
        'collected_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }
}