<?php

namespace App\Services\BillingLab\Scenarios;

use App\Models\Echeance;
use App\Services\BillingLab\BillingLabGuard;
use App\Services\BillingLab\ScenarioContext;
use App\Services\BillingLab\ScenarioResult;
use App\Services\PaymentVerificationService;
use App\Services\StripeCustomerService;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Stripe;

class StripeLivePiChargeScenario extends AbstractScenario
{
    public function id(): string
    {
        return 'stripe_live_pi_charge';
    }

    public function label(): string
    {
        return 'Stripe test : vrai PI 2€ → échéance paye';
    }

    public function group(): string
    {
        return 'stripe_live';
    }

    public function requiresStripeLive(): bool
    {
        return true;
    }

    public function run(ScenarioContext $ctx): ScenarioResult
    {
        if (! $ctx->allowStripeLive || ! BillingLabGuard::canCallStripe()) {
            return ScenarioResult::skipped('Clé sk_test_ absente ou mode live refusé.');
        }

        BillingLabGuard::assertNotLive();
        Stripe::setApiKey(BillingLabGuard::secret());

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

        $customerId = StripeCustomerService::ensureCustomer($user);
        $pm = PaymentMethod::create([
            'type' => 'card',
            'card' => ['token' => 'tok_visa'],
        ]);
        $pm->attach(['customer' => $customerId]);
        $user->forceFill(['stripe_payment_method_id' => $pm->id])->save();

        $pi = PaymentIntent::create([
            'amount' => 200,
            'currency' => 'eur',
            'customer' => $customerId,
            'payment_method' => $pm->id,
            'off_session' => true,
            'confirm' => true,
            'metadata' => [
                'user_id' => (string) $user->id,
                'echeance_id' => (string) $echeance->id,
                'billing_lab' => '1',
            ],
        ]);

        $result = PaymentVerificationService::markEcheancePaidFromPaymentIntent($pi->id);
        $echeance->refresh();
        $entreprise->refresh();

        if (! $result['ok'] || $echeance->statut !== Echeance::STATUT_PAYE || ! $entreprise->aSiteWebActif()) {
            return ScenarioResult::fail('Le PI test n’a pas débloqué l’option.', [
                'pi' => $pi->id,
                'pi_status' => $pi->status,
                'verify' => $result,
                'statut' => $echeance->statut,
            ]);
        }

        return ScenarioResult::pass('Vrai PaymentIntent test : payé et option débloquée.', [
            'payment_intent' => $pi->id,
            'livemode' => $pi->livemode,
        ]);
    }
}
