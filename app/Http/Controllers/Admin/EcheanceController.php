<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MarkManualEcheancePaidRequest;
use App\Http\Requests\Admin\StoreManualEcheanceRequest;
use App\Models\Echeance;
use App\Models\User;
use App\Services\Payments\ManualDebtService;
use App\Services\RefundService;
use Illuminate\Http\Request;

class EcheanceController extends Controller
{
    public function __construct(private readonly ManualDebtService $manualDebtService)
    {
    }

    public function index(Request $request)
    {
        $q = Echeance::query()->with(['user', 'entreprise', 'promoCode']);

        if ($request->filled('statut')) {
            $q->where('statut', $request->statut);
        }
        if ($request->filled('user_id')) {
            $q->where('user_id', $request->user_id);
        }
        if ($request->filled('entreprise_id')) {
            $q->where('entreprise_id', $request->entreprise_id);
        }
        if ($request->filled('type')) {
            $q->where('subscription_type', $request->type);
        }
        if ($request->filled('payment_origin')) {
            $q->where('payment_origin', $request->payment_origin);
        }
        if ($request->filled('payment_provider')) {
            if ($request->payment_provider === 'none') {
                $q->whereNull('payment_provider');
            } else {
                $q->where('payment_provider', $request->payment_provider);
            }
        }
        if ($request->filled('auto_charge_eligible')) {
            $q->where('auto_charge_eligible', $request->auto_charge_eligible === '1');
        }
        if ($request->filled('date_debut')) {
            $q->whereDate('periode_debut', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $q->whereDate('periode_fin', '<=', $request->date_fin);
        }
        if ($request->filled('search')) {
            $term = $request->search;
            $q->whereHas('user', function ($u) use ($term) {
                $u->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        $echeances = $q->orderByDesc('created_at')->paginate(50)->withQueryString();

        $stats = [
            'a_payer' => Echeance::where('statut', Echeance::STATUT_A_PAYER)->count(),
            'en_attente' => Echeance::where('statut', Echeance::STATUT_EN_ATTENTE)->count(),
            'paye' => Echeance::where('statut', Echeance::STATUT_PAYE)->count(),
            'echec' => Echeance::where('statut', Echeance::STATUT_ECHEC)->count(),
            'annule' => Echeance::where('statut', Echeance::STATUT_ANNULE)->count(),
            'arrete' => Echeance::where('statut', Echeance::STATUT_ARRETE)->count(),
            'rembourse' => Echeance::where('statut', Echeance::STATUT_REMBOURSE)->count(),
        ];

        return view('admin.echeances.index', [
            'echeances' => $echeances,
            'stats' => $stats,
            'users' => User::orderBy('name')->limit(500)->get(['id', 'name', 'email']),
        ]);
    }

    public function storeManual(StoreManualEcheanceRequest $request)
    {
        $echeance = $this->manualDebtService->createManualDebt($request->validated(), (int) auth()->id());

        return back()->with('success', "Dette manuelle #{$echeance->id} créée.");
    }

    public function markPaidManual(MarkManualEcheancePaidRequest $request, Echeance $echeance)
    {
        if ($echeance->estPayee()) {
            return back()->with('error', 'Cette échéance est déjà payée.');
        }

        $this->manualDebtService->markManualPaid($echeance, $request->validated(), (int) auth()->id());

        return back()->with('success', "Échéance #{$echeance->id} marquée payée.");
    }

    public function convertStripeDebtToManual(Echeance $echeance)
    {
        if ($echeance->estPayee()) {
            return back()->with('error', 'Impossible de convertir une échéance déjà payée.');
        }
        if ($echeance->payment_origin === Echeance::ORIGIN_MANUAL) {
            return back()->with('error', 'Cette échéance est déjà manuelle.');
        }

        $metadata = $echeance->metadata ?? [];
        $metadata['converted_to_manual_by_admin_id'] = auth()->id();
        $metadata['converted_to_manual_at'] = now()->toIso8601String();

        $echeance->update([
            'payment_origin' => Echeance::ORIGIN_MANUAL,
            'payment_provider' => null,
            'auto_charge_eligible' => false,
            'metadata' => $metadata,
        ]);

        return back()->with('success', 'Dette convertie en manuel (plus de prélèvement auto).');
    }

    public function markOfflineSettled(Echeance $echeance, Request $request)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        if ($echeance->estPayee()) {
            return back()->with('error', 'Cette échéance est déjà payée.');
        }

        $metadata = $echeance->metadata ?? [];
        $metadata['offline_settled_by_admin_id'] = auth()->id();
        $metadata['offline_settled_at'] = now()->toIso8601String();
        $metadata['offline_settled_note'] = $validated['note'] ?? null;

        $echeance->update([
            'statut' => Echeance::STATUT_PAYE,
            'payment_origin' => Echeance::ORIGIN_MANUAL,
            'payment_provider' => null,
            'auto_charge_eligible' => false,
            'paye_at' => now(),
            'metadata' => $metadata,
        ]);

        return back()->with('success', 'Dette marquée réglée hors-ligne.');
    }

    public function updateReduction(Request $request, Echeance $echeance)
    {
        $validated = $request->validate([
            'reduction_manuel' => 'required|numeric|min:0',
            'reduction_manuel_notes' => 'nullable|string|max:500',
        ]);

        $echeance->update([
            'reduction_manuel' => $validated['reduction_manuel'],
            'reduction_manuel_notes' => $validated['reduction_manuel_notes'] ?? null,
        ]);

        return back()->with('success', 'Réduction enregistrée.');
    }

    public function marquerArrete(Echeance $echeance)
    {
        if ($echeance->estPayee()) {
            return back()->with('error', 'Impossible d\'arrêter une échéance déjà payée.');
        }
        $echeance->update(['statut' => Echeance::STATUT_ARRETE]);
        return back()->with('success', 'Échéance marquée comme arrêtée.');
    }

    public function marquerAnnule(Echeance $echeance)
    {
        if ($echeance->estPayee()) {
            return back()->with('error', 'Impossible d\'annuler une échéance déjà payée.');
        }
        $echeance->update(['statut' => Echeance::STATUT_ANNULE]);
        return back()->with('success', 'Échéance annulée.');
    }

    /**
     * Rembourser une échéance payée (total ou partiel) via Stripe.
     */
    public function refund(Request $request, Echeance $echeance)
    {
        $validated = $request->validate([
            'refund_type' => 'required|in:total,partiel',
            'refund_amount' => 'required_if:refund_type,partiel|nullable|numeric|min:0.01',
            'refund_reason' => 'required|in:requested_by_customer,duplicate,fraudulent',
            'refund_notes' => 'nullable|string|max:1000',
        ]);

        $amount = $validated['refund_type'] === 'total' ? null : (float) $validated['refund_amount'];

        $result = RefundService::refund(
            echeance: $echeance,
            amount: $amount,
            reason: $validated['refund_reason'],
            notes: $validated['refund_notes'] ?? null,
            adminId: auth()->id(),
        );

        if ($result['ok']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }
}
