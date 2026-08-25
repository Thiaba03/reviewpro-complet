<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReviewsApiTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(
        string $brandName,
        string $brandSlug,
        string $productName
    ): Product {
        $brand = Brand::create([
            'name' => $brandName,
            'slug' => $brandSlug,
        ]);

        return Product::create([
            'brand_id' => $brand->id,
            'source' => 'test',
            'source_product_id' => (string) Str::uuid(),
            'name' => $productName,
            'category' => 'Electronics',
        ]);
    }

    private function createReview(
        Product $product,
        string $sentiment,
        float $rating
    ): Review {
        $content = "Test review {$sentiment} ".Str::uuid();

        return Review::create([
            'product_id' => $product->id,
            'content' => $content,
            'language' => 'en',
            'content_hash' => hash('sha256', $content),
            'source' => 'test',
            'source_review_id' => (string) Str::uuid(),
            'auteur' => null,
            'is_anonymized' => true,
            'note' => $rating,
            'sentiment' => $sentiment,
            'score' => (int) (($rating - 1) * 25),
        ]);
    }

    public function test_reviews_are_paginated_and_filtered_by_sentiment(): void
    {
        $product = $this->createProduct(
            'Test Brand',
            'test-brand',
            'Test Product'
        );

        $this->createReview($product, 'negative', 1);
        $this->createReview($product, 'negative', 2);
        $this->createReview($product, 'negative', 2);
        $this->createReview($product, 'positive', 5);

        $response = $this->getJson(
            '/api/reviews?per_page=2&sentiment=negative'
        );

        $response
            ->assertOk()
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('per_page', 2)
            ->assertJsonPath('total', 3)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.product.brand.name', 'Test Brand');

        $this->assertArrayNotHasKey(
            'auteur',
            $response->json('data.0')
        );
    }

    public function test_reviews_can_be_filtered_by_brand(): void
    {
        $firstProduct = $this->createProduct(
            'First Brand',
            'first-brand',
            'First Product'
        );

        $secondProduct = $this->createProduct(
            'Second Brand',
            'second-brand',
            'Second Product'
        );

        $this->createReview($firstProduct, 'negative', 1);
        $this->createReview($secondProduct, 'negative', 1);

        $response = $this->getJson(
            '/api/reviews?brand_id='.$firstProduct->brand_id
        );

        $response
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath(
                'data.0.product.brand.name',
                'First Brand'
            );
    }

    public function test_per_page_cannot_exceed_one_hundred(): void
    {
        $this->getJson('/api/reviews?per_page=500')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }
}