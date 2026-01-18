<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'id' => (int) $this->resource->id,
            'key' => (string) $this->resource->key,
            'symbole' => (string) $this->resource->symbole,
            'nom' => (string) $this->resource->nom,
            'volume' => (int) $this->resource->volume,
            'cours_veille' => (float) $this->resource->cours_veille,
            'cours_ouverture' => (float) $this->resource->cours_ouverture,
            'cours_cloture' => (float) $this->resource->cours_cloture,
            'variation' => (float) $variation,
            'variation_formatted' => $variation >= 0
                ? '+' . number_format($variation, 2) . '%'
                : number_format($variation, 2) . '%',
            'couleur_variation' => $couleur,
            'isAuthUserFollow' => $isAuthUserFollow,

            // Relations conditionnelles
            'secteur_brvm' => $this->whenLoaded('brvmSector', fn () => [
                'id' => (int) $this->brvmSector->id,
                'nom' => (string) $this->brvmSector->nom,
                'slug' => (string) $this->brvmSector->slug,
            ]),

            'secteur_reclassifie' => $this->whenLoaded('classifiedSector', fn () => [
                'id' => (int) $this->classifiedSector->id,
                'nom' => (string) $this->classifiedSector->nom,
                'slug' => (string) $this->classifiedSector->slug,
            ]),

            'actionnaires' => $this->whenLoaded('shareholders', fn () =>
                $this->shareholders->map(fn ($shareholder) => [
                    'id' => (int) $shareholder->id ?? null,
                    'nom' => (string) $shareholder->nom ?? '',
                    'pourcentage' => (float) ($shareholder->percentage ?? 0),
                    'rang' => (int) $shareholder->rang ?? null,
                ])->values()->all()
            ),

            'employees' => $this->whenLoaded('employees', fn () =>
                $this->employees->map(fn ($employee) => [
                    'position' => (string) $employee->position ? strtoupper($employee->position->nom) : '',
                    'nom' => (string) $employee->nom ?? '',
                ])->values()->all()
            ),
        ];
    }
}
