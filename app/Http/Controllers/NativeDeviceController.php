<?php

namespace App\Http\Controllers;

use App\Models\ApiToken;
use App\Support\CapacitorClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NativeDeviceController extends Controller
{
    public const TOKEN_NAME = 'Pocket Android';

    public function store(Request $request): JsonResponse
    {
        if (! CapacitorClient::detect($request)) {
            return response()->json([
                'message' => 'Réservé à l’application Android.',
                'code' => 'hors_application',
            ], 403);
        }

        return response()->json([
            'jeton' => $this->mint($request->user()),
        ]);
    }

    public function handoff(Request $request): View|RedirectResponse
    {
        if (! CapacitorClient::detect($request)) {
            return redirect()->route('dashboard');
        }

        $jeton = $this->mint($request->user());

        return view('native.handoff', [
            'schemeUrl' => 'allotata://handoff#token='.rawurlencode($jeton),
        ]);
    }

    private function mint($user): string
    {
        $user->apiTokens()->where('nom', self::TOKEN_NAME)->delete();

        return ApiToken::creerPour($user, self::TOKEN_NAME)['jeton'];
    }
}
