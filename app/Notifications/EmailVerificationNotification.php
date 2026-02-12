<?php

namespace App\Notifications;

use App\Models\EmailVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public EmailVerification $emailVerification
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = route('verification.verify', ['hash' => $this->emailVerification->hash]);

        return (new MailMessage)
            ->subject('Vérifiez votre adresse email - Allo Tata')
            ->greeting('Bonjour ' . $notifiable->name . ' !')
            ->line('Merci de vous être inscrit sur Allo Tata.')
            ->line('Pour accéder à votre compte, veuillez vérifier votre adresse email en cliquant sur le bouton ci-dessous :')
            ->action('Vérifier mon email', $verificationUrl)
            ->line('Ce lien est valide pendant 7 jours.')
            ->line('Si vous n\'avez pas créé de compte, ignorez cet email.')
            ->salutation('L\'équipe Allo Tata');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'hash' => $this->emailVerification->hash,
            'expires_at' => $this->emailVerification->expires_at,
        ];
    }
}
