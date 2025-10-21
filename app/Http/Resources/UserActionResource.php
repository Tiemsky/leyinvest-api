<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="UserActionResource",
 *     type="object",
 *     title="User Action Resource",
 *     description="Relation entre un utilisateur et une action suivie, incluant les paramètres stop_loss et take_profit",
 *     @OA\Property(property="id", type="integer", example=15),
 *     @OA\Property(property="user_id", type="integer", example=3),
 *     @OA\Property(property="action_id", type="integer", example=1),
 *     @OA\Property(property="stop_loss", type="number", format="float", nullable=true, example=980.0),
 *     @OA\Property(property="take_profit", type="number", format="float", nullable=true, example=1250.0),
 *     @OA\Property(
 *         property="action",
 *         type="object",
 *         nullable=true,
 *         description="Informations sur l'action suivie",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="symbole", type="string", example="ABJC"),
 *         @OA\Property(property="nom", type="string", example="SERVAIR ABIDJAN COTE D'IVOIRE")
 *     ),
 *     @OA\Property(
 *         property="user",
 *         type="object",
 *         nullable=true,
 *         description="Informations sur l'utilisateur qui suit cette action",
 *         @OA\Property(property="id", type="integer", example=3),
 *         @OA\Property(property="nom", type="string", example="Kouassi"),
 *         @OA\Property(property="prenom", type="string", example="Eren"),
 *         @OA\Property(property="email", type="string", example="eren@example.com"),
 *         @OA\Property(property="numero", type="string", example="+2250700000000")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-21T12:34:56Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-10-21T13:10:00Z")
 * )
 */
class UserActionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'action_id' => $this->action_id,
            'stop_loss' => $this->stop_loss,
            'take_profit' => $this->take_profit,
            'action' => $this->whenLoaded('action', function () {
                return [
                    'id' => $this->action->id,
                    'symbole' => $this->action->symbole,
                    'nom' => $this->action->nom,
                ];
            }),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'nom' => $this->user->nom,
                    'prenom' => $this->user->prenom,
                    'email' => $this->user->email,
                    'numero' => $this->user->numero,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
