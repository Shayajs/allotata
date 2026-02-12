<?php

namespace App\Services;

use App\Models\User;
use App\Models\Entreprise;
use App\Models\EntrepriseSubscription;
use Laravel\Cashier\Subscription;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Subscription as StripeSubscription;
use Stripe\Customer;

class StripeSubscriptionSyncService
{
    /**
     * Synchronise tous les abonnements d'un utilisateur depuis Stripe
     * 
     * @param User $user
     * @return array ['synced' => bool, 'subscriptions' => array]
     */
    public static function syncUserSubscriptions(User $user): array
    {
        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            // Vérifier si l'utilisateur a un customer_id Stripe
            if (!$user->stripe_id) {
                Log::info('Utilisateur sans stripe_id, pas de synchronisation possible', [
                    'user_id' => $user->id,
                ]);
                return ['synced' => false, 'subscriptions' => []];
            }

            // Récupérer le customer depuis Stripe
            try {
                $stripeCustomer = Customer::retrieve($user->stripe_id);
            } catch (\Exception $e) {
                // Si l'utilisateur n'existe plus chez Stripe, on force un nettoyage local ou on log juste
                // Ici on log en warning et on arrête car sans customer valide on ne peut rien sync
                Log::warning('Impossible de récupérer le customer Stripe', [
                    'user_id' => $user->id,
                    'stripe_id' => $user->stripe_id,
                    'error' => $e->getMessage(),
                ]);
                
                // Si l'erreur est "No such customer", on pourrait vouloir nullifier le stripe_id du user ?
                // Pour l'instant on se contente de ne pas crasher.
                return ['synced' => false, 'subscriptions' => []];
            }

            // Récupérer tous les abonnements actifs depuis Stripe
            $stripeSubscriptions = StripeSubscription::all([
                'customer' => $user->stripe_id,
                'status' => 'all', // Récupérer tous les statuts pour avoir une vue complète
                'limit' => 100,
            ]);

            $syncedSubscriptions = [];

            foreach ($stripeSubscriptions->data as $stripeSubscription) {
                // Vérifier si c'est un abonnement de type 'default' (utilisateur)
                // On vérifie le premier item pour déterminer le type
                $priceId = $stripeSubscription->items->data[0]->price->id ?? null;
                
                if (!$priceId) {
                    continue;
                }

                // Déterminer si c'est un abonnement utilisateur (default) ou entreprise
                // Les abonnements utilisateurs ont généralement le type 'default'
                
                // FILTRE : Si c'est un abonnement entreprise, ON L'IGNORE dans la sync utilisateur
                $isEntrepriseSubscription = false;

                // 1. Vérification par metadata (le plus fiable)
                if (isset($stripeSubscription->metadata['type']) && in_array($stripeSubscription->metadata['type'], ['site_web', 'multi_personnes'])) {
                    $isEntrepriseSubscription = true;
                }
                // 2. Vérification par metadata 'entreprise_id'
                elseif (isset($stripeSubscription->metadata['entreprise_id'])) {
                    $isEntrepriseSubscription = true;
                }
                // 3. Vérification par Price ID
                elseif (
                    in_array($priceId, config('services.stripe.allowed_prices.site_web', [config('services.stripe.price_id_site_web')])) || 
                    in_array($priceId, config('services.stripe.allowed_prices.multi_personnes', [config('services.stripe.price_id_multi_personnes')]))
                ) {
                    $isEntrepriseSubscription = true;
                }

                // Si c'est un abonnement entreprise, on passe au suivant (il sera traité par syncEntrepriseSubscriptions)
                if ($isEntrepriseSubscription) {
                    continue;
                }

                // Créer ou mettre à jour l'abonnement dans la base de données
                $subscription = Subscription::updateOrCreate(
                    [
                        'stripe_id' => $stripeSubscription->id,
                    ],
                    [
                        'user_id' => $user->id,
                        'name' => 'default',
                        'type' => 'default',
                        'stripe_status' => $stripeSubscription->status,
                        'stripe_price' => $priceId,
                        'quantity' => $stripeSubscription->items->data[0]->quantity ?? 1,
                        'trial_ends_at' => $stripeSubscription->trial_end ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->trial_end) : null,
                        'ends_at' => $stripeSubscription->cancel_at ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->cancel_at) : 
                                    ($stripeSubscription->cancel_at_period_end && $stripeSubscription->current_period_end ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end) : null),
                    ]
                );

                $syncedSubscriptions[] = [
                    'type' => 'user',
                    'subscription_id' => $subscription->id,
                    'stripe_id' => $stripeSubscription->id,
                    'status' => $stripeSubscription->status,
                ];

                Log::info('Abonnement utilisateur synchronisé depuis Stripe', [
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'stripe_subscription_id' => $stripeSubscription->id,
                    'status' => $stripeSubscription->status,
                ]);
            }

            return ['synced' => true, 'subscriptions' => $syncedSubscriptions];

        } catch (\Exception $e) {
            Log::error('Erreur lors de la synchronisation des abonnements utilisateur', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ['synced' => false, 'subscriptions' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Synchronise tous les abonnements d'une entreprise depuis Stripe
     * 
     * @param Entreprise $entreprise
     * @return array ['synced' => bool, 'subscriptions' => array]
     */
    public static function syncEntrepriseSubscriptions(Entreprise $entreprise): array
    {
        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            // Récupérer l'utilisateur propriétaire de l'entreprise
            $user = $entreprise->user;
            
            if (!$user || !$user->stripe_id) {
                Log::info('Entreprise sans utilisateur/stripe_id, pas de synchronisation possible', [
                    'entreprise_id' => $entreprise->id,
                ]);
                return ['synced' => false, 'subscriptions' => []];
            }

            // Récupérer tous les abonnements depuis Stripe
            $stripeSubscriptions = StripeSubscription::all([
                'customer' => $user->stripe_id,
                'status' => 'all',
                'limit' => 100,
            ]);

            $syncedSubscriptions = [];

            foreach ($stripeSubscriptions->data as $stripeSubscription) {
                $priceId = $stripeSubscription->items->data[0]->price->id ?? null;
                
                if (!$priceId) {
                    continue;
                }

                // Vérifier si c'est un abonnement d'entreprise en regardant les metadata ou le nom
                // Les abonnements d'entreprise ont généralement des metadata avec 'type' = 'site_web' ou 'multi_personnes'
                $subscriptionType = null;
                $subscriptionName = null;

                // DÉTECTION DU TYPE (Robuste)
                // On cherche 'site_web' ou 'multi_personnes' dans les clés 'type' ou 'name' des métadonnées
                // car parfois elles peuvent contenir des préfixes (ex: entreprise_site_web_4) qui bloquent la comparaison stricte
                
                $metaType = $stripeSubscription->metadata['type'] ?? null;
                $metaName = $stripeSubscription->metadata['name'] ?? null;
                
                if ($metaType && (str_contains($metaType, 'site_web') || $metaType === 'site_web')) {
                    $subscriptionType = 'site_web';
                } elseif ($metaType && (str_contains($metaType, 'multi_personnes') || $metaType === 'multi_personnes')) {
                    $subscriptionType = 'multi_personnes';
                } elseif ($metaName && (str_contains($metaName, 'site_web') || str_contains($metaName, 'Site Web'))) {
                     $subscriptionType = 'site_web';
                } elseif ($metaName && (str_contains($metaName, 'multi_personnes') || str_contains($metaName, 'Multi-Personnes'))) {
                     $subscriptionType = 'multi_personnes';
                }

                // Si on n'a pas trouvé le type, essayer de le déduire du price_id
                if (!$subscriptionType) {
                    // Vérifier si le price_id correspond à un prix d'entreprise (support Multi-Prix / Grandfathering)
                    // On récupère les listes de prix acceptés depuis la config
                    
                    $allowedSiteWeb = config('services.stripe.allowed_prices.site_web', [config('services.stripe.price_id_site_web')]);
                    $allowedMulti = config('services.stripe.allowed_prices.multi_personnes', [config('services.stripe.price_id_multi_personnes')]);
                    
                    // Sécurité : s'assurer que c'est des tableaux
                    if (!is_array($allowedSiteWeb)) $allowedSiteWeb = [$allowedSiteWeb];
                    if (!is_array($allowedMulti)) $allowedMulti = [$allowedMulti];

                    if (in_array($priceId, $allowedSiteWeb)) {
                        $subscriptionType = 'site_web';
                    } elseif (in_array($priceId, $allowedMulti)) {
                        $subscriptionType = 'multi_personnes';
                    }
                }

                // Si on n'a toujours pas le type, on ne synchronise pas cet abonnement
                if (!$subscriptionType || !in_array($subscriptionType, ['site_web', 'multi_personnes'])) {
                    continue;
                }

                // VÉRIFICATION D'APPARTENANCE
                $isTargetEntreprise = false;
                $hasEntrepriseIdInfo = false;

                // 1. Vérification par DESCRIPTION (Priorité haute, méthode infaillible "User Request")
                // On cherche le tag [ENTREPRISE_ID:123]
                if (isset($stripeSubscription->description) && preg_match('/\[ENTREPRISE_ID:(\d+)\]/', $stripeSubscription->description, $matches)) {
                    $hasEntrepriseIdInfo = true;
                    if ((string)$matches[1] === (string)$entreprise->id) {
                        $isTargetEntreprise = true;
                    }
                }
                // 2. Vérification par METADATA (Si pas trouvé dans description)
                elseif (isset($stripeSubscription->metadata['entreprise_id'])) {
                    $hasEntrepriseIdInfo = true;
                    if ((string)$stripeSubscription->metadata['entreprise_id'] === (string)$entreprise->id) {
                        $isTargetEntreprise = true;
                    }
                }

                // LOGIQUE DE DÉCISION
                if ($hasEntrepriseIdInfo) {
                    // Si on a l'info explicite et que ce n'est pas la bonne entreprise, on ignore cet abonnement
                    if (!$isTargetEntreprise) {
                        continue;
                    }
                } else {
                    // Si aucune info explicite (anciens abonnements sans metadata ni description)
                    // On ne peut prendre le risque que si l'utilisateur n'a qu'une seule entreprise.
                    if ($user->entreprises()->count() > 1) {
                        // Risque trop élevé d'erreur d'attribution pour les comptes multi-entreprises
                        continue; 
                    }
                }

                // Créer ou mettre à jour l'abonnement dans la base de données
                $subscription = EntrepriseSubscription::updateOrCreate(
                    [
                        'stripe_id' => $stripeSubscription->id,
                    ],
                    [
                        'entreprise_id' => $entreprise->id,
                        'type' => $subscriptionType,
                        'name' => $subscriptionName ?? $subscriptionType,
                        'stripe_status' => $stripeSubscription->status,
                        'stripe_price' => $priceId,
                        'est_manuel' => false,
                        'trial_ends_at' => $stripeSubscription->trial_end ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->trial_end) : null,
                        'ends_at' => $stripeSubscription->cancel_at ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->cancel_at) : 
                                    ($stripeSubscription->cancel_at_period_end && $stripeSubscription->current_period_end ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end) : null),
                    ]
                );

                $syncedSubscriptions[] = [
                    'type' => 'entreprise',
                    'subscription_id' => $subscription->id,
                    'stripe_id' => $stripeSubscription->id,
                    'subscription_type' => $subscriptionType,
                    'status' => $stripeSubscription->status,
                ];

                Log::info('Abonnement entreprise synchronisé depuis Stripe', [
                    'entreprise_id' => $entreprise->id,
                    'subscription_id' => $subscription->id,
                    'stripe_subscription_id' => $stripeSubscription->id,
                    'type' => $subscriptionType,
                    'status' => $stripeSubscription->status,
                ]);
            }

            return ['synced' => true, 'subscriptions' => $syncedSubscriptions];

        } catch (\Exception $e) {
            Log::error('Erreur lors de la synchronisation des abonnements entreprise', [
                'entreprise_id' => $entreprise->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ['synced' => false, 'subscriptions' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Synchronise tous les abonnements d'un utilisateur (utilisateur + entreprises)
     * 
     * @param User $user
     * @return array
     */
    public static function syncAllUserSubscriptions(User $user): array
    {
        $results = [
            'user_subscriptions' => [],
            'entreprise_subscriptions' => [],
        ];

        // Synchroniser les abonnements utilisateur
        $userResult = self::syncUserSubscriptions($user);
        $results['user_subscriptions'] = $userResult;

        // Synchroniser les abonnements de toutes les entreprises de l'utilisateur
        foreach ($user->entreprises as $entreprise) {
            $entrepriseResult = self::syncEntrepriseSubscriptions($entreprise);
            $results['entreprise_subscriptions'][$entreprise->id] = $entrepriseResult;
        }

        return $results;
    }

    /**
     * Vérifie et synchronise un abonnement spécifique par son ID Stripe
     * 
     * @param string $stripeSubscriptionId
     * @return array
     */
    public static function syncSubscriptionByStripeId(string $stripeSubscriptionId): array
    {
        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            try {
                $stripeSubscription = StripeSubscription::retrieve($stripeSubscriptionId);
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                // Gestion spécifique pour "No such subscription"
                // Cela arrive si l'abonnement a été supprimé definitivement chez Stripe mais existe encore en local
                if (str_contains($e->getMessage(), 'No such subscription')) {
                    Log::warning('Abonnement introuvable chez Stripe (Orphelin détecté)', [
                        'stripe_subscription_id' => $stripeSubscriptionId,
                    ]);

                    // On marque l'abonnement local comme "orphelin" si on le trouve
                    // Recherche dans User Subscriptions
                    $localUserSub = Subscription::where('stripe_id', $stripeSubscriptionId)->first();
                    if ($localUserSub) {
                        $localUserSub->update(['stripe_status' => 'error_orphan']);
                    }

                    // Recherche dans Entreprise Subscriptions
                    $localEntSub = EntrepriseSubscription::where('stripe_id', $stripeSubscriptionId)->first();
                    if ($localEntSub) {
                        $localEntSub->update(['stripe_status' => 'error_orphan']);
                    }

                    return ['synced' => false, 'error' => 'not_found_on_stripe'];
                }

                throw $e; // Relancer les autres erreurs
            }

            $customerId = $stripeSubscription->customer;

            // Trouver l'utilisateur par customer_id
            $user = User::where('stripe_id', $customerId)->first();

            if (!$user) {
                return ['synced' => false, 'error' => 'Utilisateur non trouvé pour ce customer_id'];
            }

            // Synchroniser tous les abonnements de cet utilisateur
            return self::syncAllUserSubscriptions($user);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la synchronisation d\'un abonnement spécifique', [
                'stripe_subscription_id' => $stripeSubscriptionId,
                'error' => $e->getMessage(),
            ]);
            return ['synced' => false, 'error' => $e->getMessage()];
        }
    }
}
