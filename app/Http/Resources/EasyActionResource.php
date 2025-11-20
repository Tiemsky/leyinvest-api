<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="EasyActionResource",
 *     type="object",
 *     description="Informations essentielles d'une action",
 *     @OA\Property(property="id", type="integer", example=12),
 *     @OA\Property(property="key", type="string", example="boa-ci"),
 *     @OA\Property(property="symbole", type="string", example="BOA"),
 *     @OA\Property(property="nom", type="string", example="BOA Côte d'Ivoire"),
 *     @OA\Property(property="volume", type="integer", example=15000),
 *     @OA\Property(property="cours_veille", type="number", format="float", example=78.5),
 *     @OA\Property(property="cours_ouverture", type="number", format="float", example=79.0),
 *     @OA\Property(property="cours_cloture", type="number", format="float", example=80.0),
 *     @OA\Property(property="variation", type="string", example="+1.27%")
 * )
 */

class EasyActionResource extends JsonResource
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
        ];
    }
}
