<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function(){
    // Routes publiques avec rate limiting
Route::prefix('auth')->middleware('throttle:auth')->group(function () {
    // Inscription étape 1
    Route::post('/register', [AuthController::class, 'registerStepOne']);

    // Inscription étape 2
    Route::post('/complete-profile', [AuthController::class, 'registerStepTwo']);

    // Connexion
    Route::post('/login', [AuthController::class, 'login']);

    // Mot de passe oublié
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Routes OTP avec rate limiting spécifique
Route::prefix('auth')->middleware('throttle:otp')->group(function () {
    Route::post('/verify-email', [AuthController::class, 'verifyRegistrationOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
});

// Routes protégées (nécessitent authentification)
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/update-profile', [AuthController::class, 'updateProfile']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/upload-avatar', [AuthController::class, 'uploadAvatar']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    });
});
});
