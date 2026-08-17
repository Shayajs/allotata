<?php

namespace App\Services\BillingLab;

use RuntimeException;

class BillingLabGuard
{
    public static function secret(): string
    {
        return (string) config('services.stripe.secret', '');
    }

    public static function isLiveMode(): bool
    {
        return str_starts_with(self::secret(), 'sk_live_');
    }

    public static function isTestMode(): bool
    {
        return str_starts_with(self::secret(), 'sk_test_');
    }

    public static function canCallStripe(): bool
    {
        return self::isTestMode();
    }

    public static function mode(): string
    {
        if (self::isLiveMode()) {
            return 'blocked_live';
        }

        if (self::isTestMode()) {
            return 'stripe_test';
        }

        return 'offline';
    }

    public static function assertNotLive(): void
    {
        if (self::isLiveMode()) {
            throw new RuntimeException(
                'Laboratoire de facturation refusé : clé Stripe LIVE détectée. Aucun appel Stripe n’est autorisé.'
            );
        }
    }

    public static function assertCanCallStripe(): void
    {
        self::assertNotLive();

        if (! self::isTestMode()) {
            throw new RuntimeException('Clé Stripe test (sk_test_) absente : scénario live ignoré.');
        }
    }
}
