<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="ShowSingleActionResource",
 *     type="object",
 *     description="Informations complètes d'une action",
 *     @OA\Property(property="id", type="integer", example=12),
 *     @OA\Property(property="key", type="string", example="act_abc123def"),
 *     @OA\Property(property="symbole", type="string", example="BOA"),
 *     @OA\Property(property="nom", type="string", example="BOA Côte d'Ivoire"),
 *     @OA\Property(property="volume", type="integer", example=15000),
 *     @OA\Property(property="cours_veille", type="number", format="float", example=78.5),
 *     @OA\Property(property="cours_ouverture", type="number", format="float", example=79.0),
 *     @OA\Property(property="cours_cloture", type="number", format="float", example=80.0),
 *     @OA\Property(property="variation", type="string", example="+1.27%"),
 *
 *     @OA\Property(
 *         property="secteur_brvm",
 *         ref="#/components/schemas/BrvmSectorResource"
 *     ),
 *
 *     @OA\Property(
 *         property="secteur_reclassifie",
 *         ref="#/components/schemas/ClassifiedSectorResource"
 *     ),
 *
 *     @OA\Property(
 *         property="actionnaires",
 *         type="array",
 *         description="Liste des actionnaires",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="nom", type="string", example="Fonds souverain CI"),
 *             @OA\Property(property="pourcentage", type="number", format="float", example=12.5),
 *             @OA\Property(property="rang", type="integer", example=1)
 *         )
 *     ),
 *
 *     @OA\Property(
 *         property="employees",
 *         type="array",
 *         description="Liste des employés liés à l'action",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="position", type="string", example="DIRECTEUR FINANCIER"),
 *             @OA\Property(property="nom", type="string", example="Jean Kouadio")
 *         )
 *     )
 * )
 */

class ShowSingleActionResource extends JsonResource
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
            'cours_veille' => (float) $this->cours_veille,
            'cours_ouverture' => (float) $this->cours_ouverture,
            'cours_cloture' => (float) $this->cours_cloture,
            'variation' => $this->variation,

            // Relations vers les secteurs
            'secteur_brvm' => $this->whenLoaded('brvmSector')
                ? BrvmSectorResource::make($this->brvmSector)
                : [
                    'id' => $this->brvm_sector_id,
                    'nom' => optional($this->brvmSector)->nom,
                    'slug' => optional($this->brvmSector)->slug,
                ],

            'secteur_reclassifie' => $this->whenLoaded('classifiedSector')
                ? ClassifiedSectorResource::make($this->classifiedSector)
                : [
                    'id' => $this->classified_sector_id,
                    'nom' => optional($this->classifiedSector)->nom,
                    'slug' => optional($this->classifiedSector)->slug,
                ],

            'actionnaires' => $this->whenLoaded('shareholders')->map(function ($shareholder) {
                return [
                    'id' => $shareholder->id,
                    'nom' => $shareholder->nom,
                    'pourcentage' => (float) $shareholder->percentage,
                    'rang' => $shareholder->rang,
                ];
            })->toArray(),

            'employees' => $this->whenLoaded('employees', function () {
                return $this->employees->map(function ($employee) {
                    return [
                        'position' => $employee->position ? strtoupper($employee->position->nom) : '',
                        'nom' => $employee->nom,
                    ];
                })->toArray();
            }),

        ];
    }
}
