<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\PlanController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\PaymentWebhookController;
use App\Http\Controllers\Api\V1\Admin\PlanManagementController;
use App\Http\Controllers\Api\V1\Admin\FeatureManagementController;

/**
 * Routes de gestion des abonnements et plans
 */

Route::prefix('v1')->group(function () {

    // ============================================
    // ROUTES PUBLIQUES - Plans
    // ============================================
    Route::prefix('plans')->group(function () {
        Route::get('/', [PlanController::class, 'index']); // Liste des plans
        Route::get('/{slug}', [PlanController::class, 'show']); // Détails d'un plan
    });

    // ============================================
    // ROUTES UTILISATEUR AUTHENTIFIÉ
    // ============================================
    Route::middleware(['auth:sanctum'])->group(function () {

        // Abonnements (Subscriptions)
        Route::prefix('subscriptions')->group(function () {
            Route::get('/current', [SubscriptionController::class, 'current']); // Abonnement actif
            Route::post('/subscribe', [SubscriptionController::class, 'subscribe']); // Souscrire à un plan
            Route::post('/payment-link', [SubscriptionController::class, 'getPaymentLink']); // Obtenir le lien de paiement
            Route::post('/change-plan', [SubscriptionController::class, 'changePlan']); // Changer de plan
            Route::post('/cancel', [SubscriptionController::class, 'cancel']); // Annuler
            Route::post('/resume', [SubscriptionController::class, 'resume']); // Réactiver
            Route::post('/validate-coupon', [SubscriptionController::class, 'validateCoupon']); // Valider un coupon
        });

        // Factures (Invoices)
        Route::prefix('invoices')->group(function () {
            Route::get('/', [InvoiceController::class, 'index']); // Liste des factures
            Route::get('/{invoiceNumber}', [InvoiceController::class, 'show']); // Détails d'une facture
        });
    });

    // ============================================
    // WEBHOOKS PAIEMENT (pas d'authentification)
    // ============================================
    // Les webhooks sont appelés par les opérateurs de paiement (Stripe, Fedapay, etc.)
    Route::post('/webhooks/payment/{provider}', [PaymentWebhookController::class, 'handleWebhook'])
        ->name('webhooks.payment');

    // ============================================
    // ROUTES ADMIN (nécessite le rôle admin)
    // ============================================
    // Décommentez quand vous aurez créé le middleware 'role:admin'
    /*
    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {

        // Gestion des features
        Route::apiResource('features', FeatureManagementController::class);

        // Gestion des plans
        Route::apiResource('plans', PlanManagementController::class);
        Route::post('plans/{plan}/features', [PlanManagementController::class, 'attachFeatures']);
        Route::delete('plans/{plan}/features', [PlanManagementController::class, 'detachFeatures']);
        Route::patch('plans/{plan}/features/{feature}', [PlanManagementController::class, 'updateFeature']);
        Route::post('plans/{plan}/toggle-visibility', [PlanManagementController::class, 'toggleVisibility']);

        // Gestion des coupons
        Route::apiResource('coupons', \App\Http\Controllers\Api\V1\Admin\CouponManagementController::class);
        Route::post('coupons/{coupon}/activate', [\App\Http\Controllers\Api\V1\Admin\CouponManagementController::class, 'activate']);
        Route::post('coupons/{coupon}/deactivate', [\App\Http\Controllers\Api\V1\Admin\CouponManagementController::class, 'deactivate']);
        Route::get('coupons/{coupon}/stats', [\App\Http\Controllers\Api\V1\Admin\CouponManagementController::class, 'stats']);

        // Gestion des souscriptions
        Route::get('subscriptions', [\App\Http\Controllers\Api\V1\Admin\SubscriptionManagementController::class, 'index']);
        Route::get('subscriptions/{subscription}', [\App\Http\Controllers\Api\V1\Admin\SubscriptionManagementController::class, 'show']);
        Route::post('subscriptions/{subscription}/cancel', [\App\Http\Controllers\Api\V1\Admin\SubscriptionManagementController::class, 'cancel']);
        Route::post('subscriptions/{subscription}/mark-paid', [\App\Http\Controllers\Api\V1\Admin\SubscriptionManagementController::class, 'markAsPaid']);
        Route::get('subscriptions/stats/overview', [\App\Http\Controllers\Api\V1\Admin\SubscriptionManagementController::class, 'stats']);
    });
    */
});
