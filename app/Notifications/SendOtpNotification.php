<?php
// app/Notifications/SendOtpNotification.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $otpCode,
        private string $type = 'verification'
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->type === 'reset'
            ? 'Code de réinitialisation de mot de passe'
            : 'Code de vérification de votre compte';

        $message = $this->type === 'reset'
            ? 'Voici votre code de réinitialisation de mot de passe :'
            : 'Merci de vous être inscrit ! Voici votre code de vérification :';

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Bonjour ' . $notifiable->name . ' !')
            ->line($message)
            ->line('**' . $this->otpCode . '**')
            ->line('Ce code expire dans 10 minutes.')
            ->line('Si vous n\'avez pas effectué cette action, veuillez ignorer cet email.')
            ->salutation('Cordialement, L\'équipe ' . config('app.name'));
    }
}
