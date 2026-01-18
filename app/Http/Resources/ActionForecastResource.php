<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
