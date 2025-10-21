<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="UserDashboardResource",
 *     type="object",
 *     title="Ressource du tableau de bord utilisateur",
 *     description="Structure de données renvoyée par l'API /api/v1/user/dashboard",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nom", type="string", example="Doe"),
 *     @OA\Property(property="prenom", type="string", example="John"),
 *     @OA\Property(property="email", type="string", example="john.doe@example.com"),
 *     @OA\Property(property="numero", type="string", example="+2250102030405"),
 *     @OA\Property(
 *         property="actions_suivis",
 *         type="array",
 *         description="5 actions suivies aléatoirement",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="key", type="string", example="ABJC"),
 *             @OA\Property(property="symbole", type="string", example="SERVAIR ABIDJAN CÔTE D’IVOIRE"),
 *             @OA\Property(property="nom", type="string", example="SERVAIR ABIDJAN CÔTE D’IVOIRE"),
 *             @OA\Property(property="volume", type="number", example=5759),
 *             @OA\Property(property="cours_veille", type="number", example=980.0),
 *             @OA\Property(property="cours_ouverture", type="number", example=985.0),
 *             @OA\Property(property="cours_cloture", type="number", example=1005.5),
 *             @OA\Property(property="variation", type="number", example=2.4),
 *             @OA\Property(property="couleur_variation", type="string", example="green"),
 *             @OA\Property(property="stop_loss", type="number", nullable=true, example=950.0),
 *             @OA\Property(property="take_profit", type="number", nullable=true, example=1250.0)
 *         )
 *     ),
 *     @OA\Property(
 *         property="ma_liste",
 *         type="array",
 *         description="Liste complète de toutes les actions suivies",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="key", type="string", example="BOAC"),
 *             @OA\Property(property="symbole", type="string", example="BANK OF AFRICA CÔTE D’IVOIRE"),
 *             @OA\Property(property="nom", type="string", example="BANK OF AFRICA CÔTE D’IVOIRE"),
 *             @OA\Property(property="volume", type="number", example=4562),
 *             @OA\Property(property="cours_veille", type="number", example=900.0),
 *             @OA\Property(property="cours_ouverture", type="number", example=920.0),
 *             @OA\Property(property="cours_cloture", type="number", example=910.0),
 *             @OA\Property(property="variation", type="number", example=-1.1),
 *             @OA\Property(property="stop_loss", type="number", nullable=true, example=850.0),
 *             @OA\Property(property="take_profit", type="number", nullable=true, example=1150.0)
 *         )
 *     )
 * )
 */
class UserDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'email' => $this->email,
            'numero' => $this->numero,
            'actions_suivis' => $this->whenLoaded('followedActions', function () {
                return $this->followedActions
                    ->shuffle()
                    ->take(5)
                    ->map(function ($userAction) {
                        $action = $userAction->action;
                        return [
                            'key' => $action->key,
                            'symbole' => $action->symbole,
                            'nom' => $action->nom,
                            'volume' => $action->volume,
                            'cours_veille' => (float) $action->cours_veille,
                            'cours_ouverture' => (float) $action->cours_ouverture,
                            'cours_cloture' => (float) $action->cours_cloture,
                            'variation' => (float) $action->variation,

                        ];
                    });
            }),
            'ma_liste' => $this->whenLoaded('followedActions', function () {
                return $this->followedActions->map(function ($userAction) {
                    $action = $userAction->action;
                    return [
                        'key' => $action->key,
                        'symbole' => $action->symbole,
                        'nom' => $action->nom,
                        'variation' => (float) $action->variation,
                        'stop_loss' => $userAction->stop_loss,
                        'take_profit' => $userAction->take_profit,
                    ];
                });
            }),
        ];
    }
}
