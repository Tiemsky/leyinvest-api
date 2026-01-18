<?php

namespace App\Jobs;

use App\Models\Action;
use App\Services\ForecastEvaluationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessForecastsJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * Le nombre de secondes pendant lesquelles le job peut s'exécuter.
   */
  public $timeout = 300;

  public function handle(ForecastEvaluationService $forecastEngine): void
  {
    Log::info("[Job] Début du recalcul des prévisions pour toutes les actions.");

    // On récupère les actions avec leurs relations pour éviter le problème N+1
    $actions = Action::with('stockFinancials')->get();

    foreach ($actions as $action) {
      try {
        $forecastEngine->calculateForAction($action);
      } catch (\Exception $e) {
        Log::error(" Erreur de calcul pour {$action->symbole}: " . $e->getMessage());
      }
    }

    Log::info("[Job] Recalcul des prévisions terminé.");
  }
}
