<?php

namespace App\Services\BillingLab\Scenarios;

use App\Services\BillingLab\BillingLabGuard;
use App\Services\BillingLab\ScenarioContext;
use App\Services\BillingLab\ScenarioResult;
use App\Services\BillingLab\StripeTestClock;
use Stripe\StripeClient;

class StripeTestClockRenewalScenario extends AbstractScenario
{
    public function id(): string
    {
        return 'stripe_test_clock_renewal';
    }

    public function label(): string
    {
        return 'Stripe Test Clock : +32j → 2e facture';
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

        $priceId = (string) config('services.stripe.price_id');
        if ($priceId === '') {
            return ScenarioResult::skipped('STRIPE_PRICE_ID manquant : impossible de créer un abonnement Test Clock.');
        }

        BillingLabGuard::assertNotLive();
        $clocks = new StripeTestClock;
        $frozen = time();
        $clock = $clocks->create('billing-lab-'.uniqid(), $frozen);
        $client = new StripeClient(BillingLabGuard::secret());

        try {
            $customer = $client->customers->create([
                'email' => 'clock-'.uniqid().'@allotata-lab.test',
                'name' => 'Billing Lab Clock',
                'test_clock' => $clock['id'],
                'metadata' => ['billing_lab' => '1'],
            ]);

            $pm = $client->paymentMethods->create([
                'type' => 'card',
                'card' => ['token' => 'tok_visa'],
            ]);
            $client->paymentMethods->attach($pm->id, ['customer' => $customer->id]);
            $client->customers->update($customer->id, [
                'invoice_settings' => ['default_payment_method' => $pm->id],
            ]);

            $client->subscriptions->create([
                'customer' => $customer->id,
                'items' => [['price' => $priceId]],
                'default_payment_method' => $pm->id,
            ]);

            $before = $client->invoices->all(['customer' => $customer->id, 'limit' => 20]);
            $clocks->advance($clock['id'], $frozen + (32 * 86400), 90);
            $after = $client->invoices->all(['customer' => $customer->id, 'limit' => 20]);

            $paidBefore = collect($before->data)->where('status', 'paid')->count();
            $paidAfter = collect($after->data)->where('status', 'paid')->count();

            if ($paidAfter < 2 || $paidAfter <= $paidBefore) {
                return ScenarioResult::fail('Le Test Clock n’a pas produit de 2e facture payée.', [
                    'clock' => $clock['id'],
                    'invoices_before' => $paidBefore,
                    'invoices_after' => $paidAfter,
                ]);
            }

            return ScenarioResult::pass('Test Clock +32j : Stripe a bien émis le renouvellement (2 factures payées).', [
                'clock' => $clock['id'],
                'invoices_paid' => $paidAfter,
            ]);
        } finally {
            $clocks->delete($clock['id']);
        }
    }
}
