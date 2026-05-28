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
        $this->assertFalse($echeance->requiresUserPayment());
        $this->assertFalse($echeance->estReglable());
    }

    public function test_auto_card_echeance_requires_user_payment(): void
    {
        $echeance = new Echeance([
            'payment_origin' => Echeance::ORIGIN_AUTO_CARD,
            'payment_provider' => Echeance::PROVIDER_STRIPE,
            'auto_charge_eligible' => true,
            'statut' => Echeance::STATUT_A_PAYER,
            'subscription_type' => Echeance::TYPE_DEFAULT,
            'entreprise_id' => null,
        ]);

        $this->assertTrue($echeance->requiresUserPayment());
        $this->assertTrue($echeance->estReglable());
    }
}
