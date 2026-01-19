<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // On récupère l'abonnement actif s'il est chargé
        $activeSubscription = $this->whenLoaded('activeSubscription');

        return [
            'id' => (int) $this->id,
            'nom' => (string) $this->nom,
            'prenom' => (string) $this->prenom,
            'email' => (string) $this->email,
            'country' => (string) $this->country,
            'age' => (int) $this->age,
            'genre' => (string) $this->genre,
            'situation_professionnelle' => (string) $this->situation_professionnelle,
            'numero' => (string) $this->numero,
            'whatsapp' => (string) $this->whatsapp,
            'email_verified' => (bool) $this->email_verified,
            'registration_completed' => (bool) $this->registration_completed,
            'role' => (string) $this->role,
            // L'abonnement est inclus uniquement s'il existe ET qu'il est chargé (N+1 safe)
            'subscription' => $this->when($activeSubscription, function () use ($activeSubscription) {
                // Utilise la SubscriptionResource définie précédemment pour le formatage
                return new SubscriptionResource($activeSubscription);
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
