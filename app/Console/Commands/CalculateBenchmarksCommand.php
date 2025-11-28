<?php

namespace App\Console\Commands;

use App\Services\BenchmarkService;
use Illuminate\Console\Command;

/**
 * Commande pour calculer/recalculer les benchmarks sectoriels
 *
 * Usage:
 * php artisan benchmarks:calculate
 * php artisan benchmarks:calculate --year=2024
 * php artisan benchmarks:calculate --year=2024 --force
 */
class CalculateBenchmarksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'benchmarks:calculate
                            {--year= : Année à calculer (défaut: année précédente)}
                            {--force : Force le recalcul même si déjà calculé}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calcule les benchmarks sectoriels pour une année donnée';

    /**
     * Execute the console command.
     */
    public function handle(BenchmarkService $benchmarkService): int
    {
        $year = $this->option('year') ?? now()->year - 1;
        $force = $this->option('force');

        $this->info("🚀 Début du calcul des benchmarks pour l'année {$year}");
        $this->newLine();

        if ($force) {
            $this->warn("⚠️  Mode FORCE activé - Recalcul de tous les benchmarks");
        }

        try {
            $startTime = microtime(true);

            // Calculer tous les benchmarks
            $count = $benchmarkService->calculateAllBenchmarks($year);

            $duration = round(microtime(true) - $startTime, 2);

            $this->newLine();
            $this->info("✅ Calcul terminé avec succès !");
            $this->table(
                ['Métrique', 'Valeur'],
                [
                    ['Année', $year],
                    ['Benchmarks générés', $count],
                    ['Durée', "{$duration}s"],
                ]
            );

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Erreur lors du calcul des benchmarks:");
            $this->error($e->getMessage());
            $this->newLine();

            if ($this->output->isVerbose()) {
                $this->error($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }
}
