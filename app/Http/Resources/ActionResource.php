<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="ActionResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="key", type="string", example="ABJC"),
 *     @OA\Property(property="symbole", type="string", example="SERVAIR ABIDJAN CÔTE D’IVOIRE"),
 *     @OA\Property(property="nom", type="string", example="SERVAIR ABIDJAN CÔTE D’IVOIRE"),
 *     @OA\Property(property="volume", type="number", example=5759),
 *     @OA\Property(property="cours_veille", type="number", example=980.5),
 *     @OA\Property(property="cours_ouverture", type="number", example=985.0),
 *     @OA\Property(property="cours_cloture", type="number", example=1005.5),
 *     @OA\Property(property="variation", type="number", example=2.4),
 *     @OA\Property(property="couleur_variation", type="string", example="green"),
 *     @OA\Property(property="isAuthUserFollow", type="boolean", example=true)
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
            'variation' => (float) $this->variation,
            'couleur_variation' => $this->couleur_variation,
            'isAuthUserFollow' => in_array($this->id, $userFollows),
        ];
    }
}
