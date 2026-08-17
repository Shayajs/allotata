<?php

namespace App\Services;

use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    /**
     * Vérifie le statut de l'abonnement avec une priorité stricte :
     * 1. MANUEL actif (source de vérité admin)
     * 2. STRIPE / échéance / période d'essai provisionnée
     * 3. ESSAI GRATUIT interne
     *
     * Un manuel expiré ne bloque plus Stripe, les échéances ni les essais.
     *
     * @param  Entreprise|User  $entity
     * @param  string|null  $type  Pour les entreprises : 'site_web' ou 'multi_personnes'
     */
    public static function checkSubscriptionStatus($entity, $type = null): bool
    {
        try {
            if ($entity instanceof User) {
                return self::userHasAccess($entity);
            }

            if ($entity instanceof Entreprise) {
                return self::entrepriseHasAccess($entity, $type);
            }

            return false;
        } catch (\Throwable $e) {
            Log::error('Erreur critique vérification abonnement : '.$e->getMessage(), [
                'entity_id' => $entity->id ?? null,
                'entity_type' => is_object($entity) ? get_class($entity) : gettype($entity),
                'subscription_type' => $type,
            ]);

            return false;
        }
    }

    private static function userHasAccess(User $user): bool
    {
        if ($user->hasActiveManualPremium()) {
            return true;
        }

        if (method_exists($user, 'hasActivePlayPremium') && $user->hasActivePlayPremium()) {
            return true;
        }

        if (PremiumAccessService::hasPremiumUntil($user)) {
            return true;
        }

        if (PremiumAccessService::hasLegacyCashierBilling($user)) {
            return true;
        }

        if (method_exists($user, 'onGenericTrial') && $user->onGenericTrial()) {
            return true;
        }

        return method_exists($user, 'aAccesViaEssai') && $user->aAccesViaEssai('premium');
    }

    private static function entrepriseHasAccess(Entreprise $entreprise, ?string $type): bool
    {
        $subscription = $entreprise->abonnements()
            ->where('type', $type)
            ->orderByDesc('est_manuel')
            ->orderByDesc('updated_at')
            ->first();

        if ($subscription && $subscription->estActif()) {
            return true;
        }

        return $type
            && method_exists($entreprise, 'aAccesViaEssai')
            && $entreprise->aAccesViaEssai($type);
    }
}
