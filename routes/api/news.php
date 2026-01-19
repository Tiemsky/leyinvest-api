<?php

use App\Http\Controllers\Api\V1\FinancialNewsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/financial-news')->middleware(['auth:sanctum', 'check.token.expiration', 'throttle:api'])->group(function () {
    // Recupere la listes de toutes les actualities avec toutes actions inclues
    Route::get('/', [FinancialNewsController::class, 'index']);

    // Recupere la listes de toutes les actualities classee par actions
    Route::get('companies', [FinancialNewsController::class, 'companies']);

    // Recupere la listes de toutes les actualities classee par source (brvm ou etat financier)
    Route::get('sources', [FinancialNewsController::class, 'sources']);

    Route::get('statistics', [FinancialNewsController::class, 'statistics']);

    // Recupere la listes de toutes les actualities avec toutes actions inclues par ordres (plus recent jusqu'au ..)
    Route::get('recent/{days?}', [FinancialNewsController::class, 'recent']);

    // Filtrer par sources
    Route::get('source/{source}', [FinancialNewsController::class, 'getFinancialNewBySource']);

    // Afficher les details a travers la key
    Route::get('{financialNews}', [FinancialNewsController::class, 'show']);
});
