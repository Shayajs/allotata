<?php

namespace Tests\Feature\BillingLab;

use App\Services\BillingLab\ScenarioRunner;

class DoubleChargeEvidenceTest extends BillingLabTestCase
{

    public function test_double_moteur_cashier_et_echeance_est_impossible(): void
    {
        $result = app(ScenarioRunner::class)->run('double_charge_evidence');

        $this->assertTrue($result['ok'], $result['message'] ?? '');
        $this->assertFalse($result['findings']['double_charge'] ?? true, $result['message'] ?? '');
        $this->assertSame('evidence_safe', $result['status']);
    }

    public function test_triple_filet_reste_idempotent(): void
    {
        $result = app(ScenarioRunner::class)->run('triple_net_idempotence');

        $this->assertTrue($result['ok'], $result['message'] ?? '');
    }
}
