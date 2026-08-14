<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Reservation;
use App\Services\ExceptionDateService;
use App\Services\ReservationSlotService;
use App\Support\ApiV1Presenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReservationController extends ApiController
{
    private const STATUTS = ['en_attente', 'confirmee', 'annulee', 'terminee'];

    public function index(Request $request, string $slug): JsonResponse
    {
        $entreprise = $this->entreprise($request, $slug);

        $requete = Reservation::where('entreprise_id', $entreprise->id)
            ->orderByDesc('date_reservation');

        if ($request->filled('statut')) {
            $statut = (string) $request->query('statut');

            if (! in_array($statut, self::STATUTS, true)) {
                $this->erreur(
                    'Statut inconnu : '.$statut.'. Valeurs acceptées : '.implode(', ', self::STATUTS).'.',
                    'statut_invalide',
                    422
                );
            }

            $requete->where('statut', $statut);
        }

        if ($du = $this->date($request->query('du'))) {
            $requete->where('date_reservation', '>=', $du->startOfDay());
        }

        if ($au = $this->date($request->query('au'))) {
            $requete->where('date_reservation', '<=', $au->endOfDay());
        }

        if ($request->filled('service_id')) {
            $requete->where('type_service_id', (int) $request->query('service_id'));
        }

        if ($request->filled('payees')) {
            $requete->where('est_paye', $request->boolean('payees'));
        }

        return $this->liste(
            $requete->paginate($this->parPage($request)),
            fn ($reservation) => ApiV1Presenter::reservation($reservation)
        );
    }

    public function show(Request $request, string $slug, int $reservation): JsonResponse
    {
        $entreprise = $this->entreprise($request, $slug);

        $modele = Reservation::where('entreprise_id', $entreprise->id)->find($reservation);

        if (! $modele) {
            $this->erreur('Réservation introuvable pour cette entreprise.', 'reservation_inconnue', 404);
        }

        return response()->json(ApiV1Presenter::reservation($modele));
    }

    /**
     * Creneaux encore libres pour une date.
     *
     * L'occupation n'est pas recalculee ici : la question est posee a
     * ReservationSlotService, celui qui la tranche aussi au moment de reserver.
     */
    public function disponibilites(Request $request, string $slug): JsonResponse
    {
        $entreprise = $this->entreprise($request, $slug);
        $date = ($this->date($request->query('date')) ?? now())->startOfDay();

        $service = $request->filled('service_id')
            ? $entreprise->typesServices()->find((int) $request->query('service_id'))
            : null;

        if ($request->filled('service_id') && ! $service) {
            $this->erreur('Service introuvable pour cette entreprise.', 'service_inconnu', 404);
        }

        $membreId = $request->filled('membre_id') ? (int) $request->query('membre_id') : null;
        $intervalle = $entreprise->resolveIntervalleCreneauxMinutes();
        $duree = (int) ($service->duree_minutes
            ?? $entreprise->typesServices()->min('duree_minutes')
            ?? $intervalle);
        $duree = max($intervalle, (int) ceil($duree / $intervalle) * $intervalle);

        $plages = app(ExceptionDateService::class)->getHorairesForDate($entreprise, $date);
        $creneaux = [];

        foreach ($plages as $plage) {
            if (! $plage->heure_ouverture || ! $plage->heure_fermeture) {
                continue;
            }

            $curseur = $date->copy()->setTimeFromTimeString(Carbon::parse($plage->heure_ouverture)->format('H:i'));
            $fermeture = $date->copy()->setTimeFromTimeString(Carbon::parse($plage->heure_fermeture)->format('H:i'));

            // Meme delai de prevenance que la prise de rendez-vous publique.
            if ($date->isToday()) {
                $curseur = $curseur->max(now()->addHour()->startOfHour());
            }

            while ($curseur->copy()->addMinutes($duree)->lte($fermeture)) {
                if (ReservationSlotService::estCreneauDisponible($entreprise->id, $membreId, $curseur->copy(), $duree)) {
                    $creneaux[] = [
                        'debut' => $curseur->copy()->toIso8601String(),
                        'fin' => $curseur->copy()->addMinutes($duree)->toIso8601String(),
                        'heure' => $curseur->format('H:i'),
                    ];
                }

                $curseur->addMinutes($intervalle);
            }
        }

        usort($creneaux, fn (array $a, array $b) => strcmp($a['debut'], $b['debut']));

        return response()->json([
            'entreprise' => $entreprise->slug,
            'date' => $date->toDateString(),
            'duree_minutes' => $duree,
            'intervalle_minutes' => $intervalle,
            'service_id' => $service?->id,
            'membre_id' => $membreId,
            'ferme' => $creneaux === [] && $plages->isEmpty(),
            'creneaux' => $creneaux,
        ]);
    }
}
