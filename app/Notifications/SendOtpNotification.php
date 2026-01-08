<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SendOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // Configuration de la file d'attente
    public $tries = 3;
    public $timeout = 120;
    public $retryAfter = 60;

    public function __construct(
        private string $otpCode,
        private string $type = 'verification'
    ) {
        $this->onQueue('high');
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Si $notifiable n'a pas d'ID, c'est notre commande de test
        $isTest = !isset($notifiable->id);
        $view = $isTest ? 'emails.otp.test' : $this->getView();


        return (new MailMessage)
            ->subject($this->getSubject())
            ->view($view, [
                'user' => $notifiable,
                'otpCode' => $this->otpCode,
                'type' => $this->type,
                'expiry' => 10,
                'appName' => config('app.name')
            ])
            // Metadata pour le tracking dans l'API Brevo
            ->metadata('otp_type', $this->type)
            ->metadata('user_id', (string) $notifiable->id);
    }

    /**
     * Détermine la vue Blade selon le type
     */
    private function getView(): string
    {
        return match ($this->type) {
            'reset' => 'emails.otp.reset',
            'resend' => 'emails.otp.resend',
            default => 'emails.otp.verification',
        };
    }

    private function getSubject(): string
    {
        return match ($this->type) {
            'reset' => '🔐 Réinitialisation de mot de passe - ' . config('app.name'),
            default => '✅ Vérification de votre compte - ' . config('app.name'),
        };
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Échec envoi OTP ({$this->type}) à l'ID: {$this->id}", [
            'error' => $exception->getMessage(),
            'otp_prefix' => substr($this->otpCode, 0, 2) . '***'
        ]);
    }

    public function tags(): array
    {
        return ['otp', $this->type];
    }
}
