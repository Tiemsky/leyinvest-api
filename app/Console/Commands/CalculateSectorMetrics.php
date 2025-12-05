<?php

namespace App\Console\Commands;

use App\Services\SectorMetricAggregator;
use Illuminate\Console\Command;

class CalculateSectorMetrics extends Command
{
    protected $signature = 'metrics:calculate-sectors
                          {--year= : Année spécifique (optionnel)}
                          {--from= : Année de début (défaut: année actuelle - 5)}
                          {--to= : Année de fin (défaut: année actuelle - 1)}
                          {--type= : Type de secteur (brvm, classified, ou all)}';

    protected $description = 'Calculer les métriques sectorielles (moyennes et écart-types)';

    private SectorMetricAggregator $aggregator;

    public function __construct(SectorMetricAggregator $aggregator)
    {
        parent::__construct();
        $this->aggregator = $aggregator;
    }

    public function handle(): int
    {
        $this->info('🚀 Calcul des métriques sectorielles...');

        $year = $this->option('year');
        $from = $this->option('from') ?? (now()->year - 5);
        $to = $this->option('to') ?? (now()->year - 1);
        $type = $this->option('type') ?? 'all';

        try {
            if ($year) {
                $this->calculateForYear($year, $type);
            } else {
                for ($y = $from; $y <= $to; $y++) {
                    $this->info("Calcul pour l'année {$y}...");
                    $this->calculateForYear($y, $type);
                }
            }

            $this->info('✅ Calcul terminé avec succès !');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Erreur : {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    private function calculateForYear(int $year, string $type): void
    {
        if ($type === 'brvm' || $type === 'all') {
            $this->info("  → Calcul des secteurs BRVM...");
            $this->aggregator->calculateBrvmSectors($year);
        }

        if ($type === 'classified' || $type === 'all') {
            $this->info("  → Calcul des secteurs classifiés...");
            $this->aggregator->calculateClassifiedSectors($year);
        }
    }
}
