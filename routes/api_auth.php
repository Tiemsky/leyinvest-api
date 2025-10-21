<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\GoogleAuthController;

Route::prefix('v1')->group(function () {

    // ============================================
    // ROUTES PUBLIQUES - AUTHENTIFICATION
    // ============================================
    Route::prefix('auth')->middleware('throttle:60,1')->group(function () {
        // Inscription étape 1
        Route::post('register', [AuthController::class, 'registerStepOne']);

        // Inscription étape 2 (compléter le profil)
        Route::post('complete-profile', [AuthController::class, 'registerStepTwo']);

        // Connexion
        Route::post('login', [AuthController::class, 'login']);

        // Refresh token (route publique car le user n'a plus d'access token valide)
        Route::post('refresh-token', [AuthController::class, 'refreshToken']);

        // Mot de passe oublié
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('verify-reset-otp', [AuthController::class, 'verifyResetOtp']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);



        // ============================================
        // GOOGLE OAUTH ROUTES
        // ============================================

        // Initier la connexion Google
    Route::get('/google/login', [GoogleAuthController::class, 'login'])
    ->name('google.login');

// Callback après autorisation Google
Route::get('/google/callback', [GoogleAuthController::class, 'callback'])
    ->name('google.callback');

// Connexion avec Google ID Token (Mobile/SPA)
Route::post('/google/token', [GoogleAuthController::class, 'tokenLogin'])
    ->name('google.token');
    });

    // ============================================
    // ROUTES OTP - RATE LIMITING STRICT
    // ============================================
    Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
        // Vérification OTP
        Route::post('verify-email', [AuthController::class, 'verifyRegistrationOtp']);

        // Renvoyer un code OTP
        Route::post('resend-code', [AuthController::class, 'resendOtp']);
    });

    // ============================================
    // ROUTES PROTÉGÉES - AUTHENTIFICATION REQUISE
    // ============================================
    Route::middleware(['auth:sanctum', 'check.token.expiration'])->prefix('auth')->group(function () {
        // Informations utilisateur
        Route::get('user/me', [AuthController::class, 'user']);

        // Mise à jour du profil
        Route::post('update-profile', [AuthController::class, 'updateProfile']);
        Route::put('update-profile', [AuthController::class, 'updateProfile']); // Alternative REST

        // Changement de mot de passe
        Route::post('change-password', [AuthController::class, 'changePassword']);

        // Upload avatar
        Route::post('upload-avatar', [AuthController::class, 'uploadAvatar']);

        // Déconnexion
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
    });
});
