<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Commerce extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'categorie',
        'ville',
        'google_place_id',
        'trustpilot_slug',
        'google_rating',
        'google_rating_count',
        'google_synced_at',
    ];

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}