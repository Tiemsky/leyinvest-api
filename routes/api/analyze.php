<?php

namespace Routes\Api;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SectorStatsController;
use App\Http\Controllers\Api\V1\ActionController;
use App\Http\Controllers\Api\V1\BocIndicatorController;
use App\Http\Controllers\Api\V1\ActionForecastController;
use App\Http\Controllers\Api\V1\ActionDashboardController;

Route::prefix('v1')->middleware(['auth:sanctum', 'check.token.expiration', 'throttle:api'])->group(function () {

    // ========== ACTIONS - BOC INDICATOR ==========
    //Recuperations de tous les indicateurs qui proviennt du PDF BOC sur le site de la Bvrm
    Route::get('/indicators', [BocIndicatorController::class, 'index']);

    // ========== ACTIONS - ANALYZE - DASHBOARD & HISTORIQUE ==========
    Route::prefix('actions')->group(function () {

        // Recuperer la liste de toutes les actions disponibles - Liste Globale
        Route::get('/', [ActionController::class, 'index']);

        //Afficher les détails complet d’une action avec les indicateurs boursiers
        Route::get('analyze/{action}', [ActionController::class, 'show']);

        // Analyze - Dashboard Complet de l'année N-1 avec comparaisons sectorielles
        Route::get('{action}/dashboard', [ActionDashboardController::class, 'dashboard'])
            ->name('actions.dashboard');

        //Analyze -  Historique complet sur 5 ans
        Route::get('{action}/history', [ActionDashboardController::class, 'history'])
            ->name('actions.history');

        //Analyze Prevision de rendement
        Route::get('forecast', [ActionForecastController::class, 'index'])->name('actions.forecasts');
    });

    // ========== SECTEURS - STATISTIQUES ==========
    Route::prefix('sectors')->group(function () {
        // Secteurs BRVM
        Route::prefix('brvm')->group(function () {
            Route::get('{sectorId}/stats', [SectorStatsController::class, 'brvmStats'])
                ->name('sectors.brvm.stats');

            Route::get('{sectorId}/history', [SectorStatsController::class, 'brvmHistory'])
                ->name('sectors.brvm.history');
        });

        // Secteurs Classifiés
        Route::prefix('classified')->group(function () {
            Route::get('{sectorId}/stats', [SectorStatsController::class, 'classifiedStats'])
                ->name('sectors.classified.stats');

            Route::get('{sectorId}/history', [SectorStatsController::class, 'classifiedHistory'])
                ->name('sectors.classified.history');
        });
    });
});
