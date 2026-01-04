<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\EntrepriseFinance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EntrepriseFinanceController extends Controller
{
    /**
     * Voir les finances de l'entreprise
     */
    public function index(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403);
        }

        $query = $entreprise->finances();

        // Filtres
        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('date_record', $request->month)
                  ->whereYear('date_record', $request->year);
        } elseif ($request->filled('year')) {
            $query->whereYear('date_record', $request->year);
        } else {
            // Par défaut, mois en cours
            $query->whereMonth('date_record', now()->month)
                  ->whereYear('date_record', now()->year);
        }

        $finances = $query->get();
        
        // Calculs des totaux pour la période sélectionnée
        $totalIncome = $finances->where('type', 'income')->sum('amount');
        $totalExpense = $finances->where('type', 'expense')->sum('amount');
        
        // Calcul des charges estimées (URSSAF)
        $chargesEstimées = $this->calculateEstimatedCharges($entreprise, $totalIncome);

        return view('entreprise.dashboard.tabs.finances', [
            'entreprise' => $entreprise,
            'finances' => $finances,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'chargesEstimées' => $chargesEstimées,
            'selectedMonth' => $request->get('month', now()->month),
            'selectedYear' => $request->get('year', now()->year),
        ]);
    }

    /**
     * Enregistrer une nouvelle recette ou dépense
     */
    public function store(Request $request, $slug)
    {
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        
        if (!$entreprise->peutEtreGereePar(Auth::user()) && !Auth::user()->is_admin) {
            abort(403);
        }

        $validated = $request->validate([
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'date_record' => 'required|date',
        ]);

        $finance = $entreprise->finances()->create($validated);

        return back()->with('success', 'Enregistrement ajouté avec succès.');
    }

    /**
     * Mettre à jour un enregistrement
     */
    public function update(Request $request, $slug, $id)
    {
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        $finance = EntrepriseFinance::where('entreprise_id', $entreprise->id)->findOrFail($id);

        if (!$entreprise->peutEtreGereePar(Auth::user()) && !Auth::user()->is_admin) {
            abort(403);
        }

        $validated = $request->validate([
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'date_record' => 'required|date',
        ]);

        $finance->update($validated);

        return back()->with('success', 'Enregistrement mis à jour.');
    }

    /**
     * Supprimer un enregistrement
     */
    public function destroy($slug, $id)
    {
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        $finance = EntrepriseFinance::where('entreprise_id', $entreprise->id)->findOrFail($id);

        if (!$entreprise->peutEtreGereePar(Auth::user()) && !Auth::user()->is_admin) {
            abort(403);
        }

        $finance->delete();

        return back()->with('success', 'Enregistrement supprimé.');
    }

    /**
     * Calcul des charges estimées (URSSAF + Impôts)
     */
    public function calculateEstimatedCharges(Entreprise $entreprise, $totalIncome)
    {
        $type = $entreprise->type_activite;
        // Dans le calculateur actuel de outils.blade.php:
        // Prestation de services (BIC) - 21.2%
        // Profession libérale (BNC) - 21.1%
        // Vente de marchandises - 12.3%
        
        $tauxUrssaf = 0.212; // Par défaut
        if (stripos($type, 'vente') !== false) {
            $tauxUrssaf = 0.123;
        } elseif (stripos($type, 'liberale') !== false || stripos($type, 'bnc') !== false) {
            $tauxUrssaf = 0.211;
        }

        $urssaf = $totalIncome * $tauxUrssaf;
        
        // Impôt sur le revenu (Prélèvement libératoire si activé, sinon estimation simplifiée)
        // Pour faire simple, 2.2% pour prestation, 1.7% pour libéral, 1% pour vente
        $tauxImpot = 0.022;
        if (stripos($type, 'vente') !== false) {
            $tauxImpot = 0.01;
        } elseif (stripos($type, 'liberale') !== false || stripos($type, 'bnc') !== false) {
            $tauxImpot = 0.017;
        }
        
        $impot = $totalIncome * $tauxImpot;

        return [
            'urssaf' => $urssaf,
            'impot' => $impot,
            'total' => $urssaf + $impot,
            'taux_combine' => ($tauxUrssaf + $tauxImpot) * 100
        ];
    }
}
