<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marquer les souscriptions expirées comme expired';

    /**
     * Execute the console command.
     */
    public function handle(SubscriptionService $subscriptionService)
    {
        $this->info('Vérification des souscriptions expirées...');

        $expiredCount = $subscriptionService->expireSubscriptions();

        if ($expiredCount > 0) {
            $this->info("✓ {$expiredCount} souscription(s) marquée(s) comme expirée(s)");
        } else {
            $this->info('Aucune souscription expirée trouvée');
        }

        return 0;
    }
}
