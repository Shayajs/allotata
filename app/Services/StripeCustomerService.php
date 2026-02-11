<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Stripe\Customer;
use Stripe\PaymentMethod;
use Stripe\Stripe;

class StripeCustomerService
{
    /**
     * S'assure qu'un Customer Stripe existe pour le user. Crée si besoin.
     */
    public static function ensureCustomer(User $user): string
    {
        if ($user->stripe_id) {
            return $user->stripe_id;
        }

        Stripe::setApiKey(config('services.stripe.secret'));
        $customer = Customer::create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => ['user_id' => (string) $user->id],
        ]);

        // stripe_id hors du $fillable : assignation explicite
        $user->stripe_id = $customer->id;
        $user->save();
        return $customer->id;
    }

    /**
     * Attache un PaymentMethod au Customer et le définit comme défaut.
     */
    public static function attachPaymentMethod(string $customerId, string $paymentMethodId): void
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $pm = PaymentMethod::retrieve($paymentMethodId);
        if ($pm->customer && $pm->customer !== $customerId) {
            throw new \InvalidArgumentException('Ce moyen de paiement n\'appartient pas à ce client.');
        }

        if (!$pm->customer) {
            $pm->attach(['customer' => $customerId]);
        }

        Customer::update($customerId, [
            'invoice_settings' => ['default_payment_method' => $paymentMethodId],
        ]);
    }

    /**
     * Récupère pm_type et pm_last_four depuis l'objet PaymentMethod (card).
     */
    public static function cardDisplayFromPaymentMethod(string $paymentMethodId): array
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $pm = PaymentMethod::retrieve($paymentMethodId);
        $card = $pm->card ?? null;
        if (!$card) {
            return ['pm_type' => 'card', 'pm_last_four' => null];
        }
        $brand = $card->brand ?? 'card';
        $last4 = $card->last4 ?? null;
        return [
            'pm_type' => $brand,
            'pm_last_four' => $last4,
        ];
    }
}
