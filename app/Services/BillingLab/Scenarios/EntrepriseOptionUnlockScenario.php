<?php

namespace App\Services\BillingLab\Scenarios;

use App\Models\Echeance;
use App\Models\EntrepriseSubscription;
use App\Services\BillingLab\ScenarioContext;
use App\Services\BillingLab\ScenarioResult;

class EntrepriseOptionUnlockScenario extends AbstractScenario
{
    public function id(): string
    {
        return 'entreprise_option_unlock';
    }

    public function label(): string
    {
        return 'Option entreprise Stripe : PI → actif_jusqu';
    }

    public function group(): string
    {
        return 'unlock';
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

        $ctx->fakeStripe->behavior = 'ok';
        $output = $ctx->runProcessPayments();
        $echeance->refresh();
        $entreprise->refresh();

        $sub = EntrepriseSubscription::query()
            ->where('entreprise_id', $entreprise->id)
            ->where('type', 'site_web')
            ->first();

        if ($echeance->statut !== Echeance::STATUT_PAYE || ! $sub?->estActif() || ! $entreprise->aSiteWebActif()) {
            return ScenarioResult::fail('Le paiement d’échéance n’a pas débloqué le site web.', [
                'echeance' => $echeance->statut,
                'sub_actif' => $sub?->estActif(),
                'site_web' => $entreprise->aSiteWebActif(),
                'cron' => $output,
            ]);
        }

        return ScenarioResult::pass('Option site web débloquée après PI (actif_jusqu).', [
            'actif_jusqu' => $sub->actif_jusqu?->toDateString(),
            'ledger' => $ctx->ledger->forUser($user, $ctx->fakeStripe),
        ]);
    }
}
