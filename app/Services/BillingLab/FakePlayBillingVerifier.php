<?php

namespace App\Services\BillingLab;

use App\Services\PlayBilling\PlayBillingVerifierContract;
use Carbon\Carbon;

class FakePlayBillingVerifier implements PlayBillingVerifierContract
{
    /** @var array<string, array<string, mixed>> */
    public array $responses = [];

    /** @var list<array{token:string,product_id:string,kind:string}> */
    public array $acknowledged = [];

    public function setResponse(string $purchaseToken, array $response): void
    {
        $this->responses[$purchaseToken] = $response;
    }

    public function verify(string $purchaseToken, string $productId, string $kind = 'subscription'): array
    {
        $configured = $this->responses[$purchaseToken] ?? null;

        if ($configured === null) {
            return [
                'valid' => false,
                'order_id' => null,
                'expires_at' => null,
                'acknowledged' => false,
                'payload' => ['reason' => 'no_fake_response'],
            ];
        }

        return [
            'valid' => (bool) ($configured['valid'] ?? false),
            'order_id' => $configured['order_id'] ?? 'GPA.LAB.'.$purchaseToken,
            'expires_at' => $configured['expires_at'] ?? Carbon::now()->addMonth(),
            'acknowledged' => (bool) ($configured['acknowledged'] ?? true),
            'payload' => $configured['payload'] ?? ['subscriptionState' => 'SUBSCRIPTION_STATE_ACTIVE'],
        ];
    }

    public function acknowledge(string $purchaseToken, string $productId, string $kind = 'subscription'): void
    {
        $this->acknowledged[] = [
            'token' => $purchaseToken,
            'product_id' => $productId,
            'kind' => $kind,
        ];
    }
}
