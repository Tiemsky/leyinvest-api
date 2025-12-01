<?php

namespace App\Jobs;

use App\Models\BrvmSector;
use App\Services\BenchmarkService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculateSectorBenchmarks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600; // 1 heure
    public int $tries = 3;

    public function __construct(
        private ?int $year = null,
        private ?string $horizon = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(BenchmarkService $benchmarkService): void
    {
        $year = $this->year ?? (now()->year - 1);
        $horizons = $this->horizon
            ? [$this->horizon]
            : config('financial_indicators.horizons', ['court_terme', 'moyen_terme', 'long_terme']);

        Log::info("Début du calcul des benchmarks pour l'année {$year}");

        $sectors = BrvmSector::all();
        $totalCalculations = 0;

        foreach ($sectors as $sector) {
            foreach ($horizons as $horizon) {
                try {
                    Log::info("Calcul benchmark pour {$sector->nom} - {$horizon}");

                    $benchmarkService->calculateAndStoreSectorBenchmarks($sector, $year, $horizon);
                    $totalCalculations++;

                } catch (\Exception $e) {
                    Log::error("Erreur calcul benchmark pour {$sector->nom} - {$horizon}: {$e->getMessage()}");
                }
            }
        }

        // Calculer les benchmarks de la sous-région
        foreach ($horizons as $horizon) {
            try {
                Log::info("Calcul benchmark sous-région - {$horizon}");

                $benchmarkService->calculateAndStoreSubRegionBenchmarks($year, $horizon);
                $totalCalculations++;

            } catch (\Exception $e) {
                Log::error("Erreur calcul benchmark sous-région - {$horizon}: {$e->getMessage()}");
            }
        }

        Log::info("Calcul des benchmarks terminé. Total: {$totalCalculations} calculs effectués");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Échec du job CalculateSectorBenchmarks: {$exception->getMessage()}");
    }
}
