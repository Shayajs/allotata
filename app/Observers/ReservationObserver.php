<?php

namespace App\Observers;

use App\Models\Reservation;
use App\Models\Facture;

class ReservationObserver
{
    /**
     * Handle the Reservation "updated" event.
     */
    public function updated(Reservation $reservation): void
    {
        // Vérifier si la réservation vient d'être marquée comme payée
        if ($reservation->isDirty('est_paye') && $reservation->est_paye) {
            // Recharger la réservation pour avoir les relations à jour
            $reservation->refresh();
            
            // Générer automatiquement une facture pour toute réservation payée
            // La facture peut être générée même sans SIREN vérifié (pour les auto-entrepreneurs, etc.)
            try {
                Facture::generateFromReservation($reservation);
            } catch (\Exception $e) {
                // Logger l'erreur mais ne pas bloquer la mise à jour de la réservation
                \Log::error('Erreur lors de la génération de la facture pour la réservation #' . $reservation->id . ': ' . $e->getMessage());
            }
        }
    }
}
