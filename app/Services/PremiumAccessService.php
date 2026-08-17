<?php

namespace App\Services;

use App\Models\Echeance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Subscription;

class PremiumAccessService
{
    public const GRACE_DAYS = 7;

    public static function isPremiumEcheance(Echeance $echeance): bool
    {
        return $echeance->entreprise_id === null
            && $echeance->subscription_type === Echeance::TYPE_DEFAULT;
    }

    public static function hasPremiumUntil(User $user): bool
    {
        if (! $user->premium_actif_jusqu) {
            return false;
        }

        return $user->premium_actif_jusqu->isFuture() || $user->premium_actif_jusqu->isToday();
    }

    public static function hasLegacyCashierBilling(User $user): bool
    {
        try {
            $subscription = $user->subscription('default');

            return $subscription && $subscription->valid();
        } catch (\Throwable $e) {
            Log::warning('PremiumAccess: lecture Cashier impossible', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public static function isEligibleForStripeCron(User $user): bool
    {
        if ($user->payment_provider !== 'stripe') {
            return false;
        }

        if ($user->jour_facturation === null || $user->jour_facturation === '') {
            return false;
        }

        if (! $user->premium_actif_jusqu) {
            return false;
        }

        if ($user->hasActiveManualPremium()) {
            return false;
        }

        if (method_exists($user, 'hasActivePlayPremium') && $user->hasActivePlayPremium()) {
            return false;
        }

        if (self::hasLegacyCashierBilling($user)) {
            return false;
        }

        return true;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function nextAnniversaryPeriod(User $user): array
    {
        $until = $user->premium_actif_jusqu->copy()->startOfDay();
        $debut = $until->copy()->addDay();
        $fin = $debut->copy()->addMonth()->subDay();

        return [$debut, $fin];
    }

    public static function ensureFromEcheance(Echeance $echeance): void
    {
        if (! self::isPremiumEcheance($echeance) || ! $echeance->estPayee()) {
            return;
        }

        $user = $echeance->user;
        if (! $user) {
            return;
        }

        $payload = [
            'payment_provider' => Echeance::PROVIDER_STRIPE,
            'abonnement_manuel' => false,
            'abonnement_manuel_actif_jusqu' => null,
            'abonnement_manuel_notes' => null,
        ];

        if ($echeance->periode_fin) {
            $until = $echeance->periode_fin->copy()->startOfDay();
            $current = $user->premium_actif_jusqu?->copy()->startOfDay();
            if (! $current || $until->gte($current)) {
                $payload['premium_actif_jusqu'] = $until->toDateString();
            }
        }

        if ($user->jour_facturation === null && $echeance->periode_debut) {
            $payload['jour_facturation'] = (int) $echeance->periode_debut->day;
        }

        $user->forceFill($payload)->save();
    }

    public static function applyGrace(Echeance $echeance): void
    {
        if (! self::isPremiumEcheance($echeance)) {
            return;
        }

        $meta = $echeance->metadata ?? [];
        if (! empty($meta['grace_applied_at'])) {
            return;
        }

        $user = $echeance->user;
        if (! $user) {
            return;
        }

        $graceUntil = now()->addDays(self::GRACE_DAYS)->startOfDay();
        $current = $user->premium_actif_jusqu?->copy()->startOfDay();
        if (! $current || $current->lt($graceUntil)) {
            $user->forceFill(['premium_actif_jusqu' => $graceUntil->toDateString()])->save();
        }

        $echeance->update([
            'metadata' => array_merge($meta, [
                'grace_applied_at' => now()->toIso8601String(),
                'grace_until' => $graceUntil->toDateString(),
            ]),
        ]);
    }

    public static function revoke(User $user): void
    {
        $payload = [
            'premium_actif_jusqu' => now()->subDay()->toDateString(),
        ];

        if ($user->payment_provider === Echeance::PROVIDER_STRIPE) {
            $payload['payment_provider'] = null;
        }

        $user->forceFill($payload)->save();

        $subscription = null;
        try {
            $subscription = $user->subscription('default');
        } catch (\Throwable) {
            $subscription = null;
        }

        if (! $subscription || ! $subscription->valid()) {
            return;
        }

        try {
            $subscription->cancel();
        } catch (\Throwable $e) {
            Log::warning('PremiumAccess: cancel Cashier Stripe échoué, clôture locale', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            $subscription->update([
                'ends_at' => now()->subDay(),
                'stripe_status' => 'canceled',
            ]);
        }
    }

    public static function applyLocalCashierMigration(User $user, Subscription $subscription, Carbon $periodEnd): void
    {
        $jour = (int) $periodEnd->copy()->addDay()->day;

        $user->forceFill([
            'premium_actif_jusqu' => $periodEnd->toDateString(),
            'payment_provider' => Echeance::PROVIDER_STRIPE,
            'jour_facturation' => $user->jour_facturation ?: $jour,
        ])->save();

        $subscription->update([
            'ends_at' => $periodEnd,
        ]);
    }
}
