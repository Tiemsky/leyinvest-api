<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Log d'un message pour confirmer que le Worker a exécuté le Job
        $message = "✅ Job ProcessTestJob executed successfully by worker at: " . now()->toDateTimeString();
        Log::info($message);
        // Cette ligne apparaitra dans les logs du conteneur 'worker' et dans laravel.log
        echo "LOG: " . $message . "\n";
    }
}
