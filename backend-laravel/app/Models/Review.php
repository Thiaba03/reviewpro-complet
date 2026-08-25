<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    // Champs qu'on a le droit de remplir
    protected $fillable = [
    'user_id',
    'commerce_id',
    'product_id',
    'import_batch_id',
    'content',
    'language',
    'content_hash',
    'source',
    'source_review_id',
    'auteur',
    'is_anonymized',
    'note',
    'date_avis',
    'sentiment',
    'score',
    'topics',
];

    // Conversion automatique : La base stocke du JSON, mais Laravel  donne un Tableau
    protected $casts = [
    'topics' => 'array',
    'date_avis' => 'datetime',
    'is_anonymized' => 'boolean',
];

    // Lien vers l'utilisateur (un avis appartient à un User) - optionnel pour les avis collectes
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Lien vers le commerce concerne (Darty, Boulanger, Fnac...) - optionnel pour les avis saisis via le formulaire
    public function commerce()
    {
        return $this->belongsTo(Commerce::class);
    }

    public function importBatch()
{
    return $this->belongsTo(ImportBatch::class);

}
public function product()
{
    return $this->belongsTo(Product::class);
}
}