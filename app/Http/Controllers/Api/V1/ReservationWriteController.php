<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ApiIdempotency;
use App\Models\Reservation;
use App\Services\ReservationStatusService;
use App\Support\ApiV1Presenter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ReservationWriteController extends ApiController
{
    public function accepter(Request $request, string $slug, int $reservation): JsonResponse
    {
        return $this->transition($request, $slug, $reservation, 'accepter');
    }

    public function refuser(Request $request, string $slug, int $reservation): JsonResponse
    {
        return $this->transition($request, $slug, $reservation, 'refuser');
    }

    private function transition(Request $request, string $slug, int $id, string $action): JsonResponse
    {
        $valide = $request->validate([
            'idempotency_key' => ['required', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $this->utilisateur($request);
        $existante = ApiIdempotency::where('user_id', $user->id)
            ->where('cle', $valide['idempotency_key'])
            ->first();

        if ($existante) {
            return response()->json($existante->reponse, $existante->status);
        }

        $entreprise = $this->entreprise($request, $slug);
        $modele = Reservation::where('entreprise_id', $entreprise->id)->find($id);

        if (! $modele) {
            $this->erreur('Réservation introuvable pour cette entreprise.', 'reservation_inconnue', 404);
        }

        try {
            $service = app(ReservationStatusService::class);
            $miseAJour = $action === 'accepter'
                ? $service->accepter($modele, $user, $valide['notes'] ?? null)
                : $service->refuser($modele, $user, $valide['notes'] ?? null);
        } catch (AuthorizationException $e) {
            $this->erreur($e->getMessage(), 'interdit', 403);
        } catch (InvalidArgumentException $e) {
            $this->erreur($e->getMessage(), 'statut_invalide', 422);
        }

        $corps = ApiV1Presenter::reservation($miseAJour->load('entreprise:id,slug,nom'));
        ApiIdempotency::create([
            'user_id' => $user->id,
            'cle' => $valide['idempotency_key'],
            'status' => 200,
            'reponse' => $corps,
        ]);

        return response()->json($corps);
    }
}
