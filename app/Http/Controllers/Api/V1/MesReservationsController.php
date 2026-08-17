<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Reservation;
use App\Support\ApiV1Presenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MesReservationsController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->utilisateur($request);
        $du = ($this->date($request->query('du')) ?? now()->subDays(7))->startOfDay();
        $au = ($this->date($request->query('au')) ?? now()->addDays(30))->endOfDay();

        $requete = Reservation::where('user_id', $user->id)
            ->with(['entreprise:id,slug,nom'])
            ->whereBetween('date_reservation', [$du, $au])
            ->orderBy('date_reservation');

        return $this->liste(
            $requete->paginate($this->parPage($request)),
            fn ($reservation) => ApiV1Presenter::reservation($reservation)
        );
    }
}
