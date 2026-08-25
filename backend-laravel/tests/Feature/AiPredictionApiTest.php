<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiPredictionApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.reviewpro_ai.url' => 'http://ai.test',
            'services.reviewpro_ai.timeout' => 1,
        ]);
    }

    public function test_an_review_can_be_classified(): void
    {
        Http::fake([
            'http://ai.test/predict' => Http::response([
                'category' => 'device_hardware',
                'label' => 'Matériel, batterie, écran ou audio',
                'decision_score' => 0.72,
                'margin' => 1.54,
                'threshold' => 0.30,
                'needs_review' => false,
                'ranking' => [],
            ]),
        ]);

        $response = $this->postJson('/api/ai/predict', [
            'text' => 'The charging port is broken.',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('category', 'device_hardware')
            ->assertJsonPath('needs_review', false);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://ai.test/predict'
                && $request['text'] ===
                    'The charging port is broken.';
        });
    }

    public function test_text_is_required(): void
    {
        Http::fake();

        $response = $this->postJson('/api/ai/predict', [
            'text' => '',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('text');

        Http::assertNothingSent();
    }

    public function test_unavailable_ai_service_returns_503(): void
    {
        Http::fake([
            'http://ai.test/predict' => Http::response([
                'message' => 'Unavailable',
            ], 500),
        ]);

        $response = $this->postJson('/api/ai/predict', [
            'text' => 'The tablet is very slow.',
        ]);

        $response
            ->assertStatus(503)
            ->assertJson([
                'message' =>
                    'Le service d’analyse IA est indisponible.',
            ]);
    }
}