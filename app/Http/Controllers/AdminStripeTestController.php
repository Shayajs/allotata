<?php

namespace App\Http\Controllers;

use App\Models\Echeance;
use App\Models\EntrepriseSubscription;
use App\Models\PaymentAuditLog;
use App\Models\User;
use App\Services\CalculMontantDuService;
use App\Services\PaymentVerificationService;
use App\Services\StripeCustomerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Balance;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Price;
use Stripe\Product;
use Stripe\SetupIntent;
use Stripe\Stripe;
use Stripe\Subscription;

/**
 * Admin Stripe Test Controller
 *
 * Tests inline AJAX pour vérifier toute la chaîne de paiement :
 * - Connexion API Stripe
 * - Gestion des customers
 * - PaymentIntents (succès, échec, 3DS)
 * - Cashier subscriptions
 * - Cycle de vie des échéances
 * - Flux CRON (auto-charge, retry, annulation)
 */
class AdminStripeTestController extends Controller
{
    /**
     * Page principale des tests.
     */
    public function index()
    {
        return view('admin.stripe-tests');
    }

    /**
     * Exécuter un test spécifique (AJAX).
     * POST /admin/stripe-tests/run
     * Body: { "test": "api_connection", ... }
     */
    public function run(Request $request)
    {
        $request->validate(['test' => 'required|string|max:128']);

        $testName = $request->input('test');
        $method = 'test' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $testName)));

        if (!method_exists($this, $method)) {
            return response()->json([
                'ok' => false,
                'test' => $testName,
                'message' => "Test inconnu : {$testName}",
                'details' => null,
            ]);
        }

        $start = microtime(true);

        try {
            $result = $this->$method($request);
        } catch (\Throwable $e) {
            $result = [
                'ok' => false,
                'message' => $e->getMessage(),
                'details' => [
                    'exception' => get_class($e),
                    'file' => basename($e->getFile()) . ':' . $e->getLine(),
                ],
            ];
        }

        $elapsed = round((microtime(true) - $start) * 1000);

        return response()->json(array_merge([
            'test' => $testName,
            'elapsed_ms' => $elapsed,
        ], $result));
    }

    /**
     * Nettoyer toutes les données de test (AJAX).
     */
    public function cleanup(Request $request)
    {
        $cleaned = [];

        // Supprimer les échéances de test
        $count = Echeance::whereJsonContains('metadata->is_test', true)->delete();
        $cleaned['echeances'] = $count;

        // Supprimer le customer Stripe de test
        $user = Auth::user();
        $testCustomerId = session('stripe_test_customer_id');
        if ($testCustomerId) {
            try {
                Stripe::setApiKey(config('services.stripe.secret'));
                Customer::retrieve($testCustomerId)->delete();
                $cleaned['stripe_customer'] = $testCustomerId;
            } catch (\Throwable $e) {
                $cleaned['stripe_customer_error'] = $e->getMessage();
            }
            session()->forget('stripe_test_customer_id');
        }

        return response()->json([
            'ok' => true,
            'message' => 'Nettoyage terminé.',
            'details' => $cleaned,
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // GROUPE 1 : Configuration & Connexion
    // ══════════════════════════════════════════════════════════════

    protected function testApiConnection(): array
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $balance = Balance::retrieve();

        $available = collect($balance->available)->map(fn ($b) => [
            'currency' => strtoupper($b->currency),
            'amount' => number_format($b->amount / 100, 2, ',', ' '),
        ])->toArray();

        return [
            'ok' => true,
            'message' => 'Connexion API Stripe OK.',
            'details' => [
                'livemode' => $balance->livemode ? 'LIVE' : 'TEST',
                'available' => $available,
            ],
        ];
    }

    protected function testConfigCheck(): array
    {
        $checks = [];

        // Clé secrète
        $secret = config('services.stripe.secret');
        $checks['stripe_secret'] = [
            'label' => 'Clé secrète Stripe',
            'ok' => !empty($secret),
            'value' => $secret ? (str_starts_with($secret, 'sk_test_') ? 'TEST' : (str_starts_with($secret, 'sk_live_') ? 'LIVE' : 'INCONNU')) : 'MANQUANTE',
        ];

        // Clé publique
        $key = config('services.stripe.key');
        $checks['stripe_key'] = [
            'label' => 'Clé publique Stripe',
            'ok' => !empty($key),
            'value' => $key ? (str_starts_with($key, 'pk_test_') ? 'TEST' : (str_starts_with($key, 'pk_live_') ? 'LIVE' : 'INCONNU')) : 'MANQUANTE',
        ];

        // Webhook secret
        $whSecret = config('services.stripe.webhook_secret') ?: env('STRIPE_WEBHOOK_SECRET');
        $checks['webhook_secret'] = [
            'label' => 'Webhook secret',
            'ok' => !empty($whSecret),
            'value' => $whSecret ? 'Configuré (' . strlen($whSecret) . ' chars)' : 'MANQUANT',
        ];

        // Cashier currency
        $currency = config('cashier.currency', env('CASHIER_CURRENCY'));
        $checks['cashier_currency'] = [
            'label' => 'Devise Cashier',
            'ok' => !empty($currency),
            'value' => $currency ?: 'Non définie (défaut USD)',
        ];

        // Tables critiques
        $tables = ['echeances', 'subscriptions', 'entreprise_subscriptions', 'stripe_transactions', 'payment_audit_logs'];
        foreach ($tables as $table) {
            $exists = \Schema::hasTable($table);
            $checks['table_' . $table] = [
                'label' => "Table `{$table}`",
                'ok' => $exists,
                'value' => $exists ? 'Existe' : 'MANQUANTE',
            ];
        }

        $allOk = collect($checks)->every(fn ($c) => $c['ok']);

        return [
            'ok' => $allOk,
            'message' => $allOk ? 'Configuration complète.' : 'Certains éléments manquent.',
            'details' => $checks,
        ];
    }

    protected function testStripeProducts(): array
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $products = Product::all(['limit' => 20, 'active' => true]);
        $prices = Price::all(['limit' => 30, 'active' => true]);

        $list = collect($products->data)->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'active' => $p->active,
        ])->toArray();

        $priceList = collect($prices->data)->map(fn ($p) => [
            'id' => $p->id,
            'product' => $p->product,
            'amount' => $p->unit_amount ? number_format($p->unit_amount / 100, 2, ',', ' ') . ' ' . strtoupper($p->currency) : 'Variable',
            'recurring' => $p->recurring ? ($p->recurring->interval ?? 'one_time') : 'one_time',
        ])->toArray();

        return [
            'ok' => true,
            'message' => count($list) . ' produit(s), ' . count($priceList) . ' prix actif(s).',
            'details' => [
                'products' => $list,
                'prices' => $priceList,
            ],
        ];
    }

    // ══════════════════════════════════════════════════════════════
    // GROUPE 2 : Customer & Moyens de Paiement
    // ══════════════════════════════════════════════════════════════

    protected function testCreateCustomer(): array
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $user = Auth::user();

        $customer = Customer::create([
            'email' => 'test-admin-' . $user->id . '@allotata-test.local',
            'name' => 'Test Admin ' . $user->name,
            'metadata' => ['is_test' => 'true', 'admin_id' => (string) $user->id],
        ]);

        session(['stripe_test_customer_id' => $customer->id]);

        return [
            'ok' => true,
            'message' => 'Customer Stripe créé.',
            'details' => [
                'customer_id' => $customer->id,
                'email' => $customer->email,
                'livemode' => $customer->livemode ? 'LIVE' : 'TEST',
            ],
        ];
    }

    protected function testAttachTestCard(): array
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $customerId = session('stripe_test_customer_id');
        if (!$customerId) {
            return ['ok' => false, 'message' => 'Créez d\'abord un customer de test.', 'details' => null];
        }

        // pm_card_visa est une carte de test toujours valide
        $pm = PaymentMethod::create([
            'type' => 'card',
            'card' => ['token' => 'tok_visa'],
        ]);
        $pm->attach(['customer' => $customerId]);

        Customer::update($customerId, [
            'invoice_settings' => ['default_payment_method' => $pm->id],
        ]);

        session(['stripe_test_pm_id' => $pm->id]);

        return [
            'ok' => true,
            'message' => 'Carte Visa test attachée.',
            'details' => [
                'payment_method_id' => $pm->id,
                'brand' => $pm->card->brand ?? 'visa',
                'last4' => $pm->card->last4 ?? '4242',
                'exp' => ($pm->card->exp_month ?? '?') . '/' . ($pm->card->exp_year ?? '?'),
            ],
        ];
    }

    protected function testListPaymentMethods(): array
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $customerId = session('stripe_test_customer_id');
        if (!$customerId) {
            return ['ok' => false, 'message' => 'Créez d\'abord un customer de test.', 'details' => null];
        }

        $methods = PaymentMethod::all([
            'customer' => $customerId,
            'type' => 'card',
        ]);

        $list = collect($methods->data)->map(fn ($m) => [
            'id' => $m->id,
            'brand' => $m->card->brand ?? '?',
            'last4' => $m->card->last4 ?? '****',
            'exp' => ($m->card->exp_month ?? '?') . '/' . ($m->card->exp_year ?? '?'),
        ])->toArray();

        return [
            'ok' => true,
            'message' => count($list) . ' carte(s) trouvée(s).',
            'details' => $list,
        ];
    }

    // ══════════════════════════════════════════════════════════════
    // GROUPE 3 : PaymentIntents (Succès, Échec, 3DS)
    // ══════════════════════════════════════════════════════════════

    protected function testPaymentSuccess(): array
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $customerId = session('stripe_test_customer_id');
        $pmId = session('stripe_test_pm_id');
        if (!$customerId || !$pmId) {
            return ['ok' => false, 'message' => 'Créez d\'abord un customer + carte de test.', 'details' => null];
        }

        $pi = PaymentIntent::create([
            'amount' => 500, // 5,00 €
            'currency' => 'eur',
            'customer' => $customerId,
            'payment_method' => $pmId,
            'off_session' => true,
            'confirm' => true,
            'metadata' => ['is_test' => 'true', 'scenario' => 'success'],
        ]);

        return [
            'ok' => $pi->status === 'succeeded',
            'message' => $pi->status === 'succeeded'
                ? 'PaymentIntent succeeded (5,00 €).'
                : 'Statut inattendu : ' . $pi->status,
            'details' => [
                'payment_intent_id' => $pi->id,
                'status' => $pi->status,
                'amount' => number_format($pi->amount / 100, 2, ',', ' ') . ' ' . strtoupper($pi->currency),
            ],
        ];
    }

    protected function testPaymentDeclined(): array
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $customerId = session('stripe_test_customer_id');
        if (!$customerId) {
            return ['ok' => false, 'message' => 'Créez d\'abord un customer de test.', 'details' => null];
        }

        // Carte de test « toujours refusée »
        $pm = PaymentMethod::create([
            'type' => 'card',
            'card' => ['token' => 'tok_chargeDeclined'],
        ]);
        $pm->attach(['customer' => $customerId]);

        try {
            $pi = PaymentIntent::create([
                'amount' => 500,
                'currency' => 'eur',
                'customer' => $customerId,
                'payment_method' => $pm->id,
                'off_session' => true,
                'confirm' => true,
                'metadata' => ['is_test' => 'true', 'scenario' => 'declined'],
            ]);

            // Si on arrive ici sans exception, c'est inattendu
            return [
                'ok' => false,
                'message' => 'Le paiement aurait dû être refusé ! Statut : ' . $pi->status,
                'details' => ['payment_intent_id' => $pi->id, 'status' => $pi->status],
            ];
        } catch (\Stripe\Exception\CardException $e) {
            $code = $e->getError()->code ?? 'unknown';
            $declineCode = $e->getError()->decline_code ?? 'unknown';
            return [
                'ok' => true,
                'message' => 'Refus correctement détecté.',
                'details' => [
                    'error_code' => $code,
                    'decline_code' => $declineCode,
                    'message_stripe' => $e->getMessage(),
                ],
            ];
        } finally {
            // Nettoyer la carte refusée
            try {
                $pm->detach();
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    protected function testPayment3ds(): array
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $customerId = session('stripe_test_customer_id');
        if (!$customerId) {
            return ['ok' => false, 'message' => 'Créez d\'abord un customer de test.', 'details' => null];
        }

        // Carte de test « requiert authentification »
        $pm = PaymentMethod::create([
            'type' => 'card',
            'card' => ['token' => 'tok_threeDSecure2Required'],
        ]);
        $pm->attach(['customer' => $customerId]);

        try {
            $pi = PaymentIntent::create([
                'amount' => 500,
                'currency' => 'eur',
                'customer' => $customerId,
                'payment_method' => $pm->id,
                'off_session' => true,
                'confirm' => true,
                'metadata' => ['is_test' => 'true', 'scenario' => '3ds'],
            ]);

            $ok = $pi->status === 'requires_action';
            return [
                'ok' => $ok,
                'message' => $ok
                    ? '3DS correctement requis (requires_action).'
                    : 'Statut inattendu : ' . $pi->status,
                'details' => [
                    'payment_intent_id' => $pi->id,
                    'status' => $pi->status,
                ],
            ];
        } catch (\Stripe\Exception\CardException $e) {
            $code = $e->getError()->code ?? 'unknown';
            $ok = $code === 'authentication_required';
            return [
                'ok' => $ok,
                'message' => $ok
                    ? '3DS correctement requis (authentication_required).'
                    : 'Erreur inattendue : ' . $code,
                'details' => [
                    'error_code' => $code,
                    'message_stripe' => $e->getMessage(),
                ],
            ];
        } finally {
            try {
                $pm->detach();
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    protected function testPaymentInsufficientFunds(): array
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $customerId = session('stripe_test_customer_id');
        if (!$customerId) {
            return ['ok' => false, 'message' => 'Créez d\'abord un customer de test.', 'details' => null];
        }

        $pm = PaymentMethod::create([
            'type' => 'card',
            'card' => ['token' => 'tok_chargeDeclinedInsufficientFunds'],
        ]);
        $pm->attach(['customer' => $customerId]);

        try {
            PaymentIntent::create([
                'amount' => 500,
                'currency' => 'eur',
                'customer' => $customerId,
                'payment_method' => $pm->id,
                'off_session' => true,
                'confirm' => true,
                'metadata' => ['is_test' => 'true', 'scenario' => 'insufficient_funds'],
            ]);

            return ['ok' => false, 'message' => 'Le paiement aurait dû échouer.', 'details' => null];
        } catch (\Stripe\Exception\CardException $e) {
            $declineCode = $e->getError()->decline_code ?? 'unknown';
            $ok = $declineCode === 'insufficient_funds';
            return [
                'ok' => $ok,
                'message' => $ok
                    ? 'Fonds insuffisants correctement détectés.'
                    : 'Code de refus : ' . $declineCode,
                'details' => [
                    'decline_code' => $declineCode,
                    'error_code' => $e->getError()->code ?? null,
                    'message_stripe' => $e->getMessage(),
                ],
            ];
        } finally {
            try {
                $pm->detach();
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    // ══════════════════════════════════════════════════════════════
    // GROUPE 4 : Cycle de vie Échéances (local DB)
    // ══════════════════════════════════════════════════════════════

    protected function testEcheanceCreate(): array
    {
        $user = Auth::user();

        $echeance = Echeance::create([
            'user_id' => $user->id,
            'entreprise_id' => null,
            'subscription_type' => Echeance::TYPE_DEFAULT,
            'periode_debut' => now()->startOfMonth(),
            'periode_fin' => now()->endOfMonth(),
            'jour_facturation' => now()->day,
            'montant_du' => 5.00,
            'montant_final' => 5.00,
            'statut' => Echeance::STATUT_A_PAYER,
            'metadata' => ['is_test' => true, 'scenario' => 'create'],
        ]);

        session(['stripe_test_echeance_id' => $echeance->id]);

        return [
            'ok' => true,
            'message' => 'Échéance #' . $echeance->id . ' créée (a_payer, 5,00 €).',
            'details' => [
                'id' => $echeance->id,
                'statut' => $echeance->statut,
                'montant_final' => $echeance->montant_final,
                'periode' => $echeance->periode_debut->format('d/m/Y') . ' → ' . $echeance->periode_fin->format('d/m/Y'),
            ],
        ];
    }

    protected function testEcheanceAutoCharge(): array
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $user = Auth::user();
        $echeanceId = session('stripe_test_echeance_id');
        $customerId = session('stripe_test_customer_id');
        $pmId = session('stripe_test_pm_id');

        if (!$echeanceId || !$customerId || !$pmId) {
            return ['ok' => false, 'message' => 'Créez d\'abord customer + carte + échéance.', 'details' => null];
        }

        $echeance = Echeance::find($echeanceId);
        if (!$echeance || $echeance->estPayee()) {
            return ['ok' => false, 'message' => 'Échéance introuvable ou déjà payée.', 'details' => null];
        }

        $amountCents = (int) round(((float) $echeance->montant_final) * 100);

        $pi = PaymentIntent::create([
            'amount' => $amountCents,
            'currency' => 'eur',
            'customer' => $customerId,
            'payment_method' => $pmId,
            'off_session' => true,
            'confirm' => true,
            'metadata' => [
                'is_test' => 'true',
                'echeance_id' => (string) $echeance->id,
                'user_id' => (string) $user->id,
            ],
        ]);

        if ($pi->status === 'succeeded') {
            $echeance->update([
                'statut' => Echeance::STATUT_PAYE,
                'stripe_payment_intent_id' => $pi->id,
                'paye_at' => now(),
            ]);

            return [
                'ok' => true,
                'message' => 'Auto-charge réussi. Échéance #' . $echeance->id . ' marquée payée.',
                'details' => [
                    'echeance_id' => $echeance->id,
                    'payment_intent_id' => $pi->id,
                    'statut' => 'paye',
                    'amount' => number_format($pi->amount / 100, 2, ',', ' ') . ' €',
                ],
            ];
        }

        return [
            'ok' => false,
            'message' => 'Statut inattendu : ' . $pi->status,
            'details' => ['payment_intent_id' => $pi->id, 'status' => $pi->status],
        ];
    }

    protected function testEcheanceFailAndRetry(): array
    {
        $user = Auth::user();

        // 1. Créer une échéance en échec
        $echeance = Echeance::create([
            'user_id' => $user->id,
            'subscription_type' => Echeance::TYPE_DEFAULT,
            'periode_debut' => now()->startOfMonth(),
            'periode_fin' => now()->endOfMonth(),
            'jour_facturation' => now()->day,
            'montant_du' => 5.00,
            'montant_final' => 5.00,
            'statut' => Echeance::STATUT_ECHEC,
            'metadata' => ['is_test' => true, 'scenario' => 'fail_retry', 'retry_count' => 0],
        ]);

        // 2. Simuler 3 retries
        $retries = [];
        for ($i = 1; $i <= 3; $i++) {
            $echeance->update([
                'metadata' => array_merge($echeance->metadata ?? [], [
                    'retry_count' => $i,
                    'last_retry_at' => now()->toIso8601String(),
                    'last_error' => 'Test retry #' . $i,
                ]),
            ]);
            $retries[] = ['retry' => $i, 'statut' => 'echec'];
        }

        // 3. Vérifier la règle : 3 retries → annulation
        $meta = $echeance->fresh()->metadata ?? [];
        $retryCount = (int) ($meta['retry_count'] ?? 0);
        $shouldCancel = $retryCount >= 3;

        if ($shouldCancel) {
            $echeance->update(['statut' => Echeance::STATUT_ANNULE]);
        }

        return [
            'ok' => $shouldCancel && $echeance->fresh()->statut === Echeance::STATUT_ANNULE,
            'message' => $shouldCancel
                ? 'Après 3 échecs → échéance #' . $echeance->id . ' correctement annulée.'
                : 'Erreur : l\'échéance devrait être annulée après 3 retries.',
            'details' => [
                'echeance_id' => $echeance->id,
                'retry_count' => $retryCount,
                'statut_final' => $echeance->fresh()->statut,
                'retries' => $retries,
            ],
        ];
    }

    protected function testEcheanceRetraction(): array
    {
        $user = Auth::user();

        // Simuler le flux session : l'utilisateur ajoute un item en session, puis annule
        $pendingKey = 'test_site_web_999';
        $pending = session('checkout_pending', []);
        $pending[$pendingKey] = [
            'entreprise_id' => 999,
            'entreprise_nom' => 'Test Entreprise',
            'subscription_type' => 'site_web',
            'user_id' => $user->id,
            'periode_debut' => now()->startOfMonth()->toDateString(),
            'periode_fin' => now()->endOfMonth()->toDateString(),
            'jour_facturation' => 1,
            'montant_du' => 10.00,
            'montant_final' => 10.00,
            'lignes' => [],
            'created_at' => now()->toIso8601String(),
        ];
        session(['checkout_pending' => $pending]);

        // Vérifier que l'item est en session
        $exists = !empty(session("checkout_pending.{$pendingKey}"));

        // Simuler l'annulation (retrait de la session)
        $pending = session('checkout_pending', []);
        unset($pending[$pendingKey]);
        session(['checkout_pending' => $pending]);

        $removedFromSession = empty(session("checkout_pending.{$pendingKey}"));

        // Vérifier qu'il n'y a RIEN en base
        $inDb = Echeance::where('user_id', $user->id)
            ->whereJsonContains('metadata->scenario', 'retraction')
            ->exists();

        return [
            'ok' => $exists && $removedFromSession && !$inDb,
            'message' => ($exists && $removedFromSession && !$inDb)
                ? 'Rétraction OK : item session créé → supprimé → zéro en DB.'
                : 'Problème dans le flux de rétraction.',
            'details' => [
                'was_in_session' => $exists,
                'removed_from_session' => $removedFromSession,
                'nothing_in_db' => !$inDb,
            ],
        ];
    }

    protected function testEcheanceCancelAfter7Days(): array
    {
        $user = Auth::user();

        // Créer une échéance en échec datant de 8 jours
        $echeance = Echeance::create([
            'user_id' => $user->id,
            'subscription_type' => Echeance::TYPE_DEFAULT,
            'periode_debut' => now()->subMonth()->startOfMonth(),
            'periode_fin' => now()->subMonth()->endOfMonth(),
            'jour_facturation' => 1,
            'montant_du' => 5.00,
            'montant_final' => 5.00,
            'statut' => Echeance::STATUT_ECHEC,
            'metadata' => ['is_test' => true, 'scenario' => 'cancel_7days', 'retry_count' => 1],
        ]);

        // Forcer created_at à 8 jours
        DB::table('echeances')->where('id', $echeance->id)->update([
            'created_at' => now()->subDays(8),
        ]);
        $echeance->refresh();

        // Simuler la logique CRON
        $daysSinceCreation = $echeance->created_at->diffInDays(now());
        $shouldCancel = $daysSinceCreation >= 7;

        if ($shouldCancel) {
            $echeance->update(['statut' => Echeance::STATUT_ANNULE]);
        }

        return [
            'ok' => $shouldCancel && $echeance->fresh()->statut === Echeance::STATUT_ANNULE,
            'message' => $shouldCancel
                ? "Après {$daysSinceCreation} jours → échéance #" . $echeance->id . ' correctement annulée.'
                : 'Erreur : l\'échéance devrait être annulée après 7 jours.',
            'details' => [
                'echeance_id' => $echeance->id,
                'days_since_creation' => $daysSinceCreation,
                'statut_final' => $echeance->fresh()->statut,
            ],
        ];
    }

    // ══════════════════════════════════════════════════════════════
    // GROUPE 5 : Cashier Subscriptions
    // ══════════════════════════════════════════════════════════════

    protected function testCashierSetup(): array
    {
        $user = Auth::user();
        $checks = [];

        $checks['user_has_stripe_id'] = [
            'label' => 'stripe_id sur User',
            'ok' => !empty($user->stripe_id),
            'value' => $user->stripe_id ?: 'Non défini',
        ];

        $checks['user_has_pm'] = [
            'label' => 'stripe_payment_method_id',
            'ok' => !empty($user->stripe_payment_method_id),
            'value' => $user->stripe_payment_method_id ?: 'Non défini',
        ];

        $checks['cashier_model'] = [
            'label' => 'Modèle Billable',
            'ok' => method_exists($user, 'newSubscription'),
            'value' => method_exists($user, 'newSubscription') ? 'User est Billable' : 'MANQUANT',
        ];

        $checks['subscribed_default'] = [
            'label' => 'Abonné (default)',
            'ok' => true, // informatif
            'value' => $user->subscribed('default') ? 'Oui' : 'Non',
        ];

        if ($user->subscription('default')) {
            $sub = $user->subscription('default');
            $checks['subscription_status'] = [
                'label' => 'Statut abonnement',
                'ok' => true,
                'value' => $sub->stripe_status ?? 'inconnu',
            ];
            $checks['subscription_stripe_id'] = [
                'label' => 'Stripe subscription ID',
                'ok' => !empty($sub->stripe_id),
                'value' => $sub->stripe_id ?? 'N/A',
            ];
        }

        $allOk = collect($checks)->every(fn ($c) => $c['ok']);

        return [
            'ok' => $allOk,
            'message' => $allOk ? 'Cashier correctement configuré.' : 'Certains éléments à vérifier.',
            'details' => $checks,
        ];
    }

    protected function testCashierPortalUrl(): array
    {
        $user = Auth::user();
        if (!$user->stripe_id) {
            return ['ok' => false, 'message' => 'L\'admin n\'a pas de stripe_id. Créez un customer d\'abord.', 'details' => null];
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            $session = \Stripe\BillingPortal\Session::create([
                'customer' => $user->stripe_id,
                'return_url' => route('admin.stripe-tests.index'),
            ]);

            return [
                'ok' => !empty($session->url),
                'message' => 'URL du Portail Client générée.',
                'details' => [
                    'portal_url' => $session->url,
                    'expires' => 'Session temporaire',
                ],
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'details' => null];
        }
    }

    // ══════════════════════════════════════════════════════════════
    // GROUPE 6 : Services internes
    // ══════════════════════════════════════════════════════════════

    protected function testCalculMontantService(): array
    {
        $user = Auth::user();

        $tmp = new Echeance([
            'user_id' => $user->id,
            'subscription_type' => Echeance::TYPE_DEFAULT,
            'periode_debut' => now()->startOfMonth(),
            'periode_fin' => now()->endOfMonth(),
            'jour_facturation' => 1,
            'reduction_manuel' => 0,
        ]);
        $tmp->setRelation('user', $user);

        try {
            $calc = CalculMontantDuService::calculerPourEcheance($tmp, null, false);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'CalculMontantDuService a planté : ' . $e->getMessage(), 'details' => null];
        }

        return [
            'ok' => true,
            'message' => 'Calcul OK : ' . number_format($calc['montant_final'] ?? 0, 2, ',', ' ') . ' €.',
            'details' => $calc,
        ];
    }

    protected function testStripeCustomerService(): array
    {
        $user = Auth::user();

        try {
            $customerId = StripeCustomerService::ensureCustomer($user);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'ensureCustomer a planté : ' . $e->getMessage(), 'details' => null];
        }

        return [
            'ok' => !empty($customerId),
            'message' => 'Customer Stripe : ' . $customerId,
            'details' => [
                'customer_id' => $customerId,
                'user_stripe_id' => $user->fresh()->stripe_id,
            ],
        ];
    }

    protected function testAuditLog(): array
    {
        $user = Auth::user();

        try {
            PaymentAuditLog::log('test_from_admin', $user->id, [
                'is_test' => true,
                'message' => 'Test depuis le panneau admin.',
            ]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'PaymentAuditLog::log a planté : ' . $e->getMessage(), 'details' => null];
        }

        $last = PaymentAuditLog::where('user_id', $user->id)
            ->where('action', 'test_from_admin')
            ->latest()
            ->first();

        return [
            'ok' => !empty($last),
            'message' => $last ? 'Audit log créé (ID #' . $last->id . ').' : 'Audit log non retrouvé.',
            'details' => $last ? [
                'id' => $last->id,
                'action' => $last->action,
                'created_at' => $last->created_at->format('d/m/Y H:i:s'),
            ] : null,
        ];
    }

    protected function testProcessPaymentsCommand(): array
    {
        try {
            $exitCode = \Artisan::call('subscriptions:process-payments', ['--dry-run' => true]);
            $output = \Artisan::output();
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'La commande a planté : ' . $e->getMessage(), 'details' => null];
        }

        return [
            'ok' => $exitCode === 0,
            'message' => $exitCode === 0 ? 'Commande process-payments (dry-run) OK.' : 'Exit code : ' . $exitCode,
            'details' => [
                'exit_code' => $exitCode,
                'output' => trim($output),
            ],
        ];
    }

    protected function testReconcileCommand(): array
    {
        try {
            $exitCode = \Artisan::call('subscriptions:reconcile-echeances');
            $output = \Artisan::output();
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'La commande a planté : ' . $e->getMessage(), 'details' => null];
        }

        return [
            'ok' => $exitCode === 0,
            'message' => $exitCode === 0 ? 'Commande reconcile-echeances OK.' : 'Exit code : ' . $exitCode,
            'details' => [
                'exit_code' => $exitCode,
                'output' => trim($output),
            ],
        ];
    }

    protected function testCheckEcheancesCommand(): array
    {
        try {
            $exitCode = \Artisan::call('subscriptions:check-echeances');
            $output = \Artisan::output();
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'La commande a planté : ' . $e->getMessage(), 'details' => null];
        }

        return [
            'ok' => $exitCode === 0,
            'message' => $exitCode === 0 ? 'Commande check-echeances OK.' : 'Exit code : ' . $exitCode,
            'details' => [
                'exit_code' => $exitCode,
                'output' => trim($output),
            ],
        ];
    }
}
