<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Stripe\Balance;
use Stripe\Checkout\Session as StripeSession;
use Stripe\PaymentIntent;
use Stripe\SetupIntent;
use Stripe\Stripe;

/**
 * Service de test Stripe (admin Tarifs) : vérification des clés, paiement test.
 *
 * Flux recommandé : Setup Intent (enregistrer la carte) → Débit API (0,50 €).
 * Si le Setup fonctionne, le débit fonctionnera au niveau API (sans interaction utilisateur).
 * Utile pour X jours gratuits : enregistrer la carte à l'inscription, débiter après l'essai.
 */
class StripeTestService
{
    /**
     * Vérifie que les clés Stripe permettent une communication avec l'API.
     *
     * @return array{ok: bool, message: string, livemode?: bool}
     */
    public static function verifyKeys(): array
    {
        $secret = config('services.stripe.secret');
        if (!$secret || !str_starts_with((string) $secret, 'sk_')) {
            return ['ok' => false, 'message' => 'Clé secrète Stripe manquante ou invalide (STRIPE_SECRET).'];
        }

        Stripe::setApiKey($secret);
        try {
            $balance = Balance::retrieve();
            $livemode = $balance->livemode ?? false;
            return [
                'ok' => true,
                'message' => 'Communication Stripe OK.' . ($livemode ? ' (Mode live)' : ' (Mode test)'),
                'livemode' => $livemode,
            ];
        } catch (\Stripe\Exception\AuthenticationException $e) {
            Log::warning('StripeTestService: auth failed', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'Clé secrète invalide ou révoquée : ' . $e->getMessage()];
        } catch (\Throwable $e) {
            Log::warning('StripeTestService: verify failed', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'Erreur Stripe : ' . $e->getMessage()];
        }
    }

    /**
     * Crée une session Checkout "paiement test" (0,50 € minimum Stripe).
     * Metadata admin_test=1 pour ne pas lier à une échéance.
     *
     * @return array{ok: bool, url?: string, message: string}
     */
    public static function createTestCheckoutSession(string $successUrl, string $cancelUrl, ?string $customerEmail = null): array
    {
        $secret = config('services.stripe.secret');
        if (!$secret || !str_starts_with((string) $secret, 'sk_')) {
            return ['ok' => false, 'message' => 'Clé secrète Stripe manquante.'];
        }

        Stripe::setApiKey($secret);
        try {
            $params = [
                'mode' => 'payment',
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Test Allotata – Paiement 0,50 €',
                            'description' => 'Paiement test admin (modules Stripe)',
                        ],
                        'unit_amount' => 50, // 0,50 €
                    ],
                    'quantity' => 1,
                ]],
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => ['admin_test' => '1'],
            ];
            if ($customerEmail) {
                $params['customer_email'] = $customerEmail;
            }
            $session = StripeSession::create($params);
            return [
                'ok' => true,
                'url' => $session->url,
                'message' => 'Session créée.',
            ];
        } catch (\Throwable $e) {
            Log::warning('StripeTestService: test checkout failed', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'Erreur : ' . $e->getMessage()];
        }
    }

    /**
     * Vérifie qu'une session Stripe "admin test" est payée (sans toucher aux échéances).
     *
     * @return array{ok: bool, paid: bool, message: string}
     */
    public static function verifyTestSession(string $sessionId): array
    {
        $secret = config('services.stripe.secret');
        if (!$secret) {
            return ['ok' => false, 'paid' => false, 'message' => 'Clé secrète manquante.'];
        }

        Stripe::setApiKey($secret);
        try {
            $session = StripeSession::retrieve($sessionId);
            $meta = $session->metadata ?? [];
            if (is_object($meta)) {
                $meta = (array) $meta;
            }
            if (($meta['admin_test'] ?? '') !== '1') {
                return ['ok' => false, 'paid' => false, 'message' => 'Session non test admin.'];
            }
            $paid = ($session->mode ?? '') === 'payment' && ($session->payment_status ?? '') === 'paid';
            return [
                'ok' => true,
                'paid' => $paid,
                'message' => $paid ? 'Paiement test reçu.' : 'Paiement non effectué.',
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'paid' => false, 'message' => 'Session introuvable : ' . $e->getMessage()];
        }
    }

    /**
     * Crée un SetupIntent pour l'admin (test). Enregistrer la carte sans débiter.
     * Utilisé pour "Test Setup" : si ça passe, le débit API fonctionnera.
     *
     * @return array{ok: bool, client_secret?: string, message: string}
     */
    public static function createTestSetupIntent(User $user): array
    {
        $secret = config('services.stripe.secret');
        if (!$secret || !str_starts_with((string) $secret, 'sk_')) {
            return ['ok' => false, 'message' => 'Clé secrète Stripe manquante.'];
        }

        Stripe::setApiKey($secret);
        try {
            $customerId = StripeCustomerService::ensureCustomer($user);
            $si = SetupIntent::create([
                'customer' => $customerId,
                'usage' => 'off_session',
                'metadata' => ['user_id' => (string) $user->id, 'admin_test' => '1'],
            ]);
            return [
                'ok' => true,
                'client_secret' => $si->client_secret,
                'message' => 'SetupIntent créé.',
            ];
        } catch (\Throwable $e) {
            Log::warning('StripeTestService: test setup intent failed', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'Erreur : ' . $e->getMessage()];
        }
    }

    /**
     * Débite 0,50 € via API (PaymentIntent off_session) avec la carte enregistrée du user.
     * Aucune interaction utilisateur : si le Setup a fonctionné, le débit passe côté API.
     *
     * @return array{ok: bool, message: string}
     */
    public static function chargeTestPaymentMethod(User $user): array
    {
        $secret = config('services.stripe.secret');
        if (!$secret || !str_starts_with((string) $secret, 'sk_')) {
            return ['ok' => false, 'message' => 'Clé secrète Stripe manquante.'];
        }

        if (empty($user->stripe_payment_method_id)) {
            return ['ok' => false, 'message' => 'Aucune carte enregistrée. Lancez d\'abord « Test Setup » pour enregistrer une carte.'];
        }

        Stripe::setApiKey($secret);
        $customerId = StripeCustomerService::ensureCustomer($user);

        try {
            $pi = PaymentIntent::create([
                'amount' => 50,
                'currency' => 'eur',
                'customer' => $customerId,
                'payment_method' => $user->stripe_payment_method_id,
                'off_session' => true,
                'confirm' => true,
                'metadata' => ['admin_test' => '1', 'user_id' => (string) $user->id],
            ]);
        } catch (\Stripe\Exception\CardException $e) {
            Log::warning('StripeTestService: test charge card error', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'Carte refusée : ' . ($e->getError()->message ?? $e->getMessage())];
        } catch (\Throwable $e) {
            Log::warning('StripeTestService: test charge failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'Erreur débit : ' . $e->getMessage()];
        }

        $status = $pi->status ?? '';
        if ($status === 'succeeded') {
            return ['ok' => true, 'message' => 'Débit API (0,50 €) OK. Setup + Payment validés.'];
        }
        if ($status === 'requires_action') {
            return ['ok' => false, 'message' => '3D Secure requis. Utilisez une carte test sans 3DS (ex. 4242…) ou validez 3DS sur le checkout.'];
        }
        return ['ok' => false, 'message' => 'Statut inattendu : ' . $status];
    }
}
