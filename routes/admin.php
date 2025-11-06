<?php

use App\Http\Controllers\Api\V1\Admin\MaintenanceController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {
    // =======================================================================
    // ROUTES PROTÉGÉES - AUTHENTIFICATION REQUISE - ROLE ADMIN AUTORISÉ
    // =======================================================================
    Route::middleware(['auth:sanctum', 'check.token.expiration', EnsureUserIsAdmin::class ])->prefix('admin')->group(function () {
        // Informations utilisateur
        Route::post('maintenance/cleanup-incomplete-registrations', [MaintenanceController::class, 'cleanupIncompleteRegistrations']);

    });
});
