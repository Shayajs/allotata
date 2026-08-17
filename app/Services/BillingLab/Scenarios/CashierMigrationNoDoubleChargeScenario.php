<?php

namespace App\Services\BillingLab\Scenarios;

use App\Models\Echeance;
use App\Services\BillingLab\ScenarioContext;
use App\Services\BillingLab\ScenarioResult;
use App\Services\PremiumAccessService;
use Carbon\Carbon;

class CashierMigrationNoDoubleChargeScenario extends AbstractScenario
{
    public function id(): string
    {
        return 'cashier_migration_no_double_charge';
    }

    public function label(): string
    {
        return 'Migration Cashier : zéro double débit, relais à l’anniversaire';
    }

    public function group(): string
    {
        return 'collision';
    }

    public function run(ScenarioContext $ctx): ScenarioResult
    {
        $now = Carbon::create(2026, 4, 1, 10, 0);
        $ctx->clock->travelTo($now);

        $user = $ctx->fixtures->user();
        $sub = $ctx->fixtures->cashierSubscription($user);
        $periodEnd = Carbon::create(2026, 4, 16, 23, 59, 59);
        PremiumAccessService::applyLocalCashierMigration($user, $sub, $periodEnd);
        $user->refresh();
        $user->stripe_payment_method_id = 'pm_lab_visa';
        $user->save();

        $ctx->clock->travelTo(Carbon::create(2026, 4, 10, 6, 0));
        $ctx->runCheckEcheances();
        $duringCashier = Echeance::query()
            ->where('user_id', $user->id)
            ->where('subscription_type', Echeance::TYPE_DEFAULT)
            ->count();

        $dualDuring = $ctx->ledger->detectDoubleEngine($user->fresh(), $ctx->fakeStripe);

        $ctx->clock->travelTo(Carbon::create(2026, 4, 17, 6, 0));
        $sub->refresh();
        if ($sub->valid()) {
            $sub->update([
                'ends_at' => Carbon::create(2026, 4, 16, 12, 0),
                'stripe_status' => 'canceled',
            ]);
        }

        $ctx->runCheckEcheances();
        $after = Echeance::query()
            ->where('user_id', $user->id)
            ->where('subscription_type', Echeance::TYPE_DEFAULT)
            ->where('statut', Echeance::STATUT_A_PAYER)
            ->count();

        $ctx->useFakeStripe();
        $ctx->fakeStripe->behavior = 'ok';
        $ctx->runProcessPayments();
        $charges = collect($ctx->fakeStripe->charges)->where('user_id', $user->id)->where('behavior', 'ok')->count();

        $details = [
            'during_cashier' => $duringCashier,
            'after_period' => $after,
            'charges' => $charges,
            'dual_during' => $dualDuring,
            'jour_facturation' => $user->fresh()->jour_facturation,
            'ledger' => $ctx->ledger->forUser($user->fresh(), $ctx->fakeStripe),
        ];

        if ($duringCashier !== 0 || ($dualDuring['detected'] ?? false)) {
            return ScenarioResult::fail('Une échéance a été créée pendant la période Cashier restante.', $details);
        }

        if ($after !== 1 || $charges !== 1) {
            return ScenarioResult::fail('Le relais anniversaire n’a pas débité une seule fois.', $details);
        }

        return ScenarioResult::pass('Migration : aucun débit pendant Cashier, un débit à l’anniversaire.', $details, [
            'double_charge' => false,
        ]);
    }
}
