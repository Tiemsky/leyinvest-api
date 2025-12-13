<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\PlanController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\PaymentWebhookController;

// Importation des contrôleurs Admin pour la clarté (même s'ils sont commentés)
use App\Http\Controllers\Api\V1\Admin\PlanManagementController;
use App\Http\Controllers\Api\V1\Admin\FeatureManagementController;
use App\Http\Controllers\Api\V1\Admin\CouponManagementController;
use App\Http\Controllers\Api\V1\Admin\SubscriptionManagementController;


Route::prefix('v1')->group(function () {

    // ============================================
    // 1. ROUTES PUBLIQUES - Plans & Features
    // ============================================
    Route::prefix('plans')->group(function () {
        // [GET] /api/v1/plans : Liste de tous les plans publics
        Route::get('/', [PlanController::class, 'index']);
        // [GET] /api/v1/plans/{slug} : Détails d'un plan spécifique
        Route::get('/{slug}', [PlanController::class, 'show']);
    });


    // ============================================
    // 2. WEBHOOKS PAIEMENT (pas d'authentification)
    // ============================================
    // Les webhooks sont appelés par les opérateurs de paiement (Stripe, Fedapay, etc.)
    // La route doit gérer l'authentification du Webhook via la signature (géré dans le contrôleur).
    Route::post('/webhooks/payment/{provider}', [PaymentWebhookController::class, 'handleWebhook'])
        ->name('webhooks.payment');


    // ============================================
    // 3. ROUTES UTILISATEUR AUTHENTIFIÉ
    // ============================================
    Route::middleware(['auth:sanctum'])->group(function () {

        // --- Abonnements (Subscriptions) ---
        Route::prefix('subscriptions')->group(function () {

            // [GET] /api/v1/subscriptions/current : Détails de l'abonnement actif
            Route::get('/current', [SubscriptionController::class, 'current']);

            // [POST] /api/v1/subscriptions/subscribe : Crée une nouvelle souscription (commande).
            // C'est la première étape, qui peut créer une Subscription en statut 'pending'.
            Route::post('/subscribe', [SubscriptionController::class, 'subscribe']);

            // [POST] /api/v1/subscriptions/payment-link : Génère le lien/session de paiement pour une souscription existante (status: pending).
            Route::post('/payment-link', [SubscriptionController::class, 'getPaymentLink']);

            // [POST] /api/v1/subscriptions/change-plan : Modification du plan (upgrade/downgrade).
            Route::post('/change-plan', [SubscriptionController::class, 'changePlan']);

            // [POST] /api/v1/subscriptions/validate-coupon : Vérifie et applique un coupon AVANT de souscrire/payer.
            Route::post('/validate-coupon', [SubscriptionController::class, 'validateCoupon']);

            // [POST] /api/v1/subscriptions/cancel : Annule l'abonnement (à la fin de la période, ou immédiatement).
            // Le corps de la requête détermine si l'annulation est immédiate ou différée.
            Route::post('/cancel', [SubscriptionController::class, 'cancel']);

            // [POST] /api/v1/subscriptions/resume : Réactive un abonnement annulé/en pause.
            Route::post('/resume', [SubscriptionController::class, 'resume']);
        });

        // --- Factures (Invoices) ---
        Route::prefix('invoices')->group(function () {
            // [GET] /api/v1/invoices : Liste des factures de l'utilisateur
            Route::get('/', [InvoiceController::class, 'index']);
            // [GET] /api/v1/invoices/{invoiceNumber} : Détails/Téléchargement d'une facture
            Route::get('/{invoiceNumber}', [InvoiceController::class, 'show']);
        });
    });


    // ============================================
    // 4. ROUTES ADMIN (Standardisation API Resource)
    // ============================================
    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {

        // Gestion des plans et features (API Resource simplifiée)
        Route::apiResource('plans', PlanManagementController::class);

        // Routes spécifiques pour les features liées à un plan
        Route::post('plans/{plan}/features', [PlanManagementController::class, 'attachFeatures']);
        Route::delete('plans/{plan}/features/{feature_id}', [PlanManagementController::class, 'detachFeature']); // Préférer l'ID de la feature
        Route::patch('plans/{plan}/features/{feature_id}', [PlanManagementController::class, 'updateFeature']);
        Route::post('plans/{plan}/toggle-visibility', [PlanManagementController::class, 'toggleVisibility']);

        // Gestion des features globales (définitions)
        Route::apiResource('features', FeatureManagementController::class)->except(['destroy']); // 'destroy' est souvent géré par la base de données

        // Gestion des coupons
        Route::apiResource('coupons', CouponManagementController::class);
        Route::post('coupons/{coupon}/activate', [CouponManagementController::class, 'activate']);
        Route::post('coupons/{coupon}/deactivate', [CouponManagementController::class, 'deactivate']);
        Route::get('coupons/stats/overview', [CouponManagementController::class, 'stats']); // Changement de format pour les stats

        // Gestion des souscriptions
        Route::apiResource('subscriptions', SubscriptionManagementController::class)->except(['store', 'update']); // L'Admin ne crée/modifie pas les subs directement
        Route::post('subscriptions/{subscription}/cancel', [SubscriptionManagementController::class, 'cancel']);
        Route::post('subscriptions/{subscription}/mark-paid', [SubscriptionManagementController::class, 'markAsPaid']);
        Route::get('subscriptions/stats/overview', [SubscriptionManagementController::class, 'stats']);
    });
});
