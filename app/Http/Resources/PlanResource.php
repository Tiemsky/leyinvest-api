<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class PlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Utilisation de la méthode when() pour inclure la vérification 'is_current'
        // uniquement si l'utilisateur est connecté.
        $isCurrentLogic = $this->when(Auth::check(), function () use ($request) {
            // NOTE: C'est une vérification simplifiée. Dans un système complet,
            // vous devriez vérifier si le plan correspond à l'activeSubscription de l'utilisateur.
            $user = $request->user();

            return $user->activeSubscription?->plan_id === $this->id;
        });

        return [
            'id' => (int) $this->id,
            'key' => (string) $this->key,
            'name' => (string) $this->nom,
            'slug' => (string) $this->slug,
            // 3. Tarification
            'price' => (float) $this->prix,
            'billing_cycle' => (string) $this->billing_cycle,
            'trial_days' => (int) $this->trial_days,

            // 4. Statut et Contexte Utilisateur
            'status' => [
                'is_active' => (bool) $this->is_active,
                'is_visible' => (bool) $this->is_visible,
                'is_free' => (bool) $this->isFree(),
                'is_current' => (bool) $isCurrentLogic, // Booléen ou absent
            ],

            // 5. Fonctionnalités (Conditionnel pour éviter N+1 Query)
            'features' => $this->whenLoaded('activeFeatures', function () {
                // Utilisation de la relation 'activeFeatures' optimisée
                return $this->activeFeatures->map(function ($feature) {
                    return [
                        'name' => (string) $feature->name,
                        'enabled' => (bool) $feature->pivot->is_enabled,
                    ];
                });
            }),

            // 6. Métadonnées (optionnel)
            'created_at' => ($this->created_at?->toISOString()) ?? null,
        ];
    }
}
