<?php

/**
 * ============================================================================
 * COMMANDE ARTISAN: Calcul des Ratios
 * ============================================================================
 * php artisan make:command CalculateFinancialRatios
 */
namespace App\Console\Commands;

use App\Services\FinancialRatiosCalculator;
use Illuminate\Console\Command;

class CalculateFinancialRatios extends Command
{
    protected $signature = 'ratios:calculate
                            {year? : Année à calculer (défaut: année en cours)}
                            {--stock= : Code d\'une action spécifique}
                            {--all : Recalculer toutes les années disponibles}';

    protected $description = 'Calcule les ratios financiers pour les actions';

    public function handle(FinancialRatiosCalculator $calculator)
    {
        $this->info('🧮 Calcul des ratios financiers...');

        try {
            if ($this->option('all')) {
                // Calcul pour toutes les années
                $years = \DB::table('stock_financials')
                    ->distinct()
                    ->pluck('year')
                    ->sortDesc();

                foreach ($years as $year) {
                    $this->info("Année {$year}...");
                    $results = $calculator->calculateForYear($year);
                    $this->displayResults($results);
                }
            } elseif ($actionCode = $this->option('stock')) {
                // Calcul pour une action spécifique
                $action = \App\Models\Action::where('code', $actionCode)->firstOrFail();
                $year = $this->argument('year') ?? date('Y');

                $calculator->calculateForStock($action, $year);
                $this->info("✓ Ratios calculés pour {$actionCode} ({$year})");
            } else {
                // Calcul pour une année
                $year = $this->argument('year') ?? date('Y');
                $this->info("Année {$year}...");

                $results = $calculator->calculateForYear($year);
                $this->displayResults($results);

                // Calcule aussi les moyennes sectorielles
                $this->info('Calcul des moyennes sectorielles...');
                $calculator->calculateSectorAverages($year);
            }

            $this->newLine();
            $this->info('✅ Calculs terminés!');
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Erreur: {$e->getMessage()}");
            return 1;
        }
    }

    protected function displayResults(array $results): void
    {
        $this->line("✓ Succès: {$results['success']}");
        $this->line("✗ Erreurs: {$results['errors']}");

        if ($this->output->isVerbose()) {
            $this->newLine();
            foreach ($results['details'] as $detail) {
                $this->line($detail);
            }
        }
    }
}
