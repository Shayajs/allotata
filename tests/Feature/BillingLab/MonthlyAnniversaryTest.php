<?php

namespace Tests\Feature\BillingLab;

use App\Services\BillingLab\ScenarioRunner;

class MonthlyAnniversaryTest extends BillingLabTestCase
{

    public function test_renouvellement_le_jour_17(): void
    {
        $result = app(ScenarioRunner::class)->run('anniversary_jour_facturation');

        $this->assertTrue($result['ok'], $result['message'] ?? '');
        $this->assertSame('pass', $result['status']);
    }

    public function test_periode_anniversaire_un_seul_debit(): void
    {
        $result = app(ScenarioRunner::class)->run('premium_anniversary');

        $this->assertTrue($result['ok'], $result['message'] ?? '');
        $this->assertSame('pass', $result['status']);
    }

    public function test_grace_puis_coupure_reelle(): void
    {
        $result = app(ScenarioRunner::class)->run('premium_grace_then_revoke');

        $this->assertTrue($result['ok'], $result['message'] ?? '');
        $this->assertSame('pass', $result['status']);
    }

    public function test_migration_cashier_sans_double_debit(): void
    {
        $result = app(ScenarioRunner::class)->run('cashier_migration_no_double_charge');

        $this->assertTrue($result['ok'], $result['message'] ?? '');
        $this->assertSame('pass', $result['status']);
    }

    public function test_clamp_fin_de_mois_fevrier(): void
    {
        $result = app(ScenarioRunner::class)->run('month_end_clamp');

        $this->assertTrue($result['ok'], $result['message'] ?? '');
    }

    public function test_jour_facturation_null_saute_le_cron(): void
    {
        $result = app(ScenarioRunner::class)->run('jour_facturation_unset');

        $this->assertTrue($result['ok'], $result['message'] ?? '');
        $this->assertTrue($result['findings']['unset_jour_skips_renewal'] ?? false);
    }

    public function test_cashier_reste_valide_apres_un_mois_local(): void
    {
        $result = app(ScenarioRunner::class)->run('cashier_access_after_month');

        $this->assertTrue($result['ok'], $result['message'] ?? '');
    }

    public function test_retry_annule_apres_7_jours(): void
    {
        $result = app(ScenarioRunner::class)->run('retry_cancel_after_failures');

        $this->assertTrue($result['ok'], $result['message'] ?? '');
    }
}
