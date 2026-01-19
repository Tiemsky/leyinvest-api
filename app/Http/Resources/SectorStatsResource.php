<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectorStatsResource extends JsonResource
{
    private $sector;

    private $metrics;

    private string $type;

    public function __construct($sector, $metrics, string $type)
    {
        $this->sector = $sector;
        $this->metrics = $metrics;
        $this->type = $type;
        parent::__construct($metrics);
    }

    public function toArray(Request $request): array
    {
        $isFinancial = $this->metrics->is_financial_sector;

        return [
            'sector' => [
                'id' => $this->sector->id,
                'nom' => $this->sector->nom,
                'slug' => $this->sector->slug,
                'type' => $this->type,
            ],
            'year' => $this->metrics->year,
            'companies_count' => $this->metrics->companies_count,
            'is_financial_sector' => $isFinancial,

            'statistics' => [
                'croissance' => $this->formatCroissance($isFinancial),
                'rentabilite' => $this->formatRentabilite(),
                'remuneration' => $this->formatRemuneration(),
                'valorisation' => $this->formatValorisation(),
                'solidite_financiere' => $this->formatSolidite($isFinancial),
            ],
        ];
    }

    private function formatCroissance(bool $isFinancial): array
    {
        if ($isFinancial) {
            return [
                'pnb' => $this->formatStat('croissance_pnb'),
                'ebit' => $this->formatStat('croissance_ebit'),
                'ebitda' => $this->formatStat('croissance_ebitda'),
                'resultat_net' => $this->formatStat('croissance_rn'),
                'capex' => $this->formatStat('croissance_capex'),
                'moy_croissance' => $this->formatStat('moy_croissance'),
            ];
        } else {
            return [
                'chiffre_affaires' => $this->formatStat('croissance_ca'),
                'ebit' => $this->formatStat('croissance_ebit'),
                'ebitda' => $this->formatStat('croissance_ebitda'),
                'resultat_net' => $this->formatStat('croissance_rn'),
                'capex' => $this->formatStat('croissance_capex'),
                'moy_croissance' => $this->formatStat('moy_croissance'),
            ];
        }
    }

    private function formatRentabilite(): array
    {
        return [
            'marge_nette' => $this->formatStat('marge_nette'),
            'marge_ebitda' => $this->formatStat('marge_ebitda'),
            'marge_operationnelle' => $this->formatStat('marge_operationnelle'),
            'roe' => $this->formatStat('roe'),
            'roa' => $this->formatStat('roa'),
            'moy_rentabilite' => $this->formatStat('moy_rentabilite'),
        ];
    }

    private function formatRemuneration(): array
    {
        return [
            'dnpa' => $this->formatStat('dnpa'),
            'rendement_dividendes' => $this->formatStat('rendement_dividendes'),
            'taux_distribution' => $this->formatStat('taux_distribution'),
            'moy_remuneration' => $this->formatStat('moy_remuneration'),
        ];
    }

    private function formatValorisation(): array
    {
        return [
            'per' => $this->formatStat('per'),
            'pbr' => $this->formatStat('pbr'),
            'ratio_ps' => $this->formatStat('ratio_ps'),
            'ev_ebitda' => $this->formatStat('ev_ebitda'),
            'moy_valorisation' => $this->formatStat('moy_valorisation'),
        ];
    }

    private function formatSolidite(bool $isFinancial): array
    {
        if ($isFinancial) {
            return [
                'autonomie_financiere' => $this->formatStat('autonomie_financiere'),
                'ratio_prets_depots' => $this->formatStat('ratio_prets_depots'),
                'loan_to_deposit' => $this->formatStat('loan_to_deposit'),
                'endettement_general' => $this->formatStat('endettement_general'),
                'cout_du_risque' => $this->formatStat('cout_du_risque'),
                'moy_solidite' => $this->formatStat('moy_solidite'),
            ];
        } else {
            return [
                'dette_capitalisation' => $this->formatStat('dette_capitalisation'),
                'endettement_actif' => $this->formatStat('endettement_actif'),
                'endettement_general' => $this->formatStat('endettement_general'),
                'moy_solidite' => $this->formatStat('moy_solidite'),
            ];
        }
    }

    private function formatStat(string $field): array
    {
        return [
            'moy' => $this->metrics->{$field.'_moy'} ?? null,
            'ecart_type' => $this->metrics->{$field.'_ecart_type'} ?? null,
        ];
    }
}
