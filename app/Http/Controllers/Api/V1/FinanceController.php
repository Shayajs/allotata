<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\EntrepriseFinance;
use App\Support\ApiV1Presenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ecritures de tresorerie saisies dans l'onglet Finances.
 */
class FinanceController extends ApiController
{
    public function index(Request $request, string $slug): JsonResponse
    {
        $entreprise = $this->entreprise($request, $slug);
        [$du, $au] = $this->periode($request, 365);

        $requete = EntrepriseFinance::where('entreprise_id', $entreprise->id)
            ->whereBetween('date_record', [$du, $au])
            ->orderByDesc('date_record');

        if ($request->filled('type')) {
            $type = (string) $request->query('type');

            if (! in_array($type, ['income', 'expense'], true)) {
                $this->erreur('Type inconnu : '.$type.'. Valeurs acceptées : income, expense.', 'type_invalide', 422);
            }

            $requete->where('type', $type);
        }

        if ($request->filled('categorie')) {
            $requete->where('category', $request->query('categorie'));
        }

        $recettes = (float) (clone $requete)->where('type', 'income')->sum('amount');
        $depenses = (float) (clone $requete)->where('type', 'expense')->sum('amount');

        return $this->liste(
            $requete->paginate($this->parPage($request, 50)),
            fn ($ecriture) => ApiV1Presenter::finance($ecriture),
            [
                'periode' => ['du' => $du->toDateString(), 'au' => $au->toDateString()],
                'totaux' => [
                    'recettes' => $recettes,
                    'depenses' => $depenses,
                    'solde' => round($recettes - $depenses, 2),
                ],
            ],
        );
    }
}
