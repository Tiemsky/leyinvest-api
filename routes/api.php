<?php

use App\Http\Controllers\Api\V1\CountryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\FlopController;
use App\Http\Controllers\Api\V1\TopController;
use App\Http\Controllers\Api\V1\ActionController;




Route::prefix('v1')->group(function(){
    Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/flops', [FlopController::class, 'index']);
    Route::get('/tops', [TopController::class, 'index']);
    Route::get('/actions', [ActionController::class, 'index']);
    Route::get('/countries', [CountryController::class, 'index']);
});

require __DIR__.'/auth.php';
require __DIR__.'/health.php';
