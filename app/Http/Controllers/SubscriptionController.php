<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Subscription;
use Stripe\BillingPortal\Session as BillingPortalSession;
use Stripe\Stripe;
use Stripe\Price;

class SubscriptionController extends Controller
{
    /**
     * Afficher la page d'abonnement
     */
    /**
     * Afficher la page d'abonnement (Redirection vers les paramètres)
     */
    public function index()
    {
        // Rediriger vers l'onglet abonnement de la page paramètres
        return redirect()->route('settings.index', ['tab' => 'subscription']);
    }

    /**
     * Créer une session de checkout Stripe
     */
    public function checkout(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->est_gerant) {
            return back()->withErrors(['error' => 'Vous devez être gérant pour souscrire un abonnement.']);
        }

        // Vérifier si l'utilisateur a déjà un abonnement actif (manuel ou Stripe)
        if ($user->aAbonnementActif()) {
            if ($user->abonnement_manuel && $user->abonnement_manuel_actif_jusqu && $user->abonnement_manuel_actif_jusqu->isFuture()) {
                return back()->withErrors([
                    'error' => "Vous avez déjà un abonnement manuel actif jusqu'au {$user->abonnement_manuel_actif_jusqu->format('d/m/Y')}. Vous ne pouvez pas souscrire à un abonnement Stripe tant que l'abonnement manuel est actif."
                ]);
            }
            
            $subscription = $user->subscription('default');
            if ($subscription && $subscription->valid()) {
                return back()->withErrors([
                    'error' => 'Vous avez déjà un abonnement Stripe actif.'
                ]);
            }
        }

        // Vérifier s'il y a un prix personnalisé pour cet utilisateur
        $customPrice = \App\Models\CustomPrice::getForUser($user, 'default');
        
        // Utiliser le prix personnalisé s'il existe, sinon le prix par défaut
        $priceId = $customPrice ? $customPrice->stripe_price_id : config('services.stripe.price_id');
        
        // Vérifier que le price_id est bien configuré
        if (empty($priceId)) {
            return back()->withErrors([
                'error' => 'Le prix Stripe pour l\'abonnement utilisateur n\'est pas encore configuré. Veuillez contacter l\'administrateur pour créer ce prix depuis la page de gestion des prix Stripe.'
            ]);
        }
        
