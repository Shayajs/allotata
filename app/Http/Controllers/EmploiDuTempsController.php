<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Services\EmploiDuTempsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EmploiDuTempsController extends Controller
{
    protected EmploiDuTempsService $service;

    public function __construct(EmploiDuTempsService $service)
    {
        $this->service = $service;
    }

    /**
     * API : Événements d'une entreprise (réservations + Google + autres entreprises si interblocage).
     * GET /m/{slug}/emploi-du-temps/events?start=YYYY-MM-DD&end=YYYY-MM-DD
     */
    public function events($slug, Request $request)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $start = $request->has('start')
            ? Carbon::parse($request->input('start'))->startOfDay()
            : Carbon::now()->startOfMonth();

        $end = $request->has('end')
            ? Carbon::parse($request->input('end'))->endOfDay()
            : Carbon::now()->endOfMonth();

        $events = $this->service->getEntrepriseEvents($entreprise, $start, $end, $user);

        return response()->json([
            'events' => $events,
        ]);
    }

    /**
     * API : Événements fusionnés pour le dashboard utilisateur (toutes entreprises).
     * GET /dashboard/emploi-du-temps/events?start=YYYY-MM-DD&end=YYYY-MM-DD
     */
    public function userEvents(Request $request)
    {
        $user = Auth::user();

        $start = $request->has('start')
            ? Carbon::parse($request->input('start'))->startOfDay()
            : Carbon::now()->startOfMonth();

        $end = $request->has('end')
            ? Carbon::parse($request->input('end'))->endOfDay()
            : Carbon::now()->endOfMonth();

        $events = $this->service->getUserEvents($user, $start, $end);
        $colors = $this->service->assignEntrepriseColors($user->entreprises);

        return response()->json([
            'events' => $events,
            'entreprise_colors' => $colors,
        ]);
    }
}
