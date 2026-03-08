<?php

namespace App\Services\Payments\Providers;

use App\Models\Echeance;
use App\Models\Tarif;
use App\Models\User;
use App\Services\PaymentVerificationService;
use App\Services\Payments\Contracts\PaymentProviderInterface;
use App\Services\StripeCustomerService;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class StripeProvider implements PaymentProviderInterface
{
    public function key(): string
    {
        return Echeance::PROVIDER_STRIPE;
    }

    public function chargeOffSession(Echeance $echeance, User $user, int $retryCount = 0): array
    {
        $montant = (float) ($echeance->montant_final ?? $echeance->montant_du ?? 0);
        if ($montant <= 0) {
            return ['status' => 'skip', 'payment_intent_id' => null, 'message' => 'Montant nul'];
        }
        if (empty($user->stripe_payment_method_id)) {
            return ['status' => 'skip', 'payment_intent_id' => null, 'message' => 'Aucun moyen de paiement'];
        }

        $customerId = StripeCustomerService::ensureCustomer($user);
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => (int) round($montant * 100),
                'currency' => Tarif::currency(),
                'customer' => $customerId,
                'payment_method' => $user->stripe_payment_method_id,
                'off_session' => true,
                'confirm' => true,
                'metadata' => [
                    'user_id' => (string) $user->id,
                    'echeance_id' => (string) $echeance->id,
                    'auto_charge' => 'true',
                    'retry_count' => (string) $retryCount,
                ],
            ]);
        } catch (\Stripe\Exception\CardException $e) {
            return [
                'status' => 'failed',
                'payment_intent_id' => null,
                'message' => $e->getMessage(),
            ];
        }

        $status = (string) ($paymentIntent->status ?? '');
        if ($status === 'succeeded') {
            return [
                'status' => 'ok',
                'payment_intent_id' => $paymentIntent->id,
                'message' => null,
            ];
        }
        if ($status === 'requires_action') {
            return [
                'status' => 'requires_action',
                'payment_intent_id' => $paymentIntent->id,
                'message' => 'Authentication required',
            ];
        }

        return [
            'status' => 'skip',
            'payment_intent_id' => $paymentIntent->id ?? null,
            'message' => 'Statut inattendu: ' . $status,
        ];
    }

    public function verifyPaymentIntent(string $paymentIntentId): array
    {
        $result = PaymentVerificationService::markEcheancePaidFromPaymentIntent($paymentIntentId);

        return [
            'ok' => (bool) ($result['ok'] ?? false),
            'already' => (bool) ($result['already'] ?? false),
            'message' => (string) ($result['message'] ?? ''),
        ];
    }

    public function verifyCheckoutSession(string $sessionId): array
    {
        $result = PaymentVerificationService::verifyAndMarkPaid($sessionId);

        return [
            'ok' => (bool) ($result['ok'] ?? false),
            'already' => (bool) ($result['already'] ?? false),
            'message' => (string) ($result['message'] ?? ''),
        ];
    }
}
