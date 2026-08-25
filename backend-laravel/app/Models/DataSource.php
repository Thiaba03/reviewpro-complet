<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataSource extends Model
{
    protected $fillable = [
        'code',
        'name',
        'source_type',
        'source_url',
        'license_name',
        'license_url',
        'terms_checked_at',
        'rgpd_notes',
        'is_active',
    ];

    protected $casts = [
        'terms_checked_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function importBatches(): HasMany
    {
        return $this->hasMany(ImportBatch::class);
    }
}