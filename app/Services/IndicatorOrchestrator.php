<?php

namespace App\Services;

use App\Models\Action;

/**
 * Orchestrateur principal pour les indicateurs financiers
 * Point d'entrée unique pour toutes les opérations
 */
class IndicatorOrchestrator
{
    public function __construct(
        private DashboardDataService $dashboardService,
        private HistoricalDataService $historicalService,
        private BenchmarkService $benchmarkService
    ) {}

    /**
     * Récupère le dashboard d'une action
     */
    public function getDashboard(Action $action, ?int $year = null, ?string $horizon = null): array
    {
        $year = $year ?? now()->year - 1;
        $horizon = $horizon ?? config('financial_indicators.default_horizon', 'court_terme');

        return $this->dashboardService->getDashboardData($action, $year, $horizon);
    }

    /**
     * Récupère les données historiques
     */
    public function getHistorical(
        Action $action,
        ?int $startYear = null,
        ?int $endYear = null,
        ?string $horizon = null
    ): array {
        $endYear = $endYear ?? now()->year - 1;
        $startYear = $startYear ?? $endYear - 3; // 4 ans par défaut
        $horizon = $horizon ?? config('financial_indicators.default_horizon', 'court_terme');

        return $this->historicalService->getHistoricalData($action, $startYear, $endYear, $horizon);
    }

    /**
     * Recalcule les benchmarks pour une année
     */
    public function recalculateBenchmarks(int $year): int
    {
        return $this->benchmarkService->calculateAllBenchmarks($year);
    }
}
