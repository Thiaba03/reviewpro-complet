<?php

namespace App\Console\Commands;

use App\Models\Review;
use App\Services\ReviewAnalyzer;
use Illuminate\Console\Command;

class ReanalyzeReviews extends Command
{
    protected $signature = 'reviews:reanalyze';

    protected $description = 'Re-analyse tous les avis existants avec le moteur ReviewAnalyzer actuel';

    public function handle(ReviewAnalyzer $analyzer): int
    {
        $reviews = Review::all();
        $total = $reviews->count();

        if ($total === 0) {
            $this->warn('Aucun avis en base a re-analyser.');
            return self::SUCCESS;
        }

        $this->info("Re-analyse de {$total} avis...");
        $bar = $this->output->createProgressBar($total);

        $countByEngine = ['huggingface_distilcamembert' => 0, 'local_rules' => 0];
        $countChanged = 0;

        foreach ($reviews as $review) {
            $oldSentiment = $review->sentiment;

            $analysis = $analyzer->analyze($review->content);

            $review->update([
                'sentiment' => $analysis['sentiment'],
                'score' => $analysis['score'],
                'topics' => $analysis['topics'],
            ]);

            $countByEngine[$analysis['engine']] = ($countByEngine[$analysis['engine']] ?? 0) + 1;

            if ($oldSentiment !== $analysis['sentiment']) {
                $countChanged++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Termine : {$total} avis re-analyses.");
        $this->info("- Analyses via le modele Hugging Face : " . ($countByEngine['huggingface_distilcamembert'] ?? 0));
        $this->info("- Analyses via le moteur de secours (regles) : " . ($countByEngine['local_rules'] ?? 0));
        $this->info("- Avis dont le sentiment a change par rapport a l'analyse precedente : {$countChanged}");

        return self::SUCCESS;
    }
}