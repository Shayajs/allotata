<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Clientele reconstituee depuis les reservations.
 *
 * L'application n'a pas de table clients : un client est soit un compte
 * (user_id), soit un invite identifie par son email. Le regroupement se fait
 * donc en memoire, sur le seul perimetre d'une entreprise.
 */
class ClientController extends ApiController
{
    public function index(Request $request, string $slug): JsonResponse
    {
        $entreprise = $this->entreprise($request, $slug);

        $reservations = Reservation::where('entreprise_id', $entreprise->id)
            ->with('user:id,name,email,telephone')
            ->orderByDesc('date_reservation')
            ->get([
                'id', 'user_id', 'entreprise_id', 'nom_client', 'email_client',
                'telephone_client', 'telephone_client_non_inscrit', 'prix', 'est_paye',
                'statut', 'date_reservation',
            ]);

        $clients = [];

        foreach ($reservations as $reservation) {
            $cle = $reservation->user_id
                ? 'compte:'.$reservation->user_id
                : 'invite:'.strtolower((string) ($reservation->email_client ?: $reservation->nom_client ?: 'inconnu'));

            $clients[$cle] ??= [
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

        $clients = array_values($clients);

        if ($recherche = trim((string) $request->query('q', ''))) {
            $clients = array_values(array_filter($clients, fn (array $client) => str_contains(
                mb_strtolower($client['nom'].' '.$client['email']),
                mb_strtolower($recherche)
            )));
        }

        usort($clients, fn (array $a, array $b) => strcmp(
            (string) $b['derniere_reservation'],
            (string) $a['derniere_reservation']
        ));

        return $this->liste(
            $this->paginer($request, $clients),
            fn (array $client) => $client,
            ['total_clients' => count($clients)],
        );
    }
}
