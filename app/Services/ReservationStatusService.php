<?php

namespace App\Services;

use App\Helpers\EmailHelper;
use App\Models\Entreprise;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Transitions accept/refus d'une reservation en attente.
 * Les controleurs web et l'API Pocket passent par ici pour garder emails/notifs alignes.
 * SMS et Google Calendar restent sur ReservationObserver.
 */
class ReservationStatusService
{
    public function accepter(Reservation $reservation, User $acteur, ?string $notesGerant = null): Reservation
    {
        $this->autoriser($reservation, $acteur);
        $this->exigerEnAttente($reservation);

        $notes = (string) $reservation->notes;
        if ($notesGerant) {
            $notes .= "\n\n[Note de la tata] ".$notesGerant;
        }

        $reservation->update([
            'statut' => 'confirmee',
            'notes' => $notes,
        ]);

        $this->apresAcceptation($reservation->fresh(['entreprise', 'user']));

        return $reservation->fresh();
    }

    public function refuser(Reservation $reservation, User $acteur, ?string $raisonRefus = null): Reservation
    {
        $this->autoriser($reservation, $acteur);
        $this->exigerEnAttente($reservation);

        $notes = (string) $reservation->notes;
        if ($raisonRefus) {
            $notes .= "\n\n[Raison du refus] ".$raisonRefus;
        }

        $reservation->update([
            'statut' => 'annulee',
            'notes' => $notes,
        ]);

        $this->apresRefus($reservation->fresh(['entreprise', 'user']), $raisonRefus);

        return $reservation->fresh();
    }

    public function autoriser(Reservation $reservation, User $acteur): void
    {
        $entreprise = $reservation->entreprise ?? Entreprise::find($reservation->entreprise_id);

        if (! $entreprise || (! $entreprise->peutEtreGereePar($acteur) && ! $acteur->is_admin)) {
            throw new AuthorizationException('Vous n\'avez pas accès à cette entreprise.');
        }
    }

    private function exigerEnAttente(Reservation $reservation): void
    {
        if ($reservation->statut !== 'en_attente') {
            throw new InvalidArgumentException('Cette réservation n\'est plus en attente.');
        }
    }

    private function apresAcceptation(Reservation $reservation): void
    {
        $entreprise = $reservation->entreprise;
        if ($entreprise) {
            CacheService::clearEntrepriseCache($entreprise->id, $entreprise->slug);
        }

        if ($reservation->user_id) {
            app(ReservationClientNotificationService::class)->notifyPrise($reservation);
        }

        if ($reservation->user_id || ! empty($reservation->email_client)) {
            try {
                EmailHelper::sendReservationConfirmationClient($reservation);
            } catch (\Exception $e) {
                Log::error("Erreur lors de l'envoi de l'email de confirmation : ".$e->getMessage());
            }
        }
    }

    private function apresRefus(Reservation $reservation, ?string $raisonRefus): void
    {
        if ($reservation->user_id) {
            $raison = $raisonRefus ? " Raison : {$raisonRefus}" : '';
            app(ReservationClientNotificationService::class)->notifyAnnulation($reservation, $raison);
        }

        if ($reservation->user_id || ! empty($reservation->email_client)) {
            try {
                EmailHelper::sendReservationCancelledClient($reservation, 'gerant');
            } catch (\Exception $e) {
                Log::error("Erreur lors de l'envoi de l'email d'annulation : ".$e->getMessage());
            }
        }
    }
}
