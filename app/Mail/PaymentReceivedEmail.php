<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentReceivedEmail extends Mailable
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
            $subject = "Paiement reçu - {$this->reservation->entreprise->nom}";
            $view = 'emails.payment-received-client';
        } else {
            $subject = "Paiement reçu pour la réservation #{$this->reservation->id}";
            $view = 'emails.payment-received-gerant';
        }

        return $this->subject($subject)
                    ->view($view)
                    ->with([
                        'reservation' => $this->reservation,
                    ]);
    }
}
