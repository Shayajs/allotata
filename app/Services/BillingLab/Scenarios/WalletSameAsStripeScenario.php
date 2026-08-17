<?php

namespace App\Services\BillingLab\Scenarios;

use App\Models\Echeance;
use App\Services\BillingLab\ScenarioContext;
use App\Services\BillingLab\ScenarioResult;

class WalletSameAsStripeScenario extends AbstractScenario
{
    public function id(): string
    {
        return 'wallet_same_as_stripe';
    }

    public function label(): string
    {
        return 'Apple Pay / Google Pay : même PI Stripe';
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

        $ctx->fixtures->echeance($user, [
            'entreprise_id' => $entreprise->id,
            'subscription_type' => Echeance::TYPE_MULTI_PERSONNES,
            'statut' => Echeance::STATUT_A_PAYER,
            'montant_du' => 20,
            'montant_final' => 20,
            'periode_fin' => now()->addMonth(),
            'metadata' => ['is_billing_lab' => true, 'wallet' => 'apple_pay'],
        ]);

        $ctx->runProcessPayments();
        $entreprise->refresh();

        if (! $entreprise->aGestionMultiPersonnes()) {
            return ScenarioResult::fail('Le chemin PI (wallets) n’a pas débloqué multi-personnes.');
        }

        return ScenarioResult::pass(
            'Apple Pay et Google Pay sont des wallets Stripe : même PaymentIntent, même unlock.',
            ['multi_personnes' => true]
        );
    }
}
