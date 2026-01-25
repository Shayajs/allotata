<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Echeance;
use App\Models\User;
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
}
