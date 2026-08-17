<?php

namespace App\Services\PlayBilling;

use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\AndroidPublisher;
use Google\Service\AndroidPublisher\SubscriptionPurchasesAcknowledgeRequest;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PlayBillingVerifier implements PlayBillingVerifierContract
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
    public function verify(string $purchaseToken, string $productId, string $kind = 'subscription'): array
    {
        $publisher = $this->publisher();
        $package = (string) config('play.package_name');

        if ($kind === 'product') {
            $purchase = $publisher->purchases_products->get($package, $productId, $purchaseToken);
            $valid = (int) $purchase->getPurchaseState() === 0;

            return [
                'valid' => $valid,
                'order_id' => $purchase->getOrderId(),
                'expires_at' => null,
                'acknowledged' => (int) $purchase->getAcknowledgementState() === 1,
                'payload' => json_decode(json_encode($purchase), true) ?: [],
            ];
        }

        $purchase = $publisher->purchases_subscriptionsv2->get($package, $purchaseToken);
        $state = (string) $purchase->getSubscriptionState();
        $valid = in_array($state, [
            'SUBSCRIPTION_STATE_ACTIVE',
            'SUBSCRIPTION_STATE_IN_GRACE_PERIOD',
        ], true);

        $expiresAt = null;
        $matchedProduct = false;
        foreach ($purchase->getLineItems() ?? [] as $item) {
            $itemProduct = (string) ($item->getProductId() ?? '');
            if ($itemProduct === $productId) {
                $matchedProduct = true;
            }
            $expiry = $item->getExpiryTime();
            if ($expiry) {
                $expiresAt = Carbon::parse($expiry);
            }
        }

        if ($valid && ! $matchedProduct && $productId !== '') {
            Log::warning('Play Billing: product_id ne correspond pas à l’achat', [
                'expected' => $productId,
                'token' => substr($purchaseToken, 0, 12).'…',
            ]);
            $valid = false;
        }

        return [
            'valid' => $valid,
            'order_id' => $purchase->getLatestOrderId(),
            'expires_at' => $expiresAt,
            'acknowledged' => (string) $purchase->getAcknowledgementState() === 'ACKNOWLEDGEMENT_STATE_ACKNOWLEDGED',
            'payload' => json_decode(json_encode($purchase), true) ?: [],
        ];
    }

    public function acknowledge(string $purchaseToken, string $productId, string $kind = 'subscription'): void
    {
        $publisher = $this->publisher();
        $package = (string) config('play.package_name');

        if ($kind === 'product') {
            $publisher->purchases_products->acknowledge($package, $productId, $purchaseToken, new \Google\Service\AndroidPublisher\ProductPurchasesAcknowledgeRequest);
            return;
        }

        $publisher->purchases_subscriptions->acknowledge(
            $package,
            $productId,
            $purchaseToken,
            new SubscriptionPurchasesAcknowledgeRequest
        );
    }

    private function publisher(): AndroidPublisher
    {
        $path = (string) config('play.service_account_json');
        if ($path === '' || ! is_file($path)) {
            throw new RuntimeException('Compte de service Google Play introuvable.');
        }

        $client = new GoogleClient;
        $client->setAuthConfig($path);
        $client->addScope(AndroidPublisher::ANDROIDPUBLISHER);

        return new AndroidPublisher($client);
    }
}
