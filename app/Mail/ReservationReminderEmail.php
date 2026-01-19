<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservationReminderEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $reservation;
    public $hoursBefore;

    /**
     * Create a new message instance.
     */
    public function __construct(Reservation $reservation, int $hoursBefore = 24)
    {
        $this->reservation = $reservation;
        $this->hoursBefore = $hoursBefore;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = "Rappel : Votre rendez-vous dans {$this->hoursBefore}h - {$this->reservation->entreprise->nom}";

        return $this->subject($subject)
                    ->view('emails.reservation-reminder')
                    ->with([
                        'reservation' => $this->reservation,
                        'hoursBefore' => $this->hoursBefore,
                    ]);
    }
}
