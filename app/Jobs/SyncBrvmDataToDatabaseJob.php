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

    public $timeout = 120;
    public $tries = 3;

    public function handle(SyncBrvmDataService $service): void
    {
        Log::info("🚀 Job de synchronisation BRVM démarré.");

        if (!$service->syncAllData()) {
            throw new \Exception("La synchronisation BRVM a échoué.");
        }

        Log::info("Job de synchronisation BRVM terminé avec succès.");
    }

    public function failed(\Throwable $exception): void{
        Log::error("Job BRVM échoué après {$this->tries} tentatives : " . $exception->getMessage());
    }
}
