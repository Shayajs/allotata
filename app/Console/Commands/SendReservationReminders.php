<?php

namespace App\Console\Commands;

use App\Helpers\EmailHelper;
use App\Models\Reservation;
use Illuminate\Console\Command;

class SendReservationReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:send-reminders {--hours=24 : Nombre d\'heures avant le rendez-vous}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envoyer des rappels par email pour les réservations à venir';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hoursBefore = (int) $this->option('hours');

        // Calculer la date cible (maintenant + X heures)
        $targetDate = now()->addHours($hoursBefore);

        // Récupérer les réservations confirmées qui ont lieu dans X heures
        // Inclut les clients inscrits ET les invités ayant laissé un email
        $reservations = Reservation::where('statut', 'confirmee')
            ->whereNotNull('date_reservation')
            ->whereDate('date_reservation', $targetDate->format('Y-m-d'))
            ->whereTime('date_reservation', '>=', $targetDate->copy()->subMinutes(30)->format('H:i:s'))
            ->whereTime('date_reservation', '<=', $targetDate->copy()->addMinutes(30)->format('H:i:s'))
            ->where(function ($query) {
                $query->whereHas('user')
                    ->orWhereNotNull('email_client');
            })
            ->with(['user', 'entreprise', 'membre.user'])
            ->get();

        $sentCount = 0;
        $errorCount = 0;

        foreach ($reservations as $reservation) {
            try {
                $emailTo = $reservation->user?->email ?? $reservation->email_client;
                if ($emailTo) {
                    EmailHelper::sendReservationReminder($reservation, $hoursBefore);
                    $sentCount++;
                    $this->info("Rappel envoyé pour la réservation #{$reservation->id} (email: {$emailTo})");
                }
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("Erreur lors de l'envoi du rappel pour la réservation #{$reservation->id}: ".$e->getMessage());
                \Log::error("Erreur lors de l'envoi du rappel de réservation #{$reservation->id}: ".$e->getMessage());
            }
        }

        $this->info("Rappels envoyés: {$sentCount}, Erreurs: {$errorCount}");

        return Command::SUCCESS;
    }
}
