<?php

namespace App\Services;

use App\Models\Action;
use App\Models\StockFinancial;
use App\Models\StockFinancialMetric;
use Illuminate\Support\Collection;

class FinancialMetricCalculator
{
    /**
     * Calculer toutes les métriques pour une action et une année donnée
     */
    public function calculateForAction(Action $action, int $year): ?StockFinancialMetric
    {
        $currentFinancial = $action->financials()->where('year', $year)->first();
        $previousFinancial = $action->financials()->where('year', $year - 1)->first();

        if (!$currentFinancial) {
            return null;
        }

        $isFinancialSector = $action->isFinancialService();

        $metrics = [
            'action_id' => $action->id,
            'year' => $year,
            'is_financial_sector' => $isFinancialSector,
            'calculated_at' => now(),
        ];

        // Calculer les indicateurs selon le type de secteur
        if ($isFinancialSector) {
            $metrics = array_merge($metrics, $this->calculateCroissanceSF($currentFinancial, $previousFinancial));
            $metrics = array_merge($metrics, $this->calculateSoliditeSF($currentFinancial));
        } else {
            $metrics = array_merge($metrics, $this->calculateCroissanceAS($currentFinancial, $previousFinancial));
            $metrics = array_merge($metrics, $this->calculateSoliditeAS($currentFinancial));
        }

        // Indicateurs communs à tous les secteurs
        $metrics = array_merge($metrics, $this->calculateRentabilite($currentFinancial, $isFinancialSector));
        $metrics = array_merge($metrics, $this->calculateRemuneration($currentFinancial));
        $metrics = array_merge($metrics, $this->calculateValorisation($currentFinancial));

        return StockFinancialMetric::updateOrCreate(
            ['action_id' => $action->id, 'year' => $year],
            $metrics
        );
    }

    /**
     * CROISSANCE - SECTEUR FINANCIER
     */
    private function calculateCroissanceSF(?StockFinancial $current, ?StockFinancial $previous): array
    {
        if (!$current || !$previous) {
            return $this->getEmptyCroissanceSF();
        }

        $croissance_pnb = $this->calculateGrowth($current->produit_net_bancaire, $previous->produit_net_bancaire);
        $croissance_ebit = $this->calculateGrowth($current->ebit, $previous->ebit);
        $croissance_ebitda = $this->calculateGrowth($current->ebitda, $previous->ebitda);
        $croissance_rn = $this->calculateGrowth($current->resultat_net, $previous->resultat_net);
        $croissance_capex = $this->calculateGrowth($current->capex, $previous->capex);

        $moy_croissance = $this->calculateAverage([
            $croissance_pnb,
            $croissance_ebit,
            $croissance_ebitda,
            $croissance_rn,
            $croissance_capex
        ]);

        return [
            'croissance_pnb' => $croissance_pnb,
            'croissance_ebit_sf' => $croissance_ebit,
            'croissance_ebitda_sf' => $croissance_ebitda,
            'croissance_rn_sf' => $croissance_rn,
            'croissance_capex_sf' => $croissance_capex,
            'moy_croissance_sf' => $moy_croissance,
        ];
    }

    /**
     * CROISSANCE - AUTRE SECTEUR
     */
    private function calculateCroissanceAS(?StockFinancial $current, ?StockFinancial $previous): array
    {
        if (!$current || !$previous) {
            return $this->getEmptyCroissanceAS();
        }

        $croissance_ca = $this->calculateGrowth($current->chiffre_affaires, $previous->chiffre_affaires);
        $croissance_ebit = $this->calculateGrowth($current->ebit, $previous->ebit);
        $croissance_ebitda = $this->calculateGrowth($current->ebitda, $previous->ebitda);
        $croissance_rn = $this->calculateGrowth($current->resultat_net, $previous->resultat_net);
        $croissance_capex = $this->calculateGrowth($current->capex, $previous->capex);

        $moy_croissance = $this->calculateAverage([
            $croissance_ca,
            $croissance_ebit,
            $croissance_ebitda,
            $croissance_rn,
            $croissance_capex
        ]);

        return [
            'croissance_ca' => $croissance_ca,
            'croissance_ebit_as' => $croissance_ebit,
            'croissance_ebitda_as' => $croissance_ebitda,
            'croissance_rn_as' => $croissance_rn,
            'croissance_capex_as' => $croissance_capex,
            'moy_croissance_as' => $moy_croissance,
        ];
    }

