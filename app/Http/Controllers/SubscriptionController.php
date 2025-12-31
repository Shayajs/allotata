<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Cashier\Subscription;

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

        $subscription = $user->subscription('default');

        return view('subscription.index', [
            'user' => $user,
            'subscription' => $subscription,
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

        // Récupérer l'ID du prix Stripe depuis la configuration
        $priceId = config('services.stripe.price_id');
        
        // Vérifier que le price_id est bien configuré
        if (empty($priceId)) {
            return back()->withErrors([
                'error' => 'La configuration Stripe est incomplète. Veuillez contacter l\'administrateur. Le STRIPE_PRICE_ID doit être configuré dans le fichier .env'
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
        
        // Attendre un peu pour que le webhook Stripe soit traité
        // Si l'abonnement n'existe pas encore, on essaie de synchroniser
        $subscription = $user->subscription('default');
        
        // Si pas d'abonnement trouvé, on essaie de synchroniser depuis Stripe
        if (!$subscription || !$subscription->valid()) {
            // Attendre 2 secondes pour laisser le temps au webhook
            sleep(2);
            
            // Recharger l'utilisateur depuis la base de données
            $user->refresh();
            
            // Vérifier à nouveau
            $subscription = $user->subscription('default');
            
            // Si toujours pas d'abonnement, on essaie de synchroniser manuellement
            if (!$subscription || !$subscription->valid()) {
                try {
                    // Synchroniser les abonnements depuis Stripe
                    if ($user->stripe_id) {
                        // Récupérer les abonnements depuis Stripe
                        $stripeCustomer = $user->asStripeCustomer();
                        if ($stripeCustomer) {
                            $stripeSubscriptions = \Stripe\Subscription::all([
                                'customer' => $user->stripe_id,
                                'status' => 'active',
                                'limit' => 1,
                            ], ['api_key' => config('services.stripe.secret')]);
                            
                            // Si on trouve un abonnement actif, on le synchronise
                            if (!empty($stripeSubscriptions->data)) {
                                $stripeSubscription = $stripeSubscriptions->data[0];
                                
                                // Créer ou mettre à jour l'abonnement dans la base de données
                                $subscription = $user->subscriptions()->updateOrCreate(
                                    [
                                        'stripe_id' => $stripeSubscription->id,
                                    ],
                                    [
                                        'name' => 'default',
                                        'stripe_status' => $stripeSubscription->status,
                                        'stripe_price' => $stripeSubscription->items->data[0]->price->id,
                                        'quantity' => $stripeSubscription->items->data[0]->quantity ?? 1,
                                        'trial_ends_at' => $stripeSubscription->trial_end ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->trial_end) : null,
                                        'ends_at' => $stripeSubscription->cancel_at ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->cancel_at) : null,
                                    ]
                                );
                            }
                        }
                    }
                    
                    // Recharger à nouveau
                    $user->refresh();
                    $subscription = $user->subscription('default');
                } catch (\Exception $e) {
                    // En cas d'erreur, on continue quand même
                    \Log::error('Erreur lors de la synchronisation Stripe: ' . $e->getMessage());
                }
            }
        }
        
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
        
        // Si toujours pas d'abonnement, afficher un message d'attente
        return view('subscription.success', [
            'subscription' => null,
            'pending' => true,
        ]);
    }

    /**
     * Annuler l'abonnement
     */
    public function cancel()
    {
        $user = Auth::user();
        $subscription = $user->subscription('default');

        if ($subscription) {
            $subscription->cancel();
            return redirect()->route('subscription.index')
                ->with('success', 'Votre abonnement a été annulé. Il restera actif jusqu\'à la fin de la période payée.');
        }

        return back()->withErrors(['error' => 'Aucun abonnement actif trouvé.']);
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
