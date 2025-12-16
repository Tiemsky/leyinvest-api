<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
/**
 * @OA\Schema(
 *     schema="ActionForecastResource",
 *     type="object",
 *     title="Action avec prévisions",
 *     description="Action financière avec données de prévision calculées",
 *
 *     @OA\Property(property="id", type="integer", example=12),
 *     @OA\Property(property="key", type="string", example="ACT_BICICI"),
 *     @OA\Property(property="symbole", type="string", example="BICICI"),
 *     @OA\Property(property="nom", type="string", example="BICICI Côte d'Ivoire"),
 *     @OA\Property(property="cours_cloture", type="number", format="float", example=4850),
 *
 *     @OA\Property(
 *         property="previsions",
 *         type="object",
 *         description="Prévisions financières associées à l'action",
 *
 *         @OA\Property(
 *             property="rn_previsionnel",
 *             type="number",
 *             format="float",
 *             nullable=true,
 *             example=1200000000
 *         ),
 *
 *         @OA\Property(
 *             property="dnpa_previsionnel",
 *             type="number",
 *             format="float",
 *             nullable=true,
 *             example=350.75
 *         ),
 *
 *         @OA\Property(
 *             property="rendement_net_pourcent",
 *             type="string",
 *             example="7.35%",
 *             description="Rendement prévisionnel net calculé dynamiquement"
 *         )
 *     ),
 *
 *     @OA\Property(
 *         property="last_updated",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         example="2025-02-10T14:32:45Z"
 *     )
 * )
 */

class ActionForecastResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        // On sécurise l'accès à la relation 'forecast'
        $forecast = $this->forecast;

        return [
            'id' => $this->id,
            'key' => $this->key,
            'symbole' => $this->symbole,
            'nom' => $this->nom,
            'cours_cloture' => (float) $this->cours_cloture,
            'previsions' => [
                // Valeurs stockées en base (Calculées par le worker/observer)
                'rn_previsionnel' => $forecast ? (float) $forecast->rn_previsionnel : null,
                'dnpa_previsionnel' => $forecast ? (float) $forecast->dnpa_previsionnel : null,

                // Valeur dynamique (Calculée à la volée via l'Accessor du modèle Action)
                // On formate ici en pourcentage pour l'affichage
                'rendement_net_pourcent' => $this->rendement_previsionnel . '%',
            ],
            'last_updated' => $forecast ? $forecast->updated_at->toIso8601String() : null,
        ];
    }
}
