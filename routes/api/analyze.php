<?php

namespace Routes\Api;

use App\Http\Controllers\ActionDashboardController;
use App\Http\Controllers\Api\V1\ActionForecastController;
use App\Http\Controllers\SectorStatsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
        // ========== ACTIONS - DASHBOARD & HISTORIQUE ==========
        Route::prefix('actions')->group(function () {
            // Dashboard de l'année N-1 avec comparaisons sectorielles
            Route::get('{action}/dashboard', [ActionDashboardController::class, 'dashboard'])
                ->name('actions.dashboard');

            // Historique complet sur 5 ans
            Route::get('{action}/history', [ActionDashboardController::class, 'history'])
                ->name('actions.history');

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
