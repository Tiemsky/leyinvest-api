<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BocIndicatorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return  [
            'id' => (int) $this->id,
            'date_rapport' => (string) $this->date_rapport,
            'taux_rendement_moyen' => (float) $this->taux_rendement_moyen,
            'per_moyen' => (float) $this->per_moyen,
            'taux_rentabilite_moyen' => (float) $this->taux_rentabilite_moyen,
            'prime_risque_marche' => (float) $this->prime_risque_marche,
            'source_pdf' => (string) $this->source_pdf,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
