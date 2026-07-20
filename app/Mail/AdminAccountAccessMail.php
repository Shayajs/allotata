<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminAccountAccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
    ) {}

    public function build()
    {
        return $this->subject('Accès administrateur à votre compte - Allo Tata')
            ->view('emails.admin-account-access')
            ->with([
                'user' => $this->user,
            ]);
    }
}
