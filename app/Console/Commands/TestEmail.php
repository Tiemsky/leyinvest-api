<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:test-brevo-email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->ask('Sur quel email envoyer le test ?');
        $this->info("Tentative d'envoi vers $email...");

        try {
            // On simule une notification OTP
            \Illuminate\Support\Facades\Notification::route('mail', $email)
                ->notify(new \App\Notifications\SendOtpNotification('123456', 'verification'));

            $this->success("La notification a été poussée dans la file 'high' de Redis !");
            $this->info("Vérifiez maintenant vos logs worker ou Horizon.");
        } catch (\Exception $e) {
            $this->error("Erreur immédiate : " . $e->getMessage());
        }
    }
}
