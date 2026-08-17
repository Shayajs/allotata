<?php

namespace App\Services\BillingLab\Scenarios;

use App\Models\Echeance;
use App\Models\EntrepriseSubscription;
use App\Services\BillingLab\ScenarioContext;
use App\Services\BillingLab\ScenarioResult;

class TripleNetIdempotenceScenario extends AbstractScenario
{
    public function id(): string
    {
        return 'triple_net_idempotence';
    }

    public function label(): string
    {
        return 'Triple filet : 1 seul paye après 2 confirms';
    }

    public function group(): string
    {
        return 'collision';
    }

    public function run(ScenarioContext $ctx): ScenarioResult
    {
        $ctx->useFakeStripe();
        $user = $ctx->fixtures->user();
        $entreprise = $ctx->fixtures->entreprise($user);

        $echeance = $ctx->fixtures->echeance($user, [
            'entreprise_id' => $entreprise->id,
            'subscription_type' => Echeance::TYPE_SITE_WEB,
            'statut' => Echeance::STATUT_A_PAYER,
            'montant_du' => 2,
            'montant_final' => 2,
            'periode_fin' => now()->addMonth(),
        ]);

        $ctx->runProcessPayments();
        $echeance->refresh();
        $pi = $echeance->stripe_payment_intent_id;
        if (! $pi) {
            return ScenarioResult::fail('process-payments n’a pas posé de PaymentIntent.', [
                'statut' => $echeance->statut,
            ]);
        }

        $second = $ctx->fakeStripe->verifyPaymentIntent($pi);

        $count = Echeance::query()->where('user_id', $user->id)->where('statut', Echeance::STATUT_PAYE)->count();
        $subs = EntrepriseSubscription::query()->where('entreprise_id', $entreprise->id)->where('type', 'site_web')->count();

        if ($count !== 1 || $subs !== 1 || empty($second['already'])) {
            return ScenarioResult::fail('Idempotence cassée : doublon échéance ou abo.', [
                'payees' => $count,
                'subs' => $subs,
                'second' => $second,
            ]);
        }

        return ScenarioResult::pass('Deux confirms → une seule échéance paye, un seul EntrepriseSubscription.');
    }
}
