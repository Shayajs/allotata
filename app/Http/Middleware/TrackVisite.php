<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Entreprise;
use App\Models\EntrepriseVisite;
use Illuminate\Support\Facades\Auth;

class TrackVisite
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Ne tracker que les routes /p/{slug}*
        if ($request->routeIs('public.entreprise', 'public.agenda', 'public.store')) {
            $slug = $request->route('slug');
            
            if ($slug) {
                try {
                    $entreprise = Entreprise::where('slug', $slug)->first();
                    
                    if ($entreprise) {
                        $pageType = $this->determinerPageType($request);
                        $user = Auth::user();
                        
                        // Enregistrer la visite
                        EntrepriseVisite::enregistrerVisite($entreprise, $pageType, $user);
                    }
                } catch (\Exception $e) {
                    // Ne pas bloquer la requête en cas d'erreur
                    \Log::warning('Erreur lors du tracking de visite: ' . $e->getMessage());
                }
            }
        }

        return $response;
    }

    /**
     * Déterminer le type de page visitée
     */
    private function determinerPageType(Request $request): string
    {
        if ($request->routeIs('public.agenda')) {
            return 'agenda';
        }
        
        if ($request->routeIs('public.store')) {
            return 'store';
        }
        
        return 'accueil';
    }
}
