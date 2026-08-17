<?php

namespace App\Services\BillingLab\Scenarios;

use App\Services\BillingLab\ScenarioContext;
use App\Services\BillingLab\ScenarioResult;
use Carbon\Carbon;

class CashierAccessAfterMonthScenario extends AbstractScenario
{
    public function id(): string
    {
        return 'cashier_access_after_month';
    }

    public function label(): string
    {
        return 'Cashier : accès toujours vrai +32 jours (ends_at null)';
    }

    public function group(): string
    {
        return 'renewal';
    }

    public function run(ScenarioContext $ctx): ScenarioResult
    {
        $user = $ctx->fixtures->user();
        $ctx->fixtures->cashierSubscription($user);

        $ctx->clock->travelTo(Carbon::now()->addDays(32));
        $user->refresh();

        if (! $user->subscribed('default') || ! $user->aAbonnementActif()) {
            return ScenarioResult::fail('Après +32j, Cashier local a perdu l’accès (ends_at devrait rester null).', [
                'subscribed' => $user->subscribed('default'),
                'access' => $user->aAbonnementActif(),
            ]);
        }

        return ScenarioResult::pass(
            'Côté app, Cashier reste valide +32j. Le renouvellement Stripe réel se prouve avec Test Clock (scénario live).'
        );
    }
}
