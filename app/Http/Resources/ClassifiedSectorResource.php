<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClassifiedSectorResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (int) $this->id,
            'key' => (string) $this->key,
            'nom' => (string) $this->nom,
            'slug' => (string) $this->slug,
        ];
    }
}
