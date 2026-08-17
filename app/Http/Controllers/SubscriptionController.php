<?php

namespace App\Http\Controllers;

use App\Models\Echeance;
use App\Models\Entreprise;
use App\Services\CalculMontantDuService;
use App\Services\PremiumAccessService;
use Carbon\Carbon;
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

        if (\App\Support\CapacitorClient::detect($request)) {
            $productId = config('play.products.premium.id');

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'provider' => 'play',
                    'product_id' => $productId,
                ]);
            }

            return back()->with('play_billing_product', $productId);
        }
        
        if (!$user->est_gerant) {
            return back()->withErrors(['error' => 'Vous devez être gérant pour souscrire un abonnement.']);
        }

        if ($user->hasActiveManualPremium()) {
            return back()->withErrors([
                'error' => "Vous avez déjà un abonnement manuel actif jusqu'au {$user->abonnement_manuel_actif_jusqu->format('d/m/Y')}. Vous ne pouvez pas souscrire à un abonnement Stripe tant que l'abonnement manuel est actif.",
            ]);
        }

        if ($user->hasActivePlayPremium()) {
            return back()->withErrors([
                'error' => 'Vous avez déjà un abonnement Premium actif via Google Play.',
            ]);
        }

        if (PremiumAccessService::hasPremiumUntil($user)) {
            return back()->withErrors([
                'error' => 'Vous avez déjà un abonnement Premium actif.',
            ]);
        }

        $subscription = $user->subscription('default');
        if ($subscription && $subscription->valid()) {
            return back()->withErrors([
                'error' => 'Vous avez déjà un abonnement Stripe actif.',
            ]);
        }

        $debut = Carbon::now()->startOfDay();
        $fin = Carbon::now()->addMonth()->subDay()->startOfDay();
        $jour = (int) $debut->day;

        $tmp = new Echeance([
            'user_id' => $user->id,
            'entreprise_id' => null,
            'subscription_type' => Echeance::TYPE_DEFAULT,
            'periode_debut' => $debut,
            'periode_fin' => $fin,
            'jour_facturation' => $jour,
            'reduction_manuel' => 0,
        ]);
        $tmp->setRelation('user', $user);
        $calc = CalculMontantDuService::calculerPourEcheance($tmp, null, true);
        if ($calc['montant_du'] <= 0) {
            return back()->withErrors(['error' => 'Aucun montant à régler pour le Premium.']);
        }

        $pending = session('checkout_pending', []);
        $pending['default'] = [
            'entreprise_id' => null,
            'entreprise_nom' => 'Premium',
            'subscription_type' => Echeance::TYPE_DEFAULT,
            'user_id' => $user->id,
            'periode_debut' => $debut->toDateString(),
            'periode_fin' => $fin->toDateString(),
            'jour_facturation' => $jour,
            'montant_du' => $calc['montant_du'],
            'montant_final' => $calc['montant_final'],
            'reduction_promo' => $calc['reduction_promo'],
            'promo_code_id' => $calc['promo_code_id'],
            'lignes' => $calc['lignes'],
            'created_at' => now()->toIso8601String(),
        ];
        session(['checkout_pending' => $pending]);

        return redirect()->route('checkout.index')
            ->with('info', 'Réglez l\'échéance ci-dessous pour activer Premium.');
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
        if (PremiumAccessService::hasPremiumUntil($user)) {
            $user->update([
                'abonnement_manuel' => false,
                'abonnement_manuel_actif_jusqu' => null,
                'abonnement_manuel_notes' => null,
            ]);

            return redirect()->route('settings.index', ['tab' => 'subscription'])
                ->with('success', 'Votre abonnement a été activé avec succès !');
        }

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

    /**
     * Annuler une échéance à venir (a_payer / en_attente). L'utilisateur ne sera pas débité.
     */
    public function annulerEcheance(Request $request, Echeance $echeance)
    {
        $user = Auth::user();
        if ($echeance->user_id !== $user->id) {
            abort(403, 'Cette échéance ne vous appartient pas.');
        }
        if (!$echeance->estAnnulable()) {
            return back()->with('error', 'Seules les échéances à venir peuvent être annulées.');
        }
        $echeance->update(['statut' => Echeance::STATUT_ANNULE]);
        return back()->with('success', 'Échéance annulée. Vous ne serez pas débité pour cette période.');
    }
}
