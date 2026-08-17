<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garde de l'API de gestion : un jeton personnel porte dans Authorization: Bearer.
 *
 * Aucune session n'intervient ici, l'API reste sans etat et donc insensible au
 * cloisonnement par sous-domaine comme aux jetons CSRF.
 */
class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $presente = $request->bearerToken();

        if (! $presente) {
            return $this->refus(
                'Jeton d\'API manquant. Ajoutez l\'en-tête Authorization: Bearer <jeton>.',
                'jeton_absent'
            );
        }

        $jeton = ApiToken::resoudre($presente);

        if (! $jeton || ! $jeton->user) {
            return $this->refus('Jeton d\'API invalide, révoqué ou expiré.', 'jeton_invalide');
        }

        Auth::setUser($jeton->user);
        $request->setUserResolver(fn () => $jeton->user);
        $request->attributes->set('api_token', $jeton);

        $jeton->marquerUtilise();

        return $next($request);
    }

    private function refus(string $message, string $code): Response
    {
        return response()->json([
            'message' => $message,
            'code' => $code,
            'documentation' => \App\Support\SubdomainHost::enabled()
                ? \App\Support\SubdomainHost::ownerUrl('/api')
                : rtrim(config('app.url'), '/').'/api',
        ], 401, ['WWW-Authenticate' => 'Bearer']);
    }
}
