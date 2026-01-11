<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\EntrepriseInvitation;
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
     * Helper : Récupère un membre (y compris le gérant virtuel)
     */
    private function getMembre($slug, $membreId, Entreprise $entreprise): EntrepriseMembre
    {
        // Gérer le cas du gérant (propriétaire) - uniquement si c'est explicitement 'gerant'
        if ($membreId === 'gerant' || $membreId === '0') {
            // Créer un membre virtuel pour le gérant
            $membre = new EntrepriseMembre([
                'id' => 0, // ID virtuel
                'entreprise_id' => $entreprise->id,
                'user_id' => $entreprise->user_id,
                'role' => 'administrateur',
                'est_actif' => true,
            ]);
            $membre->setRelation('user', $entreprise->user);
            return $membre;
        } else {
            // Récupérer le membre normal par son ID numérique
            // Convertir en entier pour éviter les problèmes de type
            $membreIdInt = (int) $membreId;
            
            $membre = EntrepriseMembre::where('id', $membreIdInt)
                ->where('entreprise_id', $entreprise->id)
                ->firstOrFail();
            
            return $membre;
        }
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

        // Récupérer les membres actifs
        $membres = $entreprise->membres()
            ->with('user')
            ->get();

        // Récupérer aussi tous les membres (y compris inactifs) pour vérifier si le gérant y est
        $tousMembres = $entreprise->tousMembres()
            ->with('user')
            ->get();

        // S'assurer que le gérant (propriétaire) est toujours présent dans la liste
        $gerantEstMembre = $membres->contains(function($membre) use ($entreprise) {
            return $membre->user_id === $entreprise->user_id;
        });

        // Vérifier aussi dans tous les membres (actifs ou non)
        $gerantDansTousMembres = $tousMembres->contains(function($membre) use ($entreprise) {
            return $membre->user_id === $entreprise->user_id;
        });

        if (!$gerantEstMembre && $entreprise->user) {
            // Créer un objet membre virtuel pour le gérant s'il n'est pas dans la table ou s'il est inactif
            $membreGerant = new EntrepriseMembre([
                'id' => 0, // ID virtuel pour identifier le gérant
                'entreprise_id' => $entreprise->id,
                'user_id' => $entreprise->user_id,
                'role' => 'administrateur',
                'est_actif' => true,
            ]);
            $membreGerant->setRelation('user', $entreprise->user);
            $membres = $membres->prepend($membreGerant);
        } else {
            // Si le gérant est déjà dans les membres actifs, s'assurer qu'il est en premier
            $gerant = $membres->first(function($membre) use ($entreprise) {
                return $membre->user_id === $entreprise->user_id;
            });
            if ($gerant) {
                // Retirer le gérant de sa position actuelle
                $membres = $membres->reject(function($membre) use ($entreprise) {
                    return $membre->user_id === $entreprise->user_id;
                });
                // Le remettre en premier
                $membres = $membres->prepend($gerant);
            }
        }

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

        // Récupérer les invitations en cours (en attente)
        $invitationsEnCours = collect([]);
        if ($entreprise->aGestionMultiPersonnes()) {
            $invitationsEnCours = \App\Models\EntrepriseInvitation::where('entreprise_id', $entreprise->id)
                ->whereIn('statut', ['en_attente_compte', 'en_attente_acceptation'])
                ->where(function($query) {
                    $query->whereNull('expire_at')
                          ->orWhere('expire_at', '>', now());
                })
                ->with('invitePar')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('entreprise.dashboard.tabs.equipe', [
            'entreprise' => $entreprise,
            'membresAvecStats' => $membresAvecStats,
            'invitationsEnCours' => $invitationsEnCours,
        ]);
    }

    /**
     * Détails d'un membre (agenda, stats, disponibilités)
     */
    public function show(Request $request, $slug, $membreId)
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

        // Récupérer le membre (y compris le gérant virtuel)
        $membre = $this->getMembre($slug, $membreId, $entreprise);

        // Charger les relations (pour le gérant virtuel, créer des collections vides)
        if ($membre->id == 0) {
            // Pour le gérant virtuel, créer des collections vides
            $membre->setRelation('disponibilites', collect());
            $membre->setRelation('indisponibilites', collect());
        } else {
            $membre->load(['user', 'disponibilites', 'indisponibilites']);
        }

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

        // Charger les horaires d'ouverture de l'entreprise
        $horaires = $entreprise->horairesOuverture()
            ->orderBy('jour_semaine')
            ->get();

        // Si pas d'horaires, créer les horaires par défaut (fermés)
        if ($horaires->isEmpty()) {
            $horaires = collect();
            for ($i = 0; $i < 7; $i++) {
                $horaires->push(new \App\Models\HorairesOuverture([
                    'entreprise_id' => $entreprise->id,
                    'jour_semaine' => $i,
                    'heure_ouverture' => null,
                    'heure_fermeture' => null,
                ]));
            }
        }

        return view('entreprise.membre-show', [
            'entreprise' => $entreprise,
            'membre' => $membre,
            'statsMois' => $statsMois,
            'statsSemaine' => $statsSemaine,
            'activeSubTab' => $activeSubTab,
            'horaires' => $horaires,
        ]);
    }

    /**
     * Mettre à jour les disponibilités (horaires réguliers) d'un membre
     */
    public function updateDisponibilites(Request $request, $slug, $membreId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user)) {
            return back()->withErrors(['error' => 'Vous n\'avez pas accès à cette entreprise.']);
        }

        // Récupérer le membre (y compris le gérant virtuel)
        $membre = $this->getMembre($slug, $membreId, $entreprise);

        // Pour le gérant virtuel, on ne peut pas créer de disponibilités
        if ($membre->id == 0) {
            return back()->withErrors(['error' => 'Les disponibilités du gérant doivent être gérées via les horaires de l\'entreprise dans l\'onglet Agenda.']);
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
    public function storeIndisponibilite(Request $request, $slug, $membreId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user)) {
            return back()->withErrors(['error' => 'Vous n\'avez pas accès à cette entreprise.']);
        }

        // Récupérer le membre (y compris le gérant virtuel)
        $membre = $this->getMembre($slug, $membreId, $entreprise);
        
        // Pour le gérant virtuel, on ne peut pas créer d'indisponibilités
        if ($membre->id == 0) {
            return back()->withErrors(['error' => 'Les indisponibilités du gérant doivent être gérées via les horaires de l\'entreprise.']);
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
    public function deleteIndisponibilite(Request $request, $slug, $membreId, MembreIndisponibilite $indisponibilite)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user)) {
            return back()->withErrors(['error' => 'Vous n\'avez pas accès à cette entreprise.']);
        }

        // Récupérer le membre (y compris le gérant virtuel)
        $membre = $this->getMembre($slug, $membreId, $entreprise);
        
        // Pour le gérant virtuel, on ne peut pas supprimer d'indisponibilités
        if ($membre->id == 0) {
            return back()->withErrors(['error' => 'Les indisponibilités du gérant doivent être gérées via les horaires de l\'entreprise.']);
        }

        if ($indisponibilite->membre_id !== $membre->id) {
            return back()->withErrors(['error' => 'Indisponibilité introuvable.']);
        }

        $indisponibilite->delete();

        return back()->with('success', 'L\'indisponibilité a été supprimée.');
    }

    /**
     * API : Récupérer l'agenda d'un membre (JSON pour FullCalendar)
     */
    public function getAgenda(Request $request, $slug, $membreId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user)) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        // Récupérer le membre (y compris le gérant virtuel)
        $membre = $this->getMembre($slug, $membreId, $entreprise);

        // Récupérer les réservations du membre
        // Pour le gérant virtuel, récupérer toutes les réservations de l'entreprise sans membre_id
        if ($membre->id == 0) {
            $reservations = Reservation::where('entreprise_id', $entreprise->id)
                ->whereNull('membre_id')
                ->whereIn('statut', ['en_attente', 'confirmee', 'terminee'])
                ->with(['user', 'typeService'])
                ->get();
        } else {
            $reservations = Reservation::where('membre_id', $membre->id)
                ->whereIn('statut', ['en_attente', 'confirmee', 'terminee'])
                ->with(['user', 'typeService'])
                ->get();
        }

        $reservations = $reservations->map(function($reservation) {
                $debut = Carbon::parse($reservation->date_reservation);
                $fin = $debut->copy()->addMinutes((int) ($reservation->duree_minutes ?? 30));
                
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
                        'duree' => $reservation->duree_minutes ?? 30,
                        'type_service' => $reservation->typeService->nom ?? $reservation->type_service,
                        'client' => $reservation->user->name ?? 'Client',
                        'client_email' => $reservation->user->email ?? '',
                        'telephone' => $reservation->telephone_client ?? null,
                        'lieu' => $reservation->lieu ?? null,
                        'notes' => $reservation->notes ?? null,
                        'est_paye' => $reservation->est_paye ?? false,
                    ],
                ];
            });

        return response()->json($reservations);
    }

    /**
     * Statistiques détaillées d'un membre
     */
    public function getStatistiques(Request $request, $slug, $membreId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user)) {
            return redirect()->route('dashboard')
                ->with('error', 'Vous n\'avez pas accès à cette entreprise.');
        }

        // Récupérer le membre (y compris le gérant virtuel)
        $membre = $this->getMembre($slug, $membreId, $entreprise);

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
