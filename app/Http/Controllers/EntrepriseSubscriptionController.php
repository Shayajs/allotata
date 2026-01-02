<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\EntrepriseSubscription;
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

        // Retourner la vue d'abonnement
        return view('entreprise.dashboard.tabs.abonnements', [
            'entreprise' => $entreprise,
        ]);
    }

    /**
     * Créer une session de checkout Stripe pour un abonnement d'entreprise
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

        $type = $request->input('type'); // 'site_web' ou 'multi_personnes'
        
        if (!in_array($type, ['site_web', 'multi_personnes'])) {
            return back()->withErrors(['error' => 'Type d\'abonnement invalide.']);
        }

        // Vérifier si l'entreprise a déjà un abonnement actif de ce type
        $abonnementExistant = $entreprise->abonnements()
            ->where('type', $type)
            ->first();

        if ($abonnementExistant && $abonnementExistant->estActif()) {
            return back()->withErrors([
                'error' => 'Cette entreprise a déjà un abonnement actif de ce type.'
            ]);
        }

        // Vérifier s'il y a un prix personnalisé pour cette entreprise
        $customPrice = \App\Models\CustomPrice::getForEntreprise($entreprise, $type);
        
        // Récupérer l'ID du prix Stripe (personnalisé ou par défaut)
        $priceId = null;
        $priceLabel = '';
        if ($type === 'site_web') {
            $priceLabel = 'Site Web Vitrine';
            $priceId = $customPrice ? $customPrice->stripe_price_id : config('services.stripe.price_id_site_web'); // 2€/mois
        } elseif ($type === 'multi_personnes') {
            $priceLabel = 'Gestion Multi-Personnes';
            $priceId = $customPrice ? $customPrice->stripe_price_id : config('services.stripe.price_id_multi_personnes'); // 20€/mois
        }

        if (empty($priceId)) {
            Log::error('Prix Stripe non configuré pour l\'abonnement d\'entreprise', [
                'type' => $type,
                'entreprise_id' => $entreprise->id,
            ]);
            return back()->withErrors([
                'error' => "Le prix Stripe pour \"{$priceLabel}\" n'est pas encore configuré. Veuillez contacter l'administrateur pour créer ce prix depuis la page de gestion des prix Stripe."
            ]);
        }

        // Utiliser le compte Stripe du propriétaire de l'entreprise
        // Créer l'abonnement via le user (propriétaire)
        // Cashier créera automatiquement le compte Stripe si nécessaire
        $subscriptionName = 'entreprise_' . $type . '_' . $entreprise->id;
        
        $metadata = [
            'entreprise_id' => $entreprise->id,
            'type' => $type,
            'name' => $subscriptionName, // Important : Cashier utilise metadata['name'] pour déterminer le type d'abonnement
        ];

        return $user->newSubscription($subscriptionName, $priceId)
            ->checkout([
                'success_url' => route('entreprise.subscriptions.success', ['slug' => $entreprise->slug, 'type' => $type]),
                'cancel_url' => route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'abonnements']),
                'metadata' => $metadata,
            ]);
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

        // VÉRIFICATION DIRECTE SUR STRIPE (méthode de sécurité)
        Log::info('Vérification directe Stripe après checkout entreprise', [
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'type' => $type,
            'stripe_id' => $user->stripe_id,
        ]);

        // Attendre un peu pour que Stripe traite le paiement
        sleep(2);

        // Synchroniser TOUS les abonnements depuis Stripe (utilisateur + entreprises)
        $syncResult = \App\Services\StripeSubscriptionSyncService::syncAllUserSubscriptions($user);
        
        Log::info('Résultat synchronisation Stripe entreprise', [
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'type' => $type,
            'sync_result' => $syncResult,
        ]);

        // Recharger l'entreprise pour avoir les dernières données
        $entreprise->refresh();

        // Vérifier si l'abonnement existe maintenant
        $subscription = EntrepriseSubscription::where('entreprise_id', $entreprise->id)
            ->where('type', $type)
            ->where('est_manuel', false)
            ->first();

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
}
