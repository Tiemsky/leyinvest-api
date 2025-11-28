<?php

namespace App\Observers;

use App\Events\StockFinancialUpdated;
use App\Jobs\RecalculateBenchmarksJob;
use App\Models\StockFinancial;
use App\Services\FinancialCacheService;
use Illuminate\Support\Facades\Log;

/**
 * Observer pour StockFinancial
 *
 * Déclenche automatiquement:
 * 1. Invalidation du cache
 * 2. Recalcul des benchmarks (asynchrone)
 * 3. Event StockFinancialUpdated
 */
class StockFinancialObserver
{
    public function __construct(
        private FinancialCacheService $cacheService
    ) {}

    /**
     * Appelé après la création d'un StockFinancial
     */
    public function created(StockFinancial $stockFinancial): void
    {
        $this->handleUpdate($stockFinancial, 'created');
    }

    /**
     * Appelé après la modification d'un StockFinancial
     */
    public function updated(StockFinancial $stockFinancial): void
    {
        $this->handleUpdate($stockFinancial, 'updated');
    }

    /**
     * Appelé après la suppression d'un StockFinancial
     */
    public function deleted(StockFinancial $stockFinancial): void
    {
        $this->handleUpdate($stockFinancial, 'deleted');
    }

    /**
     * Gère les événements de création/modification/suppression
     */
    private function handleUpdate(StockFinancial $stockFinancial, string $event): void
    {
        // 1. Invalider le cache pour cette action
        $this->invalidateCache($stockFinancial);

        // 2. Déclencher recalcul benchmarks si activé
        if (config('financial_indicators.benchmarks.auto_recalculate', true)) {
            $this->dispatchBenchmarkRecalculation($stockFinancial);
        }

        // 3. Dispatch event pour listeners externes
        event(new StockFinancialUpdated($stockFinancial, $event));

        Log::info("📊 StockFinancial {$event}", [
            'action_id' => $stockFinancial->action_id,
            'year' => $stockFinancial->year,
            'event' => $event,
        ]);
    }

    /**
     * Invalide le cache pour l'action concernée
     */
    private function invalidateCache(StockFinancial $stockFinancial): void
    {
        try {
            $action = $stockFinancial->action;
            if ($action) {
                $this->cacheService->forgetAction($action->key, $stockFinancial->year);
                Log::debug("🗑️ Cache invalidé pour {$action->symbole} - {$stockFinancial->year}");
            }
        } catch (\Exception $e) {
            Log::warning("Erreur invalidation cache: {$e->getMessage()}");
        }
    }

    /**
     * Dispatch le job de recalcul des benchmarks
     */
    private function dispatchBenchmarkRecalculation(StockFinancial $stockFinancial): void
    {
        try {
            // Dispatch en mode asynchrone
            RecalculateBenchmarksJob::dispatch($stockFinancial->year)
                ->onQueue('benchmarks')
                ->delay(now()->addSeconds(30)); // Délai de 30s pour grouper les modifications

            Log::debug("🚀 Job de recalcul benchmarks dispatché pour {$stockFinancial->year}");

        } catch (\Exception $e) {
            Log::error("Erreur dispatch job benchmarks: {$e->getMessage()}");
        }
    }
}
