<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\EntrepriseSubscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminSubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all');
        
        // Requêtes de base
        $userSubsQuery = \Laravel\Cashier\Subscription::query()->with('user');
        $entrepriseSubsQuery = EntrepriseSubscription::query()->with('entreprise');
        $manualUserSubsQuery = User::where('abonnement_manuel', true);
        $manualEntrepriseSubsQuery = EntrepriseSubscription::where('est_manuel', true)->with('entreprise');

        // Filtrage
        if ($filter === 'users') {
            $entrepriseSubsQuery->whereRaw('0 = 1'); // Vide
            $manualEntrepriseSubsQuery->whereRaw('0 = 1');
        } elseif ($filter === 'entreprises') {
            $userSubsQuery->whereRaw('0 = 1');
            $manualUserSubsQuery->whereRaw('0 = 1');
        } elseif ($filter === 'stripe') {
            $manualUserSubsQuery->whereRaw('0 = 1');
            $manualEntrepriseSubsQuery->whereRaw('0 = 1');
        } elseif ($filter === 'manual') {
            $userSubsQuery->whereRaw('0 = 1');
            // Pour entreprise, on veut QUE les manuels
            $entrepriseSubsQuery->where('est_manuel', true); 
        }

        // Exécution
        $userSubscriptions = $userSubsQuery->orderBy('created_at', 'desc')->get();
        
        // Pour les entreprises, on sépare Stripe et Manuel si 'all' ou spécifiques
        // Note: EntrepriseSubscription contient TOUT (Stripe et Manuel) via le flag est_manuel
        // Donc on peut simplifier en prenant tout et en filtrant dans la vue, ou faire des requêtes séparées
        
        $entrepriseSubscriptionsAll = $entrepriseSubsQuery->orderBy('created_at', 'desc')->get();
        // Séparation pour la vue
        $entrepriseSubscriptionsStripe = $entrepriseSubscriptionsAll->where('est_manuel', false);
        $manualEntrepriseSubscriptions = $entrepriseSubscriptionsAll->where('est_manuel', true);
        
        // Utilisateurs manuels (Table Users)
        $manualUserSubscriptions = $manualUserSubsQuery->get();

        return view('admin.subscriptions.index', [
            'userSubscriptions' => $userSubscriptions,
            'entrepriseSubscriptions' => $entrepriseSubscriptionsStripe,
            'manualUserSubscriptions' => $manualUserSubscriptions,
            'manualEntrepriseSubscriptions' => $manualEntrepriseSubscriptions,
            'filter' => $filter,
        ]);
    }

    /**
     * Synchroniser tout depuis Stripe
     */
    public function syncAll()
    {
        // ... (Logique existante ou appel au service)
        return back()->with('success', 'Synchronisation lancée (WIP).');
    }

    // ... (Autres méthodes de sync existantes)

    /**
     * Forcer un abonnement manuel pour une entreprise
     */
    public function forceManual(Request $request)
    {
        $request->validate([
            'entreprise_id' => 'required|exists:entreprises,id',
            'type' => 'required|in:site_web,multi_personnes',
            'date_fin' => 'required|date|after:today',
            'notes' => 'nullable|string'
        ]);

        $entreprise = Entreprise::findOrFail($request->entreprise_id);

        $sub = EntrepriseSubscription::updateOrCreate(
            [
                'entreprise_id' => $entreprise->id,
                'type' => $request->type,
            ],
            [
                'est_manuel' => true,
                'actif_jusqu' => $request->date_fin,
                'notes_manuel' => $request->notes,
                // On nettoie les champs Stripe pour éviter la confusion
                // 'stripe_id' => null, // ON NE SUPPRIME PAS L'ID STRIPE SI IL EXISTAIT HISTORIQUEMENT ? 
                // Mieux vaut garder l'historique mais 'est_manuel' prend le dessus grâce au Service.
            ]
        );

        return back()->with('success', "Abonnement manuel activé pour {$entreprise->nom}");
    }
    
    /**
     * Mettre à jour un abonnement manuel
     */
    public function updateManual(Request $request, $id)
    {
        $sub = EntrepriseSubscription::findOrFail($id);
        
        if (!$sub->est_manuel) {
            return back()->with('error', 'Impossible de modifier manuellement un abonnement Stripe. Passez par Stripe ou forcez un nouvel abonnement manuel.');
        }

        $request->validate([
            'date_fin' => 'required|date',
            'notes' => 'nullable|string'
        ]);

        $sub->update([
            'actif_jusqu' => $request->date_fin,
            'notes_manuel' => $request->notes,
        ]);

        return back()->with('success', 'Abonnement manuel mis à jour.');
    }

    /**
     * Désactiver un abonnement manuel
     */
    public function stopManual($id)
    {
        $sub = EntrepriseSubscription::findOrFail($id);
        if ($sub->est_manuel) {
            $sub->update([
                'actif_jusqu' => now()->subDay(), // Expiré
            ]);
        }
        return back()->with('success', 'Abonnement manuel arrêté.');
    }
}
