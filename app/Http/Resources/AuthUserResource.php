<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\WalletResource;
use Illuminate\Http\Resources\Json\JsonResource;
/**
 * @OA\Schema(
 *     schema="AuthUserResource",
 *     type="object",
 *     title="Utilisateur Authentifié",
 *     description="Représente un utilisateur connecté et nouvellement inscrit",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nom", type="string", example="Doe"),
 *     @OA\Property(property="prenoms", type="string", example="John"),
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *     @OA\Property(property="country", type="string", nullable=true, example="Côte d'Ivoire"),
 *     @OA\Property(property="age", type="integer", nullable=true, example=30),
 *     @OA\Property(property="genre", type="string", nullable=true, example="Masculin"),
 *     @OA\Property(property="situation_professionnelle", type="string", nullable=true, example="Salarié"),
 *     @OA\Property(property="numero", type="string", nullable=true, example="+2250707070707"),
 *     @OA\Property(property="whatsapp", type="string", nullable=true, example="+2250707070707"),
 *     @OA\Property(property="email_verified", type="boolean", example=true),
 *     @OA\Property(property="registration_completed", type="boolean", example=true),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true, example="2025-10-18T12:45:00Z"),
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
            'age' => $this->age,
            'genre' => $this->genre,
            'situation_professionnelle' => $this->situation_professionnelle,
            'numero' => $this->numero,
            'whatsapp' => $this->whatsapp,
            'email_verified' => (bool) $this->email_verified,
            'registration_completed' => (bool) $this->registration_completed,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'role' => $this->role,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'wallet' => new WalletResource($this->whenLoaded('wallet')),
        ];
    }
}
