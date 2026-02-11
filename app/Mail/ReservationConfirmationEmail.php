<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservationConfirmationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $reservation;
    public $isForClient;

    /**
     * Create a new message instance.
     */
    public function __construct(Reservation $reservation, bool $isForClient = true)
    {
        $this->reservation = $reservation;
        $this->isForClient = $isForClient;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        if ($this->isForClient) {
            $subject = "Confirmation de votre réservation - {$this->reservation->entreprise->nom}";
            $view = 'emails.reservation-confirmation-client';
        } else {
            $subject = "Nouvelle réservation - {$this->reservation->entreprise->nom}";
            $view = 'emails.reservation-confirmation-gerant';
        }

        return $this->subject($subject)
                    ->view($view)
                    ->with([
                        'reservation' => $this->reservation,
                    ]);
    }
}
