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

            // Attribuer des points de fidélité si le client est inscrit
            if ($reservation->user_id && $reservation->entreprise_id) {
                try {
                    $loyaltyProgram = \App\Models\LoyaltyProgram::getOrCreate(
                        $reservation->entreprise_id,
                        $reservation->user_id
                    );
                    
                    // 1 point par euro dépensé (arrondi)
                    $points = (int) round($reservation->prix);
                    $loyaltyProgram->addPoints($points, "Réservation #{$reservation->id} payée");
                } catch (\Exception $e) {
                    \Log::error('Erreur lors de l\'attribution des points de fidélité : ' . $e->getMessage());
                }
            }
        }

        // Invalider le cache des statistiques si le statut ou le paiement a changé
        if ($reservation->isDirty(['statut', 'est_paye', 'prix'])) {
            $reservation->load('entreprise');
            if ($reservation->entreprise) {
                \App\Services\CacheService::clearEntrepriseCache($reservation->entreprise->id, $reservation->entreprise->slug);
            }
        }
    }

    /**
     * Handle the Reservation "created" event.
     */
    public function created(Reservation $reservation): void
    {
        // Invalider le cache des statistiques
        $reservation->load('entreprise');
        if ($reservation->entreprise) {
            \App\Services\CacheService::clearEntrepriseCache($reservation->entreprise->id, $reservation->entreprise->slug);
        }
    }

    /**
     * Handle the Reservation "deleted" event.
     */
    public function deleted(Reservation $reservation): void
    {
        // Invalider le cache des statistiques
        if ($reservation->entreprise) {
            \App\Services\CacheService::clearEntrepriseCache($reservation->entreprise->id, $reservation->entreprise->slug);
        }
    }
}
