<?php

namespace App\Services;

use App\Models\Recurrence;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RecurrenceService
{
    /**
     * Génère toutes les occurrences (réservations) pour une récurrence donnée.
     * Chaque occurrence est une Reservation indépendante liée à la récurrence.
     *
     * @param  Recurrence  $recurrence
     * @param  string      $statutInitial  'en_attente' ou 'confirmee'
     * @return array       Liste des réservations créées
     */
    public function genererOccurrences(Recurrence $recurrence, string $statutInitial = 'en_attente'): array
    {
        $dates = $this->calculerDates($recurrence);
        $reservations = [];

        $typeService = $recurrence->typeService;

        DB::beginTransaction();
        try {
            foreach ($dates as $date) {
                $dateTime = $date->copy()->setTimeFromTimeString($recurrence->heure);
                $dureeMinutes = $typeService->duree_minutes;

                $reservationData = [
                    'user_id' => $recurrence->user_id,
                    'entreprise_id' => $recurrence->entreprise_id,
                    'membre_id' => $recurrence->membre_id,
                    'type_service_id' => $typeService->id,
                    'date_reservation' => $dateTime,
                    'date_fin' => $dateTime->copy()->addMinutes($dureeMinutes),
                    'lieu' => $recurrence->lieu,
                    'notes' => $recurrence->notes,
                    'prix' => $recurrence->prix_par_occurrence,
                    'duree_minutes' => $dureeMinutes,
                    'type_service' => $typeService->nom,
                    'statut' => $statutInitial,
                    'recurrence_id' => $recurrence->id,
                    'nom_client' => $recurrence->nom_client,
                    'email_client' => $recurrence->email_client,
                    'telephone_client' => $recurrence->telephone_client,
                ];

                // Vérifier la disponibilité et créer
                $reservation = ReservationSlotService::reserverSiDisponible(
                    $recurrence->entreprise_id,
                    $recurrence->membre_id,
                    $dateTime,
                    $dureeMinutes,
                    fn () => Reservation::create($reservationData),
                    false
                );

                if ($reservation) {
                    $reservations[] = $reservation;
                }
                // Si le créneau est pris, on skip cette occurrence sans bloquer les autres
            }

            DB::commit();
            return $reservations;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Calcule toutes les dates d'occurrence entre date_debut et date_fin
     * selon la fréquence définie.
     *
     * @param  Recurrence  $recurrence
     * @return array<Carbon>
     */
    public function calculerDates(Recurrence $recurrence): array
    {
        $dates = [];
        $current = $recurrence->date_debut->copy();
        $fin = $recurrence->date_fin->copy();

        while ($current->lte($fin)) {
            // Ne générer que pour les dates futures ou aujourd'hui
            if ($current->gte(today())) {
                $dates[] = $current->copy();
            }

            $current = $this->prochaineDate($current, $recurrence->frequence, $recurrence->intervalle_jours);
        }

        return $dates;
    }

    /**
     * Calcule la prochaine date selon la fréquence.
     */
    private function prochaineDate(Carbon $date, string $frequence, ?int $intervalleJours): Carbon
    {
        return match ($frequence) {
            'hebdomadaire' => $date->copy()->addWeek(),
            'bimensuel' => $date->copy()->addWeeks(2),
            'mensuel' => $date->copy()->addMonth(),
            'personnalise' => $date->copy()->addDays($intervalleJours ?? 7),
            default => $date->copy()->addWeek(),
        };
    }

    /**
     * Annule toutes les occurrences futures d'une récurrence.
     *
     * @param  Recurrence  $recurrence
     * @return int  Nombre d'occurrences annulées
     */
    public function annulerOccurrencesFutures(Recurrence $recurrence): int
    {
        $count = $recurrence->reservations()
            ->where('date_reservation', '>=', now())
            ->whereNotIn('statut', ['annulee', 'terminee'])
            ->update(['statut' => 'annulee']);

        $recurrence->update(['est_active' => false]);

        return $count;
    }
}
