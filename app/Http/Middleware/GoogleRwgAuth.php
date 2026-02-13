<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware d'authentification pour les endpoints Reserve with Google (RwG).
 *
 * Vérifie que la requête contient le bon token Bearer dans le header Authorization.
 * La clé est définie dans .env : GOOGLE_RWG_API_KEY
 *
 * En mode debug/local, le middleware est permissif si aucune clé n'est configurée.
 */
class GoogleRwgAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKey = config('services.google.rwg_api_key');

        // Si aucune clé configurée en local/debug → laisser passer (dev only)
        if (empty($configuredKey) && config('app.debug')) {
            return $next($request);
        }

        // Vérifier le header Authorization: Bearer <key>
        $bearerToken = $request->bearerToken();

        if (!$bearerToken || $bearerToken !== $configuredKey) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid or missing API key.',
            ], 401);
        }

        return $next($request);
    }
}
