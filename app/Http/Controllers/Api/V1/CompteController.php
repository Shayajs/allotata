<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ApiToken;
use App\Support\ApiV1Presenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Le compte derriere le jeton et le perimetre qu'il ouvre.
 *
 * C'est l'endpoint a appeler en premier : il dit qui on est et quelles entreprises
 * sont accessibles, donc quels slugs utiliser dans les autres appels.
 */
class CompteController extends ApiController
{
    public function show(Request $request): JsonResponse
    {
        $user = $this->utilisateur($request);
        $entreprises = $this->entreprisesAccessibles($request)->get();

        /** @var ApiToken|null $jeton */
        $jeton = $request->attributes->get('api_token');

        return response()->json([
            'compte' => [
                'id' => $user->id,
                'nom' => $user->name,
                'nom_famille' => $user->surname,
                'email' => $user->email,
                'telephone' => $user->telephone,
                'ville' => $user->ville,
                'code_postal' => $user->code_postal,
                'est_gerant' => (bool) $user->est_gerant,
                'est_client' => (bool) $user->est_client,
                'inscrit_le' => $user->created_at?->toIso8601String(),
            ],
            'entreprises' => $entreprises->map(fn ($entreprise) => array_merge(
                ApiV1Presenter::entreprise($entreprise),
                ['role' => (int) $entreprise->user_id === (int) $user->id ? 'proprietaire' : 'administrateur'],
            ))->all(),
            'jeton' => [
                'nom' => $jeton?->nom,
                'apercu' => $jeton ? ApiToken::PREFIXE.$jeton->apercu.'…' : null,
                'cree_le' => $jeton?->created_at?->toIso8601String(),
                'derniere_utilisation' => $jeton?->derniere_utilisation_at?->toIso8601String(),
                'expire_le' => $jeton?->expire_at?->toIso8601String(),
            ],
        ]);
    }
}
