<?php

namespace App\Console\Commands;

use App\Models\Action;
use App\Services\FinancialMetricCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CalculateStockMetrics extends Command
{
    protected $signature = 'metrics:calculate-stock
                          {--action= : ID de l\'action spécifique (optionnel)}
                          {--year= : Année spécifique (optionnel)}
                          {--from= : Année de début (défaut: année actuelle - 5)}
                          {--to= : Année de fin (défaut: année actuelle - 1)}';

    protected $description = 'Calculer les métriques financières pour les actions';

    private FinancialMetricCalculator $calculator;

    public function __construct(FinancialMetricCalculator $calculator)
    {
        parent::__construct();
        $this->calculator = $calculator;
    }

    public function handle(): int
    {
        $this->info('🚀 Calcul des métriques financières...');

        $actionId = $this->option('action');
        $year = $this->option('year');
        $from = $this->option('from') ?? (now()->year - 5);
        $to = $this->option('to') ?? (now()->year - 1);

        try {
            if ($actionId) {
                // Calculer pour une action spécifique
                $action = Action::findOrFail($actionId);
                $this->calculateForAction($action, $year, $from, $to);
            } else {
                // Calculer pour toutes les actions
                $actions = Action::with('financials')->get();
                $bar = $this->output->createProgressBar($actions->count());
                $bar->start();

                foreach ($actions as $action) {
                    $this->calculateForAction($action, $year, $from, $to);
                    $bar->advance();
                }

                $bar->finish();
                $this->newLine();
            }

            $this->info('✅ Calcul terminé avec succès !');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Erreur : {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    private function calculateForAction(Action $action, ?int $year, int $from, int $to): void
    {
        if ($year) {
            // Calculer pour une année spécifique
            $this->calculator->calculateForAction($action, $year);
        } else {
            // Calculer pour la plage d'années
            for ($y = $from; $y <= $to; $y++) {
                $this->calculator->calculateForAction($action, $y);
            }
        }
    }
}
