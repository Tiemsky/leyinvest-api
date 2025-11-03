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
    protected ?array $followedIds = null;

    public function withFollowedIds(array $followedIds): static
    {
        $this->followedIds = $followedIds;
        return $this;
    }

    public function toArray(Request $request): array
    {
        // Détermine la couleur en fonction de la variation
        $variation = (float) $this->variation;
        $couleur = $variation > 0 ? 'green' : ($variation < 0 ? 'red' : 'gray');

        $userFollows = $this->followedIds ?? [];

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
            'couleur_variation' => $couleur,
            'isAuthUserFollow' => in_array($this->id, $userFollows),

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
        ];
    }
}
