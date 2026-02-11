<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\EntrepriseVisite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class TrackingController extends Controller
{
    /**
     * Mettre à jour la durée de visite
     */
    public function mettreAJourDuree(Request $request)
    {
        $validated = $request->validate([
            'slug' => 'required|string',
            'duree' => 'required|integer|min:0',
            'is_final' => 'boolean',
        ]);

        try {
            $entreprise = Entreprise::where('slug', $validated['slug'])->first();
            
            if (!$entreprise) {
                return response()->json(['error' => 'Entreprise non trouvée'], 404);
            }

            $sessionId = Session::getId();
            $user = Auth::user();

            // Vérifier le consentement au tracking (par défaut true pour utilisateurs non connectés)
            if ($user && !($user->tracking_consent ?? true)) {
                return response()->json(['error' => 'Consentement non donné'], 403);
            }

            // Récupérer la visite actuelle ou la créer
            $visite = EntrepriseVisite::where(function($query) use ($entreprise, $sessionId) {
                    $query->where('entreprise_id', $entreprise->id)
                          ->where('session_id', $sessionId)
                          ->where('created_at', '>=', now()->subMinutes(30)); // Visite récente (< 30 min)
                })
                ->orderBy('created_at', 'desc')
                ->first();

            // Si pas de visite trouvée, en créer une
            if (!$visite) {
                $pageType = $this->determinerPageType($request);
                $visite = EntrepriseVisite::enregistrerVisite($entreprise, $pageType, $user);
            }

            // Mettre à jour la durée
            $visite->mettreAJourDuree($validated['duree']);

            return response()->json([
                'success' => true,
                'visite_id' => $visite->id,
            ]);
        } catch (\Exception $e) {
            \Log::warning('Erreur lors de la mise à jour de la durée de visite: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Enregistrer un clic sur un service ou produit
     */
    public function enregistrerClic(Request $request)
    {
        $validated = $request->validate([
            'slug' => 'required|string',
            'type' => 'required|in:service,produit',
            'item_id' => 'required|integer',
            'item_nom' => 'required|string|max:255',
        ]);

        try {
            $entreprise = Entreprise::where('slug', $validated['slug'])->first();
            
            if (!$entreprise) {
                return response()->json(['error' => 'Entreprise non trouvée'], 404);
            }

            $sessionId = Session::getId();
            $user = Auth::user();

            // Vérifier le consentement au tracking (par défaut true pour utilisateurs non connectés)
            if ($user && !($user->tracking_consent ?? true)) {
                return response()->json(['error' => 'Consentement non donné'], 403);
            }

            // Récupérer la visite actuelle
            $visite = EntrepriseVisite::where('entreprise_id', $entreprise->id)
                ->where('session_id', $sessionId)
                ->where('created_at', '>=', now()->subMinutes(30)) // Visite récente (< 30 min)
                ->orderBy('created_at', 'desc')
                ->first();

            // Si pas de visite trouvée, en créer une
            if (!$visite) {
                $pageType = $this->determinerPageType($request);
                $visite = EntrepriseVisite::enregistrerVisite($entreprise, $pageType, $user);
            }

            // Marquer le clic
            $visite->marquerClic($validated['type'], $validated['item_id'], $validated['item_nom']);

            return response()->json([
                'success' => true,
                'visite_id' => $visite->id,
            ]);
        } catch (\Exception $e) {
            \Log::warning('Erreur lors de l\'enregistrement du clic: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Déterminer le type de page visitée
     */
    private function determinerPageType(Request $request): string
    {
        $referer = $request->header('referer') ?? '';
        
        if (strpos($referer, '/agenda') !== false) {
            return 'agenda';
        }
        
        if (strpos($referer, '/store') !== false) {
            return 'store';
        }
        
        return 'accueil';
    }
}
