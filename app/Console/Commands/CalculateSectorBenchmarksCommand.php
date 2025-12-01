<?php

namespace App\Console\Commands;

use App\Services\BenchmarkService;
use App\Models\BrvmSector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CalculateSectorBenchmarksCommand extends Command
{
    protected $signature = 'benchmark:calculate
                            {--year= : Année à traiter (par défaut: année précédente)}
                            {--horizon= : Horizon spécifique (court_terme, moyen_terme, long_terme)}';

    protected $description = 'Calcule les benchmarks sectoriels et sous-région (version CLI du job)';

    public function handle(BenchmarkService $benchmarkService): void
    {
        $year = $this->option('year') ?? (now()->year - 1);
        $horizon = $this->option('horizon');
        $horizons = $horizon ? [$horizon] : config('financial_indicators.horizons', ['court_terme', 'moyen_terme', 'long_terme']);

        $this->info("Début du calcul des benchmarks pour l'année {$year}");

        $sectors = BrvmSector::all();
        $totalCalculations = 0;

        foreach ($sectors as $sector) {
            foreach ($horizons as $h) {
                try {
                    $this->line(" → Secteur: {$sector->nom} - Horizon: {$h}");
                    Log::info("Calcul benchmark pour {$sector->nom} - {$h}");

                    $benchmarkService->calculateAndStoreSectorBenchmarks($sector, $year, $h);
                    $totalCalculations++;
                } catch (\Exception $e) {
                    $this->error("Erreur secteur {$sector->nom} ({$h}): {$e->getMessage()}");
                    Log::error("Erreur calcul benchmark pour {$sector->nom} - {$h}: {$e->getMessage()}");
                }
            }
        }

        // Sous-région
        foreach ($horizons as $h) {
            try {
                $this->line(" → Sous-région - Horizon: {$h}");
                Log::info("Calcul benchmark sous-région - {$h}");
                $benchmarkService->calculateAndStoreSubRegionBenchmarks($year, $h);
                $totalCalculations++;
            } catch (\Exception $e) {
                $this->error("Erreur sous-région ({$h}): {$e->getMessage()}");
                Log::error("Erreur calcul benchmark sous-région - {$h}: {$e->getMessage()}");
            }
        }

        $this->info("✅ Calcul terminé. Total: {$totalCalculations} benchmarks générés.");
    }
}
