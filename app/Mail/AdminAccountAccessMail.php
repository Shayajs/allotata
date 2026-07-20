<?php

namespace App\Mail;

use App\Models\User;
use App\Services\AccountAccessService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminAccountAccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $mode = AccountAccessService::MODE_EDIT,
    ) {}

    public function build()
    {
        $modeLabel = match ($this->mode) {
            AccountAccessService::MODE_SUPPORT => 'support',
            AccountAccessService::MODE_BILLING => 'facturation',
            default => 'édition',
        };

        $scope = match ($this->mode) {
            AccountAccessService::MODE_SUPPORT => 'intervenir sur vos tickets et votre messagerie',
            AccountAccessService::MODE_BILLING => 'gérer vos abonnements, paiements et finances',
            default => 'effectuer des actions au nom de votre compte (modifications, réservations, paramètres, etc.)',
        };

        return $this->subject('Accès administrateur à votre compte - Allo Tata')
            ->view('emails.admin-account-access')
            ->with([
                'user' => $this->user,
                'mode' => $this->mode,
                'modeLabel' => $modeLabel,
                'scope' => $scope,
            ]);
    }
}
