<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="TopFlop",
 *     title="Top/Flop Resource",
 *     description="Représente une action en forte hausse ou forte baisse sur le marché.",
 *     @OA\Property(property="key", type="string", example="SICC_CI", description="Clé unique de l’action."),
 *     @OA\Property(property="symbole", type="string", example="SICC", description="Symbole de l’action."),
 *     @OA\Property(property="nom", type="string", example="Société Ivoirienne de Ciment et de Construction", description="Nom complet de l’entreprise."),
 *     @OA\Property(property="volume", type="integer", example=24000, description="Volume total échangé."),
 *     @OA\Property(property="cours_veille", type="number", format="float", example=3500, description="Cours de clôture de la veille."),
 *     @OA\Property(property="cours_ouverture", type="number", format="float", example=3600, description="Cours d’ouverture du jour."),
 *     @OA\Property(property="cours_cloture", type="number", format="float", example=3400, description="Cours de clôture du jour."),
 *     @OA\Property(property="variation", type="number", format="float", example=-7.46, description="Variation en pourcentage du cours."),
 *     @OA\Property(property="created_at", type="string", format="datetime", example="2025-01-15 10:30:00", description="Date de création de l’enregistrement."),
 *     @OA\Property(property="updated_at", type="string", format="datetime", example="2025-01-15 16:45:00", description="Date de dernière mise à jour.")
 * )
 */
class TopFlopResource extends JsonResource
{
    /**
     * Transforme la ressource en tableau pour l'API.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->key,
            'symbole' => $this->symbole,
            'nom' => $this->nom,
            'volume' => $this->volume,
            'cours_veille' => $this->cours_veille,
            'cours_ouverture' => $this->cours_ouverture,
            'cours_cloture' => $this->cours_cloture,
            'variation' => (float) $this->variation,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
