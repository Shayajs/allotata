<?php

namespace App\Notifications;

use App\Models\PasswordResetCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PasswordResetCodeNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public PasswordResetCode $resetCode
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
        // Utiliser le canal approprié selon la méthode
        if ($this->resetCode->method === 'sms') {
            return ['twilio'];
        }
        
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Code de réinitialisation de mot de passe - Allo Tata')
            ->line('Vous avez demandé à réinitialiser votre mot de passe.')
            ->line('Votre code de réinitialisation est : **' . $this->resetCode->code . '**')
            ->line('Ce code est valide pendant 15 minutes.')
            ->line('Si vous n\'avez pas demandé cette réinitialisation, ignorez ce message.');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toTwilio(object $notifiable): array
    {
        $message = "Allo Tata - Code de réinitialisation : {$this->resetCode->code}. Valide 15 minutes. Si vous n'avez pas demandé ce code, ignorez ce message.";
        
        return [
            'message' => $message,
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'code' => $this->resetCode->code,
            'method' => $this->resetCode->method,
            'expires_at' => $this->resetCode->expires_at,
        ];
    }
}
