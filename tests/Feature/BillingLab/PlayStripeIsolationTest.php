<?php

namespace Tests\Feature\BillingLab;

use App\Services\BillingLab\ScenarioRunner;

class PlayStripeIsolationTest extends BillingLabTestCase
{

    public function test_play_est_isole_du_cron_stripe(): void
    {
        $result = app(ScenarioRunner::class)->run('play_stripe_isolation');

        $this->assertTrue($result['ok'], $result['message'] ?? '');
        $this->assertTrue($result['findings']['play_premium_safe'] ?? false, $result['message'] ?? '');
        $this->assertFalse($result['findings']['play_addon_leak'] ?? true, $result['message'] ?? '');
        $this->assertSame('evidence_safe', $result['status']);
    }
}
