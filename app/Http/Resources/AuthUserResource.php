<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="AuthUserResource",
 *     type="object",
 *     title="Utilisateur Authentifié",
 *     description="Représente un utilisateur connecté ou nouvellement inscrit",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nom", type="string", example="Doe"),
 *     @OA\Property(property="prenoms", type="string", example="John"),
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *     @OA\Property(property="country", type="string", example="Côte d'Ivoire"),
 *     @OA\Property(property="phone", type="string", example="+2250707070707"),
 *     @OA\Property(property="email_verified", type="boolean", example=true),
 *     @OA\Property(property="registration_completed", type="boolean", example=true),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", example="2025-10-18T12:45:00Z"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-18T12:45:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-10-18T12:45:00Z")
 * )
 */


class AuthUserResource extends JsonResource
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
            'nom' => $this->nom,
            'prenoms' => $this->prenoms,
            'email' => $this->email,
            'country' => $this->country,
            'phone' => $this->phone,
            'email_verified' => $this->email_verified,
            'registration_completed' => $this->registration_completed,
            'email_verified_at' => $this->email_verified?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
