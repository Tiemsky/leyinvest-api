<?php

namespace App\Services\Calculators;

use App\Models\StockFinancial;

/**
 * Calculateur pour les secteurs standards (non-financiers) - Version 3.0
 *
 * Secteurs concernés:
 * - Télécommunications, Distribution, Industrie, Agriculture, Transport, etc.
 *
 * Spécificités:
 * - Utilise Chiffre d'Affaires (CA) comme revenu
 * - Indicateurs de solidité financière standards (3 indicateurs)
 */
class StandardSectorCalculator extends BaseCalculator
{
    protected string $sectorType = 'autres_secteurs';

    /**
     * {@inheritDoc}
     * Pour les secteurs standards, le revenu = Chiffre d'Affaires (CA)
     */
    protected function getRevenue(StockFinancial $financial): ?float
    {
        return $financial->chiffre_affaires;
    }

    /**
     * {@inheritDoc}
     */
    public function calculateCroissance(StockFinancial $financial, ?StockFinancial $previousYearFinancial = null): array
    {
        if (!$previousYearFinancial) {
            return [
                'croissance_ca' => ['valeur' => null, 'formatted' => null],
                'croissance_rn' => ['valeur' => null, 'formatted' => null],
                'croissance_ebit' => ['valeur' => null, 'formatted' => null],
                'croissance_ebitda' => ['valeur' => null, 'formatted' => null],
                'croissance_capex' => ['valeur' => null, 'formatted' => null],
            ];
        }

        $croissanceCA = $this->calculateGrowthRate(
            $financial->chiffre_affaires,
            $previousYearFinancial->chiffre_affaires
        );

        return [
            'croissance_ca' => [
                'valeur' => $croissanceCA,
                'formatted' => $this->formatPercentage($croissanceCA),
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
     * Indicateurs de solidité financière pour secteurs STANDARDS
     */
    public function calculateSoliditeFinanciere(StockFinancial $financial): array
    {
        return [
            'dette_sur_capitalisation' => [
                'valeur' => $this->calculateDetteSurCapitalisation($financial),
                'formatted' => $this->formatPercentage($this->calculateDetteSurCapitalisation($financial)),
            ],
            'endettement_sur_actif' => [
                'valeur' => $this->calculateEndettementSurActif($financial),
                'formatted' => $this->formatPercentage($this->calculateEndettementSurActif($financial)),
            ],
            'endettement_general' => [
                'valeur' => $this->calculateEndettementGeneral($financial),
                'formatted' => $this->formatPercentage($this->calculateEndettementGeneral($financial)),
            ],
        ];
    }

    // =====================================================
    // SOLIDITÉ FINANCIÈRE - Formules Secteurs Standards
    // =====================================================

    /**
     * Dette sur Capitalisation = (Dette Totale / Capitalisation Boursière) × 100
     * Ratio < 50% = structure saine
     */
    private function calculateDetteSurCapitalisation(StockFinancial $financial): ?float
    {
        if (!$financial->dette_totale || !$financial->cours_31_12 || !$financial->nombre_titre) {
            return null;
        }
        $capitalisation = $financial->cours_31_12 * $financial->nombre_titre;
        if ($capitalisation == 0) {
            return null;
        }
        return ($financial->dette_totale / $capitalisation) * 100;
    }

    /**
     * Endettement sur Actif = (Dette Totale / Total Actif) × 100
     * Ratio < 50% = structure équilibrée
     */
    private function calculateEndettementSurActif(StockFinancial $financial): ?float
    {
        if (!$financial->dette_totale || !$financial->total_actif || $financial->total_actif == 0) {
            return null;
        }
        return ($financial->dette_totale / $financial->total_actif) * 100;
    }

    /**
     * Endettement Général = (Dette Totale / Capitaux Propres) × 100
     * Ratio de levier financier (Gearing)
     * < 100% = sain, 100-200% = attention, > 200% = risque élevé
     */
    private function calculateEndettementGeneral(StockFinancial $financial): ?float
    {
        if (!$financial->dette_totale || !$financial->capitaux_propres || $financial->capitaux_propres == 0) {
            return null;
        }
        return ($financial->dette_totale / $financial->capitaux_propres) * 100;
    }
}
