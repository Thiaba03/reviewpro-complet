<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Api\AiPredictionController;
use App\Http\Controllers\Controller;
use App\Services\AiReviewClassifier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiPredictionController extends Controller
{
    public function __invoke(
        Request $request,
        AiReviewClassifier $classifier
    ): JsonResponse {
        $validated = $request->validate([
            'text' => [
                'required',
                'string',
                'min:3',
                'max:5000',
            ],
        ]);

        try {
            return response()->json(
                $classifier->predict($validated['text'])
            );
        } catch (ConnectionException | RequestException $exception) {
            report($exception);

            return response()->json([
                'message' => 'Le service d’analyse IA est indisponible.',
            ], 503);
        }
    }
}