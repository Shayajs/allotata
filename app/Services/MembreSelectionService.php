<?php

namespace App\Services;

use App\Models\Entreprise;
use App\Models\EntrepriseMembre;
use App\Models\Reservation;
use Carbon\Carbon;

class MembreSelectionService
{
    public function selectionnerMembre(Entreprise $entreprise, Carbon $date, Carbon $heure, int $dureeMinutes): ?EntrepriseMembre
    {
        if (!$entreprise->aGestionMultiPersonnes()) {
            return null;
        }

        $membres = $entreprise->membres()
            ->where('est_actif', true)
            ->with(['disponibilites', 'indisponibilites'])
            ->get();

        if ($membres->isEmpty()) {
            return null;
        }

        $heureFin = $heure->copy()->addMinutes($dureeMinutes);

        $membresDisponibles = $membres->filter(function($membre) use ($date, $heure, $heureFin) {
            return $membre->estDisponible($date, $heure, $heureFin);
        });

        if ($membresDisponibles->isEmpty()) {
            return null;
        }

        if ($membresDisponibles->count() === 1) {
            return $membresDisponibles->first();
        }

        $dateDebut = $date->copy()->subDays(3);
        $dateFin = $date->copy()->addDays(3);

        $membresAvecCharge = $membresDisponibles->map(function($membre) use ($dateDebut, $dateFin) {
            $charge = $membre->getChargeTravail($dateDebut, $dateFin);
            return [
                'membre' => $membre,
                'charge' => $charge['nombre_reservations'],
                'duree_totale' => $charge['duree_totale_minutes'],
            ];
        });

        $membresAvecCharge = $membresAvecCharge->sortBy(function($item) {
            return $item['charge'] * 1000 + $item['duree_totale'];
        });

        return $membresAvecCharge->first()['membre'];
    }

    public function estMembreDisponible(EntrepriseMembre $membre, Carbon $date, Carbon $heure, int $dureeMinutes, ?int $reservationIdExclure = null): bool
    {
        $heureFin = $heure->copy()->addMinutes($dureeMinutes);
        if (!$membre->estDisponible($date, $heure, $heureFin)) {
            return false;
        }

        $reservations = Reservation::where('membre_id', $membre->id)
            ->whereIn('statut', ['en_attente', 'confirmee'])
            ->whereDate('date_reservation', $date->format('Y-m-d'))
            ->when($reservationIdExclure, function($query) use ($reservationIdExclure) {
                $query->where('id', '!=', $reservationIdExclure);
            })
            ->get();

        foreach ($reservations as $reservation) {
            $debutReservation = Carbon::parse($reservation->date_reservation);
            $finReservation = $debutReservation->copy()->addMinutes((int) ($reservation->duree_minutes ?? 30));
            if ($heure->lt($finReservation) && $heureFin->gt($debutReservation)) {
                return false;
            }
        }

        return true;
    }

    public function getMembresDisponibles(Entreprise $entreprise, Carbon $date, Carbon $heure, int $dureeMinutes)
    {
        if (!$entreprise->aGestionMultiPersonnes()) {
            return collect([]);
        }

        $membres = $entreprise->membres()
            ->where('est_actif', true)
            ->get();

        $heureFin = $heure->copy()->addMinutes($dureeMinutes);

        return $membres->filter(function($membre) use ($date, $heure, $heureFin, $dureeMinutes) {
            return $this->estMembreDisponible($membre, $date, $heure, $dureeMinutes);
        });
    }
}
