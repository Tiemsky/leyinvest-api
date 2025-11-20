<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="ActionResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="key", type="string", example="act_abc123def"),
 *     @OA\Property(property="symbole", type="string", example="ABJC"),
 *     @OA\Property(property="nom", type="string", example="SERVAIR ABIDJAN CÔTE D’IVOIRE"),
 *     @OA\Property(property="volume", type="integer", example=2711),
 *     @OA\Property(property="cours_veille", type="number", format="float", example=2130.0),
 *     @OA\Property(property="cours_ouverture", type="number", format="float", example=2155.0),
 *     @OA\Property(property="cours_cloture", type="number", format="float", example=2130.0),
 *     @OA\Property(property="variation", type="number", format="float", example=-7.39),
 *     @OA\Property(property="couleur_variation", type="string", example="red"),
 *     @OA\Property(property="isAuthUserFollow", type="boolean", example=false),
 *     @OA\Property(
 *         property="brvm_sector",
 *         type="object",
 *         ref="#/components/schemas/BrvmSectorResource"
 *     ),
 *     @OA\Property(
 *         property="classified_sector",
 *         type="object",
 *         ref="#/components/schemas/ClassifiedSectorResource"
 *     )
 * )
 */
class ActionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $variation = (float) $this->variation;
        $couleur = match (true) {
            $variation > 0 => 'green',
            $variation < 0 => 'red',
            default => 'gray',
        };

        // Récupère les IDs suivis (si fournis via `with()` dans le contrôleur)
        $userFollows = $this->resource->whenLoaded('followers')
            ? $this->resource->followers->pluck('id')->all()
            : ($this->followedIds ?? []);

        $isAuthUserFollow = in_array($this->id, $userFollows);

        return [
            'id' => $this->id,
            'key' => $this->key,
            'symbole' => $this->symbole,
            'nom' => $this->nom,
            'volume' => $this->volume,
            'cours_veille' => (float) $this->cours_veille,
            'cours_ouverture' => (float) $this->cours_ouverture,
            'cours_cloture' => (float) $this->cours_cloture,
            'variation' => $variation,
            'variation_formatted' => $variation >= 0
                ? '+' . number_format($variation, 2) . '%'
                : number_format($variation, 2) . '%',
            'couleur_variation' => $couleur,
            'isAuthUserFollow' => $isAuthUserFollow,

            // Relations conditionnelles
            'secteur_brvm' => $this->whenLoaded('brvmSector', function () {
                return [
                    'id' => $this->brvmSector->id,
                    'nom' => $this->brvmSector->nom,
                    'slug' => $this->brvmSector->slug,
                ];
            }),

            'secteur_reclassifie' => $this->whenLoaded('classifiedSector', function () {
                return [
                    'id' => $this->classifiedSector->id,
                    'nom' => $this->classifiedSector->nom,
                    'slug' => $this->classifiedSector->slug,
                ];
            }),

            'actionnaires' => $this->whenLoaded('shareholders', function () {
                return $this->shareholders->map(function ($shareholder) {
                    return [
                        'id' => $shareholder->id ?? null,
                        'nom' => $shareholder->nom ?? '',
                        'pourcentage' => (float) ($shareholder->percentage ?? 0),
                        'rang' => $shareholder->rang ?? null,
                    ];
                })->values()->all(); // `values()` pour réindexer en 0,1,2...
            }),

            'employees' => $this->whenLoaded('employees', function () {
                return $this->employees->map(function ($employee) {
                    return [
                        'position' => $employee->position ? strtoupper($employee->position->nom) : '',
                        'nom' => $employee->nom ?? '',
                    ];
                })->values()->all();
            }),
        ];
    }
}
