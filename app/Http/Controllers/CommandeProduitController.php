<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\Produit;
use App\Models\CommandeProduit;
use App\Models\Stock;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CommandeProduitController extends Controller
{
    /**
     * Afficher la liste des commandes pour une entreprise
     */
    public function index(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $query = CommandeProduit::where('entreprise_id', $entreprise->id)
            ->with(['user', 'produit', 'membre.user']);

        // Filtres
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('est_paye')) {
            $query->where('est_paye', $request->est_paye === '1');
        }

        if ($request->filled('produit_id')) {
            $query->where('produit_id', $request->produit_id);
        }

        if ($request->filled('date_debut')) {
            $query->whereDate('date_commande', '>=', $request->date_debut);
        }

        if ($request->filled('date_fin')) {
            $query->whereDate('date_commande', '<=', $request->date_fin);
        }

        $commandes = $query->orderBy('date_commande', 'desc')->get();

        // Charger les produits pour les filtres
        $produits = $entreprise->produits()->where('est_actif', true)->orderBy('nom')->get();

        return view('commandes.index', [
            'entreprise' => $entreprise,
            'commandes' => $commandes,
            'produits' => $produits,
            'filters' => $request->only(['statut', 'est_paye', 'produit_id', 'date_debut', 'date_fin']),
        ]);
    }

    /**
     * Afficher une commande
     */
    public function show($slug, $id)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $commande = CommandeProduit::where('id', $id)
            ->where('entreprise_id', $entreprise->id)
            ->with(['user', 'produit.stock', 'produit.images', 'membre.user'])
            ->firstOrFail();

        return view('entreprise.commandes.show', [
            'entreprise' => $entreprise,
            'commande' => $commande,
        ]);
    }

    /**
     * Accepter une commande
     */
    public function accept(Request $request, $slug, $id)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $commande = CommandeProduit::where('id', $id)
            ->where('entreprise_id', $entreprise->id)
            ->with(['produit.stock'])
            ->firstOrFail();

        if ($commande->statut !== 'en_attente') {
            return back()->withErrors(['error' => 'Cette commande ne peut plus être modifiée.']);
        }

        $validated = $request->validate([
            'notes_gerant' => 'nullable|string|max:1000',
            'date_livraison_prevue' => 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            // Mettre à jour le statut
            $commande->update([
                'statut' => 'confirmee',
                'notes' => $commande->notes . ($validated['notes_gerant'] ? "\n\n[Note de l'entreprise] " . $validated['notes_gerant'] : ''),
                'date_livraison_prevue' => $validated['date_livraison_prevue'] ?? null,
            ]);

            // Décrémenter le stock si gestion immédiate
            if ($commande->produit->gestion_stock === 'disponible_immediatement') {
                $stock = $commande->produit->stock;
                if ($stock) {
                    $stock->quantite_disponible = max(0, $stock->quantite_disponible - $commande->quantite);
                    
                    // Vérifier si alerte nécessaire
                    if ($stock->quantite_disponible <= $stock->quantite_minimum) {
                        $stock->alerte_stock = true;
                    } else {
                        $stock->alerte_stock = false;
                    }
                    
                    $stock->save();
                }
            }

            DB::commit();

            // Invalider le cache
            \App\Services\CacheService::clearEntrepriseCache($entreprise->id, $entreprise->slug);

            // Créer une notification pour le client
            if ($commande->user_id) {
                Notification::creer(
                    $commande->user_id,
                    'commande',
                    'Commande confirmée',
                    "Votre commande de {$commande->quantite}x {$commande->produit->nom} pour {$entreprise->nom} a été confirmée !",
                    route('dashboard'),
                    ['commande_id' => $commande->id, 'entreprise_id' => $entreprise->id]
                );

                // Envoyer un email de confirmation
                try {
                    $commande->refresh();
                    // TODO: Créer EmailHelper::sendCommandeConfirmationClient($commande);
                } catch (\Exception $e) {
                    \Log::error("Erreur lors de l'envoi de l'email de confirmation : " . $e->getMessage());
                }
            }

            return redirect()->route('commandes.show', [$slug, $id])
                ->with('success', 'La commande a été acceptée avec succès. Le stock a été mis à jour.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors de l\'acceptation de la commande', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Une erreur est survenue lors de l\'acceptation de la commande.']);
        }
    }

    /**
     * Refuser une commande
     */
    public function reject(Request $request, $slug, $id)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $commande = CommandeProduit::where('id', $id)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        if ($commande->statut !== 'en_attente') {
            return back()->withErrors(['error' => 'Cette commande ne peut plus être modifiée.']);
        }

        $validated = $request->validate([
            'raison_refus' => 'nullable|string|max:500',
        ]);

        $commande->update([
            'statut' => 'annulee',
            'notes' => $commande->notes . ($validated['raison_refus'] ? "\n\n[Raison du refus] " . $validated['raison_refus'] : ''),
        ]);

        // Créer une notification pour le client
        if ($commande->user_id) {
            $raison = $validated['raison_refus'] ? " Raison : {$validated['raison_refus']}" : '';
            Notification::creer(
                $commande->user_id,
                'commande',
                'Commande annulée',
                "Votre commande de {$commande->quantite}x {$commande->produit->nom} pour {$entreprise->nom} a été annulée.{$raison}",
                route('dashboard'),
                ['commande_id' => $commande->id, 'entreprise_id' => $entreprise->id]
            );

            // Envoyer un email d'annulation
            try {
                $commande->refresh();
                // TODO: Créer EmailHelper::sendCommandeCancelledClient($commande);
            } catch (\Exception $e) {
                \Log::error("Erreur lors de l'envoi de l'email d'annulation : " . $e->getMessage());
            }
        }

        return redirect()->route('commandes.show', [$slug, $id])
            ->with('success', 'La commande a été annulée avec succès.');
    }

    /**
     * Marquer une commande comme payée
     */
    public function marquerPayee(Request $request, $slug, $id)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $commande = CommandeProduit::where('id', $id)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $validated = $request->validate([
            'date_paiement' => 'nullable|date',
        ]);

        $datePaiement = $validated['date_paiement'] ?? now();

        $commande->update([
            'est_paye' => true,
            'date_paiement' => $datePaiement,
        ]);

        // Envoyer un email au client pour confirmer le paiement
        if ($commande->user_id) {
            try {
                // TODO: Créer EmailHelper::sendPaymentReceived($commande);
            } catch (\Exception $e) {
                \Log::error("Erreur lors de l'envoi de l'email de paiement : " . $e->getMessage());
            }
        }

        return redirect()->route('commandes.show', [$slug, $id])
            ->with('success', 'Le paiement a été marqué comme effectué.');
    }
}
