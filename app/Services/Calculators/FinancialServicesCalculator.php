<?php

namespace App\Services\Calculators;

use App\Models\StockFinancial;

/**
 * Calculateur pour le secteur des services financiers - Version 3.0
 *
 * Spécificités:
 * - Utilise Produit Net Bancaire (PNB) au lieu du Chiffre d'Affaires
 * - Indicateurs de solidité spécifiques (5 indicateurs)
 * - Coût du Risque
 */
class FinancialServicesCalculator extends BaseCalculator
{
    protected string $sectorType = 'services_financiers';

    /**
     * {@inheritDoc}
     * Pour les services financiers, le revenu = Produit Net Bancaire (PNB)
     */
    protected function getRevenue(StockFinancial $financial): ?float
    {
        return $financial->produit_net_bancaire;
    }

    /**
     * {@inheritDoc}
     */
    public function calculateCroissance(StockFinancial $financial, ?StockFinancial $previousYearFinancial = null): array
    {
        if (!$previousYearFinancial) {
            return [
                'croissance_pnb' => ['valeur' => null, 'formatted' => null],
                'croissance_rn' => ['valeur' => null, 'formatted' => null],
                'croissance_ebit' => ['valeur' => null, 'formatted' => null],
                'croissance_ebitda' => ['valeur' => null, 'formatted' => null],
                'croissance_capex' => ['valeur' => null, 'formatted' => null],
            ];
        }

        $croissancePNB = $this->calculateGrowthRate(
            $financial->produit_net_bancaire,
            $previousYearFinancial->produit_net_bancaire
        );

        return [
            'croissance_pnb' => [
                'valeur' => $croissancePNB,
                'formatted' => $this->formatPercentage($croissancePNB),
            ],
            'croissance_rn' => [
                'valeur' => $this->calculateCroissanceRN($financial, $previousYearFinancial),
                'formatted' => $this->formatPercentage(
                    $this->calculateCroissanceRN($financial, $previousYearFinancial)
                ),
            ],
            'croissance_ebit' => [
                'valeur' => $this->calculateCroissanceEBIT($financial, $previousYearFinancial),
                'formatted' => $this->formatPercentage(
                    $this->calculateCroissanceEBIT($financial, $previousYearFinancial)
                ),
            ],
            'croissance_ebitda' => [
                'valeur' => $this->calculateCroissanceEBITDA($financial, $previousYearFinancial),
                'formatted' => $this->formatPercentage(
                    $this->calculateCroissanceEBITDA($financial, $previousYearFinancial)
                ),
            ],
            'croissance_capex' => [
                'valeur' => $this->calculateCroissanceCAPEX($financial, $previousYearFinancial),
                'formatted' => $this->formatPercentage(
                    $this->calculateCroissanceCAPEX($financial, $previousYearFinancial)
                ),
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function calculateRentabilite(StockFinancial $financial): array
    {
        return [
            'marge_nette' => [
                'valeur' => $this->calculateMargeNette($financial),
                'formatted' => $this->formatPercentage($this->calculateMargeNette($financial)),
            ],
            'marge_ebitda' => [
                'valeur' => $this->calculateMargeEbitda($financial),
                'formatted' => $this->formatPercentage($this->calculateMargeEbitda($financial)),
            ],
            'marge_operationnelle' => [
                'valeur' => $this->calculateMargeOperationnelle($financial),
                'formatted' => $this->formatPercentage($this->calculateMargeOperationnelle($financial)),
            ],
            'roe' => [
                'valeur' => $this->calculateROE($financial),
                'formatted' => $this->formatPercentage($this->calculateROE($financial)),
            ],
            'roa' => [
                'valeur' => $this->calculateROA($financial),
                'formatted' => $this->formatPercentage($this->calculateROA($financial)),
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function calculateRemuneration(StockFinancial $financial): array
    {
        return [
            'dnpa' => [
                'valeur' => $financial->dnpa,
                'formatted' => $this->formatAmount($financial->dnpa),
            ],
            'rendement_dividende' => [
                'valeur' => $this->calculateRendementDividende($financial),
                'formatted' => $this->formatPercentage($this->calculateRendementDividende($financial)),
            ],
            'taux_distribution' => [
                'valeur' => $this->calculateTauxDistribution($financial),
                'formatted' => $this->formatPercentage($this->calculateTauxDistribution($financial)),
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function calculateValorisation(StockFinancial $financial): array
    {
        return [
            'per' => [
                'valeur' => $financial->per,
                'formatted' => $this->formatRatio($financial->per),
            ],
            'pbr' => [
                'valeur' => $this->calculatePBR($financial),
                'formatted' => $this->formatRatio($this->calculatePBR($financial)),
            ],
            'ratio_ps' => [
                'valeur' => $this->calculateRatioPS($financial),
                'formatted' => $this->formatRatio($this->calculateRatioPS($financial)),
            ],
            'ev_ebitda' => [
                'valeur' => $this->calculateEVEbitda($financial),
                'formatted' => $this->formatRatio($this->calculateEVEbitda($financial)),
            ],
            'cours_cible' => [
                'valeur' => $this->calculateCoursCible($financial),
                'formatted' => $this->calculateCoursCible($financial) !== null
                    ? $this->formatAmount($this->calculateCoursCible($financial))
                    : null,
            ],
        ];
    }

    /**
     * {@inheritDoc}
     * Indicateurs de solidité financière SPÉCIFIQUES aux services financiers
     */
    public function calculateSoliditeFinanciere(StockFinancial $financial): array
    {
        return [
            'ratio_autonomie_financiere' => [
                'valeur' => $this->calculateRatioAutonomieFinanciere($financial),
                'formatted' => $this->formatPercentage($this->calculateRatioAutonomieFinanciere($financial)),
            ],
            'ratio_prets_depots_capitaux' => [
                'valeur' => $this->calculateRatioPretsDepotsCapitaux($financial),
                'formatted' => $this->formatRatio($this->calculateRatioPretsDepotsCapitaux($financial)),
            ],
            'loan_to_deposit' => [
                'valeur' => $this->calculateLoanToDeposit($financial),
                'formatted' => $this->formatPercentage($this->calculateLoanToDeposit($financial)),
            ],
            'ratio_endettement_general' => [
                'valeur' => $this->calculateRatioEndettementGeneral($financial),
                'formatted' => $this->formatPercentage($this->calculateRatioEndettementGeneral($financial)),
            ],
            'cout_du_risque' => [
                'valeur' => $financial->cout_du_risque,
                'formatted' => $this->formatAmount($financial->cout_du_risque),
            ],
        ];
    }

    // =====================================================
    // SOLIDITÉ FINANCIÈRE - Formules Services Financiers
    // =====================================================

    /**
     * Ratio d'Autonomie Financière = (Capitaux Propres / Total Actif) × 100
     * Plus élevé = meilleure solidité
     */
    private function calculateRatioAutonomieFinanciere(StockFinancial $financial): ?float
    {
        if (!$financial->total_actif || !$financial->capitaux_propres || $financial->total_actif == 0) {
            return null;
        }
        return ($financial->capitaux_propres / $financial->total_actif) * 100;
    }

    /**
     * Ratio Prêts/Dépôts/Capitaux = Crédits Clientèle / (Dépôts Clientèle + Capitaux Propres)
     * Idéalement < 1 (ressources couvrent emplois)
     */
    private function calculateRatioPretsDepotsCapitaux(StockFinancial $financial): ?float
    {
        if (!$financial->credits_clientele || !$financial->depots_clientele || !$financial->capitaux_propres) {
            return null;
        }
        $ressources = $financial->depots_clientele + $financial->capitaux_propres;
        if ($ressources == 0) {
            return null;
        }
        return $financial->credits_clientele / $ressources;
    }

    /**
     * Loan to Deposit = (Crédits Clientèle / Dépôts Clientèle) × 100
     * Norme prudentielle: < 100%
     */
    private function calculateLoanToDeposit(StockFinancial $financial): ?float
    {
        if (!$financial->credits_clientele || !$financial->depots_clientele || $financial->depots_clientele == 0) {
            return null;
        }
        return ($financial->credits_clientele / $financial->depots_clientele) * 100;
    }

    /**
     * Ratio d'Endettement Général = (Dette Totale / Capitaux Propres) × 100
     */
    private function calculateRatioEndettementGeneral(StockFinancial $financial): ?float
    {
        if (!$financial->dette_totale || !$financial->capitaux_propres || $financial->capitaux_propres == 0) {
            return null;
        }
        return ($financial->dette_totale / $financial->capitaux_propres) * 100;
    }
}
