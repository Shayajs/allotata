<?php

namespace App\Services\BillingLab\Scenarios;

use App\Models\Echeance;
use App\Services\BillingLab\ScenarioContext;
use App\Services\BillingLab\ScenarioResult;

class CardDeclineNoUnlockScenario extends AbstractScenario
{
    public function id(): string
    {
        return 'card_decline_no_unlock';
    }

    public function label(): string
    {
        return 'Carte refusée : pas d’unlock';
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

        $ctx->fakeStripe->behavior = 'failed';
        $ctx->runProcessPayments();
        $echeance->refresh();
        $entreprise->refresh();

        if ($echeance->statut !== Echeance::STATUT_ECHEC || $entreprise->aSiteWebActif()) {
            return ScenarioResult::fail('Un refus n’a pas laissé l’option fermée.', [
                'statut' => $echeance->statut,
                'site_web' => $entreprise->aSiteWebActif(),
            ]);
        }

        return ScenarioResult::pass('Carte refusée → échéance echec, aucun déblocage.');
    }
}
