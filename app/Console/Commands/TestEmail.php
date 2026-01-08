<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SendOtpNotification;

class TestEmail extends Command
{
    protected $signature = 'send:test-brevo-email {--email= : Email du destinataire}';
    protected $description = 'Envoie une notification OTP de test via la file d\'attente Redis';

    public function handle()
    {
        // Récupère l'email depuis l'option ou pose la question
        $email = $this->option('email') ?: $this->ask('Sur quel email envoyer le test ?');

        // Validation de l'email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("❌ L'adresse email saisie n'est pas valide.");
            return Command::FAILURE;
        }

        $this->info("🚀 Préparation de l'envoi vers : $email");

        try {
            // 👇 Le 3e paramètre indique explicitement que c'est un test
            Notification::route('mail', $email)
                ->notify(new SendOtpNotification(
                    otpCode: '123456',
                    type: 'verification',
                    isTest: true //  Flag de test activé pour les tests
                ));

            $this->info("✅ La notification de TEST a été mise en file avec succès !");
            $this->newLine();
            $this->comment("📋 Détails :");
            $this->line("  • File : high");
            $this->line("  • Type : verification (test)");
            $this->line("  • Vue : emails.otp.test");
            $this->newLine();
            $this->warn("💡 Vérifiez Horizon ou vos logs worker pour confirmer l'envoi.");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de la mise en file : " . $e->getMessage());
            $this->newLine();
            $this->error("Stack trace :");
            $this->line($e->getTraceAsString());

            return Command::FAILURE;
        }
    }
}
