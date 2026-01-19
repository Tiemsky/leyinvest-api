<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ShortPlanResource extends JsonResource
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
            'name' => (string) $this->nom,
            'slug' => (string) $this->slug,
            'price' => (float) $this->prix,
            'is_active' => (bool) $this->is_active,
            'is_current' => (bool) $isCurrentLogic, // Booléen ou absent
        ];
    }
}
