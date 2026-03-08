<?php

namespace Tests\Unit\Payments;

use App\Models\Echeance;
use App\Services\Payments\ProviderResolver;
use App\Services\Payments\Providers\StripeProvider;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ProviderResolverTest extends TestCase
{
    public function test_resolves_default_stripe_provider(): void
    {
        $resolver = new ProviderResolver([new StripeProvider()]);

        $provider = $resolver->resolve();

        $this->assertSame(Echeance::PROVIDER_STRIPE, $provider->key());
    }

    public function test_throws_for_unknown_provider(): void
    {
        $resolver = new ProviderResolver([new StripeProvider()]);

        $this->expectException(InvalidArgumentException::class);
        $resolver->resolve('paypal');
    }
}
