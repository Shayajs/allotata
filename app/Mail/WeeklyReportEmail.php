<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Entreprise;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WeeklyReportEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $entreprise;
    public $stats;
    public $period;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Entreprise $entreprise, array $stats, string $period = 'week')
    {
        $this->user = $user;
        $this->entreprise = $entreprise;
        $this->stats = $stats;
        $this->period = $period;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $periodLabel = $this->period === 'week' ? 'hebdomadaire' : 'mensuel';
        $subject = "Rapport {$periodLabel} - {$this->entreprise->nom}";

        return $this->subject($subject)
                    ->view('emails.report-' . $this->period)
                    ->with([
                        'user' => $this->user,
                        'entreprise' => $this->entreprise,
                        'stats' => $this->stats,
                    ]);
    }
}
