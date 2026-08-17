<?php

namespace App\Http\Controllers;

use App\Models\Echeance;
use App\Models\Entreprise;
use App\Models\EntrepriseSubscription;
use App\Services\CalculMontantDuService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\BillingPortal\Session as BillingPortalSession;

class EntrepriseSubscriptionController extends Controller
{
    /**
     * Afficher les options d'abonnement pour une entreprise
     */
    public function index($slug)
    {
        $user = Auth::user();
        
        // Récupérer l'entreprise par slug explicitement
        $entreprise = Entreprise::where('slug', $slug)->first();
        
        if (!$entreprise) {
            return redirect()->route('dashboard')
                ->with('error', 'Entreprise non trouvée.');
        }
        
        // Vérifier que l'utilisateur peut gérer cette entreprise
        if (!$entreprise->peutEtreGereePar($user)) {
            return redirect()->route('dashboard')
                ->with('error', 'Vous n\'avez pas accès à cette entreprise.');
        }

        // Rediriger vers le dashboard avec l'onglet abonnements
        return redirect()->route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'abonnements'])
            ->with('info', 'Gérez vos abonnements depuis cet onglet.');
    }

    /**
     * Retourner le contenu de la vue d'abonnement pour une modale
     */
    public function modal($slug)
    {
        $user = Auth::user();
        
        // Vérifier que l'utilisateur est authentifié
        if (!$user) {
            if (request()->expectsJson() || request()->wantsJson()) {
                return response()->json(['error' => 'Non authentifié.'], 401);
            }
            abort(401, 'Non authentifié.');
        }
        
        // Récupérer l'entreprise par slug
        $entreprise = Entreprise::where('slug', $slug)->first();
        
        if (!$entreprise) {
            if (request()->expectsJson() || request()->wantsJson()) {
                return response()->json(['error' => 'Entreprise non trouvée.'], 404);
            }
            abort(404, 'Entreprise non trouvée.');
        }
        
        // Vérifier que l'utilisateur peut gérer cette entreprise
        // Vérifier d'abord si l'utilisateur est propriétaire (comparaison avec conversion de type)
        $estProprietaire = (int)$entreprise->user_id === (int)$user->id;
        
        // Sinon, vérifier avec la méthode peutEtreGereePar (pour les administrateurs membres)
        $peutGerer = $estProprietaire || $entreprise->peutEtreGereePar($user);
        
        // Ou si l'utilisateur est admin global
        $estAdmin = $user->is_admin ?? false;
        
        if (!$peutGerer && !$estAdmin) {
            Log::warning('Tentative d\'accès non autorisée à la modale d\'abonnement', [
                'user_id' => $user->id,
                'user_id_type' => gettype($user->id),
                'entreprise_id' => $entreprise->id,
                'entreprise_user_id' => $entreprise->user_id,
                'entreprise_user_id_type' => gettype($entreprise->user_id),
                'entreprise_slug' => $slug,
                'est_proprietaire' => $estProprietaire,
                'peut_gerer' => $entreprise->peutEtreGereePar($user),
                'est_admin' => $estAdmin,
            ]);
            if (request()->expectsJson() || request()->wantsJson()) {
                return response()->json(['error' => 'Vous n\'avez pas accès à cette entreprise.'], 403);
            }
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        // ===== Données pour l'onglet Abonnements (Prix depuis Tarifs + CustomPrice) =====
        $subscriptionPrices = [
            'site_web' => \App\Models\Tarif::displayForEntreprise($entreprise, 'site_web'),
            'multi_personnes' => \App\Models\Tarif::displayForEntreprise($entreprise, 'multi_personnes'),
        ];

        // Retourner la vue d'abonnement
        return view('entreprise.dashboard.tabs.abonnements', [
            'entreprise' => $entreprise,
            'subscriptionPrices' => $subscriptionPrices,
        ]);
    }

    /**
     * Créer une échéance pour l'option entreprise puis rediriger vers l'Espace Paiement.
     * Plus de Cashier ni price IDs Stripe : Tarifs + paiement ponctuel.
     */
    public function checkout(Request $request, $slug)
    {
        $user = Auth::user();
        
        // Récupérer l'entreprise par slug explicitement
        $entreprise = Entreprise::where('slug', $slug)->first();
        
        if (!$entreprise) {
            Log::error('Entreprise non trouvée pour le checkout', [
                'slug' => $slug,
                'user_id' => $user->id,
            ]);
            return back()->withErrors(['error' => 'Entreprise non trouvée.']);
        }
        
        // Log pour déboguer
        Log::info('Vérification accès checkout abonnement entreprise', [
            'user_id' => $user->id,
            'user_id_type' => gettype($user->id),
            'entreprise_id' => $entreprise->id,
            'entreprise_user_id' => $entreprise->user_id,
            'entreprise_user_id_type' => gettype($entreprise->user_id),
            'slug' => $slug,
            'est_proprietaire' => (int)$entreprise->user_id === (int)$user->id,
            'peut_gerer' => $entreprise->peutEtreGereePar($user),
        ]);
        
        // Vérifier que l'utilisateur peut gérer cette entreprise
        if (!$entreprise->peutEtreGereePar($user)) {
            Log::warning('Tentative d\'accès non autorisée au checkout d\'abonnement d\'entreprise', [
                'user_id' => $user->id,
                'user_id_type' => gettype($user->id),
                'entreprise_id' => $entreprise->id,
                'entreprise_user_id' => $entreprise->user_id,
                'entreprise_user_id_type' => gettype($entreprise->user_id),
                'slug' => $slug,
                'est_proprietaire' => (int)$entreprise->user_id === (int)$user->id,
                'peut_gerer' => $entreprise->peutEtreGereePar($user),
            ]);
            return back()->withErrors(['error' => 'Vous n\'avez pas accès à cette entreprise.']);
        }

        // ⚠️ VÉRIFICATION CRITIQUE : L'utilisateur doit avoir un abonnement Premium actif
        // Les abonnements entreprise (site_web, multi_personnes) sont des add-ons au Premium
        if (!$user->aAbonnementActif()) {
            Log::warning('Tentative d\'achat d\'abonnement entreprise sans abonnement Premium', [
                'user_id' => $user->id,
                'entreprise_id' => $entreprise->id,
                'type_demande' => $request->input('type'),
            ]);
            return back()->withErrors([
                'error' => 'Vous devez d\'abord avoir un abonnement Premium actif pour souscrire aux options d\'entreprise. Rendez-vous sur la page d\'abonnement pour vous abonner.'
            ]);
        }

        $type = $request->input('type'); // 'site_web' ou 'multi_personnes'
        
        if (!in_array($type, ['site_web', 'multi_personnes'])) {
            return back()->withErrors(['error' => 'Type d\'abonnement invalide.']);
        }

        if (\App\Support\CapacitorClient::detect($request)) {
            $productId = config('play.products.'.$type.'.id');
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'provider' => 'play',
                    'product_id' => $productId,
                    'entreprise_id' => $entreprise->id,
                ]);
            }

            return back()->with('play_billing_product', $productId);
        }

        $abonnementExistant = $entreprise->abonnements()->where('type', $type)->first();
        if ($abonnementExistant && $abonnementExistant->estActif()) {
            return back()->withErrors(['error' => 'Cette entreprise a déjà un abonnement actif de ce type.']);
        }

        // Créer une échéance pour ce mois (Tarifs + CustomPrice), puis rediriger vers /checkout.
        $owner = $entreprise->user;
        $debut = Carbon::now()->startOfMonth()->startOfDay();
        $fin = Carbon::now()->endOfMonth()->startOfDay();
        $jour = $owner->jour_facturation ?? 1;

        $tmp = new Echeance([
            'user_id' => $owner->id,
            'entreprise_id' => $entreprise->id,
            'subscription_type' => $type,
            'periode_debut' => $debut,
            'periode_fin' => $fin,
            'jour_facturation' => $jour,
            'reduction_manuel' => 0,
        ]);
        $tmp->setRelation('user', $owner);
        $calc = CalculMontantDuService::calculerPourEcheance($tmp, null, true);
        if ($calc['montant_du'] <= 0) {
            return back()->withErrors(['error' => 'Aucun montant à régler pour cette option.']);
        }

        // ── Stateless checkout : session-based si l'utilisateur est le propriétaire ──
        // Aucune écriture en BDD avant le paiement → « intention ≠ dette ».
        // Si l'utilisateur revient en arrière, rien ne reste dans la base.
        if ((int) $user->id === (int) $owner->id) {
            $pendingKey = "{$type}_{$entreprise->id}";
            $pending = session('checkout_pending', []);
            $pending[$pendingKey] = [
                'entreprise_id'    => $entreprise->id,
                'entreprise_nom'   => $entreprise->nom,
                'subscription_type' => $type,
                'user_id'          => $owner->id,
                'periode_debut'    => $debut->toDateString(),
                'periode_fin'      => $fin->toDateString(),
                'jour_facturation' => $jour,
                'montant_du'       => $calc['montant_du'],
                'montant_final'    => $calc['montant_final'],
                'reduction_promo'  => $calc['reduction_promo'],
                'promo_code_id'    => $calc['promo_code_id'],
                'lignes'           => $calc['lignes'],
                'created_at'       => now()->toIso8601String(),
            ];
            session(['checkout_pending' => $pending]);

            return redirect()->route('checkout.index')
                ->with('info', 'Réglez l\'échéance ci-dessous pour activer l\'option.');
        }

        // ── Gestionnaire tiers → écrire en DB (a_payer) pour que le propriétaire voie l'échéance ──
        Echeance::updateOrCreate(
            [
                'user_id' => $owner->id,
                'entreprise_id' => $entreprise->id,
                'subscription_type' => $type,
                'periode_debut' => $debut,
                'periode_fin' => $fin,
            ],
            [
                'jour_facturation' => $jour,
                'montant_du' => $calc['montant_du'],
                'montant_final' => $calc['montant_final'],
                'reduction_promo' => $calc['reduction_promo'],
                'promo_code_id' => $calc['promo_code_id'],
                'statut' => Echeance::STATUT_A_PAYER,
                'metadata' => ['lignes' => $calc['lignes']],
            ]
        );

        return redirect()->route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'abonnements'])
            ->with('info', 'Échéance créée. Le propriétaire doit régler le paiement depuis l\'Espace Paiement.');
    }

    /**
     * Page de succès après paiement
     */
    public function success(Request $request, $slug, $type)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        
        if (!$entreprise->peutEtreGereePar($user)) {
            return redirect()->route('dashboard')
                ->with('error', 'Accès refusé.');
        }

        // VÉRIFICATION 1 : DIRECTE SUR STRIPE (SYNC GLOBAL)
        Log::info('Vérification Stripe entreprise (Global Sync)', [
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'type' => $type,
        ]);

        // Attendre un peu pour que Stripe traite le paiement
        sleep(2);

        // Synchroniser TOUS les abonnements depuis Stripe (utilisateur + entreprises)
        $syncResult = \App\Services\StripeSubscriptionSyncService::syncAllUserSubscriptions($user);
        
        // Recharger l'entreprise pour avoir les dernières données
        $entreprise->refresh();

        // Vérifier si l'abonnement existe maintenant via le sync global
        $subscription = EntrepriseSubscription::where('entreprise_id', $entreprise->id)
            ->where('type', $type)
            ->where('est_manuel', false)
            ->first();

        // VÉRIFICATION 2 : CHECKOUT SESSION (FALLBACK ROBUSTE)
        // Si le sync global a échoué (ex: délai API, pagination), on vérifie précisément cette session
        if ((!$subscription || !$subscription->estActif()) && $request->has('session_id')) {
            $sessionId = $request->get('session_id');
            Log::info('Vérification Stripe entreprise (Fallback Session)', ['session_id' => $sessionId]);

            try {
                \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                $session = \Stripe\Checkout\Session::retrieve($sessionId);
                
                if ($session && $session->subscription) {
                    $subscriptionId = $session->subscription;
                    
                    Log::info('Session trouvée, tentative de sync précis', ['subscription_id' => $subscriptionId]);
                    
                    // Sync précis de cet abonnement
                    \App\Services\StripeSubscriptionSyncService::syncSubscriptionByStripeId($subscriptionId);
                    
                    // Re-vérification après sync précis
                    $originalSubscription = \App\Models\EntrepriseSubscription::where('stripe_id', $subscriptionId)->first();
                    
                    // Si on ne le trouve pas via stripe_id, on re-cherche par type pour être sûr
                    if (!$subscription || !$subscription->estActif()) {
                        $subscription = EntrepriseSubscription::where('entreprise_id', $entreprise->id)
                            ->where('type', $type)
                            ->where('est_manuel', false)
                            ->first();
                    }
                }
            } catch (\Exception $e) {
                Log::error('Erreur lors du fallback Stripe Session', ['error' => $e->getMessage()]);
            }
        }

        if ($subscription && $subscription->estActif()) {
            return redirect()->route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'abonnements'])
                ->with('success', 'Votre abonnement a été activé avec succès !');
        }

        return redirect()->route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'abonnements'])
            ->with('info', 'Votre paiement est en cours de traitement. L\'abonnement sera activé sous peu.');
    }

    /**
     * Rediriger vers le portail client Stripe pour gérer l'abonnement d'entreprise
     * Le portail client Stripe permet de gérer l'annulation, la reprise, 
     * la mise à jour de la méthode de paiement, etc.
     */
    public function cancel(Request $request, $slug, $type)
    {
        $user = Auth::user();
        
        // Récupérer l'entreprise par slug explicitement
        $entreprise = Entreprise::where('slug', $slug)->first();
        
        if (!$entreprise) {
            return back()->withErrors(['error' => 'Entreprise non trouvée.']);
        }
        
        if (!$entreprise->peutEtreGereePar($user)) {
            return back()->withErrors(['error' => 'Accès refusé.']);
        }

        if (!$user->stripe_id) {
            return back()->withErrors(['error' => 'Aucun compte Stripe associé.']);
        }

        try {
            // Créer une session de portail client Stripe
            $session = BillingPortalSession::create([
                'customer' => $user->stripe_id,
                'return_url' => route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'abonnements']),
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
     * Annuler l'abonnement à la fin de la période (Grace Period)
     * Utilise la même logique que l'admin mais sans couper l'accès immédiatement
     */
    public function cancelSubscription(Request $request, $slug, $type)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        
        if (!$entreprise->peutEtreGereePar($user)) {
             return back()->withErrors(['error' => 'Accès refusé.']);
        }

        $entrepriseSubLocal = \App\Models\EntrepriseSubscription::where('entreprise_id', $entreprise->id)
            ->where('type', $type)
            ->first();

        if ($entrepriseSubLocal && $entrepriseSubLocal->stripe_id) {
            try {
                \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                $stripeSubscription = \Stripe\Subscription::retrieve($entrepriseSubLocal->stripe_id);
                
                // Annuler à la fin de la période (Non-violent)
                $stripeSubscription = \Stripe\Subscription::update($entrepriseSubLocal->stripe_id, [
                    'cancel_at_period_end' => true,
                ]);

                // Récupérer la date de fin (current_period_end est le plus fiable pour une fin de période)
                $timestamp = $stripeSubscription->current_period_end ?? $stripeSubscription->cancel_at;
                
                if (!$timestamp) {
                    $timestamp = time() + (30 * 24 * 60 * 60); // Fallback à +30 jours si vraiment rien n'est trouvé
                }
                
                $dateFin = \Carbon\Carbon::createFromTimestamp($timestamp);
                
                $entrepriseSubLocal->update([
                    'ends_at' => $dateFin,
                    'stripe_status' => $stripeSubscription->status,
                ]);

                return back()->with('success', "L'abonnement a été configuré pour s'arrêter à la fin de la période facturée (le " . $dateFin->format('d/m/Y') . ").");
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                 if (str_contains($e->getMessage(), 'No such subscription')) {
                     // L'abonnement n'existe plus chez Stripe, on force un status d'erreur local
                     $entrepriseSubLocal->update(['stripe_status' => 'error_orphan']);
                     return back()->with('error', "Impossible d'annuler : l'abonnement n'existe plus chez Stripe. Veuillez contacter le support pour nettoyer la base de données.");
                 }
                 return back()->with('error', "Erreur Stripe : " . $e->getMessage());
            } catch (\Exception $e) {
                return back()->with('error', "Erreur Stripe : " . $e->getMessage());
            }
        }

        return back()->with('error', "Aucun abonnement Stripe actif trouvé.");
    }

    /**
     * Réactiver un abonnement annulé (en Grace Period)
     */
    public function resumeSubscription(Request $request, $slug, $type)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        
        if (!$entreprise->peutEtreGereePar($user)) {
             return back()->withErrors(['error' => 'Accès refusé.']);
        }

        $entrepriseSubLocal = \App\Models\EntrepriseSubscription::where('entreprise_id', $entreprise->id)
            ->where('type', $type)
            ->first();

        if ($entrepriseSubLocal && $entrepriseSubLocal->stripe_id) {
            try {
                \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                $stripeSubscription = \Stripe\Subscription::retrieve($entrepriseSubLocal->stripe_id);
                
                // Retirer l'annulation programmée
                $stripeSubscription = \Stripe\Subscription::update($entrepriseSubLocal->stripe_id, [
                    'cancel_at_period_end' => false,
                ]);

                // Mettre à jour l'abonnement local
                $entrepriseSubLocal->update([
                    'ends_at' => null,
                    'stripe_status' => $stripeSubscription->status,
                ]);

                return back()->with('success', "L'abonnement a été réactivé avec succès. Le renouvellement automatique est rétabli.");
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                 if (str_contains($e->getMessage(), 'No such subscription')) {
                     $entrepriseSubLocal->update(['stripe_status' => 'error_orphan']);
                     return back()->with('error', "Impossible de reprendre : l'abonnement n'existe plus chez Stripe.");
                 }
                 return back()->with('error', "Erreur Stripe : " . $e->getMessage());
            } catch (\Exception $e) {
                return back()->with('error', "Erreur Stripe : " . $e->getMessage());
            }
        }

        return back()->with('error', "Impossible de réactiver cet abonnement.");
    }
}
