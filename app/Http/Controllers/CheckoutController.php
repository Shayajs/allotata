<?php

namespace App\Http\Controllers;

use App\Models\Echeance;
use App\Models\PromoCode;
use App\Services\CalculMontantDuService;
use App\Services\PaymentVerificationService;
use App\Services\StripeCustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as StripeSession;
use Stripe\PaymentIntent;
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

        return view('checkout.index', [
            'echeances' => $echeances,
            'calculs' => $calculs,
            'codePromo' => $codePromo,
            'hasPaymentMethod' => !empty($user->stripe_payment_method_id),
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
        $customerId = StripeCustomerService::ensureCustomer($user);

        Stripe::setApiKey(config('services.stripe.secret'));
        $si = SetupIntent::create([
            'customer' => $customerId,
            'usage' => 'off_session',
            'metadata' => ['user_id' => (string) $user->id],
        ]);

        return response()->json(['client_secret' => $si->client_secret]);
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
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }

        $user->update([
            'stripe_payment_method_id' => $pmId,
            'pm_type' => $display['pm_type'],
            'pm_last_four' => $display['pm_last_four'],
        ]);

        return response()->json(['success' => true]);
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
            return response()->json(['success' => false, 'error' => 'Le montant à régler est nul.'], 422);
        }

        if (empty($user->stripe_payment_method_id)) {
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
            return response()->json(['success' => false, 'error' => $msg], 422);
        } catch (\Throwable $e) {
            Log::error('Checkout charge failed', ['user_id' => $user->id, 'echeance_id' => $echeance->id, 'error' => $e->getMessage()]);
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
                return response()->json(['success' => false, 'error' => $result['message'] ?? 'Erreur enregistrement.'], 500);
            }
            return response()->json(['success' => true]);
        }

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
        $echeance = Echeance::where('user_id', $user->id)->where('stripe_payment_intent_id', $request->input('payment_intent_id'))->first();
        if (!$echeance) {
            return response()->json(['success' => false, 'error' => 'Échéance introuvable.'], 404);
        }

        $result = PaymentVerificationService::markEcheancePaidFromPaymentIntent($request->input('payment_intent_id'));
        if (!$result['ok']) {
            return response()->json(['success' => false, 'error' => $result['message'] ?? 'Paiement non confirmé.'], 422);
        }
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
