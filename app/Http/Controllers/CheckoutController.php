<?php

namespace App\Http\Controllers;

use App\Models\Echeance;
use App\Models\PaymentAuditLog;
use App\Models\PromoCode;
use App\Services\CalculMontantDuService;
use App\Services\PaymentVerificationService;
use App\Services\StripeCustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Stripe;
use Stripe\SetupIntent;

class CheckoutController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $echeances = Echeance::where('user_id', $user->id)
            ->whereIn('statut', [Echeance::STATUT_A_PAYER, Echeance::STATUT_EN_ATTENTE])
            ->orderBy('periode_debut')
            ->with('entreprise')
            ->get();

        $codePromo = $request->session()->get('checkout_promo_code');
        $calculs = [];
        foreach ($echeances as $e) {
            $calc = CalculMontantDuService::calculerPourEcheance($e, $codePromo);
            $calculs[$e->id] = $calc;
        }

        $hasPaymentMethod = !empty($user->stripe_payment_method_id);
        $showCardForm = !$hasPaymentMethod || $request->boolean('change_card');

        return view('checkout.index', [
            'echeances' => $echeances,
            'calculs' => $calculs,
            'codePromo' => $codePromo,
            'hasPaymentMethod' => $hasPaymentMethod,
            'showCardForm' => $showCardForm,
            'user' => $user,
        ]);
    }

    public function appliquerPromo(Request $request)
    {
        $request->validate(['code' => 'required|string|max:64']);
        $user = Auth::user();
        $promo = PromoCode::validateCode($request->input('code'), $user);
        if (!$promo) {
            return redirect()->route('checkout.index')
                ->with('error', 'Code promo invalide ou expiré.');
        }
        $request->session()->put('checkout_promo_code', $promo->code);
        return redirect()->route('checkout.index')
            ->with('success', 'Code promo appliqué.');
    }

    public function retirerPromo(Request $request)
    {
        $request->session()->forget('checkout_promo_code');
        return redirect()->route('checkout.index')
            ->with('success', 'Code promo retiré.');
    }

    /**
     * Créer un SetupIntent pour enregistrer un moyen de paiement (Elements).
     * Retourne le client_secret pour confirmSetup côté frontend.
     */
    public function createSetupIntent(Request $request)
    {
        $user = Auth::user();

        try {
            $customerId = StripeCustomerService::ensureCustomer($user);

            Stripe::setApiKey(config('services.stripe.secret'));
            $si = SetupIntent::create([
                'customer' => $customerId,
                'usage' => 'off_session',
                'payment_method_types' => ['card'],
                'metadata' => ['user_id' => (string) $user->id],
            ]);

            try {
                PaymentAuditLog::log('setup_intent_created', $user->id, [
                    'stripe_customer_id' => $customerId,
                    'stripe_setup_intent_id' => $si->id,
                    'context' => ['metadata' => $si->metadata],
                    'message' => 'SetupIntent créé pour enregistrement carte (Elements).',
                ]);
            } catch (\Throwable $e) {
                Log::warning('PaymentAuditLog setup_intent_created failed', ['error' => $e->getMessage()]);
            }

            return response()->json(['client_secret' => $si->client_secret]);
        } catch (\Throwable $e) {
            Log::error('Checkout createSetupIntent failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'Impossible de préparer le formulaire. ' . ($e->getMessage() ?: 'Réessayez.'),
            ], 500);
        }
    }

    /**
     * Sauvegarder le PaymentMethod après confirmSetup (Elements).
     * Body: { "payment_method": "pm_xxx" }
     */
    public function savePaymentMethod(Request $request)
    {
        $request->validate(['payment_method' => 'required|string|starts_with:pm_']);

        $user = Auth::user();
        $customerId = StripeCustomerService::ensureCustomer($user);
        $pmId = $request->input('payment_method');

        try {
            StripeCustomerService::attachPaymentMethod($customerId, $pmId);
            $display = StripeCustomerService::cardDisplayFromPaymentMethod($pmId);
        } catch (\Throwable $e) {
            Log::warning('Checkout save-payment-method failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            PaymentAuditLog::log('save_pm_fail', $user->id, [
                'stripe_customer_id' => $customerId,
                'stripe_payment_method_id' => $pmId,
                'status' => 'error',
                'context' => ['error' => $e->getMessage()],
                'message' => 'Échec enregistrement carte: ' . $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }

        $user->update([
            'stripe_payment_method_id' => $pmId,
            'pm_type' => $display['pm_type'],
            'pm_last_four' => $display['pm_last_four'],
        ]);

        PaymentAuditLog::log('save_pm_ok', $user->id, [
            'stripe_customer_id' => $customerId,
            'stripe_payment_method_id' => $pmId,
            'status' => 'ok',
            'context' => ['pm_type' => $display['pm_type'], 'pm_last_four' => $display['pm_last_four']],
            'message' => 'Carte enregistrée •••• ' . ($display['pm_last_four'] ?? ''),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Supprimer la carte enregistrée (détacher le PaymentMethod, vider user).
     */
    public function removePaymentMethod(Request $request)
    {
        $user = Auth::user();
        $pmId = $user->stripe_payment_method_id;
        if (!$pmId) {
            return redirect()->route('checkout.index')
                ->with('error', 'Aucune carte enregistrée.');
        }

        $customerId = $user->stripe_id;
        if (!$customerId) {
            $user->update([
                'stripe_payment_method_id' => null,
                'pm_type' => null,
                'pm_last_four' => null,
            ]);
            return redirect()->route('checkout.index')
                ->with('success', 'Carte supprimée.');
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            $pm = PaymentMethod::retrieve($pmId);
            if ($pm->customer === $customerId) {
                $pm->detach();
            }
            $customer = Customer::retrieve($customerId);
            $defaultPm = $customer->invoice_settings->default_payment_method ?? null;
            if ($defaultPm === $pmId) {
                Customer::update($customerId, [
                    'invoice_settings' => ['default_payment_method' => ''],
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Checkout remove-payment-method Stripe error', [
                'user_id' => $user->id,
                'stripe_payment_method_id' => $pmId,
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('checkout.index')
                ->with('error', 'Impossible de supprimer la carte. Réessayez.');
        }

        $user->update([
            'stripe_payment_method_id' => null,
            'pm_type' => null,
            'pm_last_four' => null,
        ]);

        try {
            PaymentAuditLog::log('remove_pm_ok', $user->id, [
                'stripe_customer_id' => $customerId,
                'stripe_payment_method_id' => $pmId,
                'message' => 'Carte supprimée.',
            ]);
        } catch (\Throwable $e) {
            Log::warning('PaymentAuditLog remove_pm_ok failed', ['error' => $e->getMessage()]);
        }

        return redirect()->route('checkout.index')
            ->with('success', 'Carte supprimée.');
    }

    /**
     * Débit via PaymentIntent (off_session). Remplace le redirect Checkout pour "Régler".
     * Body: { "echeance_id": 123, "code_promo": "..."? }
     * Réponses: { success, … } ou { requires_action: true, client_secret, payment_intent_id }.
     */
    public function charge(Request $request)
    {
        $request->validate([
            'echeance_id' => 'required|exists:echeances,id',
            'code_promo' => 'nullable|string|max:64',
        ]);

        $user = Auth::user();
        $echeance = Echeance::where('user_id', $user->id)
            ->whereIn('statut', [Echeance::STATUT_A_PAYER, Echeance::STATUT_EN_ATTENTE])
            ->findOrFail($request->input('echeance_id'));

        $codePromo = $request->input('code_promo') ?: $request->session()->get('checkout_promo_code');
        $calc = CalculMontantDuService::calculerPourEcheance($echeance, $codePromo);
        $montantFinal = $calc['montant_final'];
        if ($montantFinal <= 0) {
            PaymentAuditLog::log('charge_fail', $user->id, [
                'echeance_id' => $echeance->id,
                'amount' => 0,
                'status' => 'zero_amount',
                'message' => 'Montant à régler nul.',
            ]);
            return response()->json(['success' => false, 'error' => 'Le montant à régler est nul.'], 422);
        }

        if (empty($user->stripe_payment_method_id)) {
            PaymentAuditLog::log('charge_fail', $user->id, [
                'echeance_id' => $echeance->id,
                'amount' => $montantFinal,
                'currency' => 'eur',
                'status' => 'no_pm',
                'message' => 'Tentative de charge sans carte enregistrée.',
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Enregistrez d\'abord un moyen de paiement (carte) pour régler.',
            ], 409);
        }

        $customerId = StripeCustomerService::ensureCustomer($user);
        $amountCents = (int) round($montantFinal * 100);
        $currency = \App\Models\Tarif::currency();

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $pi = PaymentIntent::create([
                'amount' => $amountCents,
                'currency' => $currency,
                'customer' => $customerId,
                'payment_method' => $user->stripe_payment_method_id,
                'off_session' => true,
                'confirm' => true,
                'metadata' => [
                    'user_id' => (string) $user->id,
                    'echeance_id' => (string) $echeance->id,
                ],
            ]);
        } catch (\Stripe\Exception\CardException $e) {
            $msg = $e->getMessage();
            if (str_contains(strtolower($msg), 'insufficient') || $e->getError()->code === 'card_declined') {
                $msg = 'Carte refusée (fonds insuffisants ou refus bancaire).';
            }
            Log::warning('Checkout charge card error', ['user_id' => $user->id, 'echeance_id' => $echeance->id, 'error' => $e->getMessage()]);
            PaymentAuditLog::log('charge_fail', $user->id, [
                'echeance_id' => $echeance->id,
                'stripe_customer_id' => $customerId,
                'stripe_payment_method_id' => $user->stripe_payment_method_id,
                'amount' => $montantFinal,
                'currency' => $currency,
                'status' => 'card_error',
                'context' => ['code' => $e->getError()->code ?? null, 'raw' => $e->getMessage()],
                'message' => 'Carte refusée: ' . $msg,
            ]);
            return response()->json(['success' => false, 'error' => $msg], 422);
        } catch (\Throwable $e) {
            Log::error('Checkout charge failed', ['user_id' => $user->id, 'echeance_id' => $echeance->id, 'error' => $e->getMessage()]);
            PaymentAuditLog::log('charge_fail', $user->id, [
                'echeance_id' => $echeance->id,
                'stripe_customer_id' => $customerId,
                'amount' => $montantFinal,
                'currency' => $currency,
                'status' => 'exception',
                'context' => ['error' => $e->getMessage()],
                'message' => 'Exception charge: ' . $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'error' => 'Impossible de lancer le paiement. Réessayez.'], 500);
        }

        $status = $pi->status ?? '';

        if ($status === 'requires_action') {
            $echeance->update([
                'statut' => Echeance::STATUT_EN_ATTENTE,
                'stripe_payment_intent_id' => $pi->id,
                'reduction_promo' => $calc['reduction_promo'],
                'montant_final' => $montantFinal,
                'promo_code_id' => $calc['promo_code_id'],
                'metadata' => array_merge($echeance->metadata ?? [], ['lignes' => $calc['lignes']]),
            ]);
            PaymentAuditLog::log('charge_3ds', $user->id, [
                'echeance_id' => $echeance->id,
                'stripe_customer_id' => $customerId,
                'stripe_payment_intent_id' => $pi->id,
                'stripe_payment_method_id' => $user->stripe_payment_method_id,
                'amount' => $montantFinal,
                'currency' => $currency,
                'status' => 'requires_action',
                'message' => '3DS requis – en attente confirmation.',
            ]);
            return response()->json([
                'requires_action' => true,
                'client_secret' => $pi->client_secret,
                'payment_intent_id' => $pi->id,
            ]);
        }

        if ($status === 'succeeded') {
            $echeance->update([
                'reduction_promo' => $calc['reduction_promo'],
                'montant_final' => $montantFinal,
                'promo_code_id' => $calc['promo_code_id'],
                'metadata' => array_merge($echeance->metadata ?? [], ['lignes' => $calc['lignes']]),
            ]);
            $result = PaymentVerificationService::markEcheancePaidFromPaymentIntent($pi->id);
            if (!$result['ok']) {
                PaymentAuditLog::log('charge_fail', $user->id, [
                    'echeance_id' => $echeance->id,
                    'stripe_payment_intent_id' => $pi->id,
                    'amount' => $montantFinal,
                    'currency' => $currency,
                    'status' => 'verify_failed',
                    'context' => $result,
                    'message' => 'Charge OK mais vérification échouée: ' . ($result['message'] ?? ''),
                ]);
                return response()->json(['success' => false, 'error' => $result['message'] ?? 'Erreur enregistrement.'], 500);
            }
            PaymentAuditLog::log('charge_ok', $user->id, [
                'echeance_id' => $echeance->id,
                'stripe_customer_id' => $customerId,
                'stripe_payment_intent_id' => $pi->id,
                'stripe_payment_method_id' => $user->stripe_payment_method_id,
                'amount' => $montantFinal,
                'currency' => $currency,
                'status' => 'succeeded',
                'message' => 'Paiement réglé – ' . $echeance->libelle(),
            ]);
            return response()->json(['success' => true]);
        }

        PaymentAuditLog::log('charge_fail', $user->id, [
            'echeance_id' => $echeance->id,
            'stripe_payment_intent_id' => $pi->id,
            'amount' => $montantFinal,
            'currency' => $currency,
            'status' => $status,
            'message' => 'Statut inattendu: ' . $status,
        ]);
        return response()->json([
            'success' => false,
            'error' => 'Paiement en attente (status: ' . $status . '). Réessayez ou contactez-nous.',
        ], 422);
    }

    /**
     * Vérifie le statut d'un PaymentIntent après 3DS (handleCardAction).
     * Body: { "payment_intent_id": "pi_xxx" }
     */
    public function confirmStatus(Request $request)
    {
        $request->validate(['payment_intent_id' => 'required|string|starts_with:pi_']);

        $user = Auth::user();
        $piId = $request->input('payment_intent_id');
        $echeance = Echeance::where('user_id', $user->id)->where('stripe_payment_intent_id', $piId)->first();
        if (!$echeance) {
            PaymentAuditLog::log('confirm_status_fail', $user->id, [
                'stripe_payment_intent_id' => $piId,
                'status' => 'echeance_not_found',
                'message' => 'Échéance introuvable pour PI après 3DS.',
            ]);
            return response()->json(['success' => false, 'error' => 'Échéance introuvable.'], 404);
        }

        $result = PaymentVerificationService::markEcheancePaidFromPaymentIntent($piId);
        if (!$result['ok']) {
            PaymentAuditLog::log('confirm_status_fail', $user->id, [
                'echeance_id' => $echeance->id,
                'stripe_payment_intent_id' => $piId,
                'status' => 'verify_failed',
                'context' => $result,
                'message' => 'Confirm 3DS – vérification échouée: ' . ($result['message'] ?? ''),
            ]);
            return response()->json(['success' => false, 'error' => $result['message'] ?? 'Paiement non confirmé.'], 422);
        }
        PaymentAuditLog::log('confirm_status_ok', $user->id, [
            'echeance_id' => $echeance->id,
            'stripe_payment_intent_id' => $piId,
            'amount' => $echeance->montant_final,
            'status' => 'succeeded',
            'message' => 'Paiement confirmé après 3DS – ' . $echeance->libelle(),
        ]);
        return response()->json(['success' => true]);
    }

    public function creerSessionStripe(Request $request)
    {
        $request->validate([
            'echeance_id' => 'required|exists:echeances,id',
            'code_promo' => 'nullable|string|max:64',
        ]);
        $user = Auth::user();
        $echeance = Echeance::where('user_id', $user->id)
            ->whereIn('statut', [Echeance::STATUT_A_PAYER, Echeance::STATUT_EN_ATTENTE])
            ->findOrFail($request->input('echeance_id'));

        $codePromo = $request->input('code_promo') ?: $request->session()->get('checkout_promo_code');
        $calc = CalculMontantDuService::calculerPourEcheance($echeance, $codePromo);
        $montantFinal = $calc['montant_final'];
        if ($montantFinal <= 0) {
            return redirect()->route('checkout.index')
                ->with('error', 'Le montant à régler est nul.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));
        $amountCents = (int) round($montantFinal * 100);
        $label = sprintf(
            'Allotata – %s à %s (%s)',
            $echeance->periode_debut->format('d/m/Y'),
            $echeance->periode_fin->format('d/m/Y'),
            $echeance->libelle()
        );

        $params = [
            'mode' => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => \App\Models\Tarif::currency(),
                    'product_data' => [
                        'name' => $label,
                        'description' => 'Abonnement mensuel Allotata',
                    ],
                    'unit_amount' => $amountCents,
                ],
                'quantity' => 1,
            ]],
            'success_url' => route('checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.index'),
            'customer_email' => $user->email,
            'metadata' => [
                'user_id' => (string) $user->id,
                'echeance_id' => (string) $echeance->id,
                'periode' => $echeance->periode_debut->format('Y-m-d') . '_' . $echeance->periode_fin->format('Y-m-d'),
            ],
        ];

        try {
            $session = StripeSession::create($params);
        } catch (\Exception $e) {
            Log::error('Checkout Stripe session create failed', [
                'user_id' => $user->id,
                'echeance_id' => $echeance->id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('checkout.index')
                ->with('error', 'Impossible de créer la session de paiement. Réessayez plus tard.');
        }

        $echeance->update([
            'statut' => Echeance::STATUT_EN_ATTENTE,
            'stripe_checkout_session_id' => $session->id,
            'reduction_promo' => $calc['reduction_promo'],
            'montant_final' => $montantFinal,
            'promo_code_id' => $calc['promo_code_id'],
            'metadata' => array_merge($echeance->metadata ?? [], ['lignes' => $calc['lignes']]),
        ]);

        return redirect($session->url);
    }

    /**
     * Retour utilisateur après paiement Stripe.
     * Vérification directe sur Stripe (2e niveau) + marquage échéance payée.
     */
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');
        if (!$sessionId) {
            return redirect()->route('checkout.index')
                ->with('error', 'Session de paiement introuvable.');
        }

        $result = PaymentVerificationService::verifyAndMarkPaid($sessionId);

        if (!$result['ok']) {
            Log::info('Checkout success: verification failed', [
                'session_id' => $sessionId,
                'message' => $result['message'],
            ]);
            return redirect()->route('checkout.index')
                ->with('error', $result['message'] ?: 'Paiement non reçu.');
        }

        $userId = (int) (Echeance::find($result['echeance_id'])?->user_id ?? 0);
        if ($userId && Auth::id() !== $userId) {
            return redirect()->route('checkout.index')
                ->with('error', 'Accès refusé.');
        }

        return redirect()->route('settings.index', ['tab' => 'subscription'])
            ->with('success', $result['already'] ? 'Paiement déjà enregistré.' : 'Paiement enregistré. Merci !');
    }

    public function cancel()
    {
        return redirect()->route('checkout.index');
    }
}
