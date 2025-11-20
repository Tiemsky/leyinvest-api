<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="SectorWithActionsResource",
 *     type="object",
 *     description="Secteur avec liste de ses actions",
 *     @OA\Property(property="id", type="integer", example=4),
 *     @OA\Property(property="nom", type="string", example="Finance"),
 *     @OA\Property(property="slug", type="string", example="finance"),
 *     @OA\Property(
 *         property="actions",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/EasyActionResource")
 *     )
 * )
 */

class SectorWithActionsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'slug' => $this->slug,
            'actions' => EasyActionResource::collection($this->whenLoaded('actions')),
        ];
    }
}
