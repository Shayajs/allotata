<?php

namespace App\Http\Controllers;

use App\Models\EssaiGratuit;
use App\Models\Entreprise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EssaiGratuitController extends Controller
{
    /**
     * Démarre un essai gratuit pour l'utilisateur connecté (premium)
     */
    public function demarrerEssaiUtilisateur(Request $request)
    {
        $user = Auth::user();
        $type = 'premium';

        // Vérifier l'éligibilité
        if (!$user->peutDemarrerEssai($type)) {
            return back()->with('error', 'Vous avez déjà bénéficié d\'un essai gratuit cette année.');
        }

        // Vérifier qu'il n'a pas déjà un abonnement actif
        if ($user->aAbonnementActif()) {
            return back()->with('error', 'Vous avez déjà un abonnement actif.');
        }

        // Démarrer l'essai
        $essai = $user->demarrerEssai(
            type: $type,
            jours: 7,
            source: $request->input('source', 'bouton_cta'),
            codePromo: $request->input('code_promo'),
        );

        return back()->with('success', 'Votre essai gratuit de 7 jours a été activé ! Profitez de toutes les fonctionnalités premium.');
    }

    /**
     * Démarre un essai gratuit pour une entreprise
     */
    public function demarrerEssaiEntreprise(Request $request, Entreprise $entreprise)
    {
        $user = Auth::user();

        // Vérifier que l'utilisateur est bien le propriétaire
        if ($entreprise->user_id !== $user->id) {
            abort(403, 'Vous n\'êtes pas autorisé à effectuer cette action.');
        }

        // ⚠️ VÉRIFICATION CRITIQUE : L'utilisateur doit avoir un abonnement Premium actif
        // Les fonctionnalités entreprise (site_web, multi_personnes) sont des add-ons au Premium
        if (!$user->aAbonnementActif()) {
            return back()->with('error', 'Vous devez d\'abord avoir un abonnement Premium actif pour essayer les options d\'entreprise. Commencez par un essai gratuit Premium !');
        }

        $type = $request->input('type');
        
        // Valider le type
        if (!in_array($type, ['site_web', 'multi_personnes'])) {
            return back()->with('error', 'Type d\'abonnement invalide.');
        }

        // Vérifier l'éligibilité
        if (!$entreprise->peutDemarrerEssai($type)) {
            return back()->with('error', 'Cette entreprise a déjà bénéficié d\'un essai gratuit pour cette fonctionnalité cette année.');
        }

        // Vérifier qu'il n'a pas déjà un abonnement actif pour ce type
        if ($type === 'site_web' && $entreprise->aSiteWebActif()) {
            return back()->with('error', 'Cette entreprise a déjà un abonnement Site Web actif.');
        }
        if ($type === 'multi_personnes' && $entreprise->aGestionMultiPersonnes()) {
            return back()->with('error', 'Cette entreprise a déjà un abonnement Multi-Personnes actif.');
        }

        // Démarrer l'essai
        $essai = $entreprise->demarrerEssai(
            type: $type,
            jours: 7,
            source: $request->input('source', 'bouton_cta'),
            codePromo: $request->input('code_promo'),
        );

        $typeLabel = $type === 'site_web' ? 'Site Web Vitrine' : 'Gestion Multi-Personnes';
        
        return back()->with('success', "Votre essai gratuit de 7 jours pour \"$typeLabel\" a été activé !");
    }

    /**
     * Annule un essai gratuit (utilisateur)
     */
    public function annulerEssai(Request $request, EssaiGratuit $essai)
    {
        $user = Auth::user();

        // Vérifier que l'utilisateur est bien le propriétaire
        $isOwner = false;
        if ($essai->essayable_type === 'App\\Models\\User' && $essai->essayable_id === $user->id) {
            $isOwner = true;
        } elseif ($essai->essayable_type === 'App\\Models\\Entreprise') {
            $entreprise = Entreprise::find($essai->essayable_id);
            if ($entreprise && $entreprise->user_id === $user->id) {
                $isOwner = true;
            }
        }

        if (!$isOwner) {
            abort(403, 'Vous n\'êtes pas autorisé à effectuer cette action.');
        }

        $essai->annuler($request->input('raison', 'Annulé par l\'utilisateur'));

        return back()->with('success', 'Votre essai gratuit a été annulé.');
    }

    /**
     * Enregistre le feedback après un essai
     */
    public function feedback(Request $request, EssaiGratuit $essai)
    {
        $request->validate([
            'note_satisfaction' => 'nullable|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:1000',
            'raison_non_conversion' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();

        // Vérifier que l'utilisateur est bien le propriétaire
        $isOwner = false;
        if ($essai->essayable_type === 'App\\Models\\User' && $essai->essayable_id === $user->id) {
            $isOwner = true;
        } elseif ($essai->essayable_type === 'App\\Models\\Entreprise') {
            $entreprise = Entreprise::find($essai->essayable_id);
            if ($entreprise && $entreprise->user_id === $user->id) {
                $isOwner = true;
            }
        }

        if (!$isOwner) {
            abort(403, 'Vous n\'êtes pas autorisé à effectuer cette action.');
        }

        $essai->update([
            'note_satisfaction' => $request->input('note_satisfaction'),
            'feedback' => $request->input('feedback'),
            'raison_non_conversion' => $request->input('raison_non_conversion'),
        ]);

        return back()->with('success', 'Merci pour votre retour !');
    }
}
