<?php

namespace App\Http\Controllers\Api\V1;

use App\Support\ApiV1Presenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ce que l'entreprise vend : services (prestations) et produits.
 */
class CatalogueController extends ApiController
{
    public function services(Request $request, string $slug): JsonResponse
    {
        $entreprise = $this->entreprise($request, $slug);

        $requete = $entreprise->typesServices()
            ->orderBy('ordre_affichage')
            ->orderBy('nom');

        if ($request->filled('actifs')) {
            $requete->where('est_actif', $request->boolean('actifs'));
        }

        if ($request->filled('type_structure')) {
            $requete->where('type_structure', $request->query('type_structure'));
        }

        return $this->liste(
            $requete->paginate($this->parPage($request, 50)),
            fn ($service) => ApiV1Presenter::service($service)
        );
    }

    public function produits(Request $request, string $slug): JsonResponse
    {
        $entreprise = $this->entreprise($request, $slug);

        $requete = $entreprise->produits()
            ->orderBy('ordre_affichage')
            ->orderBy('nom');

        if ($request->filled('actifs')) {
            $requete->where('est_actif', $request->boolean('actifs'));
        }

        return $this->liste(
            $requete->paginate($this->parPage($request, 50)),
            fn ($produit) => ApiV1Presenter::produit($produit)
        );
    }
}
