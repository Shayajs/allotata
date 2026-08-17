<?php

namespace Tests\Feature\BillingLab;

use App\Services\BillingLab\ScenarioRunner;

class PlayFulfillmentTest extends BillingLabTestCase
{

    public function test_premium_play_debloque(): void
    {
        $result = app(ScenarioRunner::class)->run('play_premium_unlock');

        $this->assertTrue($result['ok'], $result['message'] ?? '');
    }

    public function test_addon_play_debloque_site_web(): void
    {
        $result = app(ScenarioRunner::class)->run('play_addon_unlock');

        $this->assertTrue($result['ok'], $result['message'] ?? '');
    }

    public function test_play_renouvellement_puis_expiration(): void
    {
        $result = app(ScenarioRunner::class)->run('play_renewal_then_expire');

        $this->assertTrue($result['ok'], $result['message'] ?? '');
    }

    public function test_play_sync_coupe_acces_expire(): void
    {
        $result = app(ScenarioRunner::class)->run('play_expiry_revoke');

        $this->assertTrue($result['ok'], $result['message'] ?? '');
        $this->assertSame('pass', $result['status']);
    }
}