    /**
     * RENTABILITÉ - TOUS SECTEURS
     */
    private function calculateRentabilite(StockFinancial $financial, bool $isFinancialSector): array
    {
        // Chiffre d'affaires ou PNB selon le secteur
        $revenue = $isFinancialSector
            ? $financial->produit_net_bancaire
            : $financial->chiffre_affaires;

        $marge_nette = $this->safeDivide($financial->resultat_net, $revenue) * 100;
        $marge_ebitda = $this->safeDivide($financial->ebitda, $revenue) * 100;
        $marge_operationnelle = $this->safeDivide($financial->ebit, $revenue) * 100;
        $roe = $this->safeDivide($financial->resultat_net, $financial->capitaux_propres) * 100;
        $roa = $this->safeDivide($financial->resultat_net, $financial->total_actif) * 100;

        $moy_rentabilite = $this->calculateAverage([
            $marge_nette,
            $marge_ebitda,
            $marge_operationnelle,
            $roe,
            $roa
        ]);

        return [
            'marge_nette' => $marge_nette,
            'marge_ebitda' => $marge_ebitda,
            'marge_operationnelle' => $marge_operationnelle,
            'roe' => $roe,
            'roa' => $roa,
            'moy_rentabilite' => $moy_rentabilite,
        ];
    }

    /**
     * RÉMUNÉRATION - TOUS SECTEURS
     */
    private function calculateRemuneration(StockFinancial $financial): array
    {
        // DNPA = (0.88 × Dividendes Total) / Nombre de titres
        $dnpa = null;
        if ($financial->dividendes_bruts && $financial->nombre_titre) {
            $dnpa = $this->safeDivide((0.88 * $financial->dividendes_bruts * 1000000), $financial->nombre_titre);
        }

        // Rendement en dividendes = (DNPA / Cours au 31/12) × 100
        $rendement_dividendes = null;
        if ($dnpa && $financial->cours_31_12) {
            $rendement_dividendes = $this->safeDivide($dnpa, $financial->cours_31_12) * 100;
        }

        // Taux de distribution = (Résultat Net / Dividendes Bruts) × 100
        $taux_distribution = null;
        if ($financial->resultat_net && $financial->dividendes_bruts) {
            $taux_distribution = $this->safeDivide($financial->resultat_net, $financial->dividendes_bruts) * 100;
        }

        $moy_remuneration = $this->calculateAverage([
            $rendement_dividendes,
            $taux_distribution
        ]);

        return [
            'dnpa_calculated' => $dnpa,
            'rendement_dividendes' => $rendement_dividendes,
            'taux_distribution' => $taux_distribution,
            'moy_remuneration' => $moy_remuneration,
        ];
    }

    /**
     * VALORISATION - TOUS SECTEURS
     */
    private function calculateValorisation(StockFinancial $financial): array
    {
        // PER = (Cours × Nombre de titres) / (RN × 1 000 000)
        $per = null;
        if ($financial->cours_31_12 && $financial->nombre_titre && $financial->resultat_net) {
            $market_cap = $financial->cours_31_12 * $financial->nombre_titre;
            $per = $this->safeDivide($market_cap, ($financial->resultat_net * 1000000));
        }

        // PBR = ROE × PER
        $pbr = null;
        $roe = $this->safeDivide($financial->resultat_net, $financial->capitaux_propres);
        if ($roe && $per) {
            $pbr = $roe * $per;
        }

        // Ratio P/S = Cours / (CA ÷ Nombre de titres)
        $ratio_ps = null;
        if ($financial->cours_31_12 && $financial->chiffre_affaires && $financial->nombre_titre) {
            $ca_per_share = $this->safeDivide(($financial->chiffre_affaires * 1000000), $financial->nombre_titre);
            $ratio_ps = $this->safeDivide($financial->cours_31_12, $ca_per_share);
        }

        // EV/EBITDA = (Cours × Nombre de titres) / EBITDA
        $ev_ebitda = null;
        if ($financial->cours_31_12 && $financial->nombre_titre && $financial->ebitda) {
            $market_cap = $financial->cours_31_12 * $financial->nombre_titre;
            $ev_ebitda = $this->safeDivide($market_cap, ($financial->ebitda * 1000000));
        }

        // Cours cible = DNPA / (Rendement moyen)
        $cours_cible = null;
        $potentiel_hausse = null;
        $dnpa = null;
        if ($financial->dividendes_bruts && $financial->nombre_titre) {
            $dnpa = $this->safeDivide((0.88 * $financial->dividendes_bruts * 1000000), $financial->nombre_titre);
            $rendement_dividendes = $this->safeDivide($dnpa, $financial->cours_31_12) * 100;

            if ($rendement_dividendes && $dnpa) {
                $cours_cible = $this->safeDivide($dnpa, ($rendement_dividendes / 100));

                if ($financial->cours_31_12) {
                    $potentiel_hausse = (($cours_cible - $financial->cours_31_12) / $financial->cours_31_12) * 100;
                }
            }
        }

        $moy_valorisation = $this->calculateAverage([
            $per,
            $pbr,
            $ratio_ps,
            $ev_ebitda
        ]);

        return [
            'per' => $per,
            'pbr' => $pbr,
            'ratio_ps' => $ratio_ps,
            'ev_ebitda' => $ev_ebitda,
            'cours_cible' => $cours_cible,
            'potentiel_hausse' => $potentiel_hausse,
            'moy_valorisation' => $moy_valorisation,
        ];
    }

