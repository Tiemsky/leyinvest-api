<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


/**
 * @OA\Schema(
 *     schema="BocIndicatorResource",
 *     type="object",
 *     title="Rapport des indicateurs via la ressource BocIndicatorResource",
 *     description="Structure d’un rapport d'indicateur financier renvoyé par l’API",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="date_rapport", type="string", format="date", example="2025-10-24"),
 *     @OA\Property(property="taux_rendement_moyen", type="number", format="float", example=7.29),
 *     @OA\Property(property="per_moyen", type="number", format="float", example=13.14),
 *     @OA\Property(property="taux_rentabilite_moyen", type="number", format="float", example=9.07),
 *     @OA\Property(property="prime_risque_marche", type="number", format="float", example=1.86),
 *     @OA\Property(property="source_pdf", type="string", example="https://www.brvm.org/sites/default/files/boc_20251024_2.pdf"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-27 10:19:38")
 * )
 */
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
            'id' => $this->id,
            'date_rapport' => $this->date_rapport,
            'taux_rendement_moyen' => (float) $this->taux_rendement_moyen,
            'per_moyen' => (float) $this->per_moyen,
            'taux_rentabilite_moyen' => (float) $this->taux_rentabilite_moyen,
            'prime_risque_marche' => (float) $this->prime_risque_marche,
            'source_pdf' => $this->source_pdf,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
