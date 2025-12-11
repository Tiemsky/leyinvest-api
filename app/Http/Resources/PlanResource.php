<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'name' => $this->nom,
            'slug' => $this->slug,
            'price' => (float) $this->prix,
            'billing_cycle' => $this->billing_cycle,
            'features' => $this->features, // Déjà un array si tu casts en JSON dans le modèle
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
