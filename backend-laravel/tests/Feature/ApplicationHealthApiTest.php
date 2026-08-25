<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ApplicationHealthApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.reviewpro_ai.url' => 'http://ai.test',
        ]);
    }

    public function test_application_is_healthy(): void
    {
        Http::fake([
            'http://ai.test/health' => Http::response([
                'status' => 'ok',
                'model' => 'review_topic_macro_svm',
                'model_version' => '1.0.0',
            ]),
        ]);

        $response = $this->getJson('/api/health');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.application.status', 'ok')
            ->assertJsonPath('checks.database.status', 'ok')
            ->assertJsonPath('checks.ai_service.status', 'ok')
            ->assertJsonPath(
                'checks.ai_service.model',
                'review_topic_macro_svm'
            )
            ->assertJsonStructure([
                'checked_at',
                'latency_ms',
            ]);

        Http::assertSent(
            fn ($request) =>
                $request->url() === 'http://ai.test/health'
        );
    }

    public function test_unavailable_ai_degrades_application(): void
    {
        Log::spy();

        Http::fake([
            'http://ai.test/health' => Http::response([
                'message' => 'Unavailable',
            ], 500),
        ]);

        $response = $this->getJson('/api/health');

        $response
            ->assertStatus(503)
            ->assertJsonPath('status', 'degraded')
            ->assertJsonPath('checks.application.status', 'ok')
            ->assertJsonPath('checks.database.status', 'ok')
            ->assertJsonPath(
                'checks.ai_service.status',
                'unavailable'
            );

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(
                fn (string $message, array $context) =>
                    $message ===
                        'Application health check: AI service unavailable.'
                    && isset($context['exception_class'])
            );
    }

    public function test_degraded_ai_status_degrades_application(): void
    {
        Log::spy();

        Http::fake([
            'http://ai.test/health' => Http::response([
                'status' => 'degraded',
                'model' => 'review_topic_macro_svm',
            ]),
        ]);

        $response = $this->getJson('/api/health');

        $response
            ->assertStatus(503)
            ->assertJsonPath('status', 'degraded')
            ->assertJsonPath(
                'checks.ai_service.status',
                'degraded'
            );

        Log::shouldHaveReceived('warning')
            ->once()
            ->with(
                'Application health check: AI service degraded.'
            );
    }
}
