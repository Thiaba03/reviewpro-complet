<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    protected $fillable = [
        'data_source_id',
        'original_filename',
        'file_checksum',
        'status',
        'rows_read',
        'rows_skipped',
        'rows_imported',
        'rows_rejected',
        'rows_duplicated',
        'parameters',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'parameters' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function dataSource(): BelongsTo
    {
        return $this->belongsTo(DataSource::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}