<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Reservation;
use App\Support\ClientAggregation;
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

        $clients = ClientAggregation::depuisReservations($reservations);

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
