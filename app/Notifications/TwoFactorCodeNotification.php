<?php

namespace App\Notifications;

use App\Models\TwoFactorCode;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TwoFactorCodeNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public TwoFactorCode $twoFactorCode
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
        if ($this->twoFactorCode->method === 'sms') {
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
            ->subject('Code de vérification - Allo Tata')
            ->line('Une tentative de connexion nécessite une vérification supplémentaire.')
            ->line('Votre code de vérification est : **' . $this->twoFactorCode->code . '**')
            ->line('Ce code est valide pendant 10 minutes.')
            ->line('Si vous n\'avez pas tenté de vous connecter, ignorez cet email et contactez le support si nécessaire.');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toTwilio(object $notifiable): array
    {
        $message = "Allo Tata - Code de vérification : {$this->twoFactorCode->code}. Valide 10 minutes. Si vous n'avez pas tenté de vous connecter, ignorez ce message.";
        
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
            'code' => $this->twoFactorCode->code,
            'method' => $this->twoFactorCode->method,
            'expires_at' => $this->twoFactorCode->expires_at,
        ];
    }
}
