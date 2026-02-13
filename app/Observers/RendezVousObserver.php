<?php

namespace App\Observers;

use App\Models\RendezVous;

class RendezVousObserver
{
    /**
     * Handle the RendezVous "created" event.
     * Synchronise le nouveau sous-rendez-vous vers Google Calendar.
     */
    public function created(RendezVous $rdv): void
    {
        $this->syncToGoogle($rdv, 'create');
    }

    /**
     * Handle the RendezVous "updated" event.
     * Met à jour ou crée l'événement Google Calendar correspondant.
     */
    public function updated(RendezVous $rdv): void
    {
        if ($rdv->isDirty(['date_heure', 'duree_minutes', 'titre', 'notes', 'statut', 'lieu'])) {
            if ($rdv->estAnnule()) {
                $this->syncToGoogle($rdv, 'delete');
            } elseif (empty($rdv->google_event_id)) {
                $this->syncToGoogle($rdv, 'create');
            } else {
                $this->syncToGoogle($rdv, 'update');
            }
        }
    }

    /**
     * Handle the RendezVous "deleted" event.
     * Supprime l'événement Google Calendar correspondant.
     */
    public function deleted(RendezVous $rdv): void
    {
        $this->syncToGoogle($rdv, 'delete');
    }

    /**
     * Dispatch la synchronisation vers Google Calendar pour un sous-rendez-vous.
     */
    protected function syncToGoogle(RendezVous $rdv, string $action): void
    {
        try {
            $rdv->loadMissing('reservation.entreprise');

            if ($rdv->reservation?->entreprise?->aGoogleCalendar()) {
                $rdvId = $rdv->id;
                defer(function () use ($rdvId, $action) {
                    $rdv = RendezVous::with('reservation.entreprise')->find($rdvId);
                    if (!$rdv) {
                        return;
                    }

                    $service = app(\App\Services\GoogleCalendarService::class);

                    match ($action) {
                        'create' => $service->createEventForRendezVous($rdv),
                        'update' => $service->updateEventForRendezVous($rdv),
                        'delete' => $service->deleteEventForRendezVous($rdv),
                        default => null,
                    };
                });
            }
        } catch (\Exception $e) {
            \Log::error("Erreur dispatch sync Google pour RDV #{$rdv->id} : " . $e->getMessage());
        }
    }
}
