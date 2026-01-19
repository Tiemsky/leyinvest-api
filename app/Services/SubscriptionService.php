<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class SubscriptionService
{
    // Injection des deux services dépendants
    public function __construct(
        protected CouponService $couponService,
        protected InvoiceService $invoiceService
    ) {}

    /**
     * Crée une nouvelle souscription pour un utilisateur (première fois).
     */
    public function subscribe(User $user, Plan $plan, array $options = []): Subscription
    {
        if ($user->activeSubscription) {
            // Utilisation d'une exception HTTP spécifique
            throw new ConflictHttpException("L'utilisateur a déjà un abonnement actif. Utilisez 'changePlan'.");
        }

        return DB::transaction(function () use ($user, $plan, $options) {

            // 1. Calcul Financier et Coupon
            $subtotal = (float) $plan->prix;
            $coupon = null;
            $discount = $options['discount'] ?? 0; // Permet de passer un rabais calculé (prorata)

            if (isset($options['coupon_code'])) {
                $coupon = $this->validateAndRetrieveCoupon($options['coupon_code'], $user->id, $plan->id, $subtotal);
                $discount += $this->couponService->calculateDiscount($coupon, $subtotal)['discount'];
            }

            $total = max(0, $subtotal - $discount); // Le montant total ne doit jamais être négatif

            // 2. Calculer les dates et le statut
            $startsAt = $options['starts_at'] ?? now();
            $endsAt = $this->calculateEndDate($plan, $startsAt, $options);
            $trialEndsAt = $plan->trial_days > 0 ? Carbon::parse($startsAt)->addDays($plan->trial_days) : null;
            $status = $this->determineInitialStatus($plan, $trialEndsAt, $options);

            // 3. Créer la souscription
            $subscription = $user->subscriptions()->create([
                // 'key' doit être géré par un Trait HasUniqueKey dans le modèle Subscription
                'plan_id' => $plan->id,
                'status' => $status,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'trial_ends_at' => $trialEndsAt,
                'payment_method' => $options['payment_method'] ?? null,
                'payment_status' => $options['payment_status'] ?? ($plan->isFree() ? 'paid' : 'pending'),
                'amount_paid' => $total,
                'currency' => $options['currency'] ?? 'XOF', // Rendre la currency obligatoire en production
                'coupon_id' => $coupon?->id,
                // Assurer que le champ 'metadata' est toujours un tableau
                'metadata' => array_merge($options['metadata'] ?? [], ['subtotal' => $subtotal, 'discount' => $discount]),
            ]);

            // 4. Post-Création (Coupon & Facture)
            if ($coupon) {
                $coupon->incrementUsage();
            }

            // Créer une facture uniquement si ce n'est pas gratuit ET pas en essai
            if (! $plan->isFree() && $status !== 'trialing') {
                $this->invoiceService->createForSubscription($subscription, $subtotal, $discount, $coupon, [
                    'currency' => $options['currency'] ?? 'XOF',
                    'status' => $subscription->payment_status, // Statut hérité de l'abonnement
                ]);
            }

            return $subscription->load('plan', 'coupon');
        });
    }

    /**
     * Met à jour l'abonnement vers un nouveau plan (Upgrade ou Downgrade).
     */
    public function changePlan(Subscription $currentSubscription, Plan $newPlan, array $options = []): Subscription
    {
        if ($currentSubscription->plan_id === $newPlan->id) {
            throw new ConflictHttpException('Vous êtes déjà abonné à ce plan.');
        }

        // La logique d'Upgrade vs Downgrade devrait comparer les prix TTC ou la valeur
        $isUpgrade = $newPlan->prix > $currentSubscription->plan->prix;
        $user = $currentSubscription->user;

        return DB::transaction(function () use ($currentSubscription, $newPlan, $isUpgrade, $user, $options) {

            // Calcul du crédit (Prorata)
            $prorataCredit = 0.00;
            if ($currentSubscription->ends_at && $currentSubscription->ends_at->isFuture()) {
                $prorataCredit = $this->calculateProrataCredit($currentSubscription);
                $options['discount'] = ($options['discount'] ?? 0) + $prorataCredit;
            }

            $cancelReason = $isUpgrade
                ? "Upgrade vers {$newPlan->nom}. Crédit de {$prorataCredit} appliqué."
                : "Downgrade vers {$newPlan->nom}. L'ancien plan reste actif jusqu'à la fin de la période.";

            // 1. Gérer l'ancienne souscription
            if ($isUpgrade && $prorataCredit > 0) {
                // Upgrade : annulation immédiate pour libérer le crédit
                $currentSubscription->cancelImmediately($cancelReason);
            } else {
                // Downgrade : annulation planifiée (Downgrade effectif à la prochaine date de renouvellement)
                $currentSubscription->cancelAtPeriodEnd($cancelReason);
                // Si Downgrade, on ne crée pas la nouvelle souscription immédiatement,
                // on ne fait qu'annuler l'ancienne pour la fin de période.
                if (! $isUpgrade) {
                    throw new ConflictHttpException("Downgrade planifié. Le plan changera le {$currentSubscription->ends_at->toDateString()}.");
                }
            }

            // 2. Créer la nouvelle souscription (uniquement pour l'Upgrade)
            // Pour le Downgrade, la logique de subscription->cancelAtPeriodEnd() gère le futur
            if ($isUpgrade) {
                $newSubscription = $this->subscribe($user, $newPlan, $options);

                return $newSubscription;
            }

            return $currentSubscription; // Retourne l'ancienne pour l'info de planification
        });
    }

    // Ajout d'une méthode de validation interne qui intègre la limite utilisateur
    protected function validateAndRetrieveCoupon(string $couponCode, int $userId, int $planId, float $amount): Coupon
    {
        $validationResult = $this->couponService->validate($couponCode, $planId, $userId);

        if (! $validationResult['valid']) {
            // Utilisation du message du service coupon
            throw new Exception($validationResult['message'], 422);
        }

        return $validationResult['coupon'];
    }

    // Ajout d'une méthode de prorata simulée
    protected function calculateProrataCredit(Subscription $subscription): float
    {
        // Si l'abonnement est gratuit ou n'a pas de date de fin, pas de crédit
        if ($subscription->plan->isFree() || ! $subscription->ends_at) {
            return 0.00;
        }

        $paidDays = $subscription->starts_at->diffInDays($subscription->ends_at);
        $remainingDays = now()->diffInDays($subscription->ends_at);

        if ($paidDays <= 0 || $remainingDays <= 0) {
            return 0.00;
        }

        $dailyRate = $subscription->amount_paid / $paidDays;
        $credit = $dailyRate * $remainingDays;

        return round($credit, 2);
    }

    protected function calculateEndDate(Plan $plan, $startsAt, array $options = [])
    {
        // ... Logique calculateEndDate non modifiée ...
        if (isset($options['ends_at'])) {
            return $options['ends_at'];
        }
        if ($plan->isFree()) {
            return null;
        }

        $start = is_string($startsAt) ? Carbon::parse($startsAt) : $startsAt;

        return match ($plan->billing_cycle) {
            'monthly' => $start->copy()->addMonth(),
            'yearly' => $start->copy()->addYear(),
            'quarterly' => $start->copy()->addMonths(3),
            'weekly' => $start->copy()->addWeek(),
            default => null,
        };
    }

    protected function determineInitialStatus(Plan $plan, $trialEndsAt, array $options): string
    {
        // ... Logique determineInitialStatus non modifiée ...
        if (isset($options['status'])) {
            return $options['status'];
        }
        if ($trialEndsAt) {
            return 'trialing';
        }
        if ($plan->isFree()) {
            return 'active';
        }

        return 'pending';
    }
}
