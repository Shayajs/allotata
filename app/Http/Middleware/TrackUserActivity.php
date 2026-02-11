<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\PresenceService;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    protected $presenceService;

    public function __construct(PresenceService $presenceService)
    {
        $this->presenceService = $presenceService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si l'utilisateur est authentifié, mettre à jour son activité
        if (Auth::check()) {
            $user = Auth::user();
            
            // Ignorer certaines routes pour éviter trop de mises à jour
            $ignoredRoutes = [
                'api.presence.heartbeat',
                'api.presence.users',
                'api.presence.user',
            ];

            if (!in_array($request->route()?->getName(), $ignoredRoutes)) {
                try {
                    $this->presenceService->updateActivity($user);
                } catch (\Exception $e) {
                    // Logger l'erreur mais ne pas bloquer la requête
                    \Log::error('Erreur lors de la mise à jour de la présence: ' . $e->getMessage());
                }
            }
        }

        return $next($request);
    }
}
