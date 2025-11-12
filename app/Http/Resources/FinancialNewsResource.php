<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


/**
 * @OA\Schema(
 *     schema="FinancialNews",
 *     type="object",
 *     title="Financial News",
 *     description="Modèle d'actualité financière",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="key", type="string", example="BRVM_2024_001"),
 *     @OA\Property(property="company", type="string", example="SONATEL"),
 *     @OA\Property(property="title", type="string", example="Résultats annuels 2024"),
 *     @OA\Property(property="pdf_url", type="string", example="https://example.com/document.pdf"),
 *     @OA\Property(property="source", type="string", example="BRVM"),
 *     @OA\Property(property="published_at", type="string", format="date", example="2024-12-01"),
 *     @OA\Property(property="published_at_human", type="string", example="il y a 2 jours"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */


class FinancialNewsResource extends JsonResource
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
            'action' => $this->company,
            'titre' => $this->title,
            'pdf_url' => $this->pdf_url,
            'source' => $this->source,
            'date_de_publication' => $this->published_at?->format('Y-m-d'),
            'temp_de_publication' => $this->published_at?->diffForHumans(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
