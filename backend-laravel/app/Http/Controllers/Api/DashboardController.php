<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalReviews = Review::count();

        $averageRating = round(
            (float) Review::whereNotNull('note')->avg('note'),
            2
        );

        $sentimentCounts = Review::query()
            ->select('sentiment', DB::raw('COUNT(*) AS total'))
            ->whereNotNull('sentiment')
            ->groupBy('sentiment')
            ->pluck('total', 'sentiment');

        $sentimentDistribution = [
            'positive' => (int) ($sentimentCounts['positive'] ?? 0),
            'neutral' => (int) ($sentimentCounts['neutral'] ?? 0),
            'negative' => (int) ($sentimentCounts['negative'] ?? 0),
        ];

        $ratingDistribution = Review::query()
            ->select('note', DB::raw('COUNT(*) AS total'))
            ->whereNotNull('note')
            ->groupBy('note')
            ->orderBy('note')
            ->get()
            ->map(fn ($row) => [
                'rating' => (float) $row->note,
                'total' => (int) $row->total,
            ]);

        $topComplaintProducts = Review::query()
            ->join('products', 'reviews.product_id', '=', 'products.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->where('reviews.sentiment', 'negative')
            ->select([
                'products.id AS product_id',
                'products.name AS product_name',
                'brands.name AS brand_name',
                DB::raw('COUNT(reviews.id) AS negative_reviews'),
            ])
            ->groupBy(
                'products.id',
                'products.name',
                'brands.name'
            )
            ->orderByDesc('negative_reviews')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'product_id' => $row->product_id,
                'product_name' => $row->product_name,
                'brand_name' => $row->brand_name,
                'negative_reviews' => (int) $row->negative_reviews,
            ]);

        $topComplaintBrands = Review::query()
            ->join('products', 'reviews.product_id', '=', 'products.id')
            ->join('brands', 'products.brand_id', '=', 'brands.id')
            ->where('reviews.sentiment', 'negative')
            ->select([
                'brands.id AS brand_id',
                'brands.name AS brand_name',
                DB::raw('COUNT(reviews.id) AS negative_reviews'),
            ])
            ->groupBy('brands.id', 'brands.name')
            ->orderByDesc('negative_reviews')
            ->get()
            ->map(fn ($row) => [
                'brand_id' => $row->brand_id,
                'brand_name' => $row->brand_name,
                'negative_reviews' => (int) $row->negative_reviews,
            ]);

        return response()->json([
            'total_reviews' => $totalReviews,
            'average_rating' => $averageRating,
            'sentiment_distribution' => $sentimentDistribution,
            'rating_distribution' => $ratingDistribution,
            'top_complaint_brands' => $topComplaintBrands,
            'top_complaint_products' => $topComplaintProducts,

            // Les thèmes seront produits par le service IA au bloc 2.
            'top_complaint_topics' => [],
        ]);
    }
}