<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Conversation;
use App\Models\Facture;
use App\Models\Reservation;
use App\Support\ApiV1Presenter;
use App\Support\ClientAggregation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends ApiController
{
    public function show(Request $request): JsonResponse
    {
        $user = $this->utilisateur($request);
        $du = $this->date($request->query('du')) ?? now()->subDays(7);
        $au = $this->date($request->query('au')) ?? now()->addDays(30);
        $entreprises = $this->entreprisesAccessibles($request)->get();
        $ids = $entreprises->pluck('id');

        $reservationsGerant = Reservation::query()
            ->whereIn('entreprise_id', $ids)
            ->whereBetween('date_reservation', [$du->copy()->startOfDay(), $au->copy()->endOfDay()])
            ->with(['entreprise:id,slug,nom', 'user:id,name,email,telephone'])
            ->orderBy('date_reservation')
            ->limit(500)
            ->get();

        $reservationsClient = Reservation::query()
            ->where('user_id', $user->id)
            ->whereBetween('date_reservation', [$du->copy()->startOfDay(), $au->copy()->endOfDay()])
            ->with(['entreprise:id,slug,nom'])
            ->orderBy('date_reservation')
            ->limit(200)
            ->get();

        $reservations = $reservationsGerant
            ->concat($reservationsClient)
            ->unique('id')
            ->values();

        $clients = [];
        foreach ($entreprises as $entreprise) {
            $duEntreprise = Reservation::where('entreprise_id', $entreprise->id)
                ->with('user:id,name,email,telephone')
                ->get([
                    'id', 'user_id', 'entreprise_id', 'nom_client', 'email_client',
                    'telephone_client', 'telephone_client_non_inscrit', 'prix', 'est_paye',
                    'statut', 'date_reservation',
                ]);
            foreach (ClientAggregation::depuisReservations($duEntreprise) as $client) {
                $client['entreprise_id'] = $entreprise->id;
                $client['entreprise_slug'] = $entreprise->slug;
                $clients[] = $client;
            }
        }

        $conversations = collect();
        if ($user->est_client) {
            $conversations = $conversations->concat(
                Conversation::where('user_id', $user->id)
                    ->where('est_archivee', false)
                    ->with(['entreprise:id,nom', 'user:id,name', 'dernierMessage', 'messages'])
                    ->orderByDesc('dernier_message_at')
                    ->limit(30)
                    ->get()
            );
        }
        if ($ids->isNotEmpty()) {
            $conversations = $conversations->concat(
                Conversation::whereIn('entreprise_id', $ids)
                    ->where('est_archivee', false)
                    ->with(['entreprise:id,nom', 'user:id,name', 'dernierMessage', 'messages'])
                    ->orderByDesc('dernier_message_at')
                    ->limit(40)
                    ->get()
            );
        }
        $conversations = $conversations->unique('id')->values();

        $factures = Facture::query()
            ->with('entreprise:id,nom')
            ->where(function ($q) use ($user, $ids) {
                $q->where('user_id', $user->id);
                if ($ids->isNotEmpty()) {
                    $q->orWhereIn('entreprise_id', $ids);
                }
            })
            ->orderByDesc('date_facture')
            ->limit(80)
            ->get();

        return response()->json([
            'sync_at' => now()->toIso8601String(),
            'fenetre' => [
                'du' => $du->toDateString(),
                'au' => $au->toDateString(),
            ],
            'compte' => [
                'id' => $user->id,
                'nom' => $user->name,
                'email' => $user->email,
                'est_gerant' => (bool) $user->est_gerant,
                'est_client' => (bool) $user->est_client,
            ],
            'entreprises' => $entreprises->map(fn ($e) => ApiV1Presenter::entreprise($e))->all(),
            'reservations' => $reservations->map(fn ($r) => ApiV1Presenter::reservation($r))->all(),
            'clients' => $clients,
            'conversations' => $conversations->map(fn ($c) => ApiV1Presenter::conversation($c, $user->id))->all(),
            'factures' => $factures->map(fn ($f) => ApiV1Presenter::facture($f))->all(),
        ]);
    }
}
