<?php

// app/Console/Commands/CalculateForecasts.php

namespace App\Console\Commands;

use App\Models\Action;
use App\Services\ForecastEvaluationService;
use Illuminate\Console\Command;

class CalculateForecasts extends Command
{
    protected $signature = 'forecast:calculate-forecasts';

    protected $description = 'Recalculate RNp and DNPAp for all stocks';

    public function handle(ForecastEvaluationService $forecastEngine)
    {
        $this->info('Début du calcul des prévisions...');

        $actions = Action::with('stockFinancials')->get();
        $bar = $this->output->createProgressBar(count($actions));

        foreach ($actions as $action) {
            try {
                $forecastEngine->calculateForAction($action);
            } catch (\Exception $e) {
                $this->error("Erreur sur {$action->symbole}: ".$e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->info("\nCalcul terminé avec succès.");
    }
}
