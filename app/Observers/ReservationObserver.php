<?php

namespace App\Observers;

use App\Models\Reservation;
use App\Models\Facture;

class ReservationObserver
{

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

        // Envoyer un SMS si la réservation est confirmée
        if ($reservation->statut === 'confirmee') {
            $this->sendSmsNotification($reservation);
        }

        // Synchroniser vers Google Calendar (asynchrone)
        $this->syncToGoogle($reservation, 'create');
    }
    
    /**
     * Handle the Reservation "updated" event.
     */
    public function updated(Reservation $reservation): void
    {
        // Vérifier si la réservation vient d'être confirmée (passage de en_attente à confirmee)
        if ($reservation->isDirty('statut') && $reservation->statut === 'confirmee') {
            $this->sendSmsNotification($reservation);
        }

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

        // Synchroniser les changements vers Google Calendar (date, statut, etc.)
        if ($reservation->isDirty(['date_reservation', 'date_fin', 'duree_minutes', 'statut', 'lieu', 'notes', 'type_service'])) {
            if ($reservation->statut === 'annulee') {
                $this->syncToGoogle($reservation, 'delete');
            } elseif (empty($reservation->google_event_id)) {
                // Pas encore d'événement Google → le créer (ex: acceptation d'une réservation en attente)
                $this->syncToGoogle($reservation, 'create');
            } else {
                $this->syncToGoogle($reservation, 'update');
            }
        }
    }
    
    /**
     * Envoie la notification SMS pour une réservation confirmée
     */
    protected function sendSmsNotification(Reservation $reservation): void
    {
        try {
            // Recharger la réservation avec les relations
            $reservation->load(['user', 'entreprise']);
            
            // Déterminer le destinataire : client inscrit ou téléphone non inscrit
            $notifiable = null;
            
            if ($reservation->user_id && $reservation->user) {
                // Client inscrit avec téléphone
                if ($reservation->user->telephone) {
                    $notifiable = $reservation->user;
                }
            } elseif ($reservation->telephone_client || $reservation->telephone_client_non_inscrit) {
                // Client non inscrit avec téléphone
                $telephone = $reservation->telephone_client ?? $reservation->telephone_client_non_inscrit;
                
                // Créer un notifiable temporaire avec le numéro de téléphone
                $notifiable = new class($telephone) {
                    public $telephone;
                    public $id = null;
                    
                    public function __construct($telephone) {
                        $this->telephone = $telephone;
                    }
                };
            }
            
            // Envoyer le SMS si on a un destinataire
            if ($notifiable) {
                $notification = new \App\Notifications\BookingConfirmedSms($reservation);
                
                // Utiliser le canal personnalisé
                $channel = new \App\Notifications\Channels\TwilioSmsChannel();
                $channel->send($notifiable, $notification);
            }
        } catch (\Exception $e) {
            // Logger l'erreur mais ne pas bloquer le processus
            \Log::error('Erreur lors de l\'envoi du SMS de confirmation de réservation #' . $reservation->id . ': ' . $e->getMessage());
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

        // Supprimer l'événement Google Calendar
        $this->syncToGoogle($reservation, 'delete');
    }

    /**
     * Dispatch un job de synchronisation vers Google Calendar si l'entreprise est connectée.
     */
    protected function syncToGoogle(Reservation $reservation, string $action): void
    {
        try {
            $reservation->loadMissing('entreprise');

            if ($reservation->entreprise?->aGoogleCalendar()) {
                \App\Jobs\SyncReservationToGoogle::dispatch($reservation->id, $action);
            }
        } catch (\Exception $e) {
            \Log::error('Erreur dispatch SyncReservationToGoogle pour réservation #' . $reservation->id . ': ' . $e->getMessage());
        }
    }
}
