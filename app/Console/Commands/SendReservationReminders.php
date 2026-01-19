<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Helpers\EmailHelper;
use Illuminate\Console\Command;
use Carbon\Carbon;

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
        $reservations = Reservation::where('statut', 'confirmee')
            ->whereDate('date_reservation', $targetDate->format('Y-m-d'))
            ->whereTime('date_reservation', '>=', $targetDate->copy()->subMinutes(30)->format('H:i:s'))
            ->whereTime('date_reservation', '<=', $targetDate->copy()->addMinutes(30)->format('H:i:s'))
            ->whereHas('user') // Uniquement les clients inscrits
            ->with(['user', 'entreprise'])
            ->get();

        $sentCount = 0;
        $errorCount = 0;

        foreach ($reservations as $reservation) {
            try {
                // Vérifier que l'utilisateur a un email
                if ($reservation->user && $reservation->user->email) {
                    EmailHelper::sendReservationReminder($reservation, $hoursBefore);
                    $sentCount++;
                    $this->info("Rappel envoyé pour la réservation #{$reservation->id} (client: {$reservation->user->email})");
                }
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("Erreur lors de l'envoi du rappel pour la réservation #{$reservation->id}: " . $e->getMessage());
                \Log::error("Erreur lors de l'envoi du rappel de réservation #{$reservation->id}: " . $e->getMessage());
            }
        }

        $this->info("Rappels envoyés: {$sentCount}, Erreurs: {$errorCount}");
        
        return Command::SUCCESS;
    }
}
