<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\TypeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReservationSlotService
{
    /**
     * Vérifie qu'un créneau est disponible ET crée la réservation dans une transaction atomique.
     * Utilise lockForUpdate() pour empêcher les double-bookings (race conditions).
     *
     * @param  int         $entrepriseId
     * @param  int|null    $membreId
     * @param  Carbon      $debut         Début du créneau souhaité
     * @param  int         $dureeMinutes  Durée en minutes
     * @param  bool        $isDateButoire Si true, on ne vérifie pas le chevauchement
     * @return bool        true si le créneau est libre, false sinon
     */
    public static function estCreneauDisponible(
        int $entrepriseId,
        ?int $membreId,
        Carbon $debut,
        int $dureeMinutes,
        bool $isDateButoire = false
    ): bool {
        if ($isDateButoire) {
            return true;
        }

        $fin = $debut->copy()->addMinutes($dureeMinutes);

        $query = Reservation::where('entreprise_id', $entrepriseId)
            ->whereIn('statut', ['en_attente', 'confirmee'])
            ->lockForUpdate();

        if ($membreId) {
            $query->where('membre_id', $membreId);
        }

        // Vérification chevauchement directement en SQL (performant)
        // Un chevauchement existe si : debut_existant < fin_nouveau ET fin_existant > debut_nouveau
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite : datetime() + interval en secondes
            $conflit = $query
                ->where('date_reservation', '<', $fin)
                ->whereRaw(
                    "datetime(date_reservation, '+' || COALESCE(duree_minutes, 30) || ' minutes') > ?",
                    [$debut->toDateTimeString()]
                )
                ->exists();
        } else {
            // MySQL / MariaDB / PostgreSQL
            $conflit = $query
                ->where('date_reservation', '<', $fin)
                ->whereRaw(
                    "DATE_ADD(date_reservation, INTERVAL COALESCE(duree_minutes, 30) MINUTE) > ?",
                    [$debut->toDateTimeString()]
                )
                ->exists();
        }

        return !$conflit;
    }

    /**
     * Vérifie la capacité restante pour un événement (plusieurs participants sur le même créneau).
     *
     * @param  int         $entrepriseId
     * @param  int         $typeServiceId
     * @param  Carbon      $debut
     * @param  int         $dureeMinutes
     * @param  int         $capaciteMax
     * @return bool        true s'il reste de la place
     */
    public static function estEvenementDisponible(
        int $entrepriseId,
        int $typeServiceId,
        Carbon $debut,
        int $dureeMinutes,
        int $capaciteMax
    ): bool {
        $fin = $debut->copy()->addMinutes($dureeMinutes);

        $nbInscrits = Reservation::where('entreprise_id', $entrepriseId)
            ->where('type_service_id', $typeServiceId)
            ->whereIn('statut', ['en_attente', 'confirmee'])
            ->where('date_reservation', '>=', $debut->copy()->startOfDay())
            ->where('date_reservation', '<=', $fin)
            ->lockForUpdate()
            ->count();

        return $nbInscrits < $capaciteMax;
    }

    /**
     * Vérifie la disponibilité dans une transaction avec lock, puis exécute le callback si disponible.
     * Retourne le résultat du callback ou null si le créneau est pris.
     *
     * @param  int         $entrepriseId
     * @param  int|null    $membreId
     * @param  Carbon      $debut
     * @param  int         $dureeMinutes
     * @param  callable    $callback      Fonction à exécuter si le créneau est libre (doit retourner la réservation)
     * @param  bool        $isDateButoire
     * @return mixed|null  Résultat du callback, ou null si créneau pris
     */
    public static function reserverSiDisponible(
        int $entrepriseId,
        ?int $membreId,
        Carbon $debut,
        int $dureeMinutes,
        callable $callback,
        bool $isDateButoire = false
    ): mixed {
        return DB::transaction(function () use ($entrepriseId, $membreId, $debut, $dureeMinutes, $callback, $isDateButoire) {
            if (!self::estCreneauDisponible($entrepriseId, $membreId, $debut, $dureeMinutes, $isDateButoire)) {
                return null;
            }

            return $callback();
        });
    }
}
