<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class BookingConfirmedSms extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Reservation $reservation
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
        // Utiliser le canal Twilio personnalisé
        return ['twilio'];
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toTwilio(object $notifiable): array
    {
        // Utiliser l'alias court pour les SMS (plus court = moins cher)
        $hash = $this->reservation->hash_alias ?? $this->reservation->hash;
        
        // Générer l'URL de la réservation avec l'alias court
        $url = route('public.reservation.show', ['hash' => $hash]);
        
        // Message SMS
        $message = "Bonjour, votre réservation Allotata est confirmée. Retrouvez vos détails ici : {$url}";
        
        return [
            'message' => $message,
            'reservation_id' => $this->reservation->id,
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
            'reservation_id' => $this->reservation->id,
            'message' => 'Réservation confirmée par SMS',
        ];
    }
}
