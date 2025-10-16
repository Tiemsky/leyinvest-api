<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\HealthController;

/*
|--------------------------------------------------------------------------
| Health Check Routes
|--------------------------------------------------------------------------
|
| These routes are used by load balancers, monitoring tools, and
| orchestration platforms to check application health.
|
*/

Route::prefix('v1')->group(function(){
// Basic health check (fast, no dependencies)
Route::get('/health', [HealthController::class, 'index'])
    ->name('health')
    ->withoutMiddleware(['throttle']);

// Comprehensive health check (checks all dependencies)
Route::get('/health/check', [HealthController::class, 'check'])
    ->name('health.check')
    ->withoutMiddleware(['throttle']);

// Kubernetes/Docker readiness probe
Route::get('/health/ready', [HealthController::class, 'ready'])
    ->name('health.ready')
    ->withoutMiddleware(['throttle']);

// Kubernetes/Docker liveness probe
Route::get('/health/alive', [HealthController::class, 'alive'])
    ->name('health.alive')
    ->withoutMiddleware(['throttle']);
});


