<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource pour formater la réponse des données historiques
 */
class HistoricalActionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'action' => [
                'key' => $this->resource['action']['key'] ?? null,
                'symbole' => $this->resource['action']['symbole'] ?? null,
                'nom' => $this->resource['action']['nom'] ?? null,
                'secteurs' => [
                    'brvm' => $this->resource['action']['brvm_sector'] ?? null,
                    'reclasse' => $this->resource['action']['classified_sector'] ?? null,
                ],
            ],

            'periode' => [
                'start_year' => $this->resource['periode']['start_year'] ?? null,
                'end_year' => $this->resource['periode']['end_year'] ?? null,
                'years' => $this->resource['periode']['years'] ?? [],
                'duration' => count($this->resource['periode']['years'] ?? []),
            ],

            'categories' => [
                'croissance' => $this->formatHistoricalCategory(
                    $this->resource['categories']['croissance'] ?? []
                ),
                'rentabilite' => $this->formatHistoricalCategory(
                    $this->resource['categories']['rentabilite'] ?? []
                ),
                'remuneration' => $this->formatHistoricalCategory(
                    $this->resource['categories']['remuneration'] ?? []
                ),
                'valorisation' => $this->formatHistoricalCategory(
                    $this->resource['categories']['valorisation'] ?? []
                ),
                'solidite_financiere' => $this->formatHistoricalCategory(
                    $this->resource['categories']['solidite_financiere'] ?? []
                ),
            ],

            'meta' => [
                'generated_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Formate une catégorie historique
     */
    private function formatHistoricalCategory(array $category): array
    {
        return [
            'indicators' => $category['indicators'] ?? [],
        ];
    }
}
