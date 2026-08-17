<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\FcmToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceFcmController extends ApiController
{
    public function store(Request $request): JsonResponse
    {
        $valide = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'device' => ['nullable', 'string', 'max:40'],
        ]);

        FcmToken::updateOrCreate(
            ['token' => $valide['token']],
            [
                'user_id' => $this->utilisateur($request)->id,
                'device' => $valide['device'] ?? 'android',
            ]
        );

        return response()->json(['ok' => true]);
    }
}
