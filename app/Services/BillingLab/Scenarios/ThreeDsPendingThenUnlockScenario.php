<?php

namespace App\Services\BillingLab\Scenarios;

use App\Models\Echeance;
use App\Services\BillingLab\ScenarioContext;
use App\Services\BillingLab\ScenarioResult;

class ThreeDsPendingThenUnlockScenario extends AbstractScenario
{
    public function id(): string
    {
        return 'threeds_pending_then_unlock';
    }

    public function label(): string
    {
        return '3DS : pas d’unlock tant que non confirmé';
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

        $ctx->fakeStripe->behavior = 'requires_action';
        $ctx->runProcessPayments();
        $echeance->refresh();
        $entreprise->refresh();

        if ($echeance->statut !== Echeance::STATUT_EN_ATTENTE || $entreprise->aSiteWebActif()) {
            return ScenarioResult::fail('3DS : statut ou unlock incorrect avant confirmation.', [
                'statut' => $echeance->statut,
                'site_web' => $entreprise->aSiteWebActif(),
            ]);
        }

        $pi = $echeance->stripe_payment_intent_id;
        $ctx->fakeStripe->paid[$pi] = $echeance->id;
        $ctx->fakeStripe->verifyPaymentIntent($pi);
        $echeance->refresh();
        $entreprise->refresh();

        if ($echeance->statut !== Echeance::STATUT_PAYE || ! $entreprise->aSiteWebActif()) {
            return ScenarioResult::fail('Après confirmation 3DS, l’option n’est pas débloquée.', [
                'statut' => $echeance->statut,
                'site_web' => $entreprise->aSiteWebActif(),
            ]);
        }

        return ScenarioResult::pass('Pas d’unlock pendant requires_action, unlock après confirm.');
    }
}
