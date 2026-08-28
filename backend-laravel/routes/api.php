<?php
use App\Http\Controllers\Api\AiPredictionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ReviewController; 

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// --- Nos routes pour les avis ---
// CRUD complet des avis
Route::get('/reviews', [ReviewController::class, 'index']);      // Liste
Route::post('/reviews', [ReviewController::class, 'store']);     // Création + IA
Route::get('/reviews/{review}', [ReviewController::class, 'show']); // Détail (Nouveau)
Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']); // Suppression (Nouveau)
Route::get('/dashboard', [App\Http\Controllers\Api\DashboardController::class, 'index']);
Route::post('/ai/predict', AiPredictionController::class);
Route::get('/health', App\Http\Controllers\Api\ApplicationHealthController::class);
