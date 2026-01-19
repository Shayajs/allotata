<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservationCancelledEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $reservation;
    public $isForClient;
    public $cancelledBy;

    /**
     * Create a new message instance.
     */
    public function __construct(Reservation $reservation, bool $isForClient = true, string $cancelledBy = 'client')
    {
        $this->reservation = $reservation;
        $this->isForClient = $isForClient;
        $this->cancelledBy = $cancelledBy;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        if ($this->isForClient) {
            $subject = "Réservation annulée - {$this->reservation->entreprise->nom}";
            $view = 'emails.reservation-cancelled-client';
        } else {
            $subject = "Réservation annulée - {$this->reservation->entreprise->nom}";
            $view = 'emails.reservation-cancelled-gerant';
        }

        return $this->subject($subject)
                    ->view($view)
                    ->with([
                        'reservation' => $this->reservation,
                        'cancelledBy' => $this->cancelledBy,
                    ]);
    }
}
