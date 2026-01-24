<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserActionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'user_id' => (int) $this->user_id,
            'action_id' => (int) $this->action_id,
            'stop_loss' => (float) $this->stop_loss,
            'take_profit' => (float) $this->take_profit,
            'action' => $this->whenLoaded('action', function () {
                return [
                    'id' => (int) $this->action->id,
                    'symbole' => (string) $this->action->symbole,
                    'nom' => (string) $this->action->nom,
                    'variation' => (float) $this->action->variation,
                    'cours_cloture' => (float) $this->action->cours_cloture,
                ];
            }),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => (int) $this->user->id,
                    'nom' => (string) $this->user->nom,
                    'prenom' => (string) $this->user->prenom,
                    'email' => (string) $this->user->email,
                    'numero' => (string) $this->user->numero,
                    'whatsapp' => (string) $this->user->whatsapp,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
