<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\EntrepriseVisite;
use App\Models\Reservation;
use App\Support\ApiV1Presenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EntrepriseController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $page = $this->entreprisesAccessibles($request)->paginate($this->parPage($request));

        return $this->liste($page, fn ($entreprise) => ApiV1Presenter::entreprise($entreprise));
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $entreprise = $this->entreprise($request, $slug);

        return response()->json(array_merge(
            ApiV1Presenter::entrepriseDetaillee($entreprise),
            [
                'compteurs' => [
                    'services_actifs' => $entreprise->typesServices()->where('est_actif', true)->count(),
                    'produits_actifs' => $entreprise->produits()->where('est_actif', true)->count(),
                    'reservations_en_attente' => $entreprise->reservations()->where('statut', 'en_attente')->count(),
                ],
            ],
        ));
    }

    /**
     * Compteurs de la periode : par defaut les 30 derniers jours.
     */
    public function statistiques(Request $request, string $slug): JsonResponse
    {
        $entreprise = $this->entreprise($request, $slug);
        [$du, $au] = $this->periode($request);

        $reservations = Reservation::where('entreprise_id', $entreprise->id)
            ->whereBetween('date_reservation', [$du, $au]);

        $parStatut = (clone $reservations)
            ->selectRaw('statut, count(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');

        $encaisse = (clone $reservations)->where('est_paye', true)->sum('prix');
        $aEncaisser = (clone $reservations)
            ->where('est_paye', false)
            ->whereIn('statut', ['confirmee', 'terminee'])
            ->sum('prix');

        $topServices = (clone $reservations)
            ->whereNotNull('type_service')
            ->selectRaw('type_service, count(*) as total')
            ->groupBy('type_service')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($ligne) => ['service' => $ligne->type_service, 'reservations' => (int) $ligne->total])
            ->all();

        return response()->json([
            'entreprise' => $entreprise->slug,
            'periode' => [
                'du' => $du->toDateString(),
                'au' => $au->toDateString(),
            ],
            'reservations' => [
                'total' => (int) array_sum($parStatut->all()),
                'en_attente' => (int) ($parStatut['en_attente'] ?? 0),
                'confirmees' => (int) ($parStatut['confirmee'] ?? 0),
                'terminees' => (int) ($parStatut['terminee'] ?? 0),
                'annulees' => (int) ($parStatut['annulee'] ?? 0),
            ],
            'chiffre_affaires' => [
                'encaisse' => (float) $encaisse,
                'a_encaisser' => (float) $aEncaisser,
            ],
            'visites' => EntrepriseVisite::where('entreprise_id', $entreprise->id)
                ->whereBetween('created_at', [$du, $au])
                ->count(),
            'top_services' => $topServices,
        ]);
    }
}
