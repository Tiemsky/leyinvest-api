<?php

use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\PaymentWebhookController;
use App\Http\Controllers\Api\V1\PlanController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Middleware\VerifyWebhookSignature;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ============================================
    // 1. ROUTES PUBLIQUES - Plans & Features  - Throttle standard
    // ============================================
    Route::prefix('plans')->middleware('throttle:api')->group(function () {
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
        ->middleware([VerifyWebhookSignature::class])
        ->name('webhooks.payment');

    // ============================================
    // 3. ROUTES UTILISATEUR AUTHENTIFIÉ
    // ============================================
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

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

        // ============================================
        // FACTURE (INVOICE)
        // ============================================
        Route::prefix('invoices')->group(function () {
            // [GET] /api/v1/invoices : Liste des factures de l'utilisateur
            Route::get('/', [InvoiceController::class, 'index']);
            // [GET] /api/v1/invoices/{invoiceNumber} : Détails/Téléchargement d'une facture
            Route::get('/{invoiceNumber}', [InvoiceController::class, 'show']);
        });
    });
});
