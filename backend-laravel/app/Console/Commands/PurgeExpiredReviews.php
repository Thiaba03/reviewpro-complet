<?php

namespace App\Console\Commands;

use App\Models\Review;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PurgeExpiredReviews extends Command
{
    protected $signature = 'reviews:purge-expired
        {--dry-run : Affiche ce qui serait anonymise sans modifier la base}';

    protected $description = 'Anonymise les avis plus anciens que la duree de conservation RGPD configuree';

    public function handle(): int
    {
        $retentionDays = (int) config('services.reviewpro_rgpd.review_retention_days');
        $cutoff = now()->subDays($retentionDays);
        $dryRun = (bool) $this->option('dry-run');

        $query = Review::query()
            ->where('is_anonymized', false)
            ->where('created_at', '<', $cutoff);

        $count = $query->count();

        if ($count === 0) {
            $this->info("Aucun avis a anonymiser (seuil : {$retentionDays} jours, avant le {$cutoff->toDateString()}).");
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("{$count} avis seraient anonymises (seuil : {$retentionDays} jours, avant le {$cutoff->toDateString()}).");
            return self::SUCCESS;
        }

        $query->chunkById(200, function ($reviews) {
            foreach ($reviews as $review) {
                $review->update([
                    'content' => null,
                    'auteur' => null,
                    'is_anonymized' => true,
                    'anonymized_at' => now(),
                ]);
            }
        });

        Log::info('PurgeExpiredReviews: anonymisation RGPD executee.', [
            'retention_days' => $retentionDays,
            'reviews_anonymized' => $count,
        ]);

        $this->info("{$count} avis anonymises avec succes (seuil : {$retentionDays} jours).");
        return self::SUCCESS;
    }
}
