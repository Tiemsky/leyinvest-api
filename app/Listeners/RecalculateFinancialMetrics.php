<?php

namespace App\Listeners;

use App\Events\StockFinancialUpdated;
use App\Services\FinancialMetricCalculator;
use App\Services\SectorMetricAggregator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class RecalculateFinancialMetrics implements ShouldQueue
{
    private FinancialMetricCalculator $calculator;
    private SectorMetricAggregator $aggregator;

    public function __construct(
        FinancialMetricCalculator $calculator,
        SectorMetricAggregator $aggregator
    ) {
        $this->calculator = $calculator;
        $this->aggregator = $aggregator;
    }

    /**
     * Gérer l'événement
     */
    public function handle(StockFinancialUpdated $event): void
    {
        try {
            $stockFinancial = $event->stockFinancial;
            $action = $stockFinancial->action;
            $year = $stockFinancial->year;

            // Recalculer les métriques de l'action
            $this->calculator->calculateForAction($action, $year);

            // Recalculer les métriques sectorielles
            $this->aggregator->recalculateForAction($action, $year);

            Log::info("Métriques financières recalculées pour l'action {$action->id} (année {$year})");
        } catch (\Exception $e) {
            Log::error("Erreur lors du recalcul des métriques: {$e->getMessage()}");
            throw $e;
        }
    }
}