        try {
            return $user->newSubscription('default', $priceId)
                ->checkout([
                    'success_url' => route('subscription.success'),
                    'cancel_url' => route('settings.index', ['tab' => 'subscription']),
                ]);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // Si le client n'existe pas chez Stripe (ex: changement d'environnement), on le reset
            if (str_contains($e->getMessage(), 'No such customer')) {
                Log::warning("Client Stripe introuvable pour le user {$user->id}. Reset du stripe_id et tentative de création d'un nouveau client.");
                
                // On efface le stripe_id invalide
                $user->stripe_id = null;
                $user->save();
                
                // On laisse Cashier recréer le customer lors du prochain appel à newSubscription()
                // On relance la même commande
                return $user->newSubscription('default', $priceId)
                    ->checkout([
                        'success_url' => route('subscription.success'),
                        'cancel_url' => route('settings.index', ['tab' => 'subscription']),
                    ]);
            }
            throw $e;
        }
    }

    /**
     * Récupérer les factures Stripe de l'utilisateur
     */
    public function getInvoices()
    {
        $user = Auth::user();
        
        if (!$user->est_gerant) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        try {
            $invoices = $user->invoices();
            return response()->json($invoices);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
             if (str_contains($e->getMessage(), 'No such customer')) {
                // Client invalide, on le reset
                $user->stripe_id = null;
                $user->save();
                return response()->json(['error' => 'Compte client Stripe invalide. Veuillez vous réabonner pour en créer un nouveau.'], 400); // 400 Bad Request
             }
             return response()->json(['error' => 'Erreur Stripe impossible de récupérer les factures'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Impossible de récupérer les factures'], 500);
        }
    }

    /**
     * Télécharger une facture Stripe
     */
    public function downloadInvoice($invoiceId)
    {
        $user = Auth::user();
        
        if (!$user->est_gerant) {
            return back()->withErrors(['error' => 'Accès refusé']);
        }

        try {
            return $user->downloadInvoice($invoiceId, [
                'vendor' => 'Allo Tata',
                'product' => 'Abonnement Premium',
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Impossible de télécharger la facture']);
        }
    }

    /**
     * Page de succès après paiement
     */
    public function success(Request $request)
    {
        $user = Auth::user();
        
        // VÉRIFICATION DIRECTE SUR STRIPE (méthode de sécurité)
        Log::info('Vérification directe Stripe après checkout', [
            'user_id' => $user->id,
            'stripe_id' => $user->stripe_id,
        ]);

        sleep(2);
        
        // Synchroniser TOUS les abonnements depuis Stripe (utilisateur + entreprises)
        $syncResult = \App\Services\StripeSubscriptionSyncService::syncAllUserSubscriptions($user);
        
        Log::info('Résultat synchronisation Stripe', [
            'user_id' => $user->id,
            'sync_result' => $syncResult,
        ]);

        $user->refresh();
        $subscription = $user->subscription('default');
        
        if ($subscription && $subscription->valid()) {
            $user->update([
                'abonnement_manuel' => false,
                'abonnement_manuel_actif_jusqu' => null,
                'abonnement_manuel_notes' => null,
            ]);
            
            return redirect()->route('settings.index', ['tab' => 'subscription'])
                ->with('success', 'Votre abonnement a été activé avec succès !');
        }
        
        // Si toujours pas d'abonnement après synchronisation, on redirige quand même vers settings avec un message
        return redirect()->route('settings.index', ['tab' => 'subscription'])
            ->with('info', 'Votre paiement est en cours de validation. Votre abonnement sera actif dans quelques instants.');
    }

    /**
     * Rediriger vers le portail client Stripe pour gérer le mode de paiement, etc.
     */
    public function manage()
    {
        $user = Auth::user();
        
        if (!$user->stripe_id) {
            return back()->withErrors(['error' => 'Aucun compte Stripe associé.']);
        }

        try {
            return $user->redirectToBillingPortal(route('settings.index', ['tab' => 'subscription']));
        } catch (\Exception $e) {
             if (str_contains($e->getMessage(), 'No such customer')) {
                // Client invalide, on le reset
                $user->stripe_id = null;
                $user->save();
                return back()->withErrors(['error' => 'Votre identifiant client Stripe n\'est plus valide (changement d\'environnement ?). Veuillez souscrire un nouvel abonnement.']);
             }

            Log::error('Erreur lors de l\'accès au portail Stripe: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Impossible d\'accéder au portail de gestion Stripe.']);
        }
    }

    /**
     * Annuler l'abonnement à la fin de la période (Grace Period)
     */
    public function cancel()
    {
        $user = Auth::user();
        $subscription = $user->subscription('default');

        if ($subscription && $subscription->active()) {
            try {
                \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                
                // Communication directe avec Stripe pour annuler à la fin de la période
                $stripeSub = \Stripe\Subscription::update($subscription->stripe_id, [
                    'cancel_at_period_end' => true,
                ]);

                // Mise à jour immédiate de l'enregistrement Cashier pour le feedback visuel
                $timestamp = $stripeSub->current_period_end ?? $stripeSub->cancel_at;
                $dateFin = $timestamp ? \Carbon\Carbon::createFromTimestamp($timestamp) : now()->addMonth();
                
                $subscription->update([
                    'ends_at' => $dateFin,
                    'stripe_status' => $stripeSub->status,
                ]);
                
                return back()->with('success', "Votre abonnement Premium s'arrêtera le " . $dateFin->format('d/m/Y') . ". Vous gardez tous vos accès jusque là.");
            } catch (\Exception $e) {
                Log::error('Erreur annulation Premium direct: ' . $e->getMessage());
                return back()->with('error', "Erreur Stripe : " . $e->getMessage());
            }
        }

        return back()->with('error', "Aucun abonnement actif trouvé.");
    }

    /**
     * Reprendre l'abonnement
     */
    public function resume()
    {
        $user = Auth::user();
        $subscription = $user->subscription('default');

        if ($subscription && $subscription->onGracePeriod()) {
            try {
                \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                
                // Retirer l'annulation programmée sur Stripe
                $stripeSub = \Stripe\Subscription::update($subscription->stripe_id, [
                    'cancel_at_period_end' => false,
                ]);

                // Mise à jour immédiate en local
                $subscription->update([
                    'ends_at' => null,
                    'stripe_status' => $stripeSub->status,
                ]);

                // Nettoyer les infos d'abonnement manuel
                $user->update([
                    'abonnement_manuel' => false,
                    'abonnement_manuel_actif_jusqu' => null,
                    'abonnement_manuel_notes' => null,
                ]);
                
                return back()->with('success', "Votre abonnement Premium a été réactivé avec succès !");
            } catch (\Exception $e) {
                Log::error('Erreur réactivation Premium direct: ' . $e->getMessage());
                return back()->with('error', "Erreur Stripe : " . $e->getMessage());
            }
        }

        return back()->with('error', "Impossible de reprendre cet abonnement.");
    }

    /**
     * Nettoyer un abonnement orphelin (supprimé côté Stripe mais bloqué en local)
     */
    public function purge(Request $request, $id)
    {
        $user = Auth::user();

        // Chercher dans les abonnements utilisateur
        $sub = \Laravel\Cashier\Subscription::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$sub) {
             return back()->with('error', "Abonnement introuvable.");
        }

        // On vérifie que c'est bien une erreur ou un orphelin
        // Sécurité : on ne laisse pas supprimer un abonnement actif 'normal' sans vérification
        // Mais ici l'utilisateur clique sur "Force Delete".
        
        try {
            // Tentative de vérification ultime
            if ($sub->stripe_id) {
                try {
                    \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                    \Stripe\Subscription::retrieve($sub->stripe_id);
                    // Si on arrive ici, il existe encore !
                    return back()->with('error', "Cet abonnement existe encore chez Stripe. Veuillez l'annuler normalement.");
                } catch (\Stripe\Exception\InvalidRequestException $e) {
                     // C'est bon, il n'existe plus, on peut purger
                }
            }
            
            // Suppression
            $sub->delete();
            
            // Nettoyage user
            $user->update([
                'abonnement_manuel' => false,
                'abonnement_manuel_actif_jusqu' => null,
                'abonnement_manuel_notes' => null,
            ]);

            return back()->with('success', "Abonnement nettoyé de la base de données.");

        } catch (\Exception $e) {
            return back()->with('error', "Erreur lors du nettoyage : " . $e->getMessage());
        }
    }
}
