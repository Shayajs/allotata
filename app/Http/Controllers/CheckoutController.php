<?php

namespace App\Http\Controllers;

use App\Models\Echeance;
use App\Models\PaymentAuditLog;
use App\Models\PromoCode;
use App\Helpers\EmailHelper;
use App\Services\CalculMontantDuService;
use App\Services\PaymentVerificationService;
use App\Services\StripeCustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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

        // ── GET ?cancel_pending=KEY → retirer un item session (nouvelle souscription) ──
        if ($cancelPendingKey = $request->query('cancel_pending')) {
            $pending = session('checkout_pending', []);
            if (isset($pending[$cancelPendingKey])) {
                unset($pending[$cancelPendingKey]);
                session(['checkout_pending' => $pending]);
                return redirect()->route('checkout.index')
                    ->with('success', 'Souscription annulée.');
            }
        }

        // ── GET ?cancel=ID → annuler une échéance DB (a_payer) ──
        if ($cancelId = $request->query('cancel')) {
            $toCancel = Echeance::where('user_id', $user->id)
                ->where('id', $cancelId)
                ->where('statut', Echeance::STATUT_A_PAYER)
                ->first();
            if ($toCancel) {
                $toCancel->update(['statut' => Echeance::STATUT_ANNULE]);
                return redirect()->route('checkout.index')
                    ->with('success', 'Échéance annulée.');
            }
        }

        // ── Backward compat : annuler tous les vieux brouillons (statut supprimé) ──
        Echeance::where('user_id', $user->id)
            ->where('statut', Echeance::STATUT_BROUILLON)
            ->update(['statut' => Echeance::STATUT_ANNULE]);

        // ── Items en session (nouvelles souscriptions, pas encore en DB) ──
        $pendingItems = collect(session('checkout_pending', []));

        // ── Récupérer les échéances DB actionnables ──
        $echeances = Echeance::where('user_id', $user->id)
            ->whereIn('statut', [
                Echeance::STATUT_ECHEC,
                Echeance::STATUT_A_PAYER,
                Echeance::STATUT_EN_ATTENTE,
            ])
            ->requiringUserPayment($user)
            ->orderBy('periode_debut')
            ->with('entreprise')
            ->get();

        // ── Catégoriser par état pour un affichage clair ──
        $echeancesEchec     = $echeances->where('statut', Echeance::STATUT_ECHEC)->values();
        $echeancesAPayer    = $echeances->where('statut', Echeance::STATUT_A_PAYER)->values();
        $echeancesEnAttente = $echeances->where('statut', Echeance::STATUT_EN_ATTENTE)->values();

        // ── Calculer les montants des échéances DB ──
        $codePromo = $request->session()->get('checkout_promo_code');
        $calculs = [];
        foreach ($echeances as $e) {
            $calc = CalculMontantDuService::calculerPourEcheance($e, $codePromo, false);
            $calculs[$e->id] = $calc;
        }

        // ── Calculer les montants des items session (nouvelles souscriptions) ──
        $pendingCalculs = [];
        foreach ($pendingItems as $key => $item) {
            $tmp = new Echeance([
                'user_id'           => $item['user_id'],
                'entreprise_id'     => $item['entreprise_id'],
                'subscription_type' => $item['subscription_type'],
                'periode_debut'     => $item['periode_debut'],
                'periode_fin'       => $item['periode_fin'],
                'jour_facturation'  => $item['jour_facturation'],
                'reduction_manuel'  => 0,
            ]);
            $tmp->setRelation('user', $user);
            if ($item['entreprise_id']) {
                $tmp->setRelation('entreprise', \App\Models\Entreprise::find($item['entreprise_id']));
            }
            $pendingCalculs[$key] = CalculMontantDuService::calculerPourEcheance($tmp, $codePromo, true);
        }

        $hasPaymentMethod = !empty($user->stripe_payment_method_id);
        $showCardForm = !$hasPaymentMethod || $request->boolean('change_card');
        $hasAnything = $echeances->isNotEmpty() || $pendingItems->isNotEmpty();

        return view('checkout.index', [
            'echeances'           => $echeances,
            'echeancesEchec'      => $echeancesEchec,
            'echeancesAPayer'     => $echeancesAPayer,
            'echeancesEnAttente'  => $echeancesEnAttente,
            'pendingItems'        => $pendingItems,
            'pendingCalculs'      => $pendingCalculs,
            'calculs'             => $calculs,
            'codePromo'           => $codePromo,
            'hasPaymentMethod'    => $hasPaymentMethod,
            'showCardForm'        => $showCardForm,
            'hasAnything'         => $hasAnything,
            'user'                => $user,
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
        } catch (\Stripe\Exception\CardException $e) {
            $errorCode = $e->getError()->code ?? null;
            $declineCode = $e->getError()->decline_code ?? null;
            Log::error('Checkout createSetupIntent: CardException', [
                'user_id' => $user->id,
                'error_code' => $errorCode,
                'decline_code' => $declineCode,
                'error' => $e->getMessage(),
            ]);
            PaymentAuditLog::log('setup_intent_fail', $user->id, [
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'CardException',
                    'error_code' => $errorCode,
                    'decline_code' => $declineCode,
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Erreur carte lors de la création du SetupIntent: ' . $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Erreur avec votre carte. Vérifiez vos informations de paiement.',
            ], 422);
        } catch (\Stripe\Exception\RateLimitException $e) {
            Log::error('Checkout createSetupIntent: RateLimitException', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            PaymentAuditLog::log('setup_intent_fail', $user->id, [
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'RateLimitException',
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Trop de requêtes à l\'API Stripe: ' . $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Trop de requêtes. Veuillez patienter quelques instants avant de réessayer.',
            ], 429);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            Log::error('Checkout createSetupIntent: InvalidRequestException', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'param' => $e->getError()->param ?? null,
            ]);
            PaymentAuditLog::log('setup_intent_fail', $user->id, [
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'InvalidRequestException',
                    'param' => $e->getError()->param ?? null,
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Paramètres invalides pour SetupIntent: ' . $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Erreur de configuration. Contactez le support.',
            ], 400);
        } catch (\Stripe\Exception\AuthenticationException $e) {
            Log::critical('Checkout createSetupIntent: AuthenticationException', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            PaymentAuditLog::log('setup_intent_fail', $user->id, [
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'AuthenticationException',
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Problème d\'authentification avec Stripe: ' . $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Erreur de configuration serveur. Contactez le support.',
            ], 500);
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            Log::error('Checkout createSetupIntent: ApiConnectionException', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            PaymentAuditLog::log('setup_intent_fail', $user->id, [
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'ApiConnectionException',
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Problème réseau avec Stripe: ' . $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Problème de connexion. Réessayez dans quelques instants.',
            ], 503);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Checkout createSetupIntent: ApiErrorException', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            PaymentAuditLog::log('setup_intent_fail', $user->id, [
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'ApiErrorException',
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Erreur API Stripe générique: ' . $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Erreur temporaire. Réessayez plus tard.',
            ], 500);
        } catch (\Throwable $e) {
            Log::error('Checkout createSetupIntent failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            PaymentAuditLog::log('setup_intent_fail', $user->id, [
                'status' => 'failed',
                'context' => [
                    'exception_type' => get_class($e),
                    'raw_error' => $e->getMessage(),
                ],
                'message' => 'Exception inattendue lors de la création du SetupIntent: ' . $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Impossible de préparer le formulaire. Réessayez.',
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
        } catch (\Stripe\Exception\CardException $e) {
            $errorCode = $e->getError()->code ?? null;
            $declineCode = $e->getError()->decline_code ?? null;
            Log::warning('Checkout save-payment-method: CardException', [
                'user_id' => $user->id,
                'error_code' => $errorCode,
                'decline_code' => $declineCode,
                'error' => $e->getMessage(),
            ]);
            PaymentAuditLog::log('save_pm_fail', $user->id, [
                'stripe_customer_id' => $customerId,
                'stripe_payment_method_id' => $pmId,
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'CardException',
                    'error_code' => $errorCode,
                    'decline_code' => $declineCode,
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Carte refusée lors de l\'enregistrement: ' . $e->getMessage(),
            ]);
            $userMessage = self::mapStripeErrorToUserMessage($errorCode, $e->getMessage());
            return response()->json(['success' => false, 'error' => $userMessage, 'error_code' => $errorCode], 422);
        } catch (\Stripe\Exception\RateLimitException $e) {
            Log::warning('Checkout save-payment-method: RateLimitException', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            PaymentAuditLog::log('save_pm_fail', $user->id, [
                'stripe_customer_id' => $customerId,
                'stripe_payment_method_id' => $pmId,
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'RateLimitException',
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Trop de requêtes à l\'API Stripe: ' . $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'error' => 'Trop de requêtes. Veuillez patienter quelques instants.'], 429);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            Log::warning('Checkout save-payment-method: InvalidRequestException', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'param' => $e->getError()->param ?? null,
            ]);
            PaymentAuditLog::log('save_pm_fail', $user->id, [
                'stripe_customer_id' => $customerId,
                'stripe_payment_method_id' => $pmId,
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'InvalidRequestException',
                    'param' => $e->getError()->param ?? null,
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Paramètres invalides: ' . $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'error' => 'Erreur de configuration. Contactez le support.'], 400);
        } catch (\Stripe\Exception\AuthenticationException $e) {
            Log::critical('Checkout save-payment-method: AuthenticationException', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            PaymentAuditLog::log('save_pm_fail', $user->id, [
                'stripe_customer_id' => $customerId,
                'stripe_payment_method_id' => $pmId,
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'AuthenticationException',
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Problème d\'authentification avec Stripe: ' . $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'error' => 'Erreur de configuration serveur. Contactez le support.'], 500);
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            Log::warning('Checkout save-payment-method: ApiConnectionException', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            PaymentAuditLog::log('save_pm_fail', $user->id, [
                'stripe_customer_id' => $customerId,
                'stripe_payment_method_id' => $pmId,
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'ApiConnectionException',
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Problème réseau avec Stripe: ' . $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'error' => 'Problème de connexion. Réessayez dans quelques instants.'], 503);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::warning('Checkout save-payment-method: ApiErrorException', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            PaymentAuditLog::log('save_pm_fail', $user->id, [
                'stripe_customer_id' => $customerId,
                'stripe_payment_method_id' => $pmId,
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'ApiErrorException',
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Erreur API Stripe générique: ' . $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'error' => 'Erreur temporaire. Réessayez plus tard.'], 500);
        } catch (\Throwable $e) {
            Log::warning('Checkout save-payment-method failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            PaymentAuditLog::log('save_pm_fail', $user->id, [
                'stripe_customer_id' => $customerId,
                'stripe_payment_method_id' => $pmId,
                'status' => 'failed',
                'context' => [
                    'exception_type' => get_class($e),
                    'raw_error' => $e->getMessage(),
                ],
                'message' => 'Exception inattendue lors de l\'enregistrement: ' . $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'error' => 'Erreur lors de l\'enregistrement. Réessayez.'], 422);
        }

        $user->update([
            'stripe_payment_method_id' => $pmId,
            'payment_provider' => Echeance::PROVIDER_STRIPE,
            'provider_customer_id' => $customerId,
            'provider_payment_method_id' => $pmId,
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
                'payment_provider' => null,
                'provider_customer_id' => null,
                'provider_payment_method_id' => null,
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
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            Log::warning('Checkout remove-payment-method: InvalidRequestException', [
                'user_id' => $user->id,
                'stripe_payment_method_id' => $pmId,
                'error' => $e->getMessage(),
            ]);
            PaymentAuditLog::log('remove_pm_fail', $user->id, [
                'stripe_customer_id' => $customerId,
                'stripe_payment_method_id' => $pmId,
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'InvalidRequestException',
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Paramètres invalides lors de la suppression: ' . $e->getMessage(),
            ]);
            return redirect()->route('checkout.index')
                ->with('error', 'Impossible de supprimer la carte. Contactez le support.');
        } catch (\Stripe\Exception\AuthenticationException $e) {
            Log::critical('Checkout remove-payment-method: AuthenticationException', [
                'user_id' => $user->id,
                'stripe_payment_method_id' => $pmId,
                'error' => $e->getMessage(),
            ]);
            PaymentAuditLog::log('remove_pm_fail', $user->id, [
                'stripe_customer_id' => $customerId,
                'stripe_payment_method_id' => $pmId,
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'AuthenticationException',
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Problème d\'authentification avec Stripe: ' . $e->getMessage(),
            ]);
            return redirect()->route('checkout.index')
                ->with('error', 'Erreur de configuration serveur. Contactez le support.');
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            Log::warning('Checkout remove-payment-method: ApiConnectionException', [
                'user_id' => $user->id,
                'stripe_payment_method_id' => $pmId,
                'error' => $e->getMessage(),
            ]);
            PaymentAuditLog::log('remove_pm_fail', $user->id, [
                'stripe_customer_id' => $customerId,
                'stripe_payment_method_id' => $pmId,
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'ApiConnectionException',
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Problème réseau avec Stripe: ' . $e->getMessage(),
            ]);
            return redirect()->route('checkout.index')
                ->with('error', 'Problème de connexion. Réessayez dans quelques instants.');
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::warning('Checkout remove-payment-method: ApiErrorException', [
                'user_id' => $user->id,
                'stripe_payment_method_id' => $pmId,
                'error' => $e->getMessage(),
            ]);
            PaymentAuditLog::log('remove_pm_fail', $user->id, [
                'stripe_customer_id' => $customerId,
                'stripe_payment_method_id' => $pmId,
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'ApiErrorException',
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Erreur API Stripe générique: ' . $e->getMessage(),
            ]);
            return redirect()->route('checkout.index')
                ->with('error', 'Erreur temporaire. Réessayez plus tard.');
        } catch (\Throwable $e) {
            Log::warning('Checkout remove-payment-method Stripe error', [
                'user_id' => $user->id,
                'stripe_payment_method_id' => $pmId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            PaymentAuditLog::log('remove_pm_fail', $user->id, [
                'stripe_customer_id' => $customerId,
                'stripe_payment_method_id' => $pmId,
                'status' => 'failed',
                'context' => [
                    'exception_type' => get_class($e),
                    'raw_error' => $e->getMessage(),
                ],
                'message' => 'Exception inattendue lors de la suppression: ' . $e->getMessage(),
            ]);
            return redirect()->route('checkout.index')
                ->with('error', 'Impossible de supprimer la carte. Réessayez.');
        }

        $user->update([
            'stripe_payment_method_id' => null,
            'payment_provider' => null,
            'provider_customer_id' => null,
            'provider_payment_method_id' => null,
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
            'echeance_id' => 'required_without:pending_key|nullable|exists:echeances,id',
            'pending_key'  => 'required_without:echeance_id|nullable|string|max:128',
            'code_promo'   => 'nullable|string|max:64',
        ]);

        $user = Auth::user();

        // ════════ Flux session (nouvelle souscription, pas encore en DB) ════════
        if ($request->filled('pending_key')) {
            return $this->chargePending($request, $user);
        }

        // ════════ Flux DB (renouvellement / régularisation) ════════
        $echeance = \DB::transaction(function () use ($user, $request) {
            return Echeance::where('user_id', $user->id)
                ->whereIn('statut', [
                    Echeance::STATUT_A_PAYER,
                    Echeance::STATUT_EN_ATTENTE,
                    Echeance::STATUT_ECHEC,
                ])
                ->lockForUpdate()
                ->findOrFail($request->input('echeance_id'));
        });

        // Vérifier que l'échéance n'est pas déjà payée (double vérification après verrou)
        if ($echeance->estPayee()) {
            PaymentAuditLog::log('charge_fail', $user->id, [
                'echeance_id' => $echeance->id,
                'status' => 'already_paid',
                'message' => 'Tentative de paiement d\'une échéance déjà payée.',
            ]);
            return response()->json(['success' => false, 'error' => 'Cette échéance est déjà payée.'], 409);
        }

        $codePromo = $request->input('code_promo') ?: $request->session()->get('checkout_promo_code');
        
        // Valider le code promo avant de calculer
        if ($codePromo) {
            $promo = PromoCode::validateCode($codePromo, $user);
            if (!$promo) {
                return response()->json(['success' => false, 'error' => 'Code promo invalide ou expiré.'], 422);
            }
            // Vérifier que le code promo n'a pas déjà été utilisé pour cette échéance
            if ($echeance->promo_code_id && $echeance->promo_code_id === $promo->id && $echeance->estPayee()) {
                return response()->json(['success' => false, 'error' => 'Ce code promo a déjà été utilisé pour cette échéance.'], 422);
            }
        }
        
        $calc = CalculMontantDuService::calculerPourEcheance($echeance, $codePromo, false);
        $montantFinal = $calc['montant_final'];
        
        // Validation stricte du montant : doit être > 0 et <= montant_du
        if ($montantFinal <= 0) {
            PaymentAuditLog::log('charge_fail', $user->id, [
                'echeance_id' => $echeance->id,
                'amount' => 0,
                'status' => 'zero_amount',
                'message' => 'Montant à régler nul.',
            ]);
            return response()->json(['success' => false, 'error' => 'Le montant à régler est nul.'], 422);
        }
        
        // Vérifier que le montant final ne dépasse pas le montant dû
        $montantDu = (float) ($echeance->montant_du ?? 0);
        if ($montantFinal > $montantDu) {
            Log::warning('Checkout charge: montant final supérieur au montant dû', [
                'user_id' => $user->id,
                'echeance_id' => $echeance->id,
                'montant_final' => $montantFinal,
                'montant_du' => $montantDu,
            ]);
            PaymentAuditLog::log('charge_fail', $user->id, [
                'echeance_id' => $echeance->id,
                'amount' => $montantFinal,
                'status' => 'amount_exceeds_du',
                'message' => 'Montant final supérieur au montant dû.',
            ]);
            return response()->json(['success' => false, 'error' => 'Erreur de calcul du montant. Contactez le support.'], 422);
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

        // Clé d'idempotence pour éviter les doublons en cas de retry réseau
        $idempotencyKey = 'charge_echeance_' . $echeance->id . '_' . time();

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
            ], [
                'idempotency_key' => $idempotencyKey,
            ]);
        } catch (\Stripe\Exception\CardException $e) {
            $errorCode = $e->getError()->code ?? null;
            $paymentIntent = $e->getError()->payment_intent ?? null;
            
            // Gestion du 3D Secure "soudain" (SCA) en mode off_session
            // Si Stripe demande une authentification, on renvoie le client_secret au frontend
            if ($errorCode === 'authentication_required' && $paymentIntent) {
                $piId = is_object($paymentIntent) ? $paymentIntent->id : $paymentIntent;
                $clientSecret = is_object($paymentIntent) ? ($paymentIntent->client_secret ?? null) : null;
                
                // Récupérer le PaymentIntent complet pour avoir le client_secret
                if (!$clientSecret && $piId) {
                    try {
                        $piRetrieved = PaymentIntent::retrieve($piId);
                        $clientSecret = $piRetrieved->client_secret ?? null;
                    } catch (\Exception $retrieveEx) {
                        Log::warning('Checkout charge: impossible de récupérer le PaymentIntent pour 3DS', [
                            'user_id' => $user->id,
                            'echeance_id' => $echeance->id,
                            'payment_intent_id' => $piId,
                            'error' => $retrieveEx->getMessage(),
                        ]);
                    }
                }
                
                if ($clientSecret) {
                    // Mettre l'échéance en attente
                    $echeance->update([
                        'statut' => Echeance::STATUT_EN_ATTENTE,
                        'stripe_payment_intent_id' => $piId,
                        'reduction_promo' => $calc['reduction_promo'],
                        'montant_final' => $montantFinal,
                        'promo_code_id' => $calc['promo_code_id'],
                        'metadata' => array_merge($echeance->metadata ?? [], ['lignes' => $calc['lignes']]),
                    ]);
                    
                    PaymentAuditLog::log('charge_3ds', $user->id, [
                        'echeance_id' => $echeance->id,
                        'stripe_customer_id' => $customerId,
                        'stripe_payment_intent_id' => $piId,
                        'stripe_payment_method_id' => $user->stripe_payment_method_id,
                        'amount' => $montantFinal,
                        'currency' => $currency,
                        'status' => 'requires_action',
                        'context' => ['code' => $errorCode, 'source' => 'off_session_authentication_required'],
                        'message' => '3DS requis (SCA) en mode off_session – authentification nécessaire.',
                    ]);
                    
                    try {
                        app(\App\Services\UserNotificationService::class)->notifyPaymentStatus(
                            $user,
                            $echeance,
                            'requires_action',
                            null,
                            fn () => EmailHelper::sendPaymentAuthenticationRequired($user, $echeance, $piId),
                        );
                        Log::info('Notification SCA selon préférences utilisateur', [
                            'user_id' => $user->id,
                            'echeance_id' => $echeance->id,
                        ]);
                    } catch (\Throwable $emailEx) {
                        Log::warning('Échec notification SCA', [
                            'user_id' => $user->id,
                            'echeance_id' => $echeance->id,
                            'error' => $emailEx->getMessage(),
                        ]);
                    }
                    
                    return response()->json([
                        'requires_action' => true,
                        'client_secret' => $clientSecret,
                        'payment_intent_id' => $piId,
                    ]);
                }
            }
            
            // Mapper les codes d'erreur Stripe vers des messages français clairs
            // C'est crucial pour l'UX : le client doit comprendre que c'est SA banque qui refuse
            $msg = self::mapStripeErrorToUserMessage($errorCode, $e->getMessage());
            
            Log::warning('Checkout charge card error', [
                'user_id' => $user->id,
                'echeance_id' => $echeance->id,
                'error_code' => $errorCode,
                'error_message' => $e->getMessage(),
            ]);
            
            PaymentAuditLog::log('charge_fail', $user->id, [
                'echeance_id' => $echeance->id,
                'stripe_customer_id' => $customerId,
                'stripe_payment_method_id' => $user->stripe_payment_method_id,
                'amount' => $montantFinal,
                'currency' => $currency,
                'status' => 'card_error',
                'context' => ['code' => $errorCode, 'raw' => $e->getMessage(), 'user_message' => $msg],
                'message' => 'Carte refusée: ' . $msg,
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $msg,
                'error_code' => $errorCode, // Envoyer aussi le code pour le frontend
            ], 422);
        } catch (\Stripe\Exception\RateLimitException $e) {
            Log::error('Checkout charge: RateLimitException', [
                'user_id' => $user->id,
                'echeance_id' => $echeance->id,
                'error' => $e->getMessage(),
            ]);
            PaymentAuditLog::log('charge_fail', $user->id, [
                'echeance_id' => $echeance->id,
                'stripe_customer_id' => $customerId,
                'amount' => $montantFinal,
                'currency' => $currency,
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'RateLimitException',
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Trop de requêtes à l\'API Stripe: ' . $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Trop de requêtes. Veuillez patienter quelques instants avant de réessayer.',
            ], 429);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            Log::error('Checkout charge: InvalidRequestException', [
                'user_id' => $user->id,
                'echeance_id' => $echeance->id,
                'error' => $e->getMessage(),
                'param' => $e->getError()->param ?? null,
            ]);
            PaymentAuditLog::log('charge_fail', $user->id, [
                'echeance_id' => $echeance->id,
                'stripe_customer_id' => $customerId,
                'amount' => $montantFinal,
                'currency' => $currency,
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'InvalidRequestException',
                    'param' => $e->getError()->param ?? null,
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Paramètres invalides pour PaymentIntent: ' . $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Erreur de configuration. Contactez le support.',
            ], 400);
        } catch (\Stripe\Exception\AuthenticationException $e) {
            Log::critical('Checkout charge: AuthenticationException', [
                'user_id' => $user->id,
                'echeance_id' => $echeance->id,
                'error' => $e->getMessage(),
            ]);
            PaymentAuditLog::log('charge_fail', $user->id, [
                'echeance_id' => $echeance->id,
                'stripe_customer_id' => $customerId,
                'amount' => $montantFinal,
                'currency' => $currency,
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'AuthenticationException',
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Problème d\'authentification avec Stripe: ' . $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Erreur de configuration serveur. Contactez le support.',
            ], 500);
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            Log::error('Checkout charge: ApiConnectionException', [
                'user_id' => $user->id,
                'echeance_id' => $echeance->id,
                'error' => $e->getMessage(),
            ]);
            PaymentAuditLog::log('charge_fail', $user->id, [
                'echeance_id' => $echeance->id,
                'stripe_customer_id' => $customerId,
                'amount' => $montantFinal,
                'currency' => $currency,
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'ApiConnectionException',
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Problème réseau avec Stripe: ' . $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Problème de connexion. Réessayez dans quelques instants.',
            ], 503);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Checkout charge: ApiErrorException', [
                'user_id' => $user->id,
                'echeance_id' => $echeance->id,
                'error' => $e->getMessage(),
            ]);
            PaymentAuditLog::log('charge_fail', $user->id, [
                'echeance_id' => $echeance->id,
                'stripe_customer_id' => $customerId,
                'amount' => $montantFinal,
                'currency' => $currency,
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'ApiErrorException',
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Erreur API Stripe générique: ' . $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Erreur temporaire. Réessayez plus tard.',
            ], 500);
        } catch (\Throwable $e) {
            Log::error('Checkout charge failed', [
                'user_id' => $user->id,
                'echeance_id' => $echeance->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            PaymentAuditLog::log('charge_fail', $user->id, [
                'echeance_id' => $echeance->id,
                'stripe_customer_id' => $customerId,
                'amount' => $montantFinal,
                'currency' => $currency,
                'status' => 'failed',
                'context' => [
                    'exception_type' => get_class($e),
                    'raw_error' => $e->getMessage(),
                ],
                'message' => 'Exception inattendue lors du charge: ' . $e->getMessage(),
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
            // Protection contre la race condition : vérifier que l'échéance n'est pas déjà payée
            // (peut arriver si le webhook a déjà traité le paiement)
            $echeance->refresh();
            if ($echeance->estPayee()) {
                PaymentAuditLog::log('charge_ok', $user->id, [
                    'echeance_id' => $echeance->id,
                    'stripe_payment_intent_id' => $pi->id,
                    'amount' => $montantFinal,
                    'currency' => $currency,
                    'status' => 'succeeded',
                    'context' => ['already_paid' => true, 'source' => 'race_condition_prevented'],
                    'message' => 'Paiement déjà enregistré (webhook a traité avant).',
                ]);
                return response()->json(['success' => true, 'already_paid' => true]);
            }

            // Vérifier que le montant débité correspond au montant calculé
            $amountPaid = $pi->amount ? $pi->amount / 100 : 0;
            if (abs($amountPaid - $montantFinal) > 0.01) {
                Log::error('Checkout charge: montant débité ne correspond pas', [
                    'user_id' => $user->id,
                    'echeance_id' => $echeance->id,
                    'amount_paid' => $amountPaid,
                    'expected_amount' => $montantFinal,
                ]);
                PaymentAuditLog::log('charge_fail', $user->id, [
                    'echeance_id' => $echeance->id,
                    'stripe_payment_intent_id' => $pi->id,
                    'amount_paid' => $amountPaid,
                    'expected_amount' => $montantFinal,
                    'status' => 'amount_mismatch',
                    'message' => 'Montant débité ne correspond pas au montant calculé.',
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Erreur de montant. Contactez le support.',
                ], 500);
            }

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
     * Charge un item session (nouvelle souscription entreprise, pas encore en DB).
     * Crée l'Echeance + EntrepriseSubscription atomiquement au succès.
     * Aucune trace en base si le paiement échoue et que l'utilisateur abandonne.
     */
    protected function chargePending(Request $request, $user)
    {
        $pendingKey = $request->input('pending_key');
        $item = session("checkout_pending.{$pendingKey}");

        if (!$item) {
            return response()->json(['success' => false, 'error' => 'Souscription introuvable ou expirée.'], 404);
        }

        $codePromo = $request->input('code_promo') ?: $request->session()->get('checkout_promo_code');

        // Calcul du montant à partir d'un Echeance temporaire
        $tmp = new Echeance([
            'user_id'           => $item['user_id'],
            'entreprise_id'     => $item['entreprise_id'],
            'subscription_type' => $item['subscription_type'],
            'periode_debut'     => $item['periode_debut'],
            'periode_fin'       => $item['periode_fin'],
            'jour_facturation'  => $item['jour_facturation'],
            'reduction_manuel'  => 0,
        ]);
        $tmp->setRelation('user', $user);
        if ($item['entreprise_id']) {
            $tmp->setRelation('entreprise', \App\Models\Entreprise::find($item['entreprise_id']));
        }

        if ($codePromo) {
            $promo = PromoCode::validateCode($codePromo, $user);
            if (!$promo) {
                return response()->json(['success' => false, 'error' => 'Code promo invalide ou expiré.'], 422);
            }
        }

        $calc = CalculMontantDuService::calculerPourEcheance($tmp, $codePromo, true);
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
                    'pending_key' => $pendingKey,
                    'subscription_type' => $item['subscription_type'],
                    'entreprise_id' => (string) $item['entreprise_id'],
                ],
            ]);
        } catch (\Stripe\Exception\CardException $e) {
            $errorCode = $e->getError()->code ?? null;
            $paymentIntent = $e->getError()->payment_intent ?? null;

            // 3DS requis en mode off_session
            if ($errorCode === 'authentication_required' && $paymentIntent) {
                $piId = is_object($paymentIntent) ? $paymentIntent->id : $paymentIntent;
                $clientSecret = is_object($paymentIntent) ? ($paymentIntent->client_secret ?? null) : null;
                if (!$clientSecret && $piId) {
                    try {
                        $piRetrieved = PaymentIntent::retrieve($piId);
                        $clientSecret = $piRetrieved->client_secret ?? null;
                    } catch (\Exception $ex) {
                        Log::warning('chargePending: cannot retrieve PI for 3DS', ['pi' => $piId, 'error' => $ex->getMessage()]);
                    }
                }
                if ($clientSecret) {
                    // Créer l'échéance en_attente pour que le flux 3DS la retrouve
                    $echeance = $this->createEcheanceFromPending($item, $calc, $montantFinal, $piId, Echeance::STATUT_EN_ATTENTE);
                    $this->removePendingFromSession($pendingKey);

                    PaymentAuditLog::log('charge_pending_3ds', $user->id, [
                        'echeance_id' => $echeance->id,
                        'stripe_payment_intent_id' => $piId,
                        'amount' => $montantFinal,
                        'currency' => $currency,
                        'status' => 'requires_action',
                        'message' => 'Nouvelle souscription : 3DS requis.',
                    ]);

                    return response()->json([
                        'requires_action' => true,
                        'client_secret' => $clientSecret,
                        'payment_intent_id' => $piId,
                    ]);
                }
            }

            $msg = self::mapStripeErrorToUserMessage($errorCode, $e->getMessage());
            Log::warning('chargePending card error', [
                'pending_key' => $pendingKey,
                'error_code' => $errorCode,
                'error' => $e->getMessage(),
            ]);
            PaymentAuditLog::log('charge_pending_fail', $user->id, [
                'pending_key' => $pendingKey,
                'amount' => $montantFinal,
                'status' => 'card_error',
                'context' => ['code' => $errorCode, 'raw' => $e->getMessage()],
                'message' => 'Première souscription refusée : ' . $msg,
            ]);

            return response()->json(['success' => false, 'error' => $msg, 'error_code' => $errorCode], 422);
        } catch (\Throwable $e) {
            Log::error('chargePending failed', ['pending_key' => $pendingKey, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Impossible de lancer le paiement. Réessayez.'], 500);
        }

        $status = $pi->status ?? '';

        if ($status === 'requires_action') {
            $echeance = $this->createEcheanceFromPending($item, $calc, $montantFinal, $pi->id, Echeance::STATUT_EN_ATTENTE);
            $this->removePendingFromSession($pendingKey);

            PaymentAuditLog::log('charge_pending_3ds', $user->id, [
                'echeance_id' => $echeance->id,
                'stripe_payment_intent_id' => $pi->id,
                'amount' => $montantFinal,
                'status' => 'requires_action',
                'message' => 'Nouvelle souscription : 3DS requis.',
            ]);

            return response()->json([
                'requires_action' => true,
                'client_secret' => $pi->client_secret,
                'payment_intent_id' => $pi->id,
            ]);
        }

        if ($status === 'succeeded') {
            $echeance = $this->createEcheanceFromPending($item, $calc, $montantFinal, $pi->id, Echeance::STATUT_PAYE);
            PaymentVerificationService::ensureEntrepriseSubscriptionForEcheance($echeance);
            PaymentVerificationService::ensurePremiumAccessForEcheance($echeance);
            PaymentVerificationService::ensureStripeTransactionFromPaymentIntent($pi, $user->id);
            $this->removePendingFromSession($pendingKey);

            if ($echeance->promo_code_id) {
                PromoCode::find($echeance->promo_code_id)?->use();
            }

            PaymentAuditLog::log('charge_pending_ok', $user->id, [
                'echeance_id' => $echeance->id,
                'stripe_payment_intent_id' => $pi->id,
                'amount' => $montantFinal,
                'currency' => $currency,
                'status' => 'succeeded',
                'message' => 'Nouvelle souscription payée – ' . $echeance->libelle(),
            ]);

            return response()->json(['success' => true]);
        }

        return response()->json([
            'success' => false,
            'error' => 'Paiement en attente (status: ' . $status . '). Réessayez ou contactez-nous.',
        ], 422);
    }

    /**
     * Crée une Echeance à partir des données session (pending item).
     */
    private function createEcheanceFromPending(array $item, array $calc, float $montantFinal, string $piId, string $statut): Echeance
    {
        return DB::transaction(function () use ($item, $calc, $montantFinal, $piId, $statut) {
            return Echeance::create([
                'user_id'                  => $item['user_id'],
                'entreprise_id'            => $item['entreprise_id'],
                'subscription_type'        => $item['subscription_type'],
                'payment_origin'           => Echeance::ORIGIN_PROVIDER_SUBSCRIPTION,
                'payment_provider'         => Echeance::PROVIDER_STRIPE,
                'auto_charge_eligible'     => true,
                'periode_debut'            => $item['periode_debut'],
                'periode_fin'              => $item['periode_fin'],
                'jour_facturation'         => $item['jour_facturation'],
                'montant_du'               => $calc['montant_du'],
                'montant_final'            => $montantFinal,
                'reduction_promo'          => $calc['reduction_promo'],
                'promo_code_id'            => $calc['promo_code_id'],
                'statut'                   => $statut,
                'stripe_payment_intent_id' => $piId,
                'paye_at'                  => $statut === Echeance::STATUT_PAYE ? now() : null,
                'metadata'                 => ['lignes' => $calc['lignes']],
            ]);
        });
    }

    /**
     * Retire un item pending de la session.
     */
    private function removePendingFromSession(string $key): void
    {
        $pending = session('checkout_pending', []);
        unset($pending[$key]);
        session(['checkout_pending' => $pending]);
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

        // Protection contre la race condition : vérifier que l'échéance n'est pas déjà payée
        // (peut arriver si le webhook a déjà traité le paiement pendant le 3DS)
        $echeance->refresh();
        if ($echeance->estPayee()) {
            PaymentAuditLog::log('confirm_status_ok', $user->id, [
                'echeance_id' => $echeance->id,
                'stripe_payment_intent_id' => $piId,
                'status' => 'already_paid',
                'context' => ['source' => 'race_condition_prevented'],
                'message' => 'Paiement déjà enregistré (webhook a traité avant).',
            ]);
            return response()->json(['success' => true, 'already_paid' => true]);
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
            ->whereIn('statut', [
                Echeance::STATUT_A_PAYER,
                Echeance::STATUT_EN_ATTENTE,
                Echeance::STATUT_ECHEC,
            ])
            ->findOrFail($request->input('echeance_id'));

        $codePromo = $request->input('code_promo') ?: $request->session()->get('checkout_promo_code');
        $calc = CalculMontantDuService::calculerPourEcheance($echeance, $codePromo, false);
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
        } catch (\Stripe\Exception\CardException $e) {
            $errorCode = $e->getError()->code ?? null;
            $declineCode = $e->getError()->decline_code ?? null;
            Log::error('Checkout creerSessionStripe: CardException', [
                'user_id' => $user->id,
                'echeance_id' => $echeance->id,
                'error_code' => $errorCode,
                'decline_code' => $declineCode,
                'error' => $e->getMessage(),
            ]);
            PaymentAuditLog::log('session_create_fail', $user->id, [
                'echeance_id' => $echeance->id,
                'amount' => $montantFinal,
                'currency' => \App\Models\Tarif::currency(),
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'CardException',
                    'error_code' => $errorCode,
                    'decline_code' => $declineCode,
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Erreur carte lors de la création de la session: ' . $e->getMessage(),
            ]);
            $userMessage = self::mapStripeErrorToUserMessage($errorCode, $e->getMessage());
            return redirect()->route('checkout.index')->with('error', $userMessage);
        } catch (\Stripe\Exception\RateLimitException $e) {
            Log::error('Checkout creerSessionStripe: RateLimitException', [
                'user_id' => $user->id,
                'echeance_id' => $echeance->id,
                'error' => $e->getMessage(),
            ]);
            PaymentAuditLog::log('session_create_fail', $user->id, [
                'echeance_id' => $echeance->id,
                'amount' => $montantFinal,
                'currency' => \App\Models\Tarif::currency(),
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'RateLimitException',
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Trop de requêtes à l\'API Stripe: ' . $e->getMessage(),
            ]);
            return redirect()->route('checkout.index')
                ->with('error', 'Trop de requêtes. Veuillez patienter quelques instants.');
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            Log::error('Checkout creerSessionStripe: InvalidRequestException', [
                'user_id' => $user->id,
                'echeance_id' => $echeance->id,
                'error' => $e->getMessage(),
                'param' => $e->getError()->param ?? null,
            ]);
            PaymentAuditLog::log('session_create_fail', $user->id, [
                'echeance_id' => $echeance->id,
                'amount' => $montantFinal,
                'currency' => \App\Models\Tarif::currency(),
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'InvalidRequestException',
                    'param' => $e->getError()->param ?? null,
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Paramètres invalides pour la session: ' . $e->getMessage(),
            ]);
            return redirect()->route('checkout.index')
                ->with('error', 'Erreur de configuration. Contactez le support.');
        } catch (\Stripe\Exception\AuthenticationException $e) {
            Log::critical('Checkout creerSessionStripe: AuthenticationException', [
                'user_id' => $user->id,
                'echeance_id' => $echeance->id,
                'error' => $e->getMessage(),
            ]);
            PaymentAuditLog::log('session_create_fail', $user->id, [
                'echeance_id' => $echeance->id,
                'amount' => $montantFinal,
                'currency' => \App\Models\Tarif::currency(),
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'AuthenticationException',
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Problème d\'authentification avec Stripe: ' . $e->getMessage(),
            ]);
            return redirect()->route('checkout.index')
                ->with('error', 'Erreur de configuration serveur. Contactez le support.');
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            Log::error('Checkout creerSessionStripe: ApiConnectionException', [
                'user_id' => $user->id,
                'echeance_id' => $echeance->id,
                'error' => $e->getMessage(),
            ]);
            PaymentAuditLog::log('session_create_fail', $user->id, [
                'echeance_id' => $echeance->id,
                'amount' => $montantFinal,
                'currency' => \App\Models\Tarif::currency(),
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'ApiConnectionException',
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Problème réseau avec Stripe: ' . $e->getMessage(),
            ]);
            return redirect()->route('checkout.index')
                ->with('error', 'Problème de connexion. Réessayez dans quelques instants.');
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Checkout creerSessionStripe: ApiErrorException', [
                'user_id' => $user->id,
                'echeance_id' => $echeance->id,
                'error' => $e->getMessage(),
            ]);
            PaymentAuditLog::log('session_create_fail', $user->id, [
                'echeance_id' => $echeance->id,
                'amount' => $montantFinal,
                'currency' => \App\Models\Tarif::currency(),
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'ApiErrorException',
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Erreur API Stripe générique: ' . $e->getMessage(),
            ]);
            return redirect()->route('checkout.index')
                ->with('error', 'Erreur temporaire. Réessayez plus tard.');
        } catch (\Exception $e) {
            Log::error('Checkout Stripe session create failed', [
                'user_id' => $user->id,
                'echeance_id' => $echeance->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            PaymentAuditLog::log('session_create_fail', $user->id, [
                'echeance_id' => $echeance->id,
                'amount' => $montantFinal,
                'currency' => \App\Models\Tarif::currency(),
                'status' => 'failed',
                'context' => [
                    'exception_type' => get_class($e),
                    'raw_error' => $e->getMessage(),
                ],
                'message' => 'Exception inattendue lors de la création de la session: ' . $e->getMessage(),
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

        // Protection contre la race condition : PaymentVerificationService est idempotent
        // et vérifie déjà si l'échéance est payée, mais on log quand même pour traçabilité
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

        // Si déjà payé (race condition avec webhook), message spécial
        $message = $result['already'] 
            ? 'Paiement déjà enregistré (traité automatiquement).' 
            : 'Paiement enregistré. Merci !';

        return redirect()->route('settings.index', ['tab' => 'subscription'])
            ->with('success', $message);
    }

    /**
     * Annuler une échéance brouillon / a_payer depuis la page checkout (POST).
     * L'utilisateur ne sera pas débité. Respecte le principe "intention ≠ dette".
     */
    public function annulerEcheance(Request $request, Echeance $echeance)
    {
        $user = Auth::user();
        if ((int) $echeance->user_id !== (int) $user->id) {
            abort(403, 'Cette échéance ne vous appartient pas.');
        }
        if (!$echeance->estAnnulable()) {
            return redirect()->route('checkout.index')
                ->with('error', 'Cette échéance ne peut plus être annulée.');
        }

        $echeance->update(['statut' => Echeance::STATUT_ANNULE]);

        Log::info('Échéance annulée par l\'utilisateur depuis checkout', [
            'user_id' => $user->id,
            'echeance_id' => $echeance->id,
            'ancien_statut' => $echeance->getOriginal('statut'),
        ]);

        return redirect()->route('checkout.index')
            ->with('success', 'Échéance annulée. Vous ne serez pas débité.');
    }

    public function cancel()
    {
        return redirect()->route('checkout.index');
    }

    /**
     * Page de finalisation de l'authentification 3DS (SCA Recovery)
     * 
     * Quand la banque exige une authentification 3DS en mode off_session,
     * l'utilisateur reçoit un email avec un lien vers cette page.
     * Cette page lance automatiquement la pop-up 3DS pour finaliser le paiement.
     */
    public function authenticatePayment(Request $request, string $paymentIntentId)
    {
        $user = Auth::user();
        
        // Vérifier que le PaymentIntent appartient à l'utilisateur
        $echeance = Echeance::where('user_id', $user->id)
            ->where('stripe_payment_intent_id', $paymentIntentId)
            ->where('statut', Echeance::STATUT_EN_ATTENTE)
            ->first();
        
        if (!$echeance) {
            return redirect()->route('checkout.index')
                ->with('error', 'Paiement introuvable ou déjà traité.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));
        
        try {
            $pi = PaymentIntent::retrieve($paymentIntentId);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            Log::error('Payment authenticate: InvalidRequestException', [
                'payment_intent_id' => $paymentIntentId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            PaymentAuditLog::log('authenticate_payment_fail', $user->id, [
                'stripe_payment_intent_id' => $paymentIntentId,
                'echeance_id' => $echeance->id ?? null,
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'InvalidRequestException',
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'PaymentIntent introuvable ou invalide: ' . $e->getMessage(),
            ]);
            return redirect()->route('checkout.index')
                ->with('error', 'Paiement introuvable ou invalide.');
        } catch (\Stripe\Exception\AuthenticationException $e) {
            Log::critical('Payment authenticate: AuthenticationException', [
                'payment_intent_id' => $paymentIntentId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            PaymentAuditLog::log('authenticate_payment_fail', $user->id, [
                'stripe_payment_intent_id' => $paymentIntentId,
                'echeance_id' => $echeance->id ?? null,
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'AuthenticationException',
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Problème d\'authentification avec Stripe: ' . $e->getMessage(),
            ]);
            return redirect()->route('checkout.index')
                ->with('error', 'Erreur de configuration serveur. Contactez le support.');
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            Log::error('Payment authenticate: ApiConnectionException', [
                'payment_intent_id' => $paymentIntentId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            PaymentAuditLog::log('authenticate_payment_fail', $user->id, [
                'stripe_payment_intent_id' => $paymentIntentId,
                'echeance_id' => $echeance->id ?? null,
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'ApiConnectionException',
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Problème réseau avec Stripe: ' . $e->getMessage(),
            ]);
            return redirect()->route('checkout.index')
                ->with('error', 'Problème de connexion. Réessayez dans quelques instants.');
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Payment authenticate: ApiErrorException', [
                'payment_intent_id' => $paymentIntentId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            PaymentAuditLog::log('authenticate_payment_fail', $user->id, [
                'stripe_payment_intent_id' => $paymentIntentId,
                'echeance_id' => $echeance->id ?? null,
                'status' => 'failed',
                'context' => [
                    'exception_type' => 'ApiErrorException',
                    'raw_error' => json_encode($e->getError()),
                ],
                'message' => 'Erreur API Stripe générique: ' . $e->getMessage(),
            ]);
            return redirect()->route('checkout.index')
                ->with('error', 'Erreur temporaire. Réessayez plus tard.');
        } catch (\Exception $e) {
            Log::error('Payment authenticate: impossible de récupérer le PaymentIntent', [
                'payment_intent_id' => $paymentIntentId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            PaymentAuditLog::log('authenticate_payment_fail', $user->id, [
                'stripe_payment_intent_id' => $paymentIntentId,
                'echeance_id' => $echeance->id ?? null,
                'status' => 'failed',
                'context' => [
                    'exception_type' => get_class($e),
                    'raw_error' => $e->getMessage(),
                ],
                'message' => 'Exception inattendue lors de la récupération du PaymentIntent: ' . $e->getMessage(),
            ]);
            return redirect()->route('checkout.index')
                ->with('error', 'Impossible de récupérer les informations de paiement.');
        }

        // Vérifier que le PaymentIntent nécessite toujours une action
        if ($pi->status === 'succeeded') {
            // Déjà payé, rediriger vers la confirmation
            $result = PaymentVerificationService::markEcheancePaidFromPaymentIntent($paymentIntentId);
            return redirect()->route('checkout.index')
                ->with('success', 'Paiement déjà effectué. Merci !');
        }

        if ($pi->status !== 'requires_action') {
            return redirect()->route('checkout.index')
                ->with('error', 'Ce paiement ne nécessite plus d\'authentification.');
        }

        return view('checkout.authenticate', [
            'payment_intent_id' => $paymentIntentId,
            'client_secret' => $pi->client_secret,
            'echeance' => $echeance,
        ]);
    }

    /**
     * Mapper les codes d'erreur Stripe vers des messages français clairs pour l'utilisateur
     * 
     * C'est crucial pour l'UX : le client doit comprendre que c'est SA banque qui refuse,
     * pas un bug du site. Cela évite la frustration et les appels support inutiles.
     * 
     * @param string|null $errorCode Code d'erreur Stripe
     * @param string $rawMessage Message brut de Stripe
     * @return string Message français clair pour l'utilisateur
     */
    private static function mapStripeErrorToUserMessage(?string $errorCode, string $rawMessage): string
    {
        // Messages spécifiques selon le code d'erreur
        $errorMessages = [
            'insufficient_funds' => 'Solde insuffisant sur cette carte. Vérifiez votre compte bancaire ou utilisez une autre carte.',
            'card_declined' => 'Votre banque a refusé le paiement. Contactez votre banque pour connaître la raison ou utilisez une autre carte.',
            'expired_card' => 'Cette carte a expiré. Veuillez utiliser une autre carte ou mettre à jour vos informations de paiement.',
            'incorrect_cvc' => 'Le code de sécurité (CVC) est incorrect. Vérifiez les 3 chiffres au dos de votre carte.',
            'incorrect_number' => 'Le numéro de carte est incorrect. Vérifiez les 16 chiffres de votre carte.',
            'processing_error' => 'Votre banque a rencontré une erreur lors du traitement. Réessayez dans quelques instants.',
            'generic_decline' => 'Votre banque a refusé le paiement sans raison spécifique. Contactez votre banque ou utilisez une autre carte.',
            'lost_card' => 'Cette carte a été signalée comme perdue. Utilisez une autre carte.',
            'stolen_card' => 'Cette carte a été signalée comme volée. Utilisez une autre carte.',
            'pickup_card' => 'Votre banque a demandé la récupération de cette carte. Contactez votre banque.',
            'restricted_card' => 'Cette carte est restreinte. Contactez votre banque.',
            'security_violation' => 'Votre banque a détecté une violation de sécurité. Contactez votre banque.',
            'service_not_allowed' => 'Cette carte ne permet pas ce type de transaction. Contactez votre banque.',
            'stop_payment_order' => 'Un ordre d\'arrêt de paiement a été émis pour cette carte. Contactez votre banque.',
            'testmode_decline' => 'Cette carte de test a été refusée. Utilisez une carte de test valide.',
            'withdrawal_count_limit_exceeded' => 'Vous avez atteint la limite de retraits autorisés. Contactez votre banque.',
        ];

        // Si on a un code spécifique, utiliser le message correspondant
        if ($errorCode && isset($errorMessages[$errorCode])) {
            return $errorMessages[$errorCode];
        }

        // Sinon, analyser le message brut pour détecter des mots-clés
        $lowerMessage = strtolower($rawMessage);
        
        if (str_contains($lowerMessage, 'insufficient') || str_contains($lowerMessage, 'fond')) {
            return 'Solde insuffisant sur cette carte. Vérifiez votre compte bancaire ou utilisez une autre carte.';
        }
        
        if (str_contains($lowerMessage, 'declined') || str_contains($lowerMessage, 'refus')) {
            return 'Votre banque a refusé le paiement. Contactez votre banque pour connaître la raison ou utilisez une autre carte.';
        }
        
        if (str_contains($lowerMessage, 'expired') || str_contains($lowerMessage, 'expir')) {
            return 'Cette carte a expiré. Veuillez utiliser une autre carte ou mettre à jour vos informations de paiement.';
        }
        
        if (str_contains($lowerMessage, 'cvc') || str_contains($lowerMessage, 'security code')) {
            return 'Le code de sécurité (CVC) est incorrect. Vérifiez les 3 chiffres au dos de votre carte.';
        }

        // Message générique mais qui indique que c'est la banque
        return 'Votre banque a refusé le paiement. Contactez votre banque pour connaître la raison ou utilisez une autre carte.';
    }
}
