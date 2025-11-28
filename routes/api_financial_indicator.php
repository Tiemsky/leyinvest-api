<?php

use App\Http\Controllers\Api\V1\FinancialIndicatorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes API - Indicateurs Financiers
|--------------------------------------------------------------------------
|
| Routes protégées par Sanctum
| Utilise 'key' pour identifier les actions (pas 'id')
|
*/

Route::prefix('actions')->middleware('auth:sanctum')->group(function () {

    /**
     * Dashboard - Indicateurs pour une année
     * GET /api/actions/{actionKey}/dashboard?year=2024&horizon=moyen_terme
     */
    Route::get('/{action:key}/dashboard', [FinancialIndicatorController::class, 'dashboard'])
        ->name('actions.dashboard');
    Route::get('/dashboard', [FinancialIndicatorController::class, 'test'])
        ->name('actions.dashboard');

    /**
     * Historique - Données sur plusieurs années
     * GET /api/actions/{actionKey}/historical?start_year=2021&end_year=2024&horizon=moyen_terme
     */
    Route::get('/{action:key}/historical', [FinancialIndicatorController::class, 'historical'])
        ->name('actions.historical');

    /**
     * Refresh - Invalide le cache
     * POST /api/actions/{actionKey}/indicators/refresh
     */
    Route::post('/{action:key}/indicators/refresh', [FinancialIndicatorController::class, 'refresh'])
        ->name('actions.indicators.refresh');

    /**
     * Années disponibles pour une action
     * GET /api/actions/{actionKey}/years
     */
    Route::get('/{action:key}/years', [FinancialIndicatorController::class, 'availableYears'])
        ->name('actions.years');
});

/**
 * Horizons d'investissement disponibles
 * GET /api/horizons
 */
Route::get('/horizons', [FinancialIndicatorController::class, 'horizons'])
    ->middleware('auth:sanctum')
    ->name('horizons.index');
