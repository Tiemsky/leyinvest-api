<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SectorWithActionsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => (int) $this->id,
            'nom' => (string) $this->nom,
            'slug' => (string) $this->slug,
            'variation' => (float) $this->variation,
            'actions' => EasyActionResource::collection($this->whenLoaded('actions')),
        ];
    }
}
