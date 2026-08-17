<?php

namespace Tests\Feature\BillingLab;

use App\Services\BillingLab\ScenarioRunner;

class StripeUnlockTest extends BillingLabTestCase
{

    public function test_premium_cashier_debloque_l_acces(): void
    {
        $result = app(ScenarioRunner::class)->run('stripe_premium_unlock');

        $this->assertTrue($result['ok']);
        $this->assertSame('pass', $result['status']);
    }

    public function test_premier_paiement_web_sans_cashier(): void
    {
        $result = app(ScenarioRunner::class)->run('premium_single_charger');

        $this->assertTrue($result['ok'], $result['message'] ?? '');
        $this->assertSame('pass', $result['status']);
    }

    public function test_option_entreprise_debloque_apres_pi(): void
    {
        $result = app(ScenarioRunner::class)->run('entreprise_option_unlock');

        $this->assertTrue($result['ok'], $result['message'] ?? '');
        $this->assertSame('pass', $result['status']);
    }

    public function test_wallets_suivent_le_meme_chemin_pi(): void
    {
        $result = app(ScenarioRunner::class)->run('wallet_same_as_stripe');

        $this->assertTrue($result['ok'], $result['message'] ?? '');
    }

    public function test_3ds_ne_debloque_pas_avant_confirm(): void
    {
        $result = app(ScenarioRunner::class)->run('threeds_pending_then_unlock');

        $this->assertTrue($result['ok'], $result['message'] ?? '');
    }

    public function test_refus_carte_ne_debloque_pas(): void
    {
        $result = app(ScenarioRunner::class)->run('card_decline_no_unlock');

        $this->assertTrue($result['ok'], $result['message'] ?? '');
    }
}
