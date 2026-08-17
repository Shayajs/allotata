<?php

namespace App\Services\BillingLab\Scenarios;

use App\Models\Echeance;
use App\Services\BillingLab\ScenarioContext;
use App\Services\BillingLab\ScenarioResult;
use Carbon\Carbon;

class RetryCancelAfterFailuresScenario extends AbstractScenario
{
    public function id(): string
    {
        return 'retry_cancel_after_failures';
    }

    public function label(): string
    {
        return '3 échecs / 7j → annule + cancel abonnement';
    }

    public function group(): string
    {
        return 'renewal';
    }

    public function run(ScenarioContext $ctx): ScenarioResult
    {
        $ctx->useFakeStripe();
        $user = $ctx->fixtures->user();
        $sub = $ctx->fixtures->cashierSubscription($user);

        $echeance = $ctx->fixtures->echeance($user, [
            'subscription_type' => Echeance::TYPE_DEFAULT,
            'statut' => Echeance::STATUT_ECHEC,
            'created_at' => Carbon::now()->subDays(8),
            'metadata' => ['is_billing_lab' => true, 'retry_count' => 3],
        ]);
        $echeance->created_at = Carbon::now()->subDays(8);
        $echeance->save();

        $ctx->runProcessPayments();
        $echeance->refresh();
        $sub->refresh();
        $user->refresh();

        if ($echeance->statut !== Echeance::STATUT_ANNULE) {
            return ScenarioResult::fail('L’échéance n’a pas été annulée après 3 retries / 8j.', [
                'statut' => $echeance->statut,
            ]);
        }

        if ($user->aAbonnementActif()) {
            return ScenarioResult::fail('L’accès Premium n’a pas été coupé après 3 échecs / 7j.', [
                'access' => true,
                'premium_actif_jusqu' => $user->premium_actif_jusqu?->toDateString(),
                'subscribed' => $user->subscribed('default'),
            ]);
        }

        return ScenarioResult::pass('Après 3 échecs / 7j : échéance annulée et accès coupé.', [
            'ends_at' => $sub->fresh()->ends_at?->toIso8601String(),
            'still_subscribed' => $user->fresh()->subscribed('default'),
            'premium_actif_jusqu' => $user->premium_actif_jusqu?->toDateString(),
        ]);
    }
}
