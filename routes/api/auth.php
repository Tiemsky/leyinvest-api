<?php

namespace Routes\Api;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\GoogleAuthController;

Route::prefix('v1')->group(function () {

    // ============================================
    // ROUTES PUBLIQUES - AUTHENTIFICATION
    // ============================================
    Route::prefix('auth')->group(function () {
        // Inscription étape 1 - Rate limit modéré
        Route::post('register', [AuthController::class, 'registerStepOne'])
            ->middleware('throttle:10,1'); // 10 tentatives par minute

        // Inscription étape 2 (compléter le profil) - Rate limit modéré
        Route::post('complete-profile', [AuthController::class, 'registerStepTwo'])
            ->middleware('throttle:10,1');

        // Connexion - Rate limit STRICT pour prévenir brute-force
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:5,1'); // 5 tentatives par minute max

        // Refresh token - Rate limit pour prévenir les abus
        // Route publique car le user n'a plus d'access token valide
        Route::post('refresh-token', [AuthController::class, 'refreshToken'])
            ->middleware('throttle:10,1'); // 10 refresh par minute

        // Mot de passe oublié - Rate limit STRICT
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
            ->middleware('throttle:3,1'); // 3 tentatives par minute
        Route::post('verify-reset-otp', [AuthController::class, 'verifyResetOtp'])
            ->middleware('throttle:5,1'); // 5 vérifications par minute
        Route::post('reset-password', [AuthController::class, 'resetPassword'])
            ->middleware('throttle:3,1'); // 3 reset par minute



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

        Route::delete('user/me', [AuthController::class, 'deleteUser']);

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
