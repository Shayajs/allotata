<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EssaiGratuitExpireMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $typeLabel,
        public string $lienAbonnement,
    ) {}

    public function build()
    {
        return $this->subject("Votre essai {$this->typeLabel} est arrêté")
            ->view('emails.essai-gratuit-expire')
            ->with([
                'user' => $this->user,
                'typeLabel' => $this->typeLabel,
                'lienAbonnement' => $this->lienAbonnement,
            ]);
    }
}
