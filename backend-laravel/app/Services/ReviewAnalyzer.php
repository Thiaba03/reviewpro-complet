<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReviewAnalyzer
{
    public function analyze(string $text): array
    {
        $result = $this->analyzeWithHuggingFace($text);

        if ($result === null) {
            Log::warning('ReviewAnalyzer: bascule sur le moteur local (regles) car le modele Hugging Face a echoue.', [
                'text_excerpt' => mb_substr($text, 0, 80),
            ]);
            $result = $this->analyzeLocal($text);
            $result['engine'] = 'local_rules';
        } else {
            $result['engine'] = 'huggingface_distilcamembert';
        }

        $result['topics'] = $this->extractTopics($text);

        return $result;
    }

    private function analyzeWithHuggingFace(string $text): ?array
    {
        $apiKey = config('services.huggingface.key');
        $model = config('services.huggingface.sentiment_model');

        if (! $apiKey) {
            return null;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(15)
                ->post("https://router.huggingface.co/hf-inference/models/{$model}", [
                    'inputs' => $text,
                ]);

            if ($response->failed()) {
                Log::warning('ReviewAnalyzer: appel Hugging Face en echec.', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 300),
                ]);
                return null;
            }

            $data = $response->json();
            $scores = is_array($data[0] ?? null) ? $data[0] : $data;

            if (empty($scores) || ! isset($scores[0]['label'])) {
                return null;
            }

            usort($scores, fn ($a, $b) => $b['score'] <=> $a['score']);
            $topLabel = $scores[0]['label'];

            return $this->mapStarLabelToSentiment($topLabel);
        } catch (\Throwable $e) {
            Log::warning('ReviewAnalyzer: exception lors de l\'appel Hugging Face.', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function mapStarLabelToSentiment(string $label): array
    {
        $starNumber = (int) filter_var($label, FILTER_SANITIZE_NUMBER_INT);

        $sentiment = 'neutral';
        if ($starNumber <= 2) {
            $sentiment = 'negative';
        } elseif ($starNumber >= 4) {
            $sentiment = 'positive';
        }

        $score = (int) round((($starNumber - 1) / 4) * 100);

        return [
            'sentiment' => $sentiment,
            'score' => $score,
        ];
    }

    private function analyzeLocal(string $text): array
    {
        $textLower = strtolower($text);

        $positiveWords = ['super', 'excellent', 'bon', 'rapide', 'top', 'génial', 'adore', 'parfait'];
        $negativeWords = ['mauvais', 'lent', 'nul', 'cher', 'problème', 'déçu', 'horrible'];

        $posCount = 0;
        $negCount = 0;

        foreach ($positiveWords as $word) {
            if (str_contains($textLower, $word)) $posCount++;
        }
        foreach ($negativeWords as $word) {
            if (str_contains($textLower, $word)) $negCount++;
        }

        $sentiment = 'neutral';
        $score = 50;

        if ($posCount > $negCount) {
            $sentiment = 'positive';
            $score = 80 + ($posCount * 5);
        } elseif ($negCount > $posCount) {
            $sentiment = 'negative';
            $score = 40 - ($negCount * 5);
        }

        $score = max(0, min(100, $score));

        return [
            'sentiment' => $sentiment,
            'score' => $score,
        ];
    }

    private function extractTopics(string $text): array
    {
        $textLower = strtolower($text);
        $topics = [];

        if (str_contains($textLower, 'livra') || str_contains($textLower, 'colis') || str_contains($textLower, 'reçu')) {
            $topics[] = 'livraison';
        }
        if (str_contains($textLower, 'prix') || str_contains($textLower, 'tarif') || str_contains($textLower, '€')) {
            $topics[] = 'prix';
        }
        if (str_contains($textLower, 'qualit') || str_contains($textLower, 'produit')) {
            $topics[] = 'qualité';
        }
        if (str_contains($textLower, 'sav') || str_contains($textLower, 'service') || str_contains($textLower, 'garantie')) {
            $topics[] = 'service';
        }
        if (str_contains($textLower, 'accueil') || str_contains($textLower, 'vendeur') || str_contains($textLower, 'conseill')) {
            $topics[] = 'accueil';
        }

        return $topics;
    }
}