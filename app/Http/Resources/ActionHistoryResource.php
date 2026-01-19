<?php

namespace App\Http\Resources;

use App\Models\Action;
use App\Models\StockFinancialMetric;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActionHistoryResource extends JsonResource
{
    private array $years;

    public function __construct($resource, array $years)
    {
        parent::__construct($resource);
        $this->years = $years;
    }

    public function toArray(Request $request): array
    {
        /** @var Action $action */
        $action = $this->resource;

        $history = [];

        foreach ($this->years as $year) {
            $financial = $action->financials()->where('year', $year)->first();

            if (! $financial) {
                continue;
            }

            $metric = StockFinancialMetric::where('action_id', $action->id)
                ->where('year', $year)
                ->first();

            $yearData = [
                'year' => $year,
                'financials' => [
                    'total_actif' => $financial->total_actif,
                    'capitaux_propres' => $financial->capitaux_propres,
                    'dette_totale' => $financial->dette_totale,
                    'ebit' => $financial->ebit,
                    'ebitda' => $financial->ebitda,
                    'resultat_net' => $financial->resultat_net,
                    'cours_31_12' => $financial->cours_31_12,
                    'nombre_titre' => $financial->nombre_titre,
                ],
            ];

            if ($action->isFinancialService()) {
                $yearData['financials']['produit_net_bancaire'] = $financial->produit_net_bancaire;
                $yearData['financials']['credits_clientele'] = $financial->credits_clientele;
                $yearData['financials']['depots_clientele'] = $financial->depots_clientele;
            } else {
                $yearData['financials']['chiffre_affaires'] = $financial->chiffre_affaires;
            }

            if ($metric) {
                $yearData['indicators'] = [
                    'croissance' => $this->formatCroissanceHistory($metric, $action->isFinancialService()),
                    'rentabilite' => $this->formatRentabiliteHistory($metric),
                    'remuneration' => $this->formatRemunerationHistory($metric),
                    'valorisation' => $this->formatValorisationHistory($metric),
                    'solidite_financiere' => $this->formatSoliditeHistory($metric, $action->isFinancialService()),
                ];
            }

            $history[] = $yearData;
        }

        return [
            'action' => [
                'id' => $action->id,
                'nom' => $action->nom,
                'key' => $action->key,
                'symbole' => $action->symbole,
                'brvm_sector' => [
                    'nom' => $action->brvmSector->nom,
                    'slug' => $action->brvmSector->slug,
                ],
                'classified_sector' => [
                    'nom' => $action->classifiedSector->nom,
                    'slug' => $action->classifiedSector->slug,
                ],
            ],
            'history' => $history,
        ];
    }

    private function formatCroissanceHistory($metric, bool $isFinancial): array
    {
        if ($isFinancial) {
            return [
                'pnb' => ['value' => $metric->croissance_pnb],
                'ebit' => ['value' => $metric->croissance_ebit_sf],
                'ebitda' => ['value' => $metric->croissance_ebitda_sf],
                'resultat_net' => ['value' => $metric->croissance_rn_sf],
                'capex' => ['value' => $metric->croissance_capex_sf],
                'moy_croissance' => ['value' => $metric->moy_croissance_sf],
            ];
        } else {
            return [
                'chiffre_affaires' => ['value' => $metric->croissance_ca],
                'ebit' => ['value' => $metric->croissance_ebit_as],
                'ebitda' => ['value' => $metric->croissance_ebitda_as],
                'resultat_net' => ['value' => $metric->croissance_rn_as],
                'capex' => ['value' => $metric->croissance_capex_as],
                'moy_croissance' => ['value' => $metric->moy_croissance_as],
            ];
        }
    }

    private function formatRentabiliteHistory($metric): array
    {
        return [
            'marge_nette' => ['value' => $metric->marge_nette],
            'marge_ebitda' => ['value' => $metric->marge_ebitda],
            'marge_operationnelle' => ['value' => $metric->marge_operationnelle],
            'roe' => ['value' => $metric->roe],
            'roa' => ['value' => $metric->roa],
            'moy_rentabilite' => ['value' => $metric->moy_rentabilite],
        ];
    }

    private function formatRemunerationHistory($metric): array
    {
        return [
            'dnpa' => ['value' => $metric->dnpa_calculated],
            'rendement_dividendes' => ['value' => $metric->rendement_dividendes],
            'taux_distribution' => ['value' => $metric->taux_distribution],
            'moy_remuneration' => ['value' => $metric->moy_remuneration],
        ];
    }

    private function formatValorisationHistory($metric): array
    {
        return [
            'per' => ['value' => $metric->per],
            'pbr' => ['value' => $metric->pbr],
            'ratio_ps' => ['value' => $metric->ratio_ps],
            'ev_ebitda' => ['value' => $metric->ev_ebitda],
            'cours_cible' => ['value' => $metric->cours_cible],
            'potentiel_hausse' => ['value' => $metric->potentiel_hausse],
            'moy_valorisation' => ['value' => $metric->moy_valorisation],
        ];
    }

    private function formatSoliditeHistory($metric, bool $isFinancial): array
    {
        if ($isFinancial) {
            return [
                'autonomie_financiere' => ['value' => $metric->autonomie_financiere],
                'ratio_prets_depots' => ['value' => $metric->ratio_prets_depots],
                'loan_to_deposit' => ['value' => $metric->loan_to_deposit],
                'endettement_general' => ['value' => $metric->endettement_general_sf],
                'cout_du_risque' => ['value' => $metric->cout_du_risque_value],
                'moy_solidite_financiere' => ['value' => $metric->moy_solidite_sf],
            ];
        } else {
            return [
                'dette_capitalisation' => ['value' => $metric->dette_capitalisation],
                'endettement_actif' => ['value' => $metric->endettement_actif],
                'endettement_general' => ['value' => $metric->endettement_general_as],
                'moy_solidite_financiere' => ['value' => $metric->moy_solidite_as],
            ];
        }
    }
}
