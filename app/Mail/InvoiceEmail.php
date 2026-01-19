<?php

namespace App\Mail;

use App\Models\Facture;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $facture;
    public $isForClient;

    /**
     * Create a new message instance.
     */
    public function __construct(Facture $facture, bool $isForClient = true)
    {
        $this->facture = $facture;
        $this->isForClient = $isForClient;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = "Facture #{$this->facture->numero} - {$this->facture->entreprise->nom}";

        return $this->subject($subject)
                    ->view('emails.invoice')
                    ->with([
                        'facture' => $this->facture,
                        'isForClient' => $this->isForClient,
                    ]);
    }
}
