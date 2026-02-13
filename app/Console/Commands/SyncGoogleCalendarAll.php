<?php

namespace App\Console\Commands;

use App\Models\Entreprise;
use App\Models\Reservation;
use App\Services\GoogleCalendarService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncGoogleCalendarAll extends Command
{
    protected $signature = 'google-calendar:sync-all';

    protected $description = 'Synchronisation complète bidirectionnelle Google Calendar pour toutes les entreprises connectées';

    public function handle(GoogleCalendarService $service): int
    {
        $entreprises = Entreprise::whereNotNull('google_refresh_token')->get();

        $this->info("Entreprises connectées à Google Calendar : {$entreprises->count()}");

        if ($entreprises->isEmpty()) {
            $this->info('Aucune entreprise connectée. Rien à synchroniser.');
            return self::SUCCESS;
        }

        $syncedUp = 0;
        $syncedDown = 0;
        $errors = 0;

        foreach ($entreprises as $entreprise) {
            $this->line("--- Entreprise #{$entreprise->id} ({$entreprise->nom}) ---");

            // ================================================
            // 1. Allotata → Google : pousser les réservations sans google_event_id
            // ================================================
            try {
                $reservations = Reservation::where('entreprise_id', $entreprise->id)
                    ->whereNull('google_event_id')
                    ->whereIn('statut', ['confirmee', 'en_attente'])
                    ->whereNotNull('date_reservation')
                    ->where('date_reservation', '>=', now()->subDays(7))
                    ->get();

                foreach ($reservations as $reservation) {
                    try {
                        $eventId = $service->createEvent($reservation);
                        if ($eventId) {
                            $syncedUp++;
                            $this->line("  [↑] Réservation #{$reservation->id} → Google (event: {$eventId})");
                        }
                    } catch (\Throwable $e) {
                        $errors++;
                        $this->error("  [ERREUR ↑] Réservation #{$reservation->id} : {$e->getMessage()}");
                        Log::error("google-calendar:sync-all push réservation #{$reservation->id} : {$e->getMessage()}");
                    }
                }
            } catch (\Throwable $e) {
                $errors++;
                $this->error("  [ERREUR] Requête réservations entreprise #{$entreprise->id} : {$e->getMessage()}");
                Log::error("google-calendar:sync-all push entreprise #{$entreprise->id} : {$e->getMessage()}");
            }

            // ================================================
            // 2. Google → Allotata : récupérer les changements (sync incrémentale)
            // ================================================
            try {
                $service->syncIncrementalChanges($entreprise);
                $syncedDown++;
                $this->line("  [↓] Sync incrémentale Google → Allotata OK");
            } catch (\Throwable $e) {
                $errors++;
                $this->error("  [ERREUR ↓] Sync incrémentale entreprise #{$entreprise->id} : {$e->getMessage()}");
                Log::error("google-calendar:sync-all pull entreprise #{$entreprise->id} : {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Terminé : {$syncedUp} réservations poussées, {$syncedDown} entreprises synchronisées (pull), {$errors} erreurs.");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
