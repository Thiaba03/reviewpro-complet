<?php

namespace App\Console\Commands;


use App\Models\Commerce;
use App\Models\Review;
use App\Services\ReviewAnalyzer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Collecte automatisee des avis Google via l'API officielle Google Places
 * (Place Details, New).
 *
 * Limite connue de l'API : Google ne renvoie que les 5 avis les plus
 * "pertinents" par etablissement (pas d'acces a l'historique complet).
 * C'est une limite de l'API elle-meme, a documenter comme telle dans le
 * dossier RNCP (BC01 : argument pour justifier la collecte multi-sources,
 * completee par l'import manuel Trustpilot).
 *
 * Usage :
 *   php artisan reviews:fetch-google
 */
class FetchGoogleReviews extends Command
{
    protected $signature = 'reviews:fetch-google';

    protected $description = 'Recupere les avis Google Places pour tous les commerces ayant un google_place_id renseigne';

    public function handle(ReviewAnalyzer $analyzer): int
    {
        $apiKey = config('services.google_places.key');

        if (! $apiKey) {
            $this->error('GOOGLE_PLACES_API_KEY manquante dans .env');
            return self::FAILURE;
        }

        $commerces = Commerce::whereNotNull('google_place_id')->get();

        if ($commerces->isEmpty()) {
            $this->warn('Aucun commerce avec un google_place_id renseigne. Mets a jour la table commerces.');
            return self::SUCCESS;
        }

        $totalInserted = 0;

        foreach ($commerces as $commerce) {
            $this->info("--- {$commerce->nom} ---");

            $response = Http::withHeaders([
                'X-Goog-Api-Key' => $apiKey,
                'X-Goog-FieldMask' => 'id,displayName,rating,userRatingCount,reviews',
            ])->get("https://places.googleapis.com/v1/places/{$commerce->google_place_id}");

            if ($response->failed()) {
                $this->error("  Erreur API ({$response->status()}) : " . $response->body());
                continue;
            }

            $reviews = $response->json('reviews', []);
            $rating = $response->json('rating');
            $ratingCount = $response->json('userRatingCount');

            // On enregistre les metadonnees agregees, disponibles meme sans
            // le champ "reviews" detaille (niveau de facturation Enterprise).
            $commerce->update([
                'google_rating' => $rating,
                'google_rating_count' => $ratingCount,
                'google_synced_at' => now(),
            ]);

            if (empty($reviews)) {
                $this->warn("  Champ 'reviews' non disponible pour ce lieu (limite API/facturation). "
                    . "Note moyenne enregistree : {$rating} ({$ratingCount} avis au total sur Google).");
            }

            $inserted = 0;

            foreach ($reviews as $rev) {
               $texte = Str::squish((string) ($rev['text']['text'] ?? ''));

if ($texte === '') {
    continue;
}

$publishTime = $rev['publishTime'] ?? null;

$contentHash = hash(
    'sha256',
    mb_strtolower($texte, 'UTF-8')
);

$sourceReviewId = hash(
    'sha256',
    $commerce->google_place_id
    .'|'.$contentHash
    .'|'.($publishTime ?? '')
);

                $exists = Review::where('source', 'google')
                    ->where('source_review_id', $sourceReviewId)
                    ->exists();

                if ($exists) {
                    continue;
                }


                $analysis = $analyzer->analyze($texte);

                Review::create([
                    'commerce_id' => $commerce->id,
                    'content' => $texte,
                    'source' => 'google',
                    'source_review_id' => $sourceReviewId,
                    'auteur' => null,
'language' => $rev['text']['languageCode'] ?? null,
'content_hash' => $contentHash,
'is_anonymized' => true,
                    'note' => $rev['rating'] ?? null,
                    'date_avis' => $publishTime,
                    'sentiment' => $analysis['sentiment'],
                    'score' => $analysis['score'],
                    'topics' => $analysis['topics'],
                ]);

                $inserted++;
            }

            $this->info("  {$inserted} nouveaux avis inseres (sur " . count($reviews) . " recuperes)");
            $totalInserted += $inserted;
        }

        $this->info("Termine. {$totalInserted} nouveaux avis inseres au total.");

        return self::SUCCESS;
    }
}
