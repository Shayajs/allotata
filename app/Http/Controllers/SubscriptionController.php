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
    public function index()
    {
        $user = Auth::user();
        
        if (!$user->est_gerant) {
            return redirect()->route('dashboard')
                ->with('error', 'Vous devez être gérant pour gérer un abonnement.');
        }

        // VÉRIFICATION DIRECTE SUR STRIPE avant d'afficher la page
        // On synchronise toujours depuis Stripe pour être sûr que les données sont à jour
        \App\Services\StripeSubscriptionSyncService::syncAllUserSubscriptions($user);
        
        // Recharger l'utilisateur pour avoir les dernières données
        $user->refresh();
        
        $subscription = $user->subscription('default');

        // Récupérer le prix actuel depuis Stripe pour l'affichage
        $currentPrice = null;
        $currentPriceAmount = null;
        try {
            // Vérifier s'il y a un prix personnalisé
            $customPrice = \App\Models\CustomPrice::getForUser($user, 'default');
            $priceId = $customPrice ? $customPrice->stripe_price_id : config('services.stripe.price_id');
            
            if ($priceId) {
                Stripe::setApiKey(config('services.stripe.secret'));
                $price = Price::retrieve($priceId);
                $currentPrice = $price;
                $currentPriceAmount = $price->unit_amount / 100; // Convertir de centimes en euros
            }
        } catch (\Exception $e) {
            Log::warning('Impossible de récupérer le prix Stripe pour l\'affichage: ' . $e->getMessage());
        }

        return view('subscription.index', [
            'user' => $user,
            'subscription' => $subscription,
            'currentPrice' => $currentPrice,
            'currentPriceAmount' => $currentPriceAmount,
        ]);
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
        
        return $user->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route('subscription.success'),
                'cancel_url' => route('subscription.index'),
            ]);
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
        // On va directement interroger Stripe pour vérifier le statut de l'abonnement
        // indépendamment des webhooks ou du Stripe CLI
        
        Log::info('Vérification directe Stripe après checkout', [
            'user_id' => $user->id,
            'stripe_id' => $user->stripe_id,
        ]);

        // Attendre un peu pour que Stripe traite le paiement
        sleep(2);
        
        // Synchroniser TOUS les abonnements depuis Stripe (utilisateur + entreprises)
        $syncResult = \App\Services\StripeSubscriptionSyncService::syncAllUserSubscriptions($user);
        
        Log::info('Résultat synchronisation Stripe', [
            'user_id' => $user->id,
            'sync_result' => $syncResult,
        ]);

        // Recharger l'utilisateur depuis la base de données
        $user->refresh();
        
        // Vérifier l'abonnement utilisateur
        $subscription = $user->subscription('default');
        
        // Désactiver l'abonnement manuel si un abonnement Stripe est actif
        if ($subscription && $subscription->valid()) {
            $user->update([
                'abonnement_manuel' => false,
                'abonnement_manuel_actif_jusqu' => null,
                'abonnement_manuel_notes' => null,
            ]);
            
            return redirect()->route('subscription.index')
                ->with('success', 'Votre abonnement a été activé avec succès !');
        }
        
        // Si toujours pas d'abonnement après synchronisation, afficher un message d'attente
        return view('subscription.success', [
            'subscription' => null,
            'pending' => true,
        ]);
    }

    /**
     * Rediriger vers le portail client Stripe pour gérer l'abonnement
     * Le portail client Stripe permet de gérer l'annulation, la reprise, 
     * la mise à jour de la méthode de paiement, etc.
     */
    public function cancel()
    {
        $user = Auth::user();
        
        if (!$user->stripe_id) {
            return back()->withErrors(['error' => 'Aucun compte Stripe associé.']);
        }

        try {
            // Créer une session de portail client Stripe
            $session = BillingPortalSession::create([
                'customer' => $user->stripe_id,
                'return_url' => route('subscription.index'),
            ], [
                'api_key' => config('services.stripe.secret'),
            ]);

            // Rediriger vers le portail client Stripe
            return redirect($session->url);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la session du portail client Stripe: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Impossible d\'accéder au portail de gestion Stripe. Veuillez réessayer plus tard.']);
        }
    }

    /**
     * Reprendre l'abonnement
     */
    public function resume()
    {
        $user = Auth::user();
        $subscription = $user->subscription('default');

        if ($subscription && $subscription->onGracePeriod()) {
            $subscription->resume();
            
            // Désactiver l'abonnement manuel si l'abonnement Stripe est réactivé
            $user->update([
                'abonnement_manuel' => false,
                'abonnement_manuel_actif_jusqu' => null,
                'abonnement_manuel_notes' => null,
            ]);
            
            return redirect()->route('subscription.index')
                ->with('success', 'Votre abonnement a été réactivé.');
        }

        return back()->withErrors(['error' => 'Impossible de reprendre l\'abonnement.']);
    }
}
