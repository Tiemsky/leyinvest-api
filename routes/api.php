<?php

use App\Models\BocIndicator;
use Illuminate\Http\Request;
use App\Jobs\ScrapeFinancialNewsJob;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\TopController;
use App\Http\Controllers\Api\V1\FlopController;
use App\Http\Controllers\Api\V1\ActionController;
use App\Http\Controllers\Api\V1\CountryController;
use App\Http\Controllers\Api\V1\UserActionController;
use App\Http\Controllers\Api\V1\BocIndicatorController;
use App\Http\Controllers\Api\V1\FinancialNewsController;
use App\Http\Controllers\Api\V1\UserDashboardController;


Route::prefix('v1')->group(function(){

    Route::middleware(['auth:sanctum', 'check.token.expiration'])->group(function(){
        Route:: get('/user/dashboard', [UserDashboardController::class, 'index']);

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

        Route::get('/actions', [ActionController::class, 'index']);
        Route::get('/indicators', [BocIndicatorController::class, 'index']);




    // ============================================
    // ROUTES PROTÉGÉES - VOIR TOUTES LES ACTUALITÉS
    // ============================================
       Route::group(['prefix' => 'financial-news'], function () {

        Route::get('companies', [FinancialNewsController::class, 'companies']);

        Route::get('sources', [FinancialNewsController::class, 'sources']);

        Route::get('statistics', [FinancialNewsController::class, 'statistics']);

        Route::get('recent/{days?}', [FinancialNewsController::class, 'recent']);

        Route::get('source/{source}', [FinancialNewsController::class, 'getFinancialNewBySource']);

        Route::get('/', [FinancialNewsController::class, 'index']);

        Route::get('{financialNews}', [FinancialNewsController::class, 'show']);
    });

    });

    Route::get('/flops', [FlopController::class, 'index']);
    Route::get('/tops', [TopController::class, 'index']);
    Route::get('/countries', [CountryController::class, 'index']);










    if (app()->environment('local')) {
        Route::get('/test-scrape', function () {
            dispatch(new ScrapeFinancialNewsJob());
            return '✅ Scraping started (check logs or DB).';
        });
    }
});

require __DIR__.'/api_auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/health.php';
