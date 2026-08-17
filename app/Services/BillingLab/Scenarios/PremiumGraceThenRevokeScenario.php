<?php

namespace App\Services\BillingLab\Scenarios;

use App\Models\Echeance;
use App\Services\BillingLab\ScenarioContext;
use App\Services\BillingLab\ScenarioResult;
use Carbon\Carbon;

class PremiumGraceThenRevokeScenario extends AbstractScenario
{
    public function id(): string
    {
        return 'premium_grace_then_revoke';
    }

    public function label(): string
    {
        return 'Premium : 3 échecs / 7j → accès réellement coupé';
    }

    public function group(): string
    {
        return 'renewal';
    }

    public function run(ScenarioContext $ctx): ScenarioResult
    {
        $ctx->useFakeStripe();
        $ctx->fakeStripe->behavior = 'failed';

        $start = Carbon::create(2026, 5, 17, 6, 15);
        $ctx->clock->travelTo($start);

        $user = $ctx->fixtures->user();
        $ctx->fixtures->stripeEcheancePremium($user, [
            'jour_facturation' => 17,
            'premium_actif_jusqu' => $start->copy()->subDay(),
        ]);

        $echeance = $ctx->fixtures->echeance($user, [
            'subscription_type' => Echeance::TYPE_DEFAULT,
            'statut' => Echeance::STATUT_A_PAYER,
            'periode_debut' => $start->copy()->startOfDay(),
            'periode_fin' => $start->copy()->addMonth()->subDay(),
            'created_at' => $start,
        ]);

        $ctx->runProcessPayments();
        $user->refresh();
        $accessDuringGrace = $user->aAbonnementActif();

        $ctx->clock->travelTo($start->copy()->addDays(8));
        $echeance->refresh();
        $ctx->runProcessPayments();

        $echeance->refresh();
        $user->refresh();

        $details = [
            'access_during_grace' => $accessDuringGrace,
            'access_after' => $user->aAbonnementActif(),
            'statut' => $echeance->statut,
            'premium_actif_jusqu' => $user->premium_actif_jusqu?->toDateString(),
            'payment_provider' => $user->payment_provider,
        ];

        if (! $accessDuringGrace) {
            return ScenarioResult::fail('La grâce de 7 jours n’a pas maintenu l’accès.', $details);
        }

        if ($echeance->statut !== Echeance::STATUT_ANNULE) {
            return ScenarioResult::fail('L’échéance n’a pas été annulée après 7 jours.', $details);
        }

        if ($user->aAbonnementActif()) {
            return ScenarioResult::fail('L’accès Premium n’a pas été coupé après 7 jours d’échec.', $details);
        }

        return ScenarioResult::pass('Grâce 7 jours puis coupure réelle de l’accès.', $details);
    }
}
