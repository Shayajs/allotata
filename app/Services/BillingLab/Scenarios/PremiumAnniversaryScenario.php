<?php

namespace App\Services\BillingLab\Scenarios;

use App\Models\Echeance;
use App\Services\BillingLab\ScenarioContext;
use App\Services\BillingLab\ScenarioResult;
use Carbon\Carbon;

class PremiumAnniversaryScenario extends AbstractScenario
{
    public function id(): string
    {
        return 'premium_anniversary';
    }

    public function label(): string
    {
        return 'Premium : renouvellement anniversaire, un seul débit';
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

        $created = Echeance::query()
            ->where('user_id', $user->id)
            ->where('subscription_type', Echeance::TYPE_DEFAULT)
            ->where('statut', Echeance::STATUT_A_PAYER)
            ->get();

        if ($on16 !== 0 || $created->count() !== 1) {
            return ScenarioResult::fail('Le CRON n’a pas créé une unique échéance le 17.', [
                'created_on_16' => $on16,
                'created_on_17' => $created->count(),
            ]);
        }

        $echeance = $created->first();
        $expectedDebut = '2026-04-17';
        $expectedFin = '2026-05-16';
        if ($echeance->periode_debut?->toDateString() !== $expectedDebut
            || $echeance->periode_fin?->toDateString() !== $expectedFin) {
            return ScenarioResult::fail('Période anniversaire incorrecte.', [
                'periode' => [$echeance->periode_debut?->toDateString(), $echeance->periode_fin?->toDateString()],
                'expected' => [$expectedDebut, $expectedFin],
            ]);
        }

        $ctx->useFakeStripe();
        $ctx->fakeStripe->behavior = 'ok';
        $ctx->runProcessPayments();

        $paid = Echeance::query()
            ->where('user_id', $user->id)
            ->where('subscription_type', Echeance::TYPE_DEFAULT)
            ->where('statut', Echeance::STATUT_PAYE)
            ->count();

        $charges = collect($ctx->fakeStripe->charges)->where('user_id', $user->id)->where('behavior', 'ok')->count();

        if ($paid !== 1 || $charges !== 1) {
            return ScenarioResult::fail('Le renouvellement n’a pas débité une seule fois.', [
                'paid' => $paid,
                'charges' => $charges,
            ]);
        }

        $user->refresh();
        if ($user->premium_actif_jusqu?->toDateString() !== $expectedFin) {
            return ScenarioResult::fail('premium_actif_jusqu n’a pas été étendu à la fin de période.', [
                'premium_actif_jusqu' => $user->premium_actif_jusqu?->toDateString(),
            ]);
        }

        return ScenarioResult::pass('Le 16 : rien. Le 17 : une échéance anniversaire, un débit.', [
            'jour_facturation' => 17,
            'ledger' => $ctx->ledger->forUser($user, $ctx->fakeStripe),
        ]);
    }
}
