<?php

namespace App\Mail;

use App\Models\Facture;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Facture $facture,
        public bool $isForClient = true,
        public ?string $pdfBinary = null,
    ) {}

    public function build()
    {
        $this->facture->loadMissing(['entreprise', 'user']);

        $numero = $this->facture->numero_facture;
        $nomEntreprise = $this->facture->entreprise?->nom ?? 'Allotata';

        $mail = $this->subject("Facture {$numero} - {$nomEntreprise}")
            ->view('emails.invoice')
            ->with([
                'facture' => $this->facture,
                'isForClient' => $this->isForClient,
                'clientName' => $this->facture->user?->name
                    ?? ($this->facture->snapshot['client']['nom'] ?? 'Client'),
                'invoiceNumber' => $numero,
                'entreprise' => $nomEntreprise,
                'invoiceDate' => optional($this->facture->date_facture)->format('d/m/Y'),
                'dueDate' => optional($this->facture->date_echeance)->format('d/m/Y'),
                'amount' => $this->facture->montant_ttc,
                'invoiceUrl' => $this->isForClient
                    ? route('factures.show', $this->facture->id)
                    : route('factures.entreprise.show', [$this->facture->entreprise->slug, $this->facture->id]),
            ]);

        if ($this->pdfBinary) {
            $mail->attachData($this->pdfBinary, 'facture-'.$numero.'.pdf', [
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}
