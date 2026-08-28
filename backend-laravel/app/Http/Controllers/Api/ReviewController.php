<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\ReviewAnalyzer;
use App\Services\AiReviewClassifier;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // POST /api/reviews
    public function store(Request $request, ReviewAnalyzer $analyzer, AiReviewClassifier $classifier)
    {
        // 1.  valide que le texte 
        $validated = $request->validate([
            'content' => 'required|string|min:5', /
        ]);

                // 2. On appelle notre Service IA pour analyser le sentiment
        $analysis = $analyzer->analyze($validated['content']);

        // 2bis. On appelle le modèle SVM certifié pour classer la plainte.
        
        $classification = null;
        try {
            $classification = $classifier->predict($validated['content']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning(
                'ReviewController: classification IA indisponible a la creation de l\'avis.',
                ['message' => $e->getMessage()]
            );
        }

        // 3. On sauvegarde tout en base
        $review = Review::create([
            'user_id' => 1,
            'content' => $validated['content'],
            'sentiment' => $analysis['sentiment'],
            'score' => $analysis['score'],
            'topics' => $analysis['topics'],
            'category' => $classification['category'] ?? null,
            'category_label' => $classification['label'] ?? null,
            'decision_margin' => $classification['margin'] ?? null,
            'needs_human_review' => $classification['needs_review'] ?? null,
            'ai_prediction_id' => $classification['prediction_id'] ?? null,
        ]);

        return response()->json($review, 201);
    }

    // GET /api/reviews

public function index(Request $request)
{
    $validated = $request->validate([
        'per_page' => 'sometimes|integer|min:1|max:100',
        'sentiment' => 'sometimes|in:positive,neutral,negative',
        'note' => 'sometimes|numeric|min:1|max:5',
        'brand_id' => 'sometimes|integer|exists:brands,id',
        'product_id' => 'sometimes|integer|exists:products,id',
        'source' => 'sometimes|string|max:100',
        'search' => 'sometimes|string|max:100',
    ]);

    $perPage = $validated['per_page'] ?? 20;

    $query = Review::query()
        ->with([
            'product:id,brand_id,name',
            'product.brand:id,name',
        ])
        ->select([
            'id',
            'product_id',
            'commerce_id',
            'source',
            'content',
            'language',
            'note',
            'date_avis',
            'sentiment',
            'score',
            'topics',
            'created_at',
        ]);

    $query->when(
        isset($validated['sentiment']),
        fn ($query) => $query->where(
            'sentiment',
            $validated['sentiment']
        )
    );

    $query->when(
        isset($validated['note']),
        fn ($query) => $query->where(
            'note',
            $validated['note']
        )
    );

    $query->when(
        isset($validated['product_id']),
        fn ($query) => $query->where(
            'product_id',
            $validated['product_id']
        )
    );

    $query->when(
        isset($validated['brand_id']),
        fn ($query) => $query->whereHas(
            'product',
            fn ($productQuery) => $productQuery->where(
                'brand_id',
                $validated['brand_id']
            )
        )
    );

    $query->when(
        isset($validated['source']),
        fn ($query) => $query->where(
            'source',
            $validated['source']
        )
    );

    $query->when(
        isset($validated['search']),
        fn ($query) => $query->where(
            'content',
            'like',
            '%'.$validated['search'].'%'
        )
    );

    return $query
        ->orderByDesc('date_avis')
        ->orderByDesc('id')
        ->paginate($perPage)
        ->withQueryString();
}

    //  Voir un seul avis
    public function show(Review $review)
    {
        return $review;
    }

    //  Supprimer un avis
    public function destroy(Review $review)
    {
        $review->delete();
        return response()->json(['message' => 'Avis supprimé avec succès']);
    }
}
