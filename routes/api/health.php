<?php

use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/health')->withoutMiddleware(['throttle', 'throttle:api', 'throttle:global'])->group(function () {
    Route::get('/', [HealthController::class, 'index'])->name('health');
    Route::get('/check', [HealthController::class, 'check'])->name('health.check');
    Route::get('/ready', [HealthController::class, 'ready'])->name('health.ready');
    Route::get('/alive', [HealthController::class, 'alive'])->name('health.alive');
});
