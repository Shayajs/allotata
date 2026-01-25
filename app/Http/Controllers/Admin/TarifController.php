<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tarif;
use App\Services\StripeCustomerService;
use App\Services\StripeTestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TarifController extends Controller
{
    /**
     * Afficher la page Tarifs (ex Prix Stripe). On définit les prix ici, plus de Stripe ni .env.
     */
    public function index()
    {
        $tarifs = Tarif::allForAdmin();
        return view('admin.tarifs.index', ['tarifs' => $tarifs]);
    }

    /**
     * Mettre à jour un tarif.
     */
    public function update(Request $request, string $type)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'label' => 'nullable|string|max:255',
        ]);
        $tarif = Tarif::where('type', $type)->firstOrFail();
        $tarif->update($validated);
        return back()->with('success', "Tarif « {$tarif->label} » mis à jour.");
    }

    /**
     * Vérifier les clés Stripe (communication API).
     */
    public function verifyStripeKeys(Request $request)
    {
        $result = StripeTestService::verifyKeys();
        if ($result['ok']) {
            return back()->with('stripe_test_ok', $result['message']);
        }
        return back()->with('stripe_test_error', $result['message']);
    }

    /**
     * Créer une session Checkout "paiement test" (0,50 €) et rediriger vers Stripe.
     */
    public function testPayment(Request $request)
    {
        $successUrl = route('admin.stripe-prices.test-success') . '?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = route('admin.stripe-prices.index');
        $email = Auth::check() ? Auth::user()->email : null;

        $result = StripeTestService::createTestCheckoutSession($successUrl, $cancelUrl, $email);
        if (!$result['ok']) {
            return back()->with('stripe_test_error', $result['message']);
        }
        return redirect($result['url']);
    }

    /**
     * Retour après paiement test Stripe (admin). Affiche succès sans toucher aux échéances.
     */
    public function testSuccess(Request $request)
    {
        $sessionId = $request->query('session_id');
        if (!$sessionId) {
            return redirect()->route('admin.stripe-prices.index')
                ->with('stripe_test_error', 'Session de paiement test introuvable.');
        }

        $result = StripeTestService::verifyTestSession($sessionId);
        if (!$result['ok']) {
            return redirect()->route('admin.stripe-prices.index')
                ->with('stripe_test_error', $result['message']);
        }
        return redirect()->route('admin.stripe-prices.index')
            ->with('stripe_test_ok', $result['paid'] ? 'Paiement test (0,50 €) reçu. Modules OK.' : $result['message']);
    }

    /**
     * Page "Test Setup" : enregistrer une carte via Setup Intent (Elements), sans débiter.
     * Si le Setup fonctionne, le débit API fonctionnera (X jours gratuits, etc.).
     */
    public function testSetupPage()
    {
        return view('admin.tarifs.test-setup');
    }

    /**
     * Redirection après succès Test Setup (flash).
     */
    public function testSetupSuccess()
    {
        return redirect()->route('admin.stripe-prices.index')
            ->with('stripe_test_ok', 'Carte enregistrée pour test. Vous pouvez lancer « Test débit API ».');
    }

    /**
     * Créer un SetupIntent pour le test (admin). JSON.
     */
    public function createTestSetupIntent(Request $request)
    {
        $user = Auth::user();
        $result = StripeTestService::createTestSetupIntent($user);
        if (!$result['ok']) {
            return response()->json(['error' => $result['message']], 422);
        }
        return response()->json(['client_secret' => $result['client_secret']]);
    }

    /**
     * Sauvegarder le PM après confirmSetup (test admin). Body: { "payment_method": "pm_xxx" }
     */
    public function saveTestPaymentMethod(Request $request)
    {
        $request->validate(['payment_method' => 'required|string|starts_with:pm_']);
        $user = Auth::user();
        $customerId = StripeCustomerService::ensureCustomer($user);
        $pmId = $request->input('payment_method');

        try {
            StripeCustomerService::attachPaymentMethod($customerId, $pmId);
            $display = StripeCustomerService::cardDisplayFromPaymentMethod($pmId);
        } catch (\Throwable $e) {
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
     * Débit API 0,50 € (admin) avec la carte enregistrée. Aucune redirection Stripe.
     */
    public function testDebitApi(Request $request)
    {
        $user = Auth::user();
        $result = StripeTestService::chargeTestPaymentMethod($user);
        if ($result['ok']) {
            return redirect()->route('admin.stripe-prices.index')
                ->with('stripe_test_ok', $result['message']);
        }
        return redirect()->route('admin.stripe-prices.index')
            ->with('stripe_test_error', $result['message']);
    }
}
