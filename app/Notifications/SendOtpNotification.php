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
        private string $type = 'verification',
        private bool $isTest = false // Nouveau paramètre explicite pour différencier les tests et les réels envois
    ) {
        $this->onQueue('high');
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Détermine le type de notifiable pour la sécurité
        $isRealUser = $notifiable instanceof \App\Models\User;
        $userId = $isRealUser ? (string) $notifiable->id : 'anonymous';

        // Sélectionne la vue appropriée
        $view = $this->isTest ? 'emails.otp.test' : $this->getView();

        return (new MailMessage)
            ->subject($this->getSubject())
            ->view($view, [
                'user' => $notifiable,
                'otpCode' => $this->otpCode,
                'type' => $this->type,
                'expiry' => 10,
            ])
            ->metadata('otp_type', $this->type)
            ->metadata('user_id', $userId)
            ->metadata('is_test', $this->isTest);
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
        $prefix = $this->isTest ? '[TEST] ' : '';

        return $prefix.match ($this->type) {
            'reset' => '🔐 Réinitialisation de mot de passe - '.config('app.name'),
            default => '✅ Vérification de votre compte - '.config('app.name'),
        };
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Échec envoi OTP ({$this->type})", [
            'is_test' => $this->isTest,
            'error' => $exception->getMessage(),
            'otp_prefix' => substr($this->otpCode, 0, 2).'***',
        ]);
    }

    public function tags(): array
    {
        $tags = ['otp', $this->type];

        if ($this->isTest) {
            $tags[] = 'test';
        }

        return $tags;
    }
}
