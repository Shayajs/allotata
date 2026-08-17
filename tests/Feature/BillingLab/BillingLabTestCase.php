<?php

namespace Tests\Feature\BillingLab;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class BillingLabTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.stripe.secret' => 'sk_test_billing_lab_offline',
            'services.stripe.key' => 'pk_test_billing_lab_offline',
            'services.stripe.price_id' => '',
        ]);
    }
}
