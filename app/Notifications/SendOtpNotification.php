<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Configuration de la queue
     */
    public $tries = 3;              // Retry jusqu'à 3 fois en cas d'échec
    public $timeout = 120;           // Timeout de 2 minutes
    public $retryAfter = 60;         // Retry après 60 secondes
    public $maxExceptions = 3;       // Maximum 3 exceptions avant abandon

    /**
     * Type de l'OTP : 'verification' ou 'reset'
     */
    private const TYPE_VERIFICATION = 'verification';
    private const TYPE_RESET = 'reset';

    /**
     * Constructeur optimisé avec readonly properties (PHP 8.2+)
     * OU utiliser private si PHP < 8.2
     */
    public function __construct(
        private string $otpCode,
        private string $type = self::TYPE_VERIFICATION
    ) {
        // Configuration de la queue prioritaire pour les OTP (optionnel)
        $this->onQueue('high');
    }

    /**
     * Canaux de notification
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Construction du message email
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->getSubject())
            ->greeting($this->getGreeting($notifiable))
            ->line($this->getMessage())
            ->line($this->formatOtpCode())
            ->line($this->getExpirationMessage())
            ->line($this->getWarningMessage())
            ->salutation($this->getSalutation());
    }

    /**
     * Sujet de l'email selon le type
     */
    private function getSubject(): string
    {
        return match ($this->type) {
            self::TYPE_RESET => 'Code de réinitialisation de mot de passe',
            default => 'Code de vérification de votre compte',
        };
    }

    /**
     * Message de salutation personnalisé
     */
    private function getGreeting(object $notifiable): string
    {
        return "Bonjour {$notifiable->name} !";
    }

    /**
     * Message principal selon le type
     */
    private function getMessage(): string
    {
        return match ($this->type) {
            self::TYPE_RESET => 'Voici votre code de réinitialisation de mot de passe :',
            default => 'Merci de vous être inscrit ! Voici votre code de vérification :',
        };
    }

    /**
     * Formatage du code OTP en gras et espacement pour lisibilité
     */
    private function formatOtpCode(): string
    {
        // Ajouter des espaces entre les chiffres pour meilleure lisibilité
        $formatted = implode(' ', str_split($this->otpCode));
        return "**{$formatted}**";
    }

    /**
     * Message d'expiration
     */
    private function getExpirationMessage(): string
    {
        return 'Ce code expire dans **10 minutes**.';
    }

    /**
     * Message d'avertissement sécurité
     */
    private function getWarningMessage(): string
    {
        return "Si vous n'avez pas effectué cette action, veuillez ignorer cet email ou contacter notre support.";
    }

    /**
     * Signature de l'email
     */
    private function getSalutation(): string
    {
        return "Cordialement,  \nL'équipe " . config('app.name');
    }

    /**
     * Handler en cas d'échec d'envoi (optionnel mais recommandé)
     */
    public function failed(\Throwable $exception): void
    {
        // Logger l'échec
        \Log::error('Failed to send OTP notification', [
            'type' => $this->type,
            'otp' => substr($this->otpCode, 0, 2) . '****', // Log partiel pour sécurité
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Optionnel : Notifier un admin par Slack/Discord/etc.
        // \Notification::route('slack', config('logging.channels.slack.url'))
        //     ->notify(new AdminAlertNotification('OTP sending failed'));
    }

    /**
     * Déterminer le délai d'envoi (optionnel - envoi immédiat par défaut)
     * Décommenter pour envoyer avec un délai
     */
    // public function withDelay(object $notifiable): \DateTimeInterface|\DateInterval|int
    // {
    //     // Envoyer immédiatement pour les OTP (priorité haute)
    //     return now();
    // }

    /**
     * Tags pour monitoring (si vous utilisez Horizon)
     */
    public function tags(): array
    {
        return [
            'notification',
            'otp',
            $this->type,
        ];
    }
}
