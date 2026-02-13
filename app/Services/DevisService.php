<?php

namespace App\Services;

use App\Models\Devis;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DevisService
{
    /**
     * Le prestataire propose un montant, un type de structure, une date et une durée.
     *
     * @param  Devis  $devis
     * @param  array  $proposition  ['montant_propose', 'type_structure_propose', 'date_proposee', 'duree_proposee_minutes', 'notes_prestataire']
     * @return Devis
     */
    public function proposer(Devis $devis, array $proposition): Devis
    {
        $devis->update([
            'montant_propose' => $proposition['montant_propose'],
            'type_structure_propose' => $proposition['type_structure_propose'] ?? 'ponctuel',
            'date_proposee' => $proposition['date_proposee'] ?? null,
            'duree_proposee_minutes' => $proposition['duree_proposee_minutes'] ?? null,
            'notes_prestataire' => $proposition['notes_prestataire'] ?? null,
            'statut' => 'propose',
        ]);

        return $devis->fresh();
    }

    /**
     * Le client accepte le devis : conversion automatique en Reservation.
     *
     * @param  Devis  $devis
     * @return Reservation
     * @throws \Exception
     */
    public function accepter(Devis $devis): Reservation
    {
        if (!$devis->estPropose()) {
            throw new \Exception('Ce devis ne peut pas être accepté (statut actuel : ' . $devis->statut . ')');
        }

        if (!$devis->montant_propose || !$devis->date_proposee) {
            throw new \Exception('Le devis doit avoir un montant et une date proposés avant d\'être accepté.');
        }

        return DB::transaction(function () use ($devis) {
            $dateReservation = Carbon::parse($devis->date_proposee);
            $dureeMinutes = $devis->duree_proposee_minutes ?? $devis->typeService->duree_minutes;

            $reservationData = [
                'user_id' => $devis->user_id,
                'entreprise_id' => $devis->entreprise_id,
                'type_service_id' => $devis->type_service_id,
                'date_reservation' => $dateReservation,
                'date_fin' => $dateReservation->copy()->addMinutes($dureeMinutes),
                'prix' => $devis->montant_propose,
                'duree_minutes' => $dureeMinutes,
                'type_service' => $devis->typeService->nom,
                'statut' => 'confirmee',
                'notes' => 'Devis #' . $devis->id . ' accepté. ' . ($devis->description_besoin ? 'Besoin : ' . $devis->description_besoin : ''),
                'nom_client' => $devis->nom_client,
                'email_client' => $devis->email_client,
                'telephone_client' => $devis->telephone_client,
            ];

            // Créer la réservation (on skip le check de disponibilité car le prestataire a choisi la date)
            $reservation = ReservationSlotService::reserverSiDisponible(
                $devis->entreprise_id,
                null,
                $dateReservation,
                $dureeMinutes,
                fn () => Reservation::create($reservationData),
                true // skip check — le prestataire a proposé cette date
            );

            if (!$reservation) {
                throw new \Exception('Impossible de créer la réservation.');
            }

            // Lier le devis à la réservation
            $devis->update([
                'statut' => 'accepte',
                'reservation_id' => $reservation->id,
            ]);

            return $reservation;
        });
    }

    /**
     * Le client refuse le devis.
     */
    public function refuser(Devis $devis): Devis
    {
        $devis->update(['statut' => 'refuse']);
        return $devis->fresh();
    }
}
