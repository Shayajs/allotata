<?php

namespace App\Services\BillingLab\Scenarios;

use App\Models\Echeance;
use App\Services\BillingLab\ScenarioContext;
use App\Services\BillingLab\ScenarioResult;
use Carbon\Carbon;

class DoubleChargeEvidenceScenario extends AbstractScenario
{
    public function id(): string
    {
        return 'double_charge_evidence';
    }

    public function label(): string
    {
        return 'Garde : Cashier legacy n’engendre plus d’échéance PI';
    }

    public function group(): string
    {
        return 'collision';
    }

    public function run(ScenarioContext $ctx): ScenarioResult
    {
        $day = 12;
        $user = $ctx->fixtures->user(['jour_facturation' => $day]);
        $ctx->fixtures->cashierSubscription($user);
        $user->stripe_payment_method_id = 'pm_lab_visa';
        $user->save();

        $ctx->clock->travelTo(Carbon::create(2026, 6, $day, 6, 15));
        $ctx->runCheckEcheances();

        $ctx->useFakeStripe();
        $ctx->fakeStripe->behavior = 'ok';
        $ctx->runProcessPayments();

        $dual = $ctx->ledger->detectDoubleEngine($user->fresh(), $ctx->fakeStripe);
        $details = [
            'dual' => $dual,
            'ledger' => $ctx->ledger->forUser($user->fresh(), $ctx->fakeStripe),
            'echeances' => Echeance::query()->where('user_id', $user->id)->get(['id', 'statut', 'montant_final'])->toArray(),
        ];

        if (! $dual['detected']) {
            return ScenarioResult::evidenceSafe(
                'Pas de double moteur sur ce run (Cashier actif n’a pas été accompagné d’un PI d’échéance).',
                $details,
                ['double_charge' => false]
            );
        }

        return ScenarioResult::evidenceRisk(
            'PROUVÉ : Cashier reste active (Stripe facturerait le mois) ET le CRON a créé + débité une échéance PaymentIntent pour la même période.',
            $details,
            ['double_charge' => true]
        );
    }
}
