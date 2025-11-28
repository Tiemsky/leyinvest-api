<?php

namespace App\Jobs;

use App\Services\BenchmarkService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job asynchrone pour recalculer les benchmarks d'une année
 *
 * Déclenché automatiquement par StockFinancialObserver
 * lors de la création/modification d'un StockFinancial
 */
class RecalculateBenchmarksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Nombre de tentatives
     */
    public int $tries = 3;

    /**
     * Timeout en secondes (5 minutes)
     */
    public int $timeout = 300;

    /**
     * Délais entre les tentatives (1 min, 3 min)
     */
    public array $backoff = [60, 180];

    /**
     * Année à recalculer
     */
    private int $year;

    /**
     * Create a new job instance.
     */
    public function __construct(int $year)
    {
        $this->year = $year;
        $this->onQueue('benchmarks'); // Queue dédiée
    }

    /**
     * Execute the job.
     */
    public function handle(BenchmarkService $benchmarkService): void
    {
        Log::info("🔄 Début recalcul benchmarks pour l'année {$this->year}");

        try {
            $count = $benchmarkService->calculateAllBenchmarks($this->year);

            Log::info("✅ Benchmarks recalculés avec succès: {$count} benchmarks générés pour {$this->year}");

        } catch (\Exception $e) {
            Log::error("❌ Erreur recalcul benchmarks {$this->year}: {$e->getMessage()}", [
                'exception' => $e,
                'year' => $this->year,
            ]);

            // Re-throw pour que Laravel gère les retries
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("❌ Échec définitif recalcul benchmarks {$this->year} après {$this->tries} tentatives", [
            'exception' => $exception->getMessage(),
            'year' => $this->year,
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['benchmarks', 'year:' . $this->year];
    }
}
