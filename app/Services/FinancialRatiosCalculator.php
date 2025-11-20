<?php

/**
 * ============================================================================
 * SERVICE: FinancialRatiosCalculator
 * ============================================================================
 * Service principal pour calculer tous les ratios
 */
namespace App\Services;

use App\Models\ActionRatio;
use App\Models\Action;
use App\Models\StockFinancial;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinancialRatiosCalculator
{
    /**
     * Calcule tous les ratios pour une action et une année
     */
    public function calculateForAction(Action $action, int $year): ActionRatio
    {
        // Récupère les données financières de l'année courante
        $currentFinancial = $action->financials()->where('year', $year)->first();

        if (!$currentFinancial) {
            throw new \Exception("Données financières manquantes pour {$action->code} année {$year}");
        }

        // Récupère les données des années précédentes pour les calculs de croissance
        $previousYearFinancial = $action->financials()->where('year', $year - 1)->first();
        $threeYearsAgo = $action->financials()->where('year', $year - 3)->first();

        $ratios = [
            'action_id' => $action->id,
            'year' => $year,
            'calculated_at' => now(),
        ];

        // === CROISSANCE ===
        $ratios = array_merge($ratios, $this->calculateGrowthMetrics(
            $currentFinancial,
            $previousYearFinancial,
            $threeYearsAgo
        ));

        // === RENTABILITÉ ===
        $ratios = array_merge($ratios, $this->calculateProfitabilityMetrics($currentFinancial));

        // === RÉMUNÉRATION ===
        $ratios = array_merge($ratios, $this->calculateShareholderReturnMetrics($currentFinancial));

        // === RATIOS STRUCTURELS ===
        $ratios = array_merge($ratios, $this->calculateStructuralRatios($currentFinancial));

        // === VALORISATION ===
        $ratios = array_merge($ratios, $this->calculateValuationMetrics($currentFinancial));

        // Sauvegarde ou met à jour
        return ActionRatio::updateOrCreate(
            ['action_id' => $action->id, 'year' => $year],
            $ratios
        );
    }

    /**
     * SECTION 1: Calculs de Croissance
     */
    protected function calculateGrowthMetrics($current, $previous, $threeYears): array
    {
        $metrics = [
            'pnb_growth' => null,
            'rn_growth' => null,
            'ebit_growth' => null,
            'ebitda_growth' => null,
            'capex_growth' => null,
            'avg_growth_3y' => null,
        ];

        if ($previous) {
            // Croissance PNB
            $metrics['pnb_growth'] = $this->calculateGrowthRate(
                $previous->produit_net_bancaire,
                $current->produit_net_bancaire
            );

            // Croissance Résultat Net
            $metrics['rn_growth'] = $this->calculateGrowthRate(
                $previous->resultat_net,
                $current->resultat_net
            );

            // Croissance EBIT
            $metrics['ebit_growth'] = $this->calculateGrowthRate(
                $previous->ebit,
                $current->ebit
            );

            // Croissance EBITDA
            $metrics['ebitda_growth'] = $this->calculateGrowthRate(
                $previous->ebitda,
                $current->ebitda
            );

            // Croissance CAPEX
            $metrics['capex_growth'] = $this->calculateGrowthRate(
                $previous->capex,
                $current->capex
            );
        }

        // Moyenne de croissance sur 3 ans (CAGR)
        if ($threeYears && $threeYears->resultat_net && $current->resultat_net) {
            $metrics['avg_growth_3y'] = $this->calculateCAGR(
                $threeYears->resultat_net,
                $current->resultat_net,
                3
            );
        }

        return $metrics;
    }

    /**
     * SECTION 2: Calculs de Rentabilité
     */
    protected function calculateProfitabilityMetrics(StockFinancial $financial): array
    {
        $metrics = [
            'net_margin' => null,
            'ebitda_margin' => null,
            'operating_margin' => null,
            'roe' => null,
            'roa' => null,
            'avg_profitability' => null,
        ];

        // Marge Nette = (RN / PNB) × 100
        if ($financial->produit_net_bancaire && $financial->produit_net_bancaire != 0) {
            $metrics['net_margin'] = round(
                ($financial->resultat_net / $financial->produit_net_bancaire) * 100,
                2
            );
        }

        // Marge EBITDA = (EBITDA / PNB) × 100
        if ($financial->produit_net_bancaire && $financial->produit_net_bancaire != 0) {
            $metrics['ebitda_margin'] = round(
                ($financial->ebitda / $financial->produit_net_bancaire) * 100,
                2
            );
        }

        // Marge Opérationnelle = (EBIT / PNB) × 100
        if ($financial->produit_net_bancaire && $financial->produit_net_bancaire != 0) {
            $metrics['operating_margin'] = round(
                ($financial->ebit / $financial->produit_net_bancaire) * 100,
                2
            );
        }

        // ROE = (RN / Capitaux Propres) × 100
        if ($financial->capitaux_propres && $financial->capitaux_propres != 0) {
            $metrics['roe'] = round(
                ($financial->resultat_net / $financial->capitaux_propres) * 100,
                2
            );
        }

        // ROA = (RN / Total Actif) × 100
        if ($financial->total_actif && $financial->total_actif != 0) {
            $metrics['roa'] = round(
                ($financial->resultat_net / $financial->total_actif) * 100,
                2
            );
        }

        // Moyenne de rentabilité (moyenne des marges)
        $margins = array_filter([
            $metrics['net_margin'],
            $metrics['ebitda_margin'],
            $metrics['operating_margin']
        ], fn($v) => $v !== null);

        if (!empty($margins)) {
            $metrics['avg_profitability'] = round(array_sum($margins) / count($margins), 2);
        }

        return $metrics;
    }

    /**
     * SECTION 3: Calculs de Rémunération
     */
    protected function calculateShareholderReturnMetrics(StockFinancial $financial): array
    {
        $metrics = [
            'dnpa' => $financial->dnpa,
            'dividend_yield' => null,
            'payout_ratio' => null,
            'avg_dividend_yield' => null,
        ];

        // Rendement du dividende = (DNPA / Cours) × 100
        if ($financial->dnpa && $financial->cours_31_12 && $financial->cours_31_12 != 0) {
            $metrics['dividend_yield'] = round(
                ($financial->dnpa / $financial->cours_31_12) * 100,
                2
            );
        }

        // Taux de distribution = (Dividendes totaux / RN) × 100
        if ($financial->dividendes_bruts && $financial->resultat_net && $financial->resultat_net > 0) {
            $metrics['payout_ratio'] = round(
                ($financial->dividendes_bruts / $financial->resultat_net) * 100,
                2
            );
        }

        // Note: avg_dividend_yield sera calculé séparément sur plusieurs années

        return $metrics;
    }

    /**
     * SECTION 4: Ratios Structurels
     */
    protected function calculateStructuralRatios(StockFinancial $financial): array
    {
        $metrics = [
            'debt_ratio' => null,
            'equity_ratio' => null,
            'cost_of_risk_ratio' => null,
            'per' => $financial->per,
        ];

        // Taux d'endettement = (Dette totale / Total Actif) × 100
        if ($financial->total_actif && $financial->total_actif != 0) {
            $metrics['debt_ratio'] = round(
                ($financial->dette_totale / $financial->total_actif) * 100,
                2
            );
        }

        // Ratio de fonds propres = (Capitaux propres / Total Actif) × 100
        if ($financial->total_actif && $financial->total_actif != 0) {
            $metrics['equity_ratio'] = round(
                ($financial->capitaux_propres / $financial->total_actif) * 100,
                2
            );
        }

        // Coût du risque = (Coût du risque / PNB) × 100
        if ($financial->produit_net_bancaire && $financial->produit_net_bancaire != 0) {
            $metrics['cost_of_risk_ratio'] = round(
                ($financial->cout_du_risque / $financial->produit_net_bancaire) * 100,
                2
            );
        }

        return $metrics;
    }

    /**
     * SECTION 5: Valorisation
     */
    protected function calculateValuationMetrics(StockFinancial $financial): array
    {
        $metrics = [
            'market_cap' => null,
            'book_value_per_share' => null,
            'price_to_book' => null,
        ];

        // Capitalisation boursière = Nombre de titres × Cours
        if ($financial->total_shares && $financial->cours_31_12) {
            $metrics['market_cap'] = round(
                ($financial->total_shares * $financial->cours_31_12) / 1000000,
                2
            ); // En millions
        }

        // Valeur comptable par action = Capitaux propres / Nombre de titres
        if ($financial->total_shares && $financial->total_shares > 0) {
            $metrics['book_value_per_share'] = round(
                ($financial->capitaux_propres * 1000000) / $financial->total_shares,
                2
            );
        }

        // Price to Book = Cours / Valeur comptable par action
        if ($metrics['book_value_per_share'] && $metrics['book_value_per_share'] > 0) {
            $metrics['price_to_book'] = round(
                $financial->cours_31_12 / $metrics['book_value_per_share'],
                2
            );
        }

        return $metrics;
    }

    // === MÉTHODES UTILITAIRES ===

    /**
     * Calcule le taux de croissance entre deux valeurs
     */
    protected function calculateGrowthRate(?float $previous, ?float $current): ?float
    {
        if (!$previous || !$current || $previous == 0) {
            return null;
        }

        return round((($current - $previous) / abs($previous)) * 100, 2);
    }

    /**
     * Calcule le CAGR (Taux de croissance annuel composé)
     */
    protected function calculateCAGR(?float $startValue, ?float $endValue, int $years): ?float
    {
        if (!$startValue || !$endValue || $startValue <= 0 || $years == 0) {
            return null;
        }

        return round((pow($endValue / $startValue, 1 / $years) - 1) * 100, 2);
    }

    /**
     * Calcule les ratios pour toutes les actions d'une année
     */
    public function calculateForYear(int $year): array
    {
        $results = [
            'success' => 0,
            'errors' => 0,
            'details' => []
        ];

        $actions = Action::where('is_active', true)
            ->whereHas('financials', fn($q) => $q->where('year', $year))
            ->get();

        foreach ($actions as $action) {
            try {
                $this->calculateForAction($action, $year);
                $results['success']++;
                $results['details'][] = "✓ {$action->code} ({$year})";
            } catch (\Exception $e) {
                $results['errors']++;
                $results['details'][] = "✗ {$action->code}: {$e->getMessage()}";
                Log::error("Erreur calcul ratios {$action->code}", ['error' => $e->getMessage()]);
            }
        }

        return $results;
    }

    /**
     * Calcule les moyennes sectorielles
     */
    public function calculateSectorAverages(int $year): void
    {
        $sectors = Action::where('is_active', true)
            ->whereNotNull('sector')
            ->distinct()
            ->pluck('sector');

        foreach ($sectors as $sector) {
            $ratios = ActionRatio::whereHas('action', fn($q) => $q->where('sector', $sector))
                ->where('year', $year)
                ->get();

            if ($ratios->isEmpty()) continue;

            SectorAverage::updateOrCreate(
                ['sector' => $sector, 'year' => $year],
                [
                    'avg_roe' => $ratios->avg('roe'),
                    'avg_roa' => $ratios->avg('roa'),
                    'avg_net_margin' => $ratios->avg('net_margin'),
                    'avg_debt_ratio' => $ratios->avg('debt_ratio'),
                    'avg_dividend_yield' => $ratios->avg('dividend_yield'),
                    'avg_per' => $ratios->avg('per'),
                    'actions_count' => $ratios->count(),
                ]
            );
        }
    }
}
