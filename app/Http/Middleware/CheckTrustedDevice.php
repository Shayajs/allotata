<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\SecurityService;
use App\Models\TrustedDevice;
use Symfony\Component\HttpFoundation\Response;

class CheckTrustedDevice
{
    /**
     * Handle an incoming request.
     * Vérifie si l'utilisateur a changé d'IP/périphérique et nécessite une vérification A2F
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ne vérifier que pour les utilisateurs authentifiés
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Ne pas vérifier si l'A2F n'est pas activé
        if (!$user->a2f_enabled) {
            return $next($request);
        }

        // Ignorer certaines routes (pour éviter les boucles)
        $excludedRoutes = [
            'two-factor.show',
            'two-factor.verify',
            'two-factor.request',
            'logout',
            'verification.required',
            'verification.resend',
            'verification.verify',
        ];

        if (in_array($request->route()?->getName(), $excludedRoutes)) {
            return $next($request);
        }

        $ipAddress = $request->ip();
        $userAgent = $request->userAgent() ?? '';

        // Vérifier dans la session si on a déjà vérifié cette IP/périphérique pour cette session
        $sessionKey = 'trusted_device_' . md5($ipAddress . $userAgent);
        
        if ($request->session()->get($sessionKey)) {
            // Déjà vérifié dans cette session
            return $next($request);
        }

        // Vérifier si le périphérique/IP sont approuvés
        if (TrustedDevice::isTrusted($user->id, $ipAddress, $userAgent)) {
            // Marquer comme vérifié pour cette session
            $request->session()->put($sessionKey, true);
            return $next($request);
        }

        // Nouvelle IP ou nouveau périphérique détecté
        // Vérifier si on doit demander l'A2F
        $securityService = app(SecurityService::class);
        
        if ($securityService->shouldRequireA2F($user, $ipAddress, $userAgent)) {
            // Stocker l'état en session pour rediriger vers A2F
            $request->session()->put('two_factor_user_id', $user->id);
            $request->session()->put('two_factor_remember', false);
            $request->session()->put('a2f_redirect_url', $request->fullUrl());

            // Déconnecter temporairement (avec gestion du CookieJar manquant)
            try {
                Auth::logout();
            } catch (\RuntimeException $e) {
                if (str_contains($e->getMessage(), 'Cookie jar has not been set')) {
                    $request->session()->forget('login_web_' . sha1(Auth::getDefaultDriver()));
                } else {
                    throw $e;
                }
            }
            $request->session()->regenerateToken();

            // Logger l'événement
            \App\Models\SecurityLog::log(
                $user->id,
                'a2f_required_session_change',
                $ipAddress,
                $userAgent,
                null,
                ['reason' => 'IP or device change detected'],
                'medium',
                false
            );

            return redirect()->route('two-factor.show')
                ->with('status', 'Une vérification est nécessaire car vous utilisez un nouvel appareil ou une nouvelle connexion réseau.');
        } else {
            // IP/périphérique pas complètement inconnus, marquer comme approuvé automatiquement
            // (ex: même réseau mais IP différente, ou même périphérique mais IP différente)
            $ipInfo = $securityService->getIpInfo($ipAddress);
            TrustedDevice::markAsTrusted(
                $user->id,
                $ipAddress,
                $userAgent,
                $ipInfo['country_code'] ?? null,
                $ipInfo['location'] ?? null
            );

            // Marquer comme vérifié pour cette session
            $request->session()->put($sessionKey, true);
            
            return $next($request);
        }
    }
}
