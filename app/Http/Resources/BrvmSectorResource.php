<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="BrvmSectorResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nom", type="string", example="Consommation de base"),
 *     @OA\Property(property="slug", type="string", example="consommation-de-base"),
 *     @OA\Property(property="key", type="string", example="brv_xyz123ab")
 * )
 */
class BrvmSectorResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'nom' => $this->nom,
            'slug' => $this->slug,
        ];
    }
}