    /**
     * SOLIDITÉ FINANCIÈRE - SECTEUR FINANCIER
     */
    private function calculateSoliditeSF(StockFinancial $financial): array
    {
        // Autonomie financière = (Capitaux Propres / Total Actif) × 100
        $autonomie_financiere = $this->safeDivide($financial->capitaux_propres, $financial->total_actif) * 100;

        // Ratio prêts sur dépôts = (Crédits clientèle / (Capitaux Propres + Dépôts + Dette)) × 100
        $ratio_prets_depots = null;
        $denominator = ($financial->capitaux_propres ?? 0) + ($financial->depots_clientele ?? 0) + ($financial->dette_totale ?? 0);
        if ($denominator > 0 && $financial->credits_clientele) {
            $ratio_prets_depots = $this->safeDivide($financial->credits_clientele, $denominator) * 100;
        }

        // Loan-to-Deposit Ratio = (Crédits / Dépôts) × 100
        $loan_to_deposit = null;
        if ($financial->credits_clientele && $financial->depots_clientele) {
            $loan_to_deposit = $this->safeDivide($financial->credits_clientele, $financial->depots_clientele) * 100;
        }

        // Endettement général = (Dette totale / Capitaux Propres) × 100
        $endettement_general = $this->safeDivide($financial->dette_totale, $financial->capitaux_propres) * 100;

        // Coût du risque (valeur directe)
        $cout_du_risque = $financial->cout_du_risque;

        $moy_solidite = $this->calculateAverage([
            $autonomie_financiere,
            $ratio_prets_depots,
            $loan_to_deposit,
            $endettement_general
        ]);

        return [
            'autonomie_financiere' => $autonomie_financiere,
            'ratio_prets_depots' => $ratio_prets_depots,
            'loan_to_deposit' => $loan_to_deposit,
            'endettement_general_sf' => $endettement_general,
            'cout_du_risque_value' => $cout_du_risque,
            'moy_solidite_sf' => $moy_solidite,
        ];
    }

    /**
     * SOLIDITÉ FINANCIÈRE - AUTRE SECTEUR
     */
    private function calculateSoliditeAS(StockFinancial $financial): array
    {
        // Dette sur capitalisation = (Dette Totale / (Dette totale + Capitaux propres)) × 100
        $dette_capitalisation = null;
        $denominator = ($financial->dette_totale ?? 0) + ($financial->capitaux_propres ?? 0);
        if ($denominator > 0 && $financial->dette_totale) {
            $dette_capitalisation = $this->safeDivide($financial->dette_totale, $denominator) * 100;
        }

        // Endettement sur actif = (Dette Totale / Total Actif) × 100
        $endettement_actif = $this->safeDivide($financial->dette_totale, $financial->total_actif) * 100;

        // Endettement général = (Dette Totale / Capitaux Propres) × 100
        $endettement_general = $this->safeDivide($financial->dette_totale, $financial->capitaux_propres) * 100;

        $moy_solidite = $this->calculateAverage([
            $dette_capitalisation,
            $endettement_actif,
            $endettement_general
        ]);

        return [
            'dette_capitalisation' => $dette_capitalisation,
            'endettement_actif' => $endettement_actif,
            'endettement_general_as' => $endettement_general,
            'moy_solidite_as' => $moy_solidite,
        ];
    }

    /**
     * Calculer la croissance entre deux valeurs
     */
    private function calculateGrowth(?float $current, ?float $previous): ?float
    {
        if (!$current || !$previous || $previous == 0) {
            return null;
        }

        return (($current - $previous) / abs($previous)) * 100;
    }

    /**
     * Division sécurisée (évite division par zéro)
     */
    private function safeDivide(?float $numerator, ?float $denominator): ?float
    {
        if (!$numerator || !$denominator || $denominator == 0) {
            return null;
        }

        return $numerator / $denominator;
    }

    /**
     * Calculer la moyenne en ignorant les valeurs null
     */
    private function calculateAverage(array $values): ?float
    {
        $filtered = array_filter($values, fn($v) => $v !== null);

        if (empty($filtered)) {
            return null;
        }

        return array_sum($filtered) / count($filtered);
    }

    /**
     * Retourner un tableau vide pour la croissance SF
     */
    private function getEmptyCroissanceSF(): array
    {
        return [
            'croissance_pnb' => null,
            'croissance_ebit_sf' => null,
            'croissance_ebitda_sf' => null,
            'croissance_rn_sf' => null,
            'croissance_capex_sf' => null,
            'moy_croissance_sf' => null,
        ];
    }

    /**
     * Retourner un tableau vide pour la croissance AS
     */
    private function getEmptyCroissanceAS(): array
    {
        return [
            'croissance_ca' => null,
            'croissance_ebit_as' => null,
            'croissance_ebitda_as' => null,
            'croissance_rn_as' => null,
            'croissance_capex_as' => null,
            'moy_croissance_as' => null,
        ];
    }
}
