<?php

namespace App\Http\Resources;

use App\Models\BocIndicator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDashboardResource extends JsonResource
{
    protected $topActions;

    protected $flopActions;

    public function __construct($resource, $topActions, $flopActions)
    {
        parent::__construct($resource);
        $this->topActions = $topActions;
        $this->flopActions = $flopActions;
    }

    public function toArray(Request $request): array
    {
        // On récupère l'abonnement actif s'il est chargé
        $activeSubscription = $this->whenLoaded('activeSubscription');

        return [
            'user' => [
                'id' => (int) $this->id,
                'nom' => (string) $this->nom,
                'prenom' => (string) $this->prenom,
                'email' => (string) $this->email,
                'country' => (string) $this->country,
                'age' => (int) $this->age,
                'genre' => (string) $this->genre,
                'situation_professionnelle' => (string) $this->situation_professionnelle,
                'numero' => (string) $this->numero,
                'whatsapp' => (string) $this->whatsapp,
                'email_verified' => (bool) $this->email_verified,
                'registration_completed' => (bool) $this->registration_completed,
                'role' => (string) $this->role,
                // L'abonnement est inclus uniquement s'il existe ET qu'il est chargé (N+1 safe)
                'subscription' => $this->when($activeSubscription, function () use ($activeSubscription) {
                    // Utilise la SubscriptionResource définie précédemment pour le formatage
                    return new SubscriptionResource($activeSubscription);
                }),
                'created_at' => $this->created_at?->toIso8601String(),
                'updated_at' => $this->updated_at?->toIso8601String(),
            ],
            'actions_suivis' => $this->whenLoaded('followedActions', function () {
                return $this->followedActions
                    ->shuffle()
                    ->take(4)
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
            'indicateur' => $this->getBocIndicators(),
            'top_actions' => $this->formatTopFlopActions($this->topActions),
            'flop_actions' => $this->formatTopFlopActions($this->flopActions),
            'evaluation' => [],
            'publications' => [],
        ];
    }

    private function getBocIndicators()
    {
        $boc = BocIndicator::query()->latest()->first();

        // On récupère les informations du marché boursier venant du bulletin officiel;
        return [
            'taux_rendement_moyen' => (float) $boc->taux_rendement_moyen,
            'per_moyen' => (float) $boc->per_moyen,
            'taux_rentabilite_moyen' => (float) $boc->taux_rentabilite_moyen,
            'prime_risque_marche' => (float) $boc->prime_risque_marche,
        ];
    }

    private function formatTopFlopActions($actions)
    {
        return $actions->map(function ($action) {
            return [
                'symbole' => (string) $action->symbole,
                'nom' => (string) $action->nom,
                'cours_veille' => (float) $action->cours_veille,
                'cours_ouverture' => (float) $action->cours_ouverture,
                'cours_cloture' => (float) $action->cours_cloture,
                'variation' => (float) $action->variation,
            ];
        });
    }
}
