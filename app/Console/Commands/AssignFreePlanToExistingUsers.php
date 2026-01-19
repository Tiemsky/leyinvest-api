<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class AssignFreePlanToExistingUsers extends Command
{
    protected $signature = 'assign-free-plan {--dry-run : Simuler sans modifier la base de données}';

    protected $description = 'Assigne le plan gratuit aux clients (role=user) existants sans abonnement.';

    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $freePlan = Plan::where('slug', 'gratuit')->where('is_active', true)->first();

        if (! $freePlan) {
            $this->error("Erreur : Plan 'gratuit' introuvable.");

            return 1;
        }

        // Requête filtrée : Rôle = 'user' ET pas d'abonnement
        $query = User::where('role', 'user')
            ->doesntHave('subscriptions');

        $count = $query->count();
        if ($count === 0) {
            $this->info('Aucun utilisateur éligible trouvé.');

            return 0;
        }

        if ($this->option('dry-run')) {
            $this->info("MODE SIMULATION ({$count} utilisateurs concernés).");

            return 0;
        }

        if (! $this->confirm("Assigner le plan gratuit à ces {$count} utilisateurs ?", true)) {
            return 0;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $success = 0;
        $errors = 0;

        $query->chunk(100, function ($users) use ($freePlan, $bar, &$success, &$errors) {
            foreach ($users as $user) {
                try {
                    // Assurance de cohérence avec le Service
                    $this->subscriptionService->subscribe($user, $freePlan, [
                        'payment_status' => 'paid',
                        'payment_method' => 'batch_command_retroactive',
                        'starts_at' => now(),
                        'amount_paid' => 0.00, // Important pour NOT NULL
                    ]);
                    $success++;
                } catch (\Exception $e) {
                    $errors++;
                    // Affichage de l'erreur pour le débogage de la commande
                    $this->error("\nÉchec pour l'utilisateur ID {$user->id} ({$user->email}): ".$e->getMessage());
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Terminé : {$success} succès, {$errors} erreurs.");

        return 0;
    }
}
