<?php

namespace App\Services;

use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    /**
     * Vérifie le statut de l'abonnement avec une priorité stricte :
     * 1. MANUEL (Source de vérité admin)
     * 2. STRIPE (Source de vérité client/paiement)
     * 3. FALLBACK (Sécurité)
     * 
     * @param Entreprise|User $entity L'entité à vérifier
     * @param string|null $type Type d'abonnement (pour les entreprises: 'site_web' ou 'multi_personnes')
     * @return bool
     */
    public static function checkSubscriptionStatus($entity, $type = null): bool
    {
        try {
            // --- ÉTAPE 1 : VÉRIFICATION MANUELLE (PRIORITÉ ABSOLUE) ---
            // Permet à l'admin d'outrepasser ou d'offrir l'accès
            
            if ($entity instanceof User) {
                if ($entity->abonnement_manuel && $entity->abonnement_manuel_actif_jusqu) {
                    if ($entity->abonnement_manuel_actif_jusqu->isFuture() || $entity->abonnement_manuel_actif_jusqu->isToday()) {
                        return true;
                    }
                }
            } elseif ($entity instanceof Entreprise) {
                // Pour une entreprise, on doit chercher l'abonnement spécifique de ce type
                // On cherche d'abord s'il existe une entrée "manuel" explicitement dans la table abonnements
                // Note : Le modèle actuel stocke tout dans EntrepriseSubscription, avec un flag 'est_manuel'
                
                $subscription = $entity->abonnements()->where('type', $type)->first();
                
                if ($subscription && $subscription->est_manuel) {
                    if ($subscription->actif_jusqu && ($subscription->actif_jusqu->isFuture() || $subscription->actif_jusqu->isToday())) {
                        return true;
                    }
                    // Si manuel mais expiré, on ne passe PAS à Stripe. 
                    // Le manuel écrase tout. S'il est défini comme manuel, c'est que l'admin a pris la main.
                    // Cependant, pour la migration, si on veut que le manuel soit juste un "plus", on pourrait continuer.
                    // Mais la demande est "Manual > Stripe". Donc si mode manuel activé mais fini, c'est fini.
                    // Sauf si "est_manuel" est juste un flag sur une ligne.
                    
                    // RAFFINEMENT : Si on a un enregistrement "Manuel" expiré, est-ce qu'on doit vérifier Stripe ?
                    // Si l'admin a mis "Manuel" c'est pour sortir de Stripe.
                    // Donc si manuel expiré -> False.
                    return false; 
                }
            }


            // --- ÉTAPE 2 : VÉRIFICATION STRIPE (si pas manuel) ---
            
            if ($entity instanceof User) {
                // Cashier standard check
                if ($entity->subscribed('default')) {
                    return true;
                }
            } elseif ($entity instanceof Entreprise) {
                // On réutilise $subscription s'il a été fetché plus haut, sinon on le cherche
                $subscription = $subscription ?? $entity->abonnements()->where('type', $type)->first();
                
                if ($subscription && !$subscription->est_manuel) {
                    // C'est un abonnement Stripe (ou vide/inconnu).
                    // On vérifie les champs Stripe.
                    // NOTE IMPORTANTE : On ne vérifie PAS le price_id ici de manière stricte pour le booléen final.
                    // On fait confiance au fait que 'stripe_status' est 'active' ou 'trialing'.
                    // C'est ça le "Grandfathering" implicite : si c'est active chez Stripe, c'est active chez nous.
                    
                    if ($subscription->stripe_id && $subscription->stripe_status) {
                        // Statuts valides
                        if (in_array($subscription->stripe_status, ['active', 'trialing'])) {
                            return true;
                        }
                        
                        // Gestion Annulation (Grace Period)
                        // Si annulé mais date de fin dans le futur
                        if ($subscription->stripe_status === 'active' || ($subscription->ends_at && $subscription->ends_at->isFuture())) {
                             // Double check pour être sûr (cas où status n'est pas updated mais ends_at oui)
                             // Mais généralement status reste 'active' jusqu'à la fin.
                             // Le code existant faisait:
                             if ($subscription->stripe_status === 'active') return true;
                             if ($subscription->ends_at && $subscription->ends_at->isFuture()) return true;
                        }
                    }
                }
            }

            // --- ÉTAPE 3 : VÉRIFICATION ESSAIS GRATUITS ---
            // Si pas d'abonnement payant (Manuel ou Stripe), on vérifie les essais gratuits internes système.
            
            if ($entity instanceof User) {
                if (method_exists($entity, 'aAccesViaEssai') && $entity->aAccesViaEssai('premium')) {
                    return true;
                }
            } elseif ($entity instanceof Entreprise) {
                if ($type && method_exists($entity, 'aAccesViaEssai') && $entity->aAccesViaEssai($type)) {
                    return true;
                }
            }

            // --- ÉTAPE 4 : FALLBACK (SÉCURITÉ) ---
            // Si rien n'a matché, c'est non.
            return false;

        } catch (\Exception $e) {
            // Anti-Fuite : En cas d'erreur code/API, on refuse l'accès plutôt que de l'ouvrir.
            Log::error("Erreur critique vérification abonnement : " . $e->getMessage(), [
                'entity_id' => $entity->id,
                'entity_type' => get_class($entity),
                'subscription_type' => $type
            ]);
            return false;
        }
    }
}
