<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiReviewClassifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ApplicationHealthController extends Controller
{
    public function __invoke(
        AiReviewClassifier $classifier
    ): JsonResponse {
        $startedAt = microtime(true);
        $statusCode = 200;

        $checks = [
            'application' => [
                'status' => 'ok',
                'service' => 'reviewpro-laravel',
            ],
        ];

        try {
            DB::select('SELECT 1');

            $checks['database'] = [
                'status' => 'ok',
                'connection' => DB::connection()->getDriverName(),
            ];
        } catch (Throwable $exception) {
            $statusCode = 503;
            $checks['database'] = ['status' => 'unavailable'];

            Log::error(
                'Application health check: database unavailable.',
                ['exception_class' => $exception::class]
            );
        }

        try {
            $aiHealth = $classifier->health();
            $aiIsHealthy = ($aiHealth['status'] ?? null) === 'ok';

            $checks['ai_service'] = [
                'status' => $aiIsHealthy ? 'ok' : 'degraded',
                'model' => $aiHealth['model'] ?? null,
                'model_version' =>
                    $aiHealth['model_version'] ?? null,
            ];

            if (! $aiIsHealthy) {
                $statusCode = 503;

                Log::warning(
                    'Application health check: AI service degraded.'
                );
            }
        } catch (Throwable $exception) {
            $statusCode = 503;
            $checks['ai_service'] = ['status' => 'unavailable'];

            Log::warning(
                'Application health check: AI service unavailable.',
                ['exception_class' => $exception::class]
            );
        }

        return response()->json([
            'status' => $statusCode === 200 ? 'ok' : 'degraded',
            'checked_at' => now()->toIso8601String(),
            'latency_ms' => round(
                (microtime(true) - $startedAt) * 1000,
                2
            ),
            'checks' => $checks,
        ], $statusCode);
    }
}
