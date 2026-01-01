<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SiteWebController extends Controller
{
    /**
     * Afficher le site web vitrine d'une entreprise
     */
    public function show(Request $request, $slug)
    {
        $user = Auth::user();
        
        // Chercher d'abord par slug_web
        $entreprise = Entreprise::where('slug_web', $slug)->first();
        
        // Si pas trouvé et que l'utilisateur est connecté, chercher par slug (pour permettre au propriétaire d'accéder)
        if (!$entreprise && $user) {
            $entreprise = Entreprise::where('slug', $slug)
                ->where('user_id', $user->id)
                ->first();
        }
        
        if (!$entreprise) {
            abort(404, 'Site web introuvable. Vérifiez que le slug est correct.');
        }

        $isOwner = $user && $entreprise->user_id === $user->id;

        // Si ce n'est pas le propriétaire, vérifier les conditions strictes
        if (!$isOwner) {
            // L'entreprise doit être vérifiée pour les visiteurs
            if (!$entreprise->est_verifiee) {
                abort(404, 'Site web non disponible.');
            }

            // L'entreprise doit avoir un abonnement site web actif pour les visiteurs
            if (!$entreprise->aSiteWebActif()) {
                abort(404, 'Site web non disponible.');
            }
            
            // Les visiteurs doivent accéder via slug_web, pas slug
            if (empty($entreprise->slug_web) || $entreprise->slug_web !== $slug) {
                abort(404, 'Site web introuvable.');
            }
        } else {
            // Le propriétaire peut accéder même si l'entreprise n'est pas vérifiée ou n'a pas d'abonnement
            // Mais on affiche un avertissement si nécessaire
        }
        
        // Déterminer le mode
        $requestedMode = $request->query('mode');
        
        if ($isOwner) {
            // Si le propriétaire accède sans paramètre ?mode=, mode édition par défaut
            if ($requestedMode === null) {
                $mode = 'edit';
            } 
            // Si le propriétaire force le mode view avec ?mode=view
            else if ($requestedMode === 'view') {
                $mode = 'view';
            }
            // Si le propriétaire force le mode edit avec ?mode=edit (redondant mais possible)
            else {
                $mode = 'edit';
            }
        } else {
            // Si ce n'est pas le propriétaire, toujours en mode view
            $mode = 'view';
        }

        if ($mode === 'edit') {
            // Charger les relations nécessaires
            $entreprise->load('realisationPhotos');
            
            return view('public.site-web-edit', [
                'entreprise' => $entreprise,
                'isOwner' => $isOwner,
            ]);
        }

        return view('public.site-web', [
            'entreprise' => $entreprise,
            'isOwner' => $isOwner,
        ]);
    }

    /**
     * Mettre à jour le contenu du site web vitrine
     */
    public function update(Request $request, $slug)
    {
        $user = Auth::user();
        
        if (!$user) {
            abort(403, 'Vous devez être connecté pour modifier ce site.');
        }

        // Chercher d'abord par slug_web
        $entreprise = Entreprise::where('slug_web', $slug)->first();
        
        // Si pas trouvé, chercher par slug (pour permettre au propriétaire d'accéder)
        if (!$entreprise) {
            $entreprise = Entreprise::where('slug', $slug)
                ->where('user_id', $user->id)
                ->first();
        }

        if (!$entreprise) {
            abort(404, 'Site web introuvable.');
        }

        // Vérifier que l'utilisateur est le propriétaire
        if ($entreprise->user_id !== $user->id) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier ce site.');
        }

        $validated = $request->validate([
            'phrase_accroche' => ['nullable', 'string', 'max:500'],
            'slug_web' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', 'unique:entreprises,slug_web,' . $entreprise->id],
            'contenu_site_web' => ['nullable', 'json'],
        ]);

        // Si le slug_web change, vérifier qu'il n'existe pas déjà
        if (isset($validated['slug_web']) && $validated['slug_web'] !== $entreprise->slug_web) {
            $existing = Entreprise::where('slug_web', $validated['slug_web'])
                ->where('id', '!=', $entreprise->id)
                ->first();
            
            if ($existing) {
                return back()->withErrors(['slug_web' => 'Ce slug est déjà utilisé.']);
            }
        }

        // Décoder le JSON si fourni
        if (isset($validated['contenu_site_web'])) {
            $validated['contenu_site_web'] = json_decode($validated['contenu_site_web'], true);
        }

        $entreprise->update($validated);
        
        // Recharger l'entreprise pour avoir le nouveau slug_web
        $entreprise->refresh();

        return redirect()->route('site-web.show', ['slug' => $entreprise->slug_web ?? $entreprise->slug])
            ->with('success', 'Votre site web a été mis à jour.');
    }
}
