<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\EntrepriseSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class EntrepriseSubscriptionController extends Controller
{
    /**
     * Afficher les options d'abonnement pour une entreprise
     */
    public function index(Entreprise $entreprise)
    {
        $user = Auth::user();
        
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
     * Créer une session de checkout Stripe pour un abonnement d'entreprise
     */
    public function checkout(Request $request, Entreprise $entreprise)
    {
        $user = Auth::user();
        
        // Vérifier que l'utilisateur peut gérer cette entreprise
        if (!$entreprise->peutEtreGereePar($user)) {
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

        // Récupérer l'ID du prix Stripe depuis la configuration
        $priceId = null;
        if ($type === 'site_web') {
            $priceId = config('services.stripe.price_id_site_web'); // 2€/mois
        } elseif ($type === 'multi_personnes') {
            $priceId = config('services.stripe.price_id_multi_personnes'); // 20€/mois
        }

        if (empty($priceId)) {
            return back()->withErrors([
                'error' => 'La configuration Stripe est incomplète. Veuillez contacter l\'administrateur.'
            ]);
        }

        // Utiliser le compte Stripe du propriétaire de l'entreprise
        // Créer l'abonnement via le user (propriétaire)
        $metadata = [
            'entreprise_id' => $entreprise->id,
            'type' => $type,
        ];

        return $user->newSubscription('entreprise_' . $type . '_' . $entreprise->id, $priceId)
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

        // Attendre un peu pour que le webhook Stripe soit traité
        sleep(2);

        // Récupérer l'abonnement Stripe du user
        $subscriptionName = 'entreprise_' . $type . '_' . $entreprise->id;
        $subscription = $user->subscription($subscriptionName);

        if ($subscription && $subscription->valid()) {
            // Créer ou mettre à jour l'abonnement dans la table entreprise_subscriptions
            EntrepriseSubscription::updateOrCreate(
                [
                    'entreprise_id' => $entreprise->id,
                    'type' => $type,
                ],
                [
                    'name' => $subscriptionName,
                    'stripe_id' => $subscription->stripe_id,
                    'stripe_status' => $subscription->stripe_status,
                    'stripe_price' => $subscription->stripe_price,
                    'est_manuel' => false,
                    'trial_ends_at' => $subscription->trial_ends_at,
                    'ends_at' => $subscription->ends_at,
                ]
            );

            return redirect()->route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'abonnements'])
                ->with('success', 'Votre abonnement a été activé avec succès !');
        }

        return redirect()->route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'abonnements'])
            ->with('info', 'Votre paiement est en cours de traitement. L\'abonnement sera activé sous peu.');
    }

    /**
     * Annuler un abonnement d'entreprise
     */
    public function cancel(Request $request, Entreprise $entreprise, $type)
    {
        $user = Auth::user();
        
        if (!$entreprise->peutEtreGereePar($user)) {
            return back()->withErrors(['error' => 'Accès refusé.']);
        }

        $abonnement = $entreprise->abonnements()->where('type', $type)->first();

        if (!$abonnement) {
            return back()->withErrors(['error' => 'Abonnement introuvable.']);
        }

        // Si c'est un abonnement Stripe, l'annuler via Stripe
        if ($abonnement->stripe_id && !$abonnement->est_manuel) {
            $subscriptionName = 'entreprise_' . $type . '_' . $entreprise->id;
            $subscription = $user->subscription($subscriptionName);
            
            if ($subscription) {
                $subscription->cancel();
            }
        }

        // Marquer l'abonnement comme terminé
        $abonnement->update([
            'ends_at' => now(),
        ]);

        return redirect()->route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'abonnements'])
            ->with('success', 'Votre abonnement a été annulé. Il restera actif jusqu\'à la fin de la période payée.');
    }
}
