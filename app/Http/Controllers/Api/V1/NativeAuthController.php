<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\PocketAuthService;
use App\Support\CapacitorClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NativeAuthController extends ApiController
{
    public function login(Request $request, PocketAuthService $auth): JsonResponse
    {
        $this->exigerApk($request);

        return $this->repondre($auth->login($request));
    }

    public function twoFactor(Request $request, PocketAuthService $auth): JsonResponse
    {
        $this->exigerApk($request);

        return $this->repondre($auth->verifierA2f($request));
    }

    public function resendTwoFactor(Request $request, PocketAuthService $auth): JsonResponse
    {
        $this->exigerApk($request);

        return $this->repondre($auth->renvoyerA2f($request));
    }

    private function exigerApk(Request $request): void
    {
        if (! CapacitorClient::detect($request)) {
            $this->erreur('Réservé à l’application Android.', 'hors_application', 403);
        }
    }

    /**
     * @param  array<string, mixed>  $resultat
     */
    private function repondre(array $resultat): JsonResponse
    {
        $status = (int) ($resultat['status'] ?? 200);
        unset($resultat['status']);

        return response()->json($resultat, $status);
    }
}
