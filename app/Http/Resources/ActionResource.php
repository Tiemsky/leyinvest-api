<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
            return [
            'id' => $this->id,
            'key' => $this->key,
            'symbole' => $this->symbole,
            'nom' => $this->nom,
            'volume' => $this->volume,
            'volume_formate' => $this->volume_formate,
            'cours_veille' => (float) $this->cours_veille,
            'cours_ouverture' => (float) $this->cours_ouverture,
            'cours_cloture' => (float) $this->cours_cloture,
            'variation' => (float) $this->variation,
            'couleur_variation' => $this->couleur_variation,
            'categorie' => $this->categorie,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
