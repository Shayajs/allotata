<?php

namespace App\Services\PlayBilling;

use Carbon\Carbon;

interface PlayBillingVerifierContract
{
    /**
     * @return array{
     *     valid: bool,
     *     order_id: ?string,
     *     expires_at: ?Carbon,
     *     acknowledged: bool,
     *     payload: array<string, mixed>
     * }
     */
    public function verify(string $purchaseToken, string $productId, string $kind = 'subscription'): array;

    public function acknowledge(string $purchaseToken, string $productId, string $kind = 'subscription'): void;
}
