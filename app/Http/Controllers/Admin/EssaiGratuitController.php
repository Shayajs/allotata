<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EssaiGratuit;
use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EssaiGratuitController extends Controller
{
    /**
     * Liste des essais gratuits
     */
    public function index(Request $request)
    {
        $filter = $request->input('filter', 'all');
        $search = $request->input('search');

        $query = EssaiGratuit::with(['essayable', 'accordeParAdmin', 'parrain'])
            ->orderByDesc('created_at');

        // Filtres par statut
        switch ($filter) {
            case 'actifs':
                $query->actifs();
                break;
            case 'expires':
                $query->where('statut', 'expire');
                break;
            case 'convertis':
                $query->where('statut', 'converti');
                break;
            case 'annules':
                $query->whereIn('statut', ['annule', 'revoque']);
                break;
            case 'users':
                $query->where('essayable_type', 'App\\Models\\User');
                break;
            case 'entreprises':
                $query->where('essayable_type', 'App\\Models\\Entreprise');
                break;
        }

        // Recherche
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHasMorph('essayable', [User::class], function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhereHasMorph('essayable', [Entreprise::class], function ($entrepriseQuery) use ($search) {
                    $entrepriseQuery->where('nom', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                });
            });
        }

        $essais = $query->paginate(20);

        // Statistiques
        $stats = $this->getStats();

        return view('admin.essais-gratuits.index', compact('essais', 'filter', 'search', 'stats'));
    }

    /**
     * Récupère les statistiques
     */
    private function getStats(): array
    {
        $total = EssaiGratuit::count();
        $actifs = EssaiGratuit::actifs()->count();
        $convertis = EssaiGratuit::where('statut', 'converti')->count();
        $expires = EssaiGratuit::where('statut', 'expire')->count();

        $tauxConversion = $total > 0 ? round(($convertis / $total) * 100, 1) : 0;

        // Par type
        $parType = EssaiGratuit::selectRaw('type_abonnement, COUNT(*) as total')
            ->groupBy('type_abonnement')
            ->pluck('total', 'type_abonnement')
            ->toArray();

        // Par source
        $parSource = EssaiGratuit::selectRaw('source, COUNT(*) as total')
            ->whereNotNull('source')
            ->groupBy('source')
            ->orderByDesc('total')
            ->limit(5)
            ->pluck('total', 'source')
            ->toArray();

        // Conversions par source
        $conversionParSource = EssaiGratuit::selectRaw('source, 
            COUNT(*) as total,
            SUM(CASE WHEN statut = "converti" THEN 1 ELSE 0 END) as convertis')
            ->whereNotNull('source')
            ->groupBy('source')
            ->get()
            ->map(function ($item) {
                $item->taux = $item->total > 0 ? round(($item->convertis / $item->total) * 100, 1) : 0;
                return $item;
            });

        // 7 derniers jours
        $derniersSeptJours = EssaiGratuit::where('date_debut', '>=', now()->subDays(7))->count();

        // Expirent bientôt (dans 2 jours)
        $expirentBientot = EssaiGratuit::expirantDans(2)->count();

        return [
            'total' => $total,
            'actifs' => $actifs,
            'convertis' => $convertis,
            'expires' => $expires,
            'taux_conversion' => $tauxConversion,
            'par_type' => $parType,
            'par_source' => $parSource,
            'conversion_par_source' => $conversionParSource,
            'derniers_sept_jours' => $derniersSeptJours,
            'expirent_bientot' => $expirentBientot,
        ];
    }

    /**
     * Accorder un essai manuellement
     */
    public function accorder(Request $request)
    {
        $request->validate([
            'type_cible' => 'required|in:user,entreprise',
            'cible_id' => 'required|integer',
            'type_abonnement' => 'required|string',
            'duree_jours' => 'required|integer|min:1|max:90',
            'notes' => 'nullable|string|max:500',
        ]);

        $admin = Auth::user();
        $typeCible = $request->input('type_cible');
        $cibleId = $request->input('cible_id');
        $typeAbonnement = $request->input('type_abonnement');
        $dureeJours = $request->input('duree_jours');
        $notes = $request->input('notes');

        // Récupérer l'entité cible
        if ($typeCible === 'user') {
            $cible = User::findOrFail($cibleId);
        } else {
            $cible = Entreprise::findOrFail($cibleId);
        }

        // Vérifier qu'il n'y a pas déjà un essai actif
        if ($cible->aEssaiEnCours($typeAbonnement)) {
            return back()->with('error', 'Un essai gratuit est déjà en cours pour ce type.');
        }

        // Créer l'essai
        $essai = $cible->demarrerEssai(
            type: $typeAbonnement,
            jours: $dureeJours,
            source: 'admin_manuel',
            adminId: $admin->id,
            notesAdmin: $notes,
        );

        return back()->with('success', "Essai gratuit de {$dureeJours} jours accordé avec succès !");
    }

    /**
     * Révoquer un essai
     */
    public function revoquer(Request $request, EssaiGratuit $essai)
    {
        $request->validate([
            'raison' => 'required|string|max:255',
        ]);

        $essai->revoquer($request->input('raison'));

        return back()->with('success', 'Essai révoqué avec succès.');
    }

    /**
     * Prolonger un essai
     */
    public function prolonger(Request $request, EssaiGratuit $essai)
    {
        $request->validate([
            'jours_supplementaires' => 'required|integer|min:1|max:30',
        ]);

        $joursSupp = $request->input('jours_supplementaires');
        $nouvelleDateFin = $essai->date_fin->addDays($joursSupp);

        $essai->update([
            'date_fin' => $nouvelleDateFin,
            'duree_jours' => $essai->duree_jours + $joursSupp,
            'statut' => 'actif', // Réactive si expiré
        ]);

        return back()->with('success', "Essai prolongé de {$joursSupp} jours. Nouvelle date de fin : " . $nouvelleDateFin->format('d/m/Y H:i'));
    }

    /**
     * Export CSV des essais
     */
    public function export(Request $request)
    {
        $essais = EssaiGratuit::with(['essayable', 'accordeParAdmin'])
            ->orderByDesc('created_at')
            ->get();

        $filename = 'essais_gratuits_' . now()->format('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($essais) {
            $file = fopen('php://output', 'w');
            
            // En-têtes CSV
            fputcsv($file, [
                'ID',
                'Type Cible',
                'Nom Cible',
                'Email Cible',
                'Type Abonnement',
                'Date Début',
                'Date Fin',
                'Durée (jours)',
                'Statut',
                'Source',
                'Connexions',
                'Actions',
                'Note Satisfaction',
                'Converti',
                'Créé le',
            ]);

            foreach ($essais as $essai) {
                $cible = $essai->essayable;
                $nomCible = $cible instanceof User ? $cible->name : ($cible instanceof Entreprise ? $cible->nom : 'N/A');
                $emailCible = $cible->email ?? 'N/A';

                fputcsv($file, [
                    $essai->id,
                    class_basename($essai->essayable_type),
                    $nomCible,
                    $emailCible,
                    $essai->type_abonnement,
                    $essai->date_debut->format('Y-m-d H:i'),
                    $essai->date_fin->format('Y-m-d H:i'),
                    $essai->duree_jours,
                    $essai->statut,
                    $essai->source ?? 'N/A',
                    $essai->nb_connexions,
                    $essai->nb_actions,
                    $essai->note_satisfaction ?? 'N/A',
                    $essai->statut === 'converti' ? 'Oui' : 'Non',
                    $essai->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Statistiques détaillées (API pour graphiques)
     */
    public function statsApi(Request $request)
    {
        $periode = $request->input('periode', '30'); // jours

        // Essais par jour
        $essaisParJour = EssaiGratuit::selectRaw('DATE(date_debut) as date, COUNT(*) as total')
            ->where('date_debut', '>=', now()->subDays($periode))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Conversions par jour
        $conversionsParJour = EssaiGratuit::selectRaw('DATE(date_conversion) as date, COUNT(*) as total')
            ->where('statut', 'converti')
            ->where('date_conversion', '>=', now()->subDays($periode))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Engagement moyen
        $engagementMoyen = EssaiGratuit::selectRaw('
            AVG(nb_connexions) as connexions_moy,
            AVG(nb_actions) as actions_moy
        ')->first();

        return response()->json([
            'essais_par_jour' => $essaisParJour,
            'conversions_par_jour' => $conversionsParJour,
            'engagement_moyen' => $engagementMoyen,
        ]);
    }
}
