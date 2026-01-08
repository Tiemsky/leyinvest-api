<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SendOtpNotification;

class TestEmail extends Command
{
    /**
     * Le nom et la signature de la commande.
     */
    protected $signature = 'send:test-brevo-email';

    /**
     * La description de la commande.
     */
    protected $description = 'Envoie une notification OTP de test via la file d\'attente Redis';

    /**
     * Exécute la commande console.
     */
    public function handle()
    {
        $email = $this->ask('Sur quel email envoyer le test ?');

        // Petite validation de l'email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("L'adresse email saisie n'est pas valide.");
            return;
        }

        $this->info("🚀 Préparation de l'envoi vers : $email");

        try {
            // Simulation de la notification OTP
            // Note : Comme elle implémente ShouldQueue, elle sera envoyée à Redis
            Notification::route('mail', $email)
                ->notify(new SendOtpNotification('123456', 'verification'));

            $this->info("✅ La notification a été poussée avec succès dans la file 'high' de Redis !");
            $this->warn("📢 Note : Vérifiez vos logs worker ou votre interface Horizon pour confirmer l'envoi final.");

        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de la mise en file d'attente : " . $e->getMessage());
        }
    }
}
