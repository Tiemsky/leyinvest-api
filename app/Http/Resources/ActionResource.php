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
    public function __construct(
        $resource,
        protected ?array $followedActionIds = null,
    ) {
        parent::__construct($resource);
    }

    public function toArray($request): array
    {
        $variation = (float) $this->resource->variation;
        $couleur = match (true) {
            $variation > 0 => 'green',
            $variation < 0 => 'red',
            default => 'gray',
        };

        // ✅ Utilisation du contexte injecté
        $isAuthUserFollow = $this->followedActionIds !== null
            ? in_array($this->resource->id, $this->followedActionIds, true)
            : false;

        return [
            'id' => $this->resource->id,
            'key' => $this->resource->key,
            'symbole' => $this->resource->symbole,
            'nom' => $this->resource->nom,
            'volume' => $this->resource->volume,
            'cours_veille' => (float) $this->resource->cours_veille,
            'cours_ouverture' => (float) $this->resource->cours_ouverture,
            'cours_cloture' => (float) $this->resource->cours_cloture,
            'variation' =>(float)  $variation,
            'variation_formatted' => $variation >= 0
                ? '+' . number_format($variation, 2) . '%'
                : number_format($variation, 2) . '%',
            'couleur_variation' => $couleur,
            'isAuthUserFollow' => $isAuthUserFollow,

            // Relations conditionnelles
            'secteur_brvm' => $this->whenLoaded('brvmSector', fn () => [
                'id' => $this->brvmSector->id,
                'nom' => $this->brvmSector->nom,
                'slug' => $this->brvmSector->slug,
            ]),

            'secteur_reclassifie' => $this->whenLoaded('classifiedSector', fn () => [
                'id' => $this->classifiedSector->id,
                'nom' => $this->classifiedSector->nom,
                'slug' => $this->classifiedSector->slug,
            ]),

            'actionnaires' => $this->whenLoaded('shareholders', fn () =>
                $this->shareholders->map(fn ($shareholder) => [
                    'id' => $shareholder->id ?? null,
                    'nom' => $shareholder->nom ?? '',
                    'pourcentage' => (float) ($shareholder->percentage ?? 0),
                    'rang' => $shareholder->rang ?? null,
                ])->values()->all()
            ),

            'employees' => $this->whenLoaded('employees', fn () =>
                $this->employees->map(fn ($employee) => [
                    'position' => $employee->position ? strtoupper($employee->position->nom) : '',
                    'nom' => $employee->nom ?? '',
                ])->values()->all()
            ),
        ];
    }
}
