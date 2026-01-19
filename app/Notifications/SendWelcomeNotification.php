<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SendWelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // Configuration de la file d'attente
    public $tries = 3;

    public $timeout = 120;

    public $retryAfter = 60;

    public function __construct()
    {
        $this->onQueue('default');
    }

    /**
     * Canaux de notification
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Configuration de l'email de bienvenue
     */
    public function toMail(object $notifiable): MailMessage
    {
        $isRealUser = $notifiable instanceof \App\Models\User;
        $userId = $isRealUser ? (string) $notifiable->id : 'anonymous';

        return (new MailMessage)
            ->subject('Bienvenue sur '.config('app.name').' !')
            ->view('emails.welcome', [
                'user' => $notifiable,
                'appName' => config('app.name'),
                'appUrl' => config('app.url'),
            ])
            ->metadata('notification_type', 'welcome')
            ->metadata('user_id', $userId);
    }

    /**
     * Gestion des échecs d'envoi
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Échec envoi email de bienvenue', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Tags pour le monitoring (Horizon/Telescope)
     */
    public function tags(): array
    {
        return ['welcome', 'onboarding'];
    }
}
