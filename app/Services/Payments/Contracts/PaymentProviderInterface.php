<?php

namespace App\Services\Payments\Contracts;

use App\Models\Echeance;
use App\Models\User;

interface PaymentProviderInterface
{
    public function key(): string;

    /**
     * @return array{status:string,payment_intent_id:?string,message:?string}
     */
    public function chargeOffSession(Echeance $echeance, User $user, int $retryCount = 0): array;

    /**
     * @return array{ok:bool,already:bool,message:string}
     */
    public function verifyPaymentIntent(string $paymentIntentId): array;

    /**
     * @return array{ok:bool,already:bool,message:string}
     */
    public function verifyCheckoutSession(string $sessionId): array;
}
