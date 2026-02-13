<?php

namespace App\Services;

use App\Models\Entreprise;
use App\Models\Reservation;
use App\Models\MembreIndisponibilite;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EmploiDuTempsService
{
    /**
     * Récupérer tous les événements d'une entreprise pour une plage de dates.
     * Inclut : réservations Allotata, événements Google Calendar, et (optionnel) autres entreprises.
     */
    public function getEntrepriseEvents(Entreprise $entreprise, Carbon $start, Carbon $end, ?User $user = null): array
    {
        $events = [];

        // 1. Réservations Allotata de cette entreprise
        $reservations = $this->getReservations($entreprise, $start, $end);
        foreach ($reservations as $reservation) {
            $events[] = $this->formatReservation($reservation, $entreprise);
        }

        // 2. Événements Google Calendar (MembreIndisponibilite avec raison google:*)
        $googleEvents = $this->getGoogleCalendarEvents($entreprise, $start, $end);
        foreach ($googleEvents as $indispo) {
            $events[] = $this->formatGoogleEvent($indispo);
        }

        // 3. Réservations des autres entreprises (si interblocage actif)
        if ($user && $user->interbloquer_entreprises) {
            $otherEntreprises = $user->entreprises()->where('id', '!=', $entreprise->id)->get();
            foreach ($otherEntreprises as $otherEntreprise) {
                $otherReservations = $this->getReservations($otherEntreprise, $start, $end);
                foreach ($otherReservations as $reservation) {
                    $events[] = $this->formatReservation($reservation, $otherEntreprise, 'other_business');
                }
            }
        }

        return $events;
    }

    /**
     * Récupérer les événements fusionnés de toutes les entreprises d'un utilisateur.
     */
    public function getUserEvents(User $user, Carbon $start, Carbon $end): array
    {
        $events = [];
        $entreprises = $user->entreprises;
        $colors = $this->assignEntrepriseColors($entreprises);

        foreach ($entreprises as $entreprise) {
            // Réservations
            $reservations = $this->getReservations($entreprise, $start, $end);
            foreach ($reservations as $reservation) {
                $event = $this->formatReservation($reservation, $entreprise);
                $event['entreprise_color'] = $colors[$entreprise->id] ?? '#6366f1';
                $events[] = $event;
            }

            // Google Calendar
            $googleEvents = $this->getGoogleCalendarEvents($entreprise, $start, $end);
            foreach ($googleEvents as $indispo) {
                $event = $this->formatGoogleEvent($indispo);
                $event['entreprise_id'] = $entreprise->id;
                $event['entreprise_name'] = $entreprise->nom;
                $event['entreprise_color'] = $colors[$entreprise->id] ?? '#6366f1';
                $events[] = $event;
            }
        }

        return $events;
    }

    /**
     * Récupérer les réservations d'une entreprise pour une plage de dates.
     */
    protected function getReservations(Entreprise $entreprise, Carbon $start, Carbon $end): Collection
    {
        return Reservation::where('entreprise_id', $entreprise->id)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('date_reservation', [$start, $end])
                  ->orWhere(function ($q2) use ($start, $end) {
                      $q2->whereNotNull('date_butoire')
                         ->whereBetween('date_butoire', [$start->format('Y-m-d'), $end->format('Y-m-d')]);
                  });
            })
            ->whereIn('statut', ['en_attente', 'confirmee', 'terminee', 'annulee'])
            ->with(['user', 'typeService', 'membre.user'])
            ->get();
    }

    /**
     * Récupérer les événements Google Calendar (indisponibilités membres avec raison google:*).
     */
    protected function getGoogleCalendarEvents(Entreprise $entreprise, Carbon $start, Carbon $end): Collection
    {
        $membreIds = $entreprise->membres()->pluck('id');

        if ($membreIds->isEmpty()) {
            return collect();
        }

        return MembreIndisponibilite::whereIn('membre_id', $membreIds)
            ->where('raison', 'LIKE', 'google:%')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('date_debut', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                  ->orWhere(function ($q2) use ($start, $end) {
                      $q2->where('date_debut', '<=', $end->format('Y-m-d'))
                         ->where(function ($q3) use ($start) {
                             $q3->where('date_fin', '>=', $start->format('Y-m-d'))
                                ->orWhereNull('date_fin');
                         });
                  });
            })
            ->get();
    }

    /**
     * Formater une réservation en événement unifié.
     */
    protected function formatReservation(Reservation $reservation, Entreprise $entreprise, string $type = 'reservation'): array
    {
        $isDateButoire = $reservation->typeService && $reservation->typeService->estDateButoire();
        $dateButoire = $reservation->date_butoire;

        if ($isDateButoire && $dateButoire) {
            $debut = Carbon::parse($dateButoire)->startOfDay();
            $fin = $debut->copy()->endOfDay();
        } else {
            $debut = Carbon::parse($reservation->date_reservation);
            $fin = $debut->copy()->addMinutes((int) ($reservation->duree_minutes ?? 30));
        }

        $clientName = $reservation->user
            ? $reservation->user->name
            : ($reservation->nom_client ?? 'Client');

        $serviceName = $reservation->typeService
            ? $reservation->typeService->nom
            : ($reservation->type_service ?? 'Réservation');

        return [
            'id' => $reservation->id,
            'type' => $type,
            'title' => $serviceName . ' - ' . $clientName,
            'start' => $debut->toIso8601String(),
            'end' => $fin->toIso8601String(),
            'status' => $reservation->statut,
            'client_name' => $clientName,
            'service_name' => $serviceName,
            'entreprise_id' => $entreprise->id,
            'entreprise_name' => $entreprise->nom,
            'source_event_id' => null,
            'meta' => [
                'hash' => $reservation->hash ?? null,
                'prix' => $reservation->prix,
                'duree' => $reservation->duree_minutes,
                'est_paye' => $reservation->est_paye,
                'membre' => $reservation->membre && $reservation->membre->user ? $reservation->membre->user->name : null,
                'date_butoire' => $isDateButoire,
            ],
        ];
    }

    /**
     * Formater un événement Google Calendar (MembreIndisponibilite) en événement unifié.
     */
    protected function formatGoogleEvent(MembreIndisponibilite $indispo): array
    {
        $debut = Carbon::parse($indispo->date_debut);
        $fin = $indispo->date_fin ? Carbon::parse($indispo->date_fin) : $debut->copy();

        if ($indispo->heure_debut) {
            $debut->setTimeFromTimeString($indispo->heure_debut);
        } else {
            $debut->startOfDay();
        }

        if ($indispo->heure_fin) {
            $fin->setTimeFromTimeString($indispo->heure_fin);
        } else {
            $fin->endOfDay();
        }

        // Extraire le Google Event ID de la raison
        $googleEventId = str_replace('google:', '', $indispo->raison ?? '');

        return [
            'id' => 'google_' . $indispo->id,
            'type' => 'google',
            'title' => $indispo->raison ? 'Google Calendar' : 'Indisponibilité',
            'start' => $debut->toIso8601String(),
            'end' => $fin->toIso8601String(),
            'status' => null,
            'client_name' => null,
            'service_name' => null,
            'entreprise_id' => $indispo->membre ? $indispo->membre->entreprise_id : null,
            'entreprise_name' => null,
            'source_event_id' => $googleEventId,
            'indisponibilite_id' => $indispo->id,
            'meta' => [
                'heure_debut' => $indispo->heure_debut,
                'heure_fin' => $indispo->heure_fin,
                'date_debut' => $indispo->date_debut?->format('Y-m-d'),
                'date_fin' => $indispo->date_fin?->format('Y-m-d'),
            ],
        ];
    }

    /**
     * Assigner des couleurs distinctes aux entreprises d'un utilisateur.
     */
    public function assignEntrepriseColors(Collection $entreprises): array
    {
        $palette = [
            '#22c55e', // green
            '#3b82f6', // blue
            '#f59e0b', // amber
            '#ef4444', // red
            '#8b5cf6', // violet
            '#ec4899', // pink
            '#14b8a6', // teal
            '#f97316', // orange
        ];

        $colors = [];
        foreach ($entreprises->values() as $i => $entreprise) {
            $colors[$entreprise->id] = $palette[$i % count($palette)];
        }

        return $colors;
    }
}
