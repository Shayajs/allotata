<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Facture;
use App\Support\ApiV1Presenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FactureApiController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->utilisateur($request);
        $ids = $this->entreprisesAccessibles($request)->pluck('id');

        $requete = Facture::query()
            ->with('entreprise:id,nom')
            ->where(function ($q) use ($user, $ids) {
                $q->where('user_id', $user->id);
                if ($ids->isNotEmpty()) {
                    $q->orWhereIn('entreprise_id', $ids);
                }
            })
            ->orderByDesc('date_facture');

        return $this->liste(
            $requete->paginate($this->parPage($request)),
            fn ($facture) => ApiV1Presenter::facture($facture)
        );
    }

    public function pdf(Request $request, int $facture): Response
    {
        $user = $this->utilisateur($request);
        $modele = Facture::with(['entreprise', 'reservation', 'reservations.user', 'reservations.typeService', 'user'])
            ->find($facture);

        if (! $modele) {
            $this->erreur('Facture introuvable.', 'facture_inconnue', 404);
        }

        $gerant = $modele->entreprise && $modele->entreprise->peutEtreGereePar($user);
        $client = (int) $modele->user_id === (int) $user->id;

        if (! $gerant && ! $client && ! $user->is_admin) {
            $this->erreur('Cette facture n’est pas dans votre périmètre.', 'hors_perimetre', 403);
        }

        if ($client && ! $gerant && ! $modele->estVisibleParClient()) {
            $this->erreur('Cette facture n’est pas encore disponible.', 'facture_invisible', 403);
        }

        return app(\App\Services\Facturation\PdfDocumentRenderer::class)
            ->facturePdf($modele)
            ->download('facture-'.$modele->numero_facture.'.pdf');
    }
}
