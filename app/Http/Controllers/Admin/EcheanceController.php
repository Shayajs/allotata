<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Echeance;
use App\Models\User;
use App\Services\RefundService;
use Illuminate\Http\Request;

class EcheanceController extends Controller
{
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
        ]);
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
