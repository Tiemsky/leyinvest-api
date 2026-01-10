<?php

namespace App\Jobs;

use App\Services\SyncBrvmDataService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncBrvmDataToDatabaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Le nombre de secondes pendant lesquelles le job peut s'exécuter.
     */
    public $timeout = 120;

    public function handle(SyncBrvmDataService $syncDataService)
    {
        Log::info("🚀 [Queue] Début du Job de synchronisation BRVM...");

        $success = $syncDataService->syncAllData();

        if ($success) {
            Log::info("✅ [Queue] Synchro réussie via le Job.");
        } else {
            Log::error("❌ [Queue] Échec de la synchro dans le Job.");
        }
    }
}
