<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use UnexpectedValueException;

class AiReviewClassifier
{
    public function predict(string $text): array
    {
        $text = trim($text);

        if (mb_strlen($text) < 3) {
            throw new InvalidArgumentException(
                'Le texte doit contenir au moins trois caractères.'
            );
        }

        $baseUrl = rtrim(
            (string) config('services.reviewpro_ai.url'),
            '/'
        );

        $timeout = (int) config(
            'services.reviewpro_ai.timeout',
            10
        );

        $response = Http::acceptJson()
            ->timeout($timeout)
            ->post($baseUrl.'/predict', [
                'text' => $text,
            ])
            ->throw();

        $prediction = $response->json();

        if (
            ! is_array($prediction)
            || ! isset(
                $prediction['category'],
                $prediction['margin'],
                $prediction['needs_review']
            )
        ) {
            throw new UnexpectedValueException(
                'La réponse du service IA est invalide.'
            );
        }

        return $prediction;
    }

    public function health(): array
    {
        $baseUrl = rtrim(
            (string) config('services.reviewpro_ai.url'),
            '/'
        );

        return Http::acceptJson()
            ->timeout(5)
            ->get($baseUrl.'/health')
            ->throw()
            ->json();
    }
}