<?php

namespace App\Services\BillingLab\Scenarios;

use App\Services\BillingLab\ScenarioContext;
use App\Services\BillingLab\ScenarioResult;

class StripePremiumUnlockScenario extends AbstractScenario
{
    public function id(): string
    {
        return 'stripe_premium_unlock';
    }

    public function label(): string
    {
        return 'Premium Stripe : paiement → déblocage';
    }

    public function group(): string
    {
        return 'unlock';
    }

    public function run(ScenarioContext $ctx): ScenarioResult
    {
        $user = $ctx->fixtures->user();
        $ctx->fixtures->cashierSubscription($user);

        $user->refresh();

        if (! $user->subscribed('default') || ! $user->aAbonnementActif()) {
            return ScenarioResult::fail('Cashier actif n’a pas débloqué Premium.', [
                'subscribed' => $user->subscribed('default'),
                'access' => $user->aAbonnementActif(),
                'ledger' => $ctx->ledger->forUser($user),
            ]);
        }

        return ScenarioResult::pass('Premium débloqué via Cashier subscribed(default).', [
            'ledger' => $ctx->ledger->forUser($user),
        ]);
    }
}
