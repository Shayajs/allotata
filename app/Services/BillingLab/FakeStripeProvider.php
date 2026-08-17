<?php

namespace App\Services\BillingLab;

use App\Models\Echeance;
use App\Models\User;
use App\Services\PaymentVerificationService;
use App\Services\Payments\Contracts\PaymentProviderInterface;

class FakeStripeProvider implements PaymentProviderInterface
{
    public string $behavior = 'ok';

    /** @var list<array{echeance_id:int,user_id:int,amount:mixed,behavior:string,pi:string}> */
    public array $charges = [];

    /** @var array<string, int> */
    public array $paid = [];

    public function key(): string
    {
        return Echeance::PROVIDER_STRIPE;
    }

    public function chargeOffSession(Echeance $echeance, User $user, int $retryCount = 0): array
    {
        $piId = 'pi_lab_'.uniqid();
        $this->charges[] = [
            'echeance_id' => $echeance->id,
            'user_id' => $user->id,
            'amount' => $echeance->montant_final,
            'behavior' => $this->behavior,
            'pi' => $piId,
        ];

        if ($this->behavior === 'failed') {
            return [
                'status' => 'failed',
                'payment_intent_id' => $piId,
                'message' => 'card_declined',
            ];
        }

        if ($this->behavior === 'requires_action') {
            return [
                'status' => 'requires_action',
                'payment_intent_id' => $piId,
                'message' => 'Authentication required',
            ];
        }

        $this->paid[$piId] = $echeance->id;

        return [
            'status' => 'ok',
            'payment_intent_id' => $piId,
            'message' => null,
        ];
    }

    public function verifyPaymentIntent(string $paymentIntentId): array
    {
        $echeanceId = $this->paid[$paymentIntentId] ?? null;
        $echeance = $echeanceId
            ? Echeance::find($echeanceId)
            : Echeance::where('stripe_payment_intent_id', $paymentIntentId)->first();

        if (! $echeance) {
            return ['ok' => false, 'already' => false, 'message' => 'PaymentIntent lab inconnu.'];
        }

        if ($echeance->estPayee()) {
            PaymentVerificationService::ensureEntrepriseSubscriptionForEcheance($echeance);
            PaymentVerificationService::ensurePremiumAccessForEcheance($echeance);

            return ['ok' => true, 'already' => true, 'message' => 'Déjà enregistré.'];
        }

        $echeance->update([
            'statut' => Echeance::STATUT_PAYE,
            'payment_provider' => Echeance::PROVIDER_STRIPE,
            'stripe_payment_intent_id' => $paymentIntentId,
            'paye_at' => now(),
        ]);

        $echeance = $echeance->fresh();
        PaymentVerificationService::ensureEntrepriseSubscriptionForEcheance($echeance);
        PaymentVerificationService::ensurePremiumAccessForEcheance($echeance);

        return ['ok' => true, 'already' => false, 'message' => 'Paiement enregistré.'];
    }

    public function verifyCheckoutSession(string $sessionId): array
    {
        return ['ok' => false, 'already' => false, 'message' => 'Checkout session non simulée.'];
    }
}
