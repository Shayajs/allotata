<?php

namespace Tests\Unit\Models;

use App\Models\Echeance;
use PHPUnit\Framework\TestCase;

class EcheancePaymentModelTest extends TestCase
{
    public function test_manual_echeance_flags_can_be_assigned(): void
    {
        $echeance = new Echeance([
            'payment_origin' => Echeance::ORIGIN_MANUAL,
            'payment_provider' => null,
            'auto_charge_eligible' => false,
            'statut' => Echeance::STATUT_A_PAYER,
        ]);

        $this->assertSame(Echeance::ORIGIN_MANUAL, $echeance->payment_origin);
        $this->assertFalse((bool) $echeance->auto_charge_eligible);
        $this->assertTrue($echeance->estAPayer());
    }
}
