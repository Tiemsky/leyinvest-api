<?php

use App\Http\Controllers\Api\V1\Admin\CouponManagementController;
use App\Http\Controllers\Api\V1\Admin\FeatureManagementController;
use App\Http\Controllers\Api\V1\Admin\MaintenanceController;
use App\Http\Controllers\Api\V1\Admin\PlanManagementController;
use App\Http\Controllers\Api\V1\Admin\SubscriptionManagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/admin')
    ->middleware(['auth:sanctum', 'check.token.expiration', 'role:admin', 'throttle:60,1'])
    ->group(function () {

        // ============================================
        // MAINTENANCE SYSTEME - SUPRESSION DES COMPTES NON COMPLET MANUELLEMENT
        // ============================================
        Route::post('maintenance/cleanup-incomplete-registrations', [MaintenanceController::class, 'cleanupIncompleteRegistrations']);

        // ============================================
        // GESTION DES PLANS ET FEATURES (API RESOURCE SIMPLIFIEE)
        // ============================================
        Route::apiResource('plans', PlanManagementController::class);

        // ============================================
        // ROUTES SPECIFIQUES POUR LES FEATURES LIEES A UN PLAN DONNEE
        // ============================================
        Route::post('plans/{plan}/features', [PlanManagementController::class, 'attachFeatures']);
        Route::delete('plans/{plan}/features/{feature_id}', [PlanManagementController::class, 'detachFeature']);
        Route::patch('plans/{plan}/features/{feature_id}', [PlanManagementController::class, 'updateFeature']);
        Route::post('plans/{plan}/toggle-visibility', [PlanManagementController::class, 'toggleVisibility']);

        // ============================================
        // GESTION DES FEATURES GLOBABLES DES PLANS (CRUD)
        // ============================================
        Route::apiResource('features', FeatureManagementController::class)->except(['destroy']);

        // ============================================
        // GESTION DES COUPONS
        // ============================================
        Route::apiResource('coupons', CouponManagementController::class);
        Route::post('coupons/{coupon}/activate', [CouponManagementController::class, 'activate']);
        Route::post('coupons/{coupon}/deactivate', [CouponManagementController::class, 'deactivate']);
        Route::get('coupons/stats/overview', [CouponManagementController::class, 'stats']);

        // ============================================
        // GESTION DES SOUSCRIPTIONS
        // ============================================
        Route::apiResource('subscriptions', SubscriptionManagementController::class)->except(['store', 'update']);
        Route::post('subscriptions/{subscription}/cancel', [SubscriptionManagementController::class, 'cancel']);
        Route::post('subscriptions/{subscription}/mark-paid', [SubscriptionManagementController::class, 'markAsPaid']);
        Route::get('subscriptions/stats/overview', [SubscriptionManagementController::class, 'stats']);
    });
