<?php

namespace App\Services\BillingLab\Scenarios;

use App\Models\Echeance;
use App\Services\BillingLab\ScenarioContext;
use App\Services\BillingLab\ScenarioResult;
use Carbon\Carbon;

class AnniversaryJourFacturationScenario extends AbstractScenario
{
    public function id(): string
    {
        return 'anniversary_jour_facturation';
    }

    public function label(): string
    {
        return 'Renouvellement le jour du 1er paiement (17)';
    }

    public function group(): string
    {
        return 'renewal';
    }

    public function run(ScenarioContext $ctx): ScenarioResult
    {
        $user = $ctx->fixtures->user();
        $ctx->fixtures->stripeEcheancePremium($user, [
            'jour_facturation' => 17,
            'premium_actif_jusqu' => Carbon::create(2026, 4, 16),
        ]);

        $ctx->clock->travelTo(Carbon::create(2026, 4, 16, 6, 0));
        $ctx->runCheckEcheances();
        $on16 = Echeance::query()
            ->where('user_id', $user->id)
            ->where('subscription_type', Echeance::TYPE_DEFAULT)
            ->count();

        $ctx->clock->travelTo(Carbon::create(2026, 4, 17, 6, 0));
        $ctx->runCheckEcheances();
        $on17 = Echeance::query()
            ->where('user_id', $user->id)
            ->where('subscription_type', Echeance::TYPE_DEFAULT)
            ->where('statut', Echeance::STATUT_A_PAYER)
            ->count();

        if ($on16 !== 0 || $on17 < 1) {
            return ScenarioResult::fail('Le CRON n’a pas respecté le jour 17.', [
                'created_on_16' => $on16,
                'created_on_17' => $on17,
            ]);
        }

        $ctx->useFakeStripe();
        $ctx->runProcessPayments();

        $paid = Echeance::query()
            ->where('user_id', $user->id)
            ->where('subscription_type', Echeance::TYPE_DEFAULT)
            ->where('statut', Echeance::STATUT_PAYE)
            ->exists();

        if (! $paid) {
            return ScenarioResult::fail('Échéance du 17 créée mais non débitée par process-payments.');
        }

        return ScenarioResult::pass('Le 16 : rien. Le 17 : échéance créée et débitée.', [
            'jour_facturation' => 17,
        ]);
    }
}
