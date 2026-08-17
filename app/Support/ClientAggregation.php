<?php

namespace App\Support;

use App\Models\Reservation;
use Illuminate\Support\Collection;

final class ClientAggregation
{
    /**
     * @param  Collection<int, Reservation>  $reservations
     * @return list<array<string, mixed>>
     */
    public static function depuisReservations(Collection $reservations): array
    {
        $clients = [];

        foreach ($reservations as $reservation) {
            $cle = $reservation->user_id
                ? 'compte:'.$reservation->user_id
                : 'invite:'.strtolower((string) ($reservation->email_client ?: $reservation->nom_client ?: 'inconnu'));

            $clients[$cle] ??= [
                'cle' => $cle,
                'utilisateur_id' => $reservation->user_id,
                'inscrit' => $reservation->user_id !== null,
                'nom' => $reservation->nom_client_complet,
                'email' => $reservation->email_client_complet,
                'telephone' => $reservation->telephone_client
                    ?: $reservation->telephone_client_non_inscrit
                    ?: $reservation->user?->telephone,
                'reservations' => 0,
                'annulees' => 0,
                'total_encaisse' => 0.0,
                'premiere_reservation' => null,
                'derniere_reservation' => null,
            ];

            $client = &$clients[$cle];
            $client['reservations']++;

            if ($reservation->statut === 'annulee') {
                $client['annulees']++;
            }

            if ($reservation->est_paye) {
                $client['total_encaisse'] += (float) $reservation->prix;
            }

            $date = $reservation->date_reservation?->toIso8601String();

            if ($date !== null) {
                $client['derniere_reservation'] = max($client['derniere_reservation'] ?? $date, $date);
                $client['premiere_reservation'] = min($client['premiere_reservation'] ?? $date, $date);
            }

            unset($client);
        }

        $liste = array_values($clients);
        usort($liste, fn (array $a, array $b) => strcmp(
            (string) $b['derniere_reservation'],
            (string) $a['derniere_reservation']
        ));

        return $liste;
    }
}
