<?php

namespace App\Services\PlayBilling;

use App\Models\Entreprise;
use App\Models\EntrepriseSubscription;
use App\Models\PlayPurchase;
use App\Models\User;
use App\Services\PremiumAccessService;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PlayBillingFulfillment
{
    public function __construct(private PlayBillingVerifierContract $verifier) {}

    /**
     * @return array{purchase: PlayPurchase, granted: bool}
     */
    public function fulfill(
        User $user,
        string $productId,
        string $purchaseToken,
        ?string $orderId = null,
        ?int $entrepriseId = null,
    ): array {
        $catalog = $this->catalogEntry($productId);
        if (! $catalog) {
            throw new InvalidArgumentException('Produit Play inconnu.');
        }

        $existing = PlayPurchase::query()->where('purchase_token', $purchaseToken)->first();
        if ($existing && (int) $existing->user_id !== (int) $user->id) {
            throw new InvalidArgumentException('Cet achat est déjà lié à un autre compte.');
        }

        $verified = $this->verifier->verify($purchaseToken, $productId, $catalog['kind']);
        if (! $verified['valid']) {
            throw new InvalidArgumentException('Achat Google Play invalide ou inactif.');
        }

        $purchase = PlayPurchase::query()->updateOrCreate(
            ['purchase_token' => $purchaseToken],
            [
                'user_id' => $user->id,
                'entreprise_id' => $entrepriseId,
                'product_id' => $productId,
                'grants' => $catalog['grants'],
                'order_id' => $orderId ?: $verified['order_id'],
                'package_name' => config('play.package_name'),
                'kind' => $catalog['kind'],
                'status' => 'active',
                'expires_at' => $verified['expires_at'],
                'payload' => $verified['payload'],
            ]
        );

        if (! $verified['acknowledged']) {
            try {
                $this->verifier->acknowledge($purchaseToken, $productId, $catalog['kind']);
                $purchase->acknowledged_at = now();
                $purchase->save();
            } catch (\Throwable $e) {
                Log::warning('Play Billing: accusé de réception impossible', [
                    'user_id' => $user->id,
                    'product_id' => $productId,
                    'error' => $e->getMessage(),
                ]);
            }
        } elseif (! $purchase->acknowledged_at) {
            $purchase->acknowledged_at = now();
            $purchase->save();
        }

        $this->grantEntitlement($user, $purchase, $entrepriseId);

        return ['purchase' => $purchase->fresh(), 'granted' => true];
    }

    public function refresh(PlayPurchase $purchase): PlayPurchase
    {
        try {
            $verified = $this->verifier->verify($purchase->purchase_token, $purchase->product_id, $purchase->kind);
        } catch (\Throwable $e) {
            Log::warning('Play Billing: rafraîchissement impossible', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage(),
            ]);

            return $purchase;
        }

        $purchase->status = $verified['valid'] ? 'active' : 'expired';
        $purchase->expires_at = $verified['expires_at'] ?? $purchase->expires_at;
        $purchase->order_id = $verified['order_id'] ?: $purchase->order_id;
        $purchase->payload = $verified['payload'];
        $purchase->save();

        if ($purchase->user) {
            if ($verified['valid']) {
                $this->grantEntitlement($purchase->user, $purchase, $purchase->entreprise_id);
            } else {
                $this->clearPlayProviderIfExpired($purchase->user);
            }
        }

        return $purchase;
    }

    private function grantEntitlement(User $user, PlayPurchase $purchase, ?int $entrepriseId): void
    {
        if ($purchase->grants === 'premium') {
            $user->refresh();
            if ($user->payment_provider === 'stripe' && PremiumAccessService::hasPremiumUntil($user)) {
                Log::info('Play Billing: Premium Stripe actif, payment_provider non écrasé', [
                    'user_id' => $user->id,
                    'purchase_id' => $purchase->id,
                ]);

                return;
            }

            $user->forceFill([
                'payment_provider' => 'play',
                'provider_customer_id' => $purchase->purchase_token,
            ])->save();

            return;
        }

        if (! in_array($purchase->grants, ['site_web', 'multi_personnes'], true)) {
            return;
        }

        $entreprise = $entrepriseId
            ? Entreprise::query()->find($entrepriseId)
            : $user->entreprises()->orderBy('id')->first();

        if (! $entreprise || ! $entreprise->peutEtreGereePar($user)) {
            throw new InvalidArgumentException('Entreprise introuvable pour cet add-on Play.');
        }

        $purchase->entreprise_id = $entreprise->id;
        $purchase->save();

        EntrepriseSubscription::query()->updateOrCreate(
            [
                'entreprise_id' => $entreprise->id,
                'type' => $purchase->grants,
            ],
            [
                'name' => 'play_'.$purchase->grants,
                'payment_provider' => 'play',
                'provider_subscription_id' => $purchase->purchase_token,
                'provider_payload' => $purchase->payload,
                'est_manuel' => false,
                'actif_jusqu' => $purchase->expires_at?->toDateString(),
                'date_debut' => now()->toDateString(),
            ]
        );
    }

    /**
     * @return array{id: string, kind: string, grants: string}|null
     */
    public function catalogEntry(string $productId): ?array
    {
        foreach (config('play.products', []) as $entry) {
            if (($entry['id'] ?? null) === $productId) {
                return $entry;
            }
        }

        return null;
    }

    private function clearPlayProviderIfExpired(User $user): void
    {
        $user->refresh();
        if ($user->payment_provider !== 'play') {
            return;
        }

        if ($user->hasActivePlayPremium()) {
            return;
        }

        $user->forceFill([
            'payment_provider' => null,
            'provider_customer_id' => null,
        ])->save();
    }
}
