<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\EntrepriseMembre;
use App\Models\MembreDisponibilite;
use App\Models\MembreIndisponibilite;
use App\Models\MembreStatistique;
use App\Models\Reservation;
use App\Services\MembreSelectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MembreGestionController extends Controller
{
    protected $selectionService;

    public function __construct(MembreSelectionService $selectionService)
    {
        $this->selectionService = $selectionService;
    }

    /**
     * Liste des membres avec statistiques
     */
    public function index(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user)) {
            return redirect()->route('dashboard')
                ->with('error', 'Vous n\'avez pas accès à cette entreprise.');
        }

        // Vérifier l'abonnement
        if (!$entreprise->aGestionMultiPersonnes()) {
            return redirect()->route('entreprise.dashboard', $slug)
                ->with('error', 'Cette fonctionnalité nécessite l\'abonnement Gestion Multi-Personnes.');
        }

        $membres = $entreprise->membres()
            ->with('user')
            ->get();

        // Calculer les stats pour chaque membre
        $membresAvecStats = $membres->map(function($membre) {
            $moisActuel = now();
            $dateDebut = $moisActuel->copy()->startOfMonth();
            $dateFin = $moisActuel->copy()->endOfMonth();
            
            $charge = $membre->getChargeTravail($dateDebut, $dateFin);
            
            return [
                'membre' => $membre,
                'stats' => [
                    'reservations_mois' => $charge['nombre_reservations'],
                    'revenu_mois' => $charge['revenu_total'],
                    'duree_totale' => $charge['duree_totale_minutes'],
                ],
            ];
        });

        return view('entreprise.dashboard.tabs.equipe', [
            'entreprise' => $entreprise,
            'membresAvecStats' => $membresAvecStats,
        ]);
    }

    /**
     * Détails d'un membre (agenda, stats, disponibilités)
     */
    public function show(Request $request, $slug, EntrepriseMembre $membre)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user)) {
            return redirect()->route('dashboard')
                ->with('error', 'Vous n\'avez pas accès à cette entreprise.');
        }

        // Vérifier que le membre appartient à l'entreprise
        if ($membre->entreprise_id !== $entreprise->id) {
            return redirect()->route('entreprise.equipe.index', $slug)
                ->with('error', 'Membre introuvable.');
        }

        // Vérifier l'abonnement
        if (!$entreprise->aGestionMultiPersonnes()) {
            return redirect()->route('entreprise.dashboard', $slug)
                ->with('error', 'Cette fonctionnalité nécessite l\'abonnement Gestion Multi-Personnes.');
        }

        $membre->load(['user', 'disponibilites', 'indisponibilites']);

        // Stats du mois
        $moisActuel = now();
        $dateDebut = $moisActuel->copy()->startOfMonth();
        $dateFin = $moisActuel->copy()->endOfMonth();
        $statsMois = $membre->getChargeTravail($dateDebut, $dateFin);

        // Stats des 7 derniers jours
        $dateDebutSemaine = now()->subDays(7);
        $statsSemaine = $membre->getChargeTravail($dateDebutSemaine, now());

        // Déterminer l'onglet actif depuis la requête
        $activeSubTab = $request->get('tab', 'agenda');

        return view('entreprise.dashboard.tabs.equipe-show', [
            'entreprise' => $entreprise,
            'membre' => $membre,
            'statsMois' => $statsMois,
            'statsSemaine' => $statsSemaine,
            'activeSubTab' => $activeSubTab,
        ]);
    }

    /**
     * Mettre à jour les disponibilités (horaires réguliers) d'un membre
     */
    public function updateDisponibilites(Request $request, $slug, EntrepriseMembre $membre)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user)) {
            return back()->withErrors(['error' => 'Vous n\'avez pas accès à cette entreprise.']);
        }

        if ($membre->entreprise_id !== $entreprise->id) {
            return back()->withErrors(['error' => 'Membre introuvable.']);
        }

        if (!$entreprise->aGestionMultiPersonnes()) {
            return back()->withErrors(['error' => 'Cette fonctionnalité nécessite l\'abonnement Gestion Multi-Personnes.']);
        }

        $validated = $request->validate([
            'horaires' => 'required|array',
            'horaires.*.jour_semaine' => 'required|integer|min:0|max:6',
            'horaires.*.heure_debut' => 'nullable|date_format:H:i',
            'horaires.*.heure_fin' => 'nullable|date_format:H:i',
            'horaires.*.est_disponible' => 'boolean',
        ]);

        // Supprimer les anciennes disponibilités non exceptionnelles
        MembreDisponibilite::where('membre_id', $membre->id)
            ->where('est_exceptionnel', false)
            ->delete();

        // Créer les nouvelles disponibilités
        foreach ($validated['horaires'] as $horaire) {
            if (!empty($horaire['heure_debut']) && !empty($horaire['heure_fin'])) {
                MembreDisponibilite::create([
                    'membre_id' => $membre->id,
                    'jour_semaine' => $horaire['jour_semaine'],
                    'heure_debut' => $horaire['heure_debut'],
                    'heure_fin' => $horaire['heure_fin'],
                    'est_disponible' => $horaire['est_disponible'] ?? true,
                    'est_exceptionnel' => false,
                ]);
            }
        }

        return redirect()->route('entreprise.equipe.show', [$slug, $membre])
            ->with('success', 'Les disponibilités ont été mises à jour.');
    }

    /**
     * Ajouter une indisponibilité ponctuelle
     */
    public function storeIndisponibilite(Request $request, $slug, EntrepriseMembre $membre)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user)) {
            return back()->withErrors(['error' => 'Vous n\'avez pas accès à cette entreprise.']);
        }

        if ($membre->entreprise_id !== $entreprise->id) {
            return back()->withErrors(['error' => 'Membre introuvable.']);
        }

        if (!$entreprise->aGestionMultiPersonnes()) {
            return back()->withErrors(['error' => 'Cette fonctionnalité nécessite l\'abonnement Gestion Multi-Personnes.']);
        }

        $validated = $request->validate([
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'heure_debut' => 'nullable|date_format:H:i',
            'heure_fin' => 'nullable|date_format:H:i|after:heure_debut',
            'raison' => 'nullable|string|max:255',
        ]);

        MembreIndisponibilite::create([
            'membre_id' => $membre->id,
            'date_debut' => $validated['date_debut'],
            'date_fin' => $validated['date_fin'] ?? $validated['date_debut'],
            'heure_debut' => $validated['heure_debut'] ?? null,
            'heure_fin' => $validated['heure_fin'] ?? null,
            'raison' => $validated['raison'] ?? null,
        ]);

        return back()->with('success', 'L\'indisponibilité a été ajoutée.');
    }

    /**
     * Supprimer une indisponibilité ponctuelle
     */
    public function deleteIndisponibilite(Request $request, $slug, EntrepriseMembre $membre, MembreIndisponibilite $indisponibilite)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user)) {
            return back()->withErrors(['error' => 'Vous n\'avez pas accès à cette entreprise.']);
        }

        if ($membre->entreprise_id !== $entreprise->id || $indisponibilite->membre_id !== $membre->id) {
            return back()->withErrors(['error' => 'Indisponibilité introuvable.']);
        }

        $indisponibilite->delete();

        return back()->with('success', 'L\'indisponibilité a été supprimée.');
    }

    /**
     * API : Récupérer l'agenda d'un membre (JSON pour FullCalendar)
     */
    public function getAgenda(Request $request, $slug, EntrepriseMembre $membre)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user)) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        if ($membre->entreprise_id !== $entreprise->id) {
            return response()->json(['error' => 'Membre introuvable'], 404);
        }

        // Récupérer les réservations du membre
        $reservations = Reservation::where('membre_id', $membre->id)
            ->whereIn('statut', ['en_attente', 'confirmee', 'terminee'])
            ->with(['user', 'typeService'])
            ->get()
            ->map(function($reservation) {
                $debut = Carbon::parse($reservation->date_reservation);
                $fin = $debut->copy()->addMinutes($reservation->duree_minutes ?? 30);
                
                $couleur = match($reservation->statut) {
                    'en_attente' => '#f59e0b',
                    'confirmee' => '#10b981',
                    'terminee' => '#3b82f6',
                    default => '#6b7280',
                };

                return [
                    'id' => $reservation->id,
                    'title' => $reservation->user->name . ' - ' . ($reservation->typeService->nom ?? $reservation->type_service),
                    'start' => $debut->toIso8601String(),
                    'end' => $fin->toIso8601String(),
                    'color' => $couleur,
                    'extendedProps' => [
                        'statut' => $reservation->statut,
                        'prix' => $reservation->prix,
                    ],
                ];
            });

        return response()->json($reservations);
    }

    /**
     * Statistiques détaillées d'un membre
     */
    public function getStatistiques(Request $request, $slug, EntrepriseMembre $membre)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user)) {
            return redirect()->route('dashboard')
                ->with('error', 'Vous n\'avez pas accès à cette entreprise.');
        }

        if ($membre->entreprise_id !== $entreprise->id) {
            return redirect()->route('entreprise.equipe.index', $slug)
                ->with('error', 'Membre introuvable.');
        }

        // Calculer les stats sur différentes périodes
        $aujourdhui = now();
        $semaine = $membre->getChargeTravail($aujourdhui->copy()->startOfWeek(), $aujourdhui->copy()->endOfWeek());
        $mois = $membre->getChargeTravail($aujourdhui->copy()->startOfMonth(), $aujourdhui->copy()->endOfMonth());
        $annee = $membre->getChargeTravail($aujourdhui->copy()->startOfYear(), $aujourdhui->copy()->endOfYear());

        // Stats par jour sur les 30 derniers jours
        $statsParJour = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = $aujourdhui->copy()->subDays($i);
            $stat = MembreStatistique::calculerPourMembre($membre, $date);
            $statsParJour[] = [
                'date' => $date->format('Y-m-d'),
                'reservations' => $stat->nombre_reservations,
                'revenu' => (float) $stat->revenu_total,
            ];
        }

        return view('entreprise.dashboard.tabs.equipe-statistiques', [
            'entreprise' => $entreprise,
            'membre' => $membre,
            'statsSemaine' => $semaine,
            'statsMois' => $mois,
            'statsAnnee' => $annee,
            'statsParJour' => $statsParJour,
        ]);
    }
}
