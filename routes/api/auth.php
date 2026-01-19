<?php

namespace Routes\Api;

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\GoogleAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->group(function () {
    // ============================================
    // ROUTES PUBLIQUES - AUTHENTIFICATION
    // ============================================

    // 1. INSCRIPTION & LOGIN (Rate Limit: 'auth' -> 10/min)
    Route::middleware(['throttle:auth'])->group(function () {

        // Inscription étape 1 - Rate limit modéré
        Route::post('register', [AuthController::class, 'registerStepOne']); // 10 tentatives par minute

        // Inscription étape 2 (compléter le profil) - Rate limit modéré
        Route::post('complete-profile', [AuthController::class, 'registerStepTwo'])
            ->middleware('throttle:10,1');

        // Connexion - Rate limit STRICT pour prévenir brute-force
        Route::post('login', [AuthController::class, 'login'])->name('login');

        // Refresh token - Rate limit pour prévenir les abus
        // Route publique car le user n'a plus d'access token valide
        Route::post('refresh-token', [AuthController::class, 'refreshToken']);
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

    Route::middleware(['throttle:otp'])->group(function () {
        // Mot de passe oublié - Rate limit STRICT
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('verify-reset-otp', [AuthController::class, 'verifyResetOtp']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
        // Vérification OTP
        Route::post('verify-email', [AuthController::class, 'verifyRegistrationOtp']);
        // Renvoyer un code OTP
        Route::post('resend-code', [AuthController::class, 'resendOtp']);
    });

    // =================================================================
    // GESTION DE COMPTE - ROUTES PROTÉGÉES - AUTHENTIFICATION REQUISE
    // =================================================================
    Route::middleware(['auth:sanctum', 'check.token.expiration', 'throttle:api'])->group(function () {
        // Informations utilisateur
        Route::get('user/me', [AuthController::class, 'user']);
        // Suprimer son compte (utilisateur)
        Route::delete('user/me', [AuthController::class, 'deleteUser']);
        // Mise à jour du profil
        Route::put('update-profile', [AuthController::class, 'updateProfile']);
        // Changement de mot de passe
        Route::post('change-password', [AuthController::class, 'changePassword']);
        // Upload avatar
        Route::post('upload-avatar', [AuthController::class, 'uploadAvatar']);
        // Déconnexion
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
    });
});
