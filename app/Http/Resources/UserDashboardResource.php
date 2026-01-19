<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'nom' => (string) $this->nom,
            'prenom' => (string) $this->prenom,
            'email' => (string) $this->email,
            'numero' => (string) $this->numero,
            'actions_suivis' => $this->whenLoaded('followedActions', function () {
                return $this->followedActions
                    ->shuffle()
                    ->take(5)
                    ->map(function ($userAction) {
                        $action = $userAction->action;

                        return [
                            'key' => (string) $action->key,
                            'symbole' => (string) $action->symbole,
                            'nom' => (string) $action->nom,
                            'volume' => (int) $action->volume,
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
                        'key' => (string) $action->key,
                        'symbole' => (string) $action->symbole,
                        'nom' => (string) $action->nom,
                        'variation' => (float) $action->variation,
                        'stop_loss' => (float) $userAction->stop_loss,
                        'take_profit' => (float) $userAction->take_profit,
                    ];
                });
            }),
        ];
    }
}
