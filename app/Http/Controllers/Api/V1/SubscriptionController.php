<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use App\Services\CouponService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;

/**
 * @tags Souscription
*/
class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected CouponService $couponService,
        protected PaymentService $paymentService
    ) {
    }

    // --- HELPER CENTRALISÉ DE GESTION DES EXCEPTIONS (Légèrement nettoyé) ---
    private function handleException(Exception $e, string $defaultMessage): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $status = ($e->getCode() >= 400 && $e->getCode() < 500) ? $e->getCode() : 500;
        $message = config('app.debug') && $status === 500 ? $e->getMessage() : $defaultMessage;

        return response()->json([
            'success' => false,
            'message' => $message,
            'debug_error' => config('app.debug') ? $e->getMessage() : null
        ], $status);
    }

    /**
     * GET /api/v1/subscriptions/current
     * Récupère les détails de l'abonnement actif de l'utilisateur.
     */
    public function current(Request $request): JsonResponse
    {
        // Ajout de 'coupon' pour un Eager Loading complet
        $subscription = $request->user()->activeSubscription()->with('plan', 'coupon')->first();
        if (!$subscription) {
            return response()->json(['success' => true, 'message' => 'Aucun abonnement actif.', 'data' => null]);
        }
        return response()->json(['success' => true, 'data' => new SubscriptionResource($subscription)]);
    }

    /**
     * POST /api/v1/subscriptions/subscribe
     * Crée un nouvel abonnement (état PENDING ou TRIALING).
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_slug' => ['required', 'string', 'exists:plans,slug'],
            'coupon_code' => ['nullable', 'string'],
            'payment_method' => ['nullable', 'string'],
        ]);

        try {
            // S'assurer que le plan est actif
            $plan = Plan::where('slug', $validated['plan_slug'])->where('is_active', true)->firstOrFail();

            // Le service crée la Subscription (PENDING ou TRIALING)
            $subscription = $this->subscriptionService->subscribe($request->user(), $plan, $validated);

            $message = $subscription->isTrialing()
                ? 'Période d\'essai activée.'
                : ($subscription->isActive() ? 'Abonnement activé.' : 'Abonnement en attente de paiement. Veuillez procéder au paiement.');

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => new SubscriptionResource($subscription),
                'next_step' => $subscription->isTrialing() || $subscription->isActive() ? 'done' : 'payment_link',
            ], 201);

        } catch (Exception $e) {
            return $this->handleException($e, 'Erreur lors de la création de l\'abonnement.');
        }
    }

    /**
     * POST /api/v1/subscriptions/payment-link
     * Récupère le lien de paiement (URL de redirection) pour une souscription PENDING.
     */

    public function getPaymentLink(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // Utiliser l'ID de la souscription créée à l'étape 'subscribe'
            'subscription_id' => ['required', 'integer', 'exists:subscriptions,id'],
            'return_url' => ['required', 'url'], // URL de redirection après succès/échec
            'cancel_url' => ['nullable', 'url'],
            'provider' => ['nullable', 'string'], // Ex: 'stripe', 'fedapay'. Si non spécifié, utiliser le défaut.
        ]);

        try {
            /** @var \App\Models\Subscription $subscription */
            $subscription = Subscription::with('plan')->findOrFail($validated['subscription_id']);

            // Sécurité : Vérifier l'appartenance
            if ($subscription->user_id !== $request->user()->id) {
                throw new Exception('Vous n\'êtes pas autorisé à payer pour cette souscription.', 403);
            }

            // Vérification du statut de la souscription (doit être PENDING ou UNPAID)
            if ($subscription->status !== Subscription::STATUS_PENDING) {
                throw new Exception('Cette souscription n\'est pas en attente de paiement.', 409); // Conflit
            }

            // Déléger au service de paiement pour la génération du lien
            $paymentLink = $this->paymentService->generateCheckoutLink(
                $subscription,
                $validated['return_url'],
                $validated['cancel_url'] ?? null,
                $validated['provider'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Lien de paiement généré.',
                'data' => [
                    'checkout_url' => $paymentLink,
                    'subscription_id' => $subscription->id,
                ],
            ]);

        } catch (Exception $e) {
            return $this->handleException($e, 'Erreur lors de la génération du lien de paiement.');
        }
    }

    /**
     * POST /api/v1/subscriptions/change-plan
     * Gère l'Upgrade et le Downgrade.
     */
    public function changePlan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_slug' => ['required', 'string', 'exists:plans,slug'],
            'coupon_code' => ['nullable', 'string'],
        ]);

        // Eager Loading de la relation 'plan' pour la comparaison de prix
        $currentSubscription = $request->user()->activeSubscription()->with('plan')->first();

        if (!$currentSubscription) {
            return response()->json(['success' => false, 'message' => 'Aucun abonnement actif à modifier.'], 404);
        }

        try {
            $newPlan = Plan::where('slug', $validated['plan_slug'])->where('is_active', true)->firstOrFail();
            // Délégué au service
            $updatedSubscription = $this->subscriptionService->changePlan($currentSubscription, $newPlan, $validated);
            // La comparaison des prix est plus sûre dans le service, mais peut être conservée ici pour le message
            $isUpgrade = $newPlan->prix > $currentSubscription->plan->prix;

            return response()->json([
                'success' => true,
                'message' => $isUpgrade
                    ? 'Plan mis à niveau (paiement prorata requis).'
                    : 'Le changement de plan est planifié pour la fin de la période.',
                'data' => new SubscriptionResource($updatedSubscription),
            ]);

        } catch (Exception $e) {
            return $this->handleException($e, 'Erreur lors du changement de plan.');
        }
    }

    /**
     * POST /api/v1/subscriptions/cancel
     */

    public function cancel(Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
            // Optionnel : permet d'annuler immédiatement ou à la fin de la période
            'immediately' => 'nullable|boolean',
        ]);
        $subscription = $request->user()->activeSubscription;

        if (!$subscription) {
            return response()->json(['success' => false, 'message' => 'Aucun abonnement actif.'], 404);
        }

        try {
            $this->subscriptionService->cancel($subscription, $request->reason, $request->boolean('immediately')); // Passage de l'option

            return response()->json([
                'success' => true,
                'message' => $request->boolean('immediately') ? 'Abonnement annulé immédiatement.' : 'Abonnement annulé à la fin de la période.',
                'data' => new SubscriptionResource($subscription->refresh()),
            ]);

        } catch (Exception $e) {
            return $this->handleException($e, 'Erreur lors de l\'annulation.');
        }
    }

    /**
     * POST /api/v1/subscriptions/resume
     * Permet de reprendre une souscription annulée dans sa période de grâce.
     */
    public function resume(Request $request): JsonResponse
    {
        // On cherche la souscription annulée qui est encore dans sa période de grâce.
        $subscription = $request->user()
            ->subscriptions()
            ->whereNotNull('canceled_at')
            ->where('ends_at', '>', now())
            ->latest()
            ->first();

        if (!$subscription || !$subscription->isCanceled()) {
            return response()->json(['success' => false, 'message' => 'Aucun abonnement annulé éligible à la reprise.'], 404);
        }

        try {
            $this->subscriptionService->resume($subscription);

            return response()->json([
                'success' => true,
                'message' => 'Abonnement réactivé avec succès.',
                'data' => new SubscriptionResource($subscription->refresh()),
            ]);

        } catch (Exception $e) {
            return $this->handleException($e, 'Erreur lors de la réactivation.');
        }
    }

    /**
     * POST /api/v1/subscriptions/validate-coupon
     * Valide un code promo pour un plan donné et retourne la réduction.
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'coupon_code' => 'required|string',
            'plan_slug' => 'required|string|exists:plans,slug',
        ]);

        try {
            $plan = Plan::where('slug', $validated['plan_slug'])->firstOrFail();
            $result = $this->couponService->validate($validated['coupon_code'], $plan->id);

            // J'ai gardé votre astuce d'exception, mais idéalement, on devrait utiliser un message d'erreur 400
            if (!$result['valid']) {
                throw new Exception($result['message'], 400);
            }

            $simulation = $this->couponService->calculateDiscount($result['coupon'], (float) $plan->prix);

            return response()->json([
                'success' => true,
                'message' => 'Coupon valide et réduction appliquée.',
                'data' => [
                    'code' => $result['coupon']->code,
                    'original_price' => $simulation['original_amount'],
                    'final_price' => $simulation['final_amount'],
                    'discount_amount' => $simulation['discount'],
                    'currency' => 'XOF',
                ],
            ]);
        } catch (Exception $e) {
            // Utilisation de la gestion centralisée des erreurs
            $defaultMessage = $e->getCode() === 400 ? $e->getMessage() : 'Erreur lors de la validation du coupon.';
            return $this->handleException($e, $defaultMessage);
        }
    }
}
