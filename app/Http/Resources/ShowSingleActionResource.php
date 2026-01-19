<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowSingleActionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'key' => (string) $this->key,
            'symbole' => (string) $this->symbole,
            'nom' => (string) $this->nom,
            'volume' => (int) $this->volume,
            'cours_veille' => (float) $this->cours_veille,
            'cours_ouverture' => (float) $this->cours_ouverture,
            'cours_cloture' => (float) $this->cours_cloture,
            'variation' => (float) $this->variation,

            // Relations vers les secteurs
            'secteur_brvm' => $this->whenLoaded('brvmSector')
                ? BrvmSectorResource::make($this->brvmSector)
                : [
                    'nom' => optional($this->brvmSector)->nom,
                    'slug' => optional($this->brvmSector)->slug,
                ],

            'secteur_reclassifie' => $this->whenLoaded('classifiedSector')
                ? ClassifiedSectorResource::make($this->classifiedSector)
                : [
                    'nom' => optional($this->classifiedSector)->nom,
                    'slug' => optional($this->classifiedSector)->slug,
                ],

            'actionnaires' => $this->whenLoaded('shareholders')->map(function ($shareholder) {
                return [
                    'nom' => (string) $shareholder->nom,
                    'pourcentage' => (float) $shareholder->percentage,
                    'rang' => (int) $shareholder->rang,
                ];
            })->toArray(),

            'employees' => $this->whenLoaded('employees', function () {
                return $this->employees->map(function ($employee) {
                    return [
                        'position' => (string) ($employee->position ? strtoupper($employee->position->nom) : ''),
                        'nom' => (string) $employee->nom,
                    ];
                })->toArray();
            }),

            'bilan' => $this->whenLoaded('financials')->map(function ($financial) {
                return [
                    'total_immobilisation' => $financial->total_immobilisation,
                    'credits_clientele' => $financial->credits_clientele,
                    'depots_clientele' => $financial->depots_clientele,
                    'total_actif' => $financial->total_actif,
                    'dette_totale' => $financial->dette_totale,
                    'capitaux_propres' => $financial->capitaux_propres,
                ];
            })->toArray(),
            'compte_de_resultat' => $this->whenLoaded('financials')->map(function ($financial) {
                return [
                    'produit_net_bancaire' => $financial->produit_net_bancaire,
                    'ebit' => $financial->ebit,
                    'ebitda' => $financial->ebitda,
                    'resultat_avant_impot' => $financial->resultat_avant_impot,
                    'resultat_net' => $financial->resultat_net,
                ];
            })->toArray(),
            'indicateur_boursiers' => $this->whenLoaded('financials')->map(function ($financial) {
                return [
                    'dividendes_total' => (float) $financial->dividendes_bruts ?? 0,
                    'nombre_de_titre' => (float) $financial->nombre_titre ?? 0,
                    'dnpa' => (float) $financial->dnpa ?? 0,
                    'bnpa' => (float) $financial->per ? (float) ($financial->cours_31_12) / ($financial->per) : 0,
                    'rendement_actuel' => (float) $financial->dnpa ? (float) $this->cours_cloture / (float) $financial->dnpa : 0,
                    'cours_cible' => 'en_attente',
                ];
            })->toArray(),

        ];
    }
}
