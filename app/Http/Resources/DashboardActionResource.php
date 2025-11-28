<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource pour formater la réponse du dashboard
 */
class DashboardActionResource extends JsonResource
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
                'description' => $this->resource['action']['description'] ?? null,
                'secteurs' => [
                    'brvm' => $this->resource['action']['brvm_sector'] ?? null,
                    'reclasse' => $this->resource['action']['classified_sector'] ?? null,
                ],
                'cours_actuel' => $this->resource['action']['cours_actuel'] ?? null,
            ],

            'periode' => [
                'year' => $this->resource['year'] ?? null,
                'horizon' => $this->resource['horizon'] ?? null,
            ],

            'presentation' => $this->resource['presentation'] ?? null,

            'bilan' => $this->resource['bilan'] ?? null,

            'compte_resultat' => $this->resource['compte_resultat'] ?? null,

            'indicateurs' => [
                'croissance' => $this->formatCategory($this->resource['indicateurs']['croissance'] ?? []),
                'rentabilite' => $this->formatCategory($this->resource['indicateurs']['rentabilite'] ?? []),
                'remuneration' => $this->formatCategory($this->resource['indicateurs']['remuneration'] ?? []),
                'valorisation' => $this->formatCategory($this->resource['indicateurs']['valorisation'] ?? []),
                'solidite_financiere' => $this->formatCategory($this->resource['indicateurs']['solidite_financiere'] ?? []),
            ],

            'sector_type' => $this->resource['sectorType'] ?? null,

            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'cache_ttl' => config('financial_indicators.cache.ttl'),
            ],
        ];
    }

    /**
     * Formate une catégorie d'indicateurs
     */
    private function formatCategory(array $indicators): array
    {
        $formatted = [];

        foreach ($indicators as $code => $data) {
            $formatted[$code] = [
                'valeur' => $data['valeur'] ?? null,
                'formatted' => $data['formatted'] ?? null,
                'benchmarks' => [
                    'secteur_brvm' => $data['moy_secteur_brvm'] ?? null,
                    'secteur_reclasse' => $data['moy_sr'] ?? null,
                ],
            ];
        }

        return $formatted;
    }
}
