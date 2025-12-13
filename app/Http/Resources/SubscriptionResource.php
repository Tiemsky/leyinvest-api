<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 * schema="SubscriptionResource",
 * type="object",
 * title="Abonnement",
 * description="Détails complets de l'abonnement utilisateur.",
 * @OA\Property(property="id", type="integer", example=42),
 * @OA\Property(property="key", type="string", example="sub_x8s7s..."),
 * @OA\Property(property="status", type="string", enum={"active", "trialing", "canceled", "expired", "pending"}, example="active"),
 * @OA\Property(property="plan", ref="#/components/schemas/PlanResource"),
 * @OA\Property(
 * property="dates",
 * type="object",
 * @OA\Property(property="starts_at", type="string", format="date-time"),
 * @OA\Property(property="ends_at", type="string", format="date-time"),
 * @OA\Property(property="trial_ends_at", type="string", format="date-time", nullable=true),
 * @OA\Property(property="canceled_at", type="string", format="date-time", nullable=true),
 * @OA\Property(property="days_remaining", type="integer", example=25, description="Jours restants avant expiration/renouvellement")
 * ),
 * @OA\Property(
 * property="state",
 * type="object",
 * description="Indicateurs booléens pour la logique frontend",
 * @OA\Property(property="is_active", type="boolean"),
 * @OA\Property(property="is_trialing", type="boolean"),
 * @OA\Property(property="is_canceled", type="boolean"),
 * @OA\Property(property="on_grace_period", type="boolean")
 * ),
 * @OA\Property(
 * property="financial",
 * type="object",
 * @OA\Property(property="amount_paid", type="number", format="float", example=5000.00),
 * @OA\Property(property="currency", type="string", example="XOF"),
 * @OA\Property(property="payment_status", type="string", enum={"paid", "pending", "failed"}, example="paid"),
 * @OA\Property(property="coupon_code", type="string", nullable=true, example="PROMO2024")
 * ),
 * @OA\Property(
 * property="details",
 * type="object",
 * description="Métadonnées de calcul (sous-total, remise, etc.)",
 * example={"subtotal": 5000, "discount": 0}
 * )
 * )
 */
class SubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        // $this->resource est l'instance de App\Models\Subscription.
        // Toutes les méthodes comme $this->isActive() sont appelées sur le modèle.

        // Calcul pour is_ended
        $isEnded = $this->status === 'expired' || ($this->ends_at && $this->ends_at->isPast() && $this->status !== 'active');

        return [
            // 1. Identification
            'id' => $this->id,
            'key' => $this->key,
            'status' => $this->status,
            // 2. Relation Plan
            'plan' => new ShortPlanResource($this->whenLoaded('plan')),
            // 3. Gestion du Temps
            'dates' => [
                'created_at' => $this->created_at?->toIso8601String(),
                'starts_at' => $this->starts_at?->toIso8601String(),
                'ends_at' => $this->ends_at?->toIso8601String(),
                'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
                'canceled_at' => $this->canceled_at?->toIso8601String(),

                // Helper : Jours restants
                'days_remaining' => $this->ends_at && now()->lt($this->ends_at)
                    ? now()->diffInDays($this->ends_at)
                    : 0,
            ],

            // 4. États Logiques (Appels aux méthodes du Modèle)
            'state' => [
                'is_active' => $this->isActive(),
                'is_trialing' => $this->isTrialing(),
                'is_canceled' => $this->isCanceled(),
                // Correction: Si 'hasEnded()' n'est pas dans le modèle, le calculer ici ou le créer dans le modèle
                'is_ended' => $this->hasEnded(),
                'on_grace_period' => $this->onGracePeriod(),
            ],

            // 5. Données Financières
            'financial' => [
                'amount_paid' => (float) $this->amount_paid,
                'currency' => $this->currency ?? 'XOF',
                'payment_status' => $this->payment_status,
                'payment_method' => $this->payment_method,
                'coupon_code' => $this->whenLoaded('coupon', fn() => $this->coupon->code),
            ],
            // 6. Métadonnées
            'details' => $this->metadata ?? [],
        ];
    }
}
