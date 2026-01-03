<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\TopController;
use App\Http\Controllers\Api\V1\FlopController;
use App\Http\Controllers\Api\V1\CountryController;
use App\Http\Controllers\Api\V1\UserActionController;
use App\Http\Controllers\Api\V1\UserDashboardController;


Route::prefix('v1')->group(function () {

    // ============================================
    // ROUTES PUBLIQUES (Lecture seule)
    // Rate Limit : 'api' (60/min - standard)
    // ============================================
    Route::middleware(['throttle:api'])->group(function () {
        Route::get('/countries', [CountryController::class, 'index']);
        Route::get('/flops', [FlopController::class, 'index']);
        Route::get('/tops', [TopController::class, 'index']);
    });



    Route::middleware(['auth:sanctum', 'check.token.expiration', 'throttle:api'])->group(function () {
        //Dashboard de l'utilisateur authentifié
        Route::get('/user/dashboard', [UserDashboardController::class, 'index']);

        // Routes pour les actions suivies
        Route::prefix('user')->name('actions.')->group(function () {

            // Obtenir toutes les actions suivies
            Route::get('/actions', [UserActionController::class, 'index'])
                ->name('index');

            // Suivre une action
            Route::post('/action/follow', [UserActionController::class, 'follow'])
                ->name('follow');

            // Ne plus suivre une action
            Route::delete('/action/{actionId}/unfollow', [UserActionController::class, 'unfollow'])
                ->name('unfollow');

            // Route pour unfollow plusieurs actions
            Route::post('/actions/unfollow', [UserActionController::class, 'unfollowMultiple']);

            // Mettre à jour les paramètres d'une action suivie
            Route::patch('/action/{actionId}', [UserActionController::class, 'update'])
                ->name('update');

            // Toggle follow/unfollow
            Route::post('/action/toggle', [UserActionController::class, 'toggle'])
                ->name('toggle');

            // Vérifier si on suit une action
            Route::get('/action/{actionId}/check', [UserActionController::class, 'checkFollowing'])
                ->name('check');

            // Obtenir les statistiques de suivi
            Route::get('/action/stats', [UserActionController::class, 'stats'])
                ->name('stats');

            // Obtenir les followers d'une action
            Route::get('/{actionId}/followers', [UserActionController::class, 'followers'])
                ->name('followers');
        });
    });
});

// Chargement des modules (routes externes)
require __DIR__ . '/api/auth.php';
require __DIR__ . '/api/analyze.php';      // Contient ActionController, BocIndicator, etc.
require __DIR__ . '/api/news.php';         // Contient FinancialNewsController
require __DIR__ . '/api/subscription.php'; // Plans, Invoices
require __DIR__ . '/api/admin.php';        // Administration
require __DIR__ . '/api/health.php';       // Monitoring (Pas de throttle)
require __DIR__ . '/api/documents.php';    // Téléchargements
