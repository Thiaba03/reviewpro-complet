<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\ReviewAnalyzer;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // POST /api/reviews
    public function store(Request $request, ReviewAnalyzer $analyzer)
    {
        // 1. On valide que le texte est bien là
        $validated = $request->validate([
            'content' => 'required|string|min:5', // Minimum 5 caractères [cite: 149]
        ]);

        // 2. On appelle notre Service IA pour analyser le texte
        $analysis = $analyzer->analyze($validated['content']);

        // 3. On sauvegarde tout en base
        // NOTE: Pour l'instant on met user_id à 1 en dur pour tester sans auth
        // Le membre B changera ça plus tard par $request->user()->id
        $review = Review::create([
            'user_id' => 1,
            'content' => $validated['content'],
            'sentiment' => $analysis['sentiment'],
            'score' => $analysis['score'],
            'topics' => $analysis['topics'],
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

    // GET /api/reviews/{id} - Voir un seul avis
    public function show(Review $review)
    {
        return $review;
    }

    // DELETE /api/reviews/{id} - Supprimer un avis
    public function destroy(Review $review)
    {
        $review->delete();
        return response()->json(['message' => 'Avis supprimé avec succès']);
    }
}