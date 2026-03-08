<?php

namespace App\Services\Payments;

use App\Models\Echeance;
use App\Services\Payments\Contracts\PaymentProviderInterface;
use InvalidArgumentException;

class ProviderResolver
{
    /**
     * @var array<string, PaymentProviderInterface>
     */
    private array $providers = [];

    /**
     * @param iterable<PaymentProviderInterface> $providers
     */
    public function __construct(iterable $providers)
    {
        foreach ($providers as $provider) {
            $this->providers[$provider->key()] = $provider;
        }
    }

    public function resolve(?string $providerKey = null): PaymentProviderInterface
    {
        $key = $providerKey ?: Echeance::PROVIDER_STRIPE;
        if (!isset($this->providers[$key])) {
            throw new InvalidArgumentException("Payment provider not supported: {$key}");
        }

        return $this->providers[$key];
    }
}
