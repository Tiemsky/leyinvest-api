<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'id' => (int) $this->id,
            'key' => (string) $this->key,
            'status' => (string) $this->status,
            'plan' => new ShortPlanResource($this->whenLoaded('plan')),
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
                'is_active' => (bool) $this->isActive(),
                'is_trialing' => (bool) $this->isTrialing(),
                'is_canceled' => (bool) $this->isCanceled(),
                // Correction: Si 'hasEnded()' n'est pas dans le modèle, le calculer ici ou le créer dans le modèle
                'is_ended' => (bool) $this->hasEnded(),
                'on_grace_period' => (bool) $this->onGracePeriod(),
            ],

            // 5. Données Financières
            'financial' => [
                'amount_paid' => (float) $this->amount_paid,
                'currency' => (string) $this->currency ?? 'XOF',
                'payment_status' => (string) $this->payment_status,
                'payment_method' => (string) $this->payment_method,
                'coupon_code' => $this->whenLoaded('coupon', fn() => $this->coupon->code),
            ],
            // 6. Métadonnées
            'details' => $this->metadata ?? [],
        ];
    }
}
