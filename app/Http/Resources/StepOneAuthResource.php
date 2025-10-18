<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="StepOneAuthResource",
 *     type="object",
 *     title="Utilisateur En Première Étape d'inscription",
 *     description="Représente un utilisateur ayant complété la première étape de l'inscription",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nom", type="string", example="Doe"),
 *     @OA\Property(property="prenoms", type="string", example="John"),
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *     @OA\Property(property="email_verified", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-18T12:45:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-10-18T12:45:00Z")
 * )
 */
class StepOneAuthResource extends JsonResource
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
            'nom' => $this->nom,
            'prenoms' => $this->prenoms,
            'email' => $this->email,
            'email_verified' => (bool) $this->email_verified,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
