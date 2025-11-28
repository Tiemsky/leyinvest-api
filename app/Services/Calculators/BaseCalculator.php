<?php

namespace App\Services\Calculators;

use App\Contracts\FinancialCalculatorInterface;
use App\Models\StockFinancial;

/**
 * Calculateur de base pour les indicateurs financiers - Version 3.0
 *
 * IMPORTANT: Utilise les données DÉJÀ EN BASE
 * Calcule UNIQUEMENT les RATIOS et les CROISSANCES
 */
abstract class BaseCalculator implements FinancialCalculatorInterface
{
    protected string $sectorType;

    public function calculate(StockFinancial $financial, ?StockFinancial $previousYearFinancial = null): array
    {
        return [
            'croissance' => $this->calculateCroissance($financial, $previousYearFinancial),
            'rentabilite' => $this->calculateRentabilite($financial),
            'remuneration' => $this->calculateRemuneration($financial),
            'valorisation' => $this->calculateValorisation($financial),
            'solidite_financiere' => $this->calculateSoliditeFinanciere($financial),
        ];
    }

    // =====================================================
    // RENTABILITÉ - Ratios calculés à partir données en base
    // =====================================================

    protected function calculateMargeNette(StockFinancial $financial): ?float
    {
        $revenue = $this->getRevenue($financial);
        if (!$revenue || !$financial->resultat_net || $revenue == 0) {
            return null;
        }
        return ($financial->resultat_net / $revenue) * 100;
    }

    protected function calculateMargeEbitda(StockFinancial $financial): ?float
    {
        $revenue = $this->getRevenue($financial);
        if (!$revenue || !$financial->ebitda || $revenue == 0) {
            return null;
        }
        return ($financial->ebitda / $revenue) * 100;
    }

    protected function calculateMargeOperationnelle(StockFinancial $financial): ?float
    {
        $revenue = $this->getRevenue($financial);
        if (!$revenue || !$financial->ebit || $revenue == 0) {
            return null;
        }
        return ($financial->ebit / $revenue) * 100;
    }

    protected function calculateROE(StockFinancial $financial): ?float
    {
        if (!$financial->capitaux_propres || !$financial->resultat_net || $financial->capitaux_propres == 0) {
            return null;
        }
        return ($financial->resultat_net / $financial->capitaux_propres) * 100;
    }

    protected function calculateROA(StockFinancial $financial): ?float
    {
        if (!$financial->total_actif || !$financial->resultat_net || $financial->total_actif == 0) {
            return null;
        }
        return ($financial->resultat_net / $financial->total_actif) * 100;
    }

    // =====================================================
    // RÉMUNÉRATION - Ratios de distribution
    // =====================================================

    protected function calculateRendementDividende(StockFinancial $financial): ?float
    {
        if (!$financial->cours_31_12 || !$financial->dnpa || $financial->cours_31_12 == 0) {
            return null;
        }
        return ($financial->dnpa / $financial->cours_31_12) * 100;
    }

    protected function calculateTauxDistribution(StockFinancial $financial): ?float
    {
        if (!$financial->resultat_net || !$financial->dividendes_bruts || abs($financial->resultat_net) < 0.01) {
            return null;
        }
        return ($financial->dividendes_bruts / abs($financial->resultat_net)) * 100;
    }

    // =====================================================
    // VALORISATION - Ratios boursiers
    // =====================================================

    protected function calculatePBR(StockFinancial $financial): ?float
    {
        if (!$financial->capitaux_propres || !$financial->nombre_titre || !$financial->cours_31_12) {
            return null;
        }
        if ($financial->nombre_titre == 0) {
            return null;
        }
        $valeurComptableParAction = $financial->capitaux_propres / $financial->nombre_titre;
        if ($valeurComptableParAction == 0) {
            return null;
        }
        return $financial->cours_31_12 / $valeurComptableParAction;
    }

    protected function calculateRatioPS(StockFinancial $financial): ?float
    {
        $revenue = $this->getRevenue($financial);
        if (!$revenue || !$financial->nombre_titre || !$financial->cours_31_12) {
            return null;
        }
        if ($financial->nombre_titre == 0) {
            return null;
        }
        $revenuePerShare = $revenue / $financial->nombre_titre;
        if ($revenuePerShare == 0) {
            return null;
        }
        return $financial->cours_31_12 / $revenuePerShare;
    }

    protected function calculateEVEbitda(StockFinancial $financial): ?float
    {
        if (!$financial->cours_31_12 || !$financial->nombre_titre || !$financial->ebitda) {
            return null;
        }
        $capitalisation = $financial->cours_31_12 * $financial->nombre_titre;
        $ev = $capitalisation + ($financial->dette_totale ?? 0);
        if ($financial->ebitda == 0) {
            return null;
        }
        return $ev / $financial->ebitda;
    }

    protected function calculateCoursCible(StockFinancial $financial): ?float
    {
        // TODO: Implémenter selon méthodologie (DCF, multiples, Gordon-Shapiro)
        return null;
    }

    // =====================================================
    // CROISSANCE - Taux de variation
    // =====================================================

    protected function calculateGrowthRate(?float $currentValue, ?float $previousValue): ?float
    {
        if (is_null($currentValue) || is_null($previousValue)) {
            return null;
        }
        if (abs($previousValue) < 0.01) {
            return null; // Éviter division par zéro
        }
        return (($currentValue - $previousValue) / abs($previousValue)) * 100;
    }

    protected function calculateCroissanceRN(StockFinancial $current, ?StockFinancial $previous): ?float
    {
        if (!$previous) {
            return null;
        }
        return $this->calculateGrowthRate($current->resultat_net, $previous->resultat_net);
    }

    protected function calculateCroissanceEBIT(StockFinancial $current, ?StockFinancial $previous): ?float
    {
        if (!$previous) {
            return null;
        }
        return $this->calculateGrowthRate($current->ebit, $previous->ebit);
    }

    protected function calculateCroissanceEBITDA(StockFinancial $current, ?StockFinancial $previous): ?float
    {
        if (!$previous) {
            return null;
        }
        return $this->calculateGrowthRate($current->ebitda, $previous->ebitda);
    }

    protected function calculateCroissanceCAPEX(StockFinancial $current, ?StockFinancial $previous): ?float
    {
        if (!$previous) {
            return null;
        }
        return $this->calculateGrowthRate($current->capex, $previous->capex);
    }

    // =====================================================
    // FORMATAGE - Utilitaires
    // =====================================================

    protected function formatPercentage(?float $value): ?string
    {
        return $value !== null ? number_format($value, 2) . '%' : null;
    }

    protected function formatRatio(?float $value): ?string
    {
        return $value !== null ? number_format($value, 2) . 'x' : null;
    }

    protected function formatAmount(?float $value): ?string
    {
        return $value !== null ? number_format($value, 2) . ' FCFA' : null;
    }

    // =====================================================
    // ABSTRACT - À implémenter par classes filles
    // =====================================================

    /**
     * Retourne le revenu principal selon le type de secteur
     * - Services financiers: produit_net_bancaire
     * - Autres secteurs: chiffre_affaires
     */
    abstract protected function getRevenue(StockFinancial $financial): ?float;
}
