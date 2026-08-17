<?php

namespace App\Services\BillingLab\Scenarios;

use App\Models\Echeance;
use App\Services\BillingLab\ScenarioContext;
use App\Services\BillingLab\ScenarioResult;
use Carbon\Carbon;

class MonthEndClampScenario extends AbstractScenario
{
    public function id(): string
    {
        return 'month_end_clamp';
    }

    public function label(): string
    {
        return 'Clamp fin de mois (jour 31 → 28 février)';
    }

    public function group(): string
    {
        return 'renewal';
    }

    public function run(ScenarioContext $ctx): ScenarioResult
    {
        $user = $ctx->fixtures->user();
        $ctx->fixtures->stripeEcheancePremium($user, [
            'jour_facturation' => 31,
            'premium_actif_jusqu' => Carbon::create(2026, 1, 31),
        ]);

        $ctx->clock->travelTo(Carbon::create(2026, 2, 28, 6, 0));
        $ctx->runCheckEcheances();

        $created = Echeance::query()
            ->where('user_id', $user->id)
            ->where('subscription_type', Echeance::TYPE_DEFAULT)
            ->whereDate('periode_debut', '2026-02-01')
            ->exists();

        if (! $created) {
            return ScenarioResult::fail('Le 28 février, jour_facturation=31 n’a pas créé d’échéance.');
        }

        return ScenarioResult::pass('Dernier jour de février rattrape jour_facturation=31.');
    }
}
