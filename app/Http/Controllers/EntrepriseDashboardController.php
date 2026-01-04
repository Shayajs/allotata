<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\EntrepriseMembre;
use App\Models\Reservation;
use App\Models\Facture;
use App\Models\Conversation;
use App\Models\HorairesOuverture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EntrepriseDashboardController extends Controller
{
    /**
     * Afficher le dashboard centralisé d'une entreprise
     */
    public function index(Request $request, $slug)
    {
        $user = Auth::user();
        
        // Récupérer l'entreprise avec les relations nécessaires
        $entreprise = Entreprise::where('slug', $slug)
            ->with(['realisationPhotos', 'typesServices.images', 'typesServices.imageCouverture', 'horairesOuverture'])
            ->firstOrFail();
        
        // Vérifier les permissions (propriétaire ou administrateur)
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        // Onglet actif (par défaut: accueil)
        $activeTab = $request->get('tab', 'accueil');

        // VÉRIFICATION DIRECTE SUR STRIPE avant d'afficher le dashboard
        // On synchronise toujours depuis Stripe pour être sûr que les données sont à jour
        if ($user->stripe_id) {
            try {
                // Si on est sur l'onglet abonnements, on force la synchro immédiate
                if ($activeTab === 'abonnements') {
                    \App\Services\StripeSubscriptionSyncService::syncAllUserSubscriptions($user);
                    
                    // Si on a un session_id dans l'URL (retour de paiement sans passer par success), on tente un sync précis
                    if ($request->has('session_id')) {
                        try {
                            $sessionId = $request->get('session_id');
                            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                            $session = \Stripe\Checkout\Session::retrieve($sessionId);
                            if ($session && $session->subscription) {
                                \App\Services\StripeSubscriptionSyncService::syncSubscriptionByStripeId($session->subscription);
                            }
                        } catch (\Exception $e) {
                            \Log::warning('Erreur sync session précise dashboard: ' . $e->getMessage());
                        }
                    }
                    
                    $entreprise->refresh();
                } else {
                    // Sinon on utilise un cache de 10 minutes pour ne pas spammer Stripe
                    \Illuminate\Support\Facades\Cache::remember('stripe_sync_' . $user->id, 600, function () use ($user) {
                        \App\Services\StripeSubscriptionSyncService::syncAllUserSubscriptions($user);
                        return true;
                    });
                }
            } catch (\Exception $e) {
                // En cas d'erreur, on continue quand même (ne pas bloquer l'affichage)
                \Log::warning('Erreur lors de la synchronisation Stripe dans le dashboard entreprise: ' . $e->getMessage());
            }
        }

        // Charger les autres entreprises de l'utilisateur (pour le switch)
        $autresEntreprises = Entreprise::where('user_id', $user->id)
            ->where('id', '!=', $entreprise->id)
            ->get();

        // ===== Données pour l'onglet Accueil/Stats =====
        $stats = $this->getStats($entreprise);
        $reservationsEnAttente = $this->getReservationsEnAttente($entreprise);

        // ===== Données pour l'onglet Agenda =====
        $horaires = $entreprise->horairesOuverture()->orderBy('jour_semaine')->get();
        $typesServices = $entreprise->typesServices()
            ->with(['images', 'imageCouverture'])
            ->orderBy('nom')
            ->get();

        // Si pas d'horaires, créer les horaires par défaut (fermés)
        if ($horaires->isEmpty()) {
            $horaires = collect();
            for ($i = 0; $i < 7; $i++) {
                $horaires->push(new HorairesOuverture([
                    'entreprise_id' => $entreprise->id,
                    'jour_semaine' => $i,
                    'heure_ouverture' => null,
                    'heure_fermeture' => null,
                ]));
            }
        }

        // ===== Données pour l'onglet Réservations =====
        $reservations = $this->getReservationsGroupedByStatus($request, $entreprise);

        // ===== Données pour l'onglet Factures =====
        $factures = $this->getFactures($request, $entreprise);

        // ===== Données pour l'onglet Messagerie =====
        $conversations = $this->getConversations($entreprise);

        // ===== Données pour l'onglet Équipe (multi-personnes) =====
        $membresAvecStats = collect([]);
        $invitationsEnCours = collect([]);
        if ($entreprise->aGestionMultiPersonnes()) {
            $membres = $entreprise->membres()
                ->with('user')
                ->get();

            // S'assurer que le gérant (propriétaire) est toujours présent dans la liste
            $gerantEstMembre = $membres->contains(function($membre) use ($entreprise) {
                return $membre->user_id === $entreprise->user_id;
            });

            if (!$gerantEstMembre && $entreprise->user) {
                // Créer un objet membre virtuel pour le gérant
                $membreGerant = new EntrepriseMembre([
                    'id' => 0, // ID virtuel pour identifier le gérant
                    'entreprise_id' => $entreprise->id,
                    'user_id' => $entreprise->user_id,
                    'role' => 'administrateur',
                    'est_actif' => true,
                ]);
                $membreGerant->setRelation('user', $entreprise->user);
                $membres = $membres->prepend($membreGerant);
            } else if ($gerantEstMembre) {
                // Si le gérant est déjà dans les membres, s'assurer qu'il est en premier
                $gerant = $membres->first(function($membre) use ($entreprise) {
                    return $membre->user_id === $entreprise->user_id;
                });
                if ($gerant) {
                    $membres = $membres->reject(function($membre) use ($entreprise) {
                        return $membre->user_id === $entreprise->user_id;
                    });
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

        // ===== Données pour l'onglet Finances =====
        $finances = collect([]);
        $financeStats = [
            'totalIncome' => 0,
            'totalExpense' => 0,
            'chargesEstimées' => ['total' => 0, 'urssaf' => 0, 'impot' => 0, 'taux_combine' => 0],
            'selectedMonth' => $request->get('finance_month', now()->month),
            'selectedYear' => $request->get('finance_year', now()->year),
        ];

        if ($activeTab === 'finances' || $activeTab === 'outils') {
            $financeController = app(\App\Http\Controllers\EntrepriseFinanceController::class);
            
            $query = $entreprise->finances();
            if ($request->filled('finance_month') && $request->filled('finance_year')) {
                $query->whereMonth('date_record', $request->finance_month)
                      ->whereYear('date_record', $request->finance_year);
            } else {
                $query->whereMonth('date_record', $financeStats['selectedMonth'])
                      ->whereYear('date_record', $financeStats['selectedYear']);
            }
            $finances = $query->get();
            $financeStats['totalIncome'] = $finances->where('type', 'income')->sum('amount');
            $financeStats['totalExpense'] = $finances->where('type', 'expense')->sum('amount');
            $financeStats['chargesEstimées'] = $financeController->calculateEstimatedCharges($entreprise, $financeStats['totalIncome']);
        }

        // Onglet actif (par défaut: accueil)


        return view('entreprise.dashboard.index', [
            'user' => $user,
            'entreprise' => $entreprise,
            'autresEntreprises' => $autresEntreprises,
            'activeTab' => $activeTab,
            // Stats
            'stats' => $stats,
            'reservationsEnAttente' => $reservationsEnAttente,
            // Agenda
            'horaires' => $horaires,
            'typesServices' => $typesServices,
            // Réservations
            'reservations' => $reservations,
            // Factures
            'factures' => $factures,
            // Messagerie
            'conversations' => $conversations,
            // Multi-personnes
            'aGestionMultiPersonnes' => $entreprise->aGestionMultiPersonnes(),
            'membresAvecStats' => $membresAvecStats,
            'invitationsEnCours' => $invitationsEnCours,
            // Finances
            'finances' => $finances,
            'financeStats' => $financeStats,
        ]);
    }

    /**
     * Calculer les statistiques de l'entreprise
     */
    private function getStats(Entreprise $entreprise): array
    {
        $allReservations = Reservation::where('entreprise_id', $entreprise->id)->get();
        
        $reservationsCeMois = $allReservations->filter(function($r) {
            return $r->date_reservation && $r->date_reservation->isCurrentMonth();
        });

        $reservationsMoisDernier = $allReservations->filter(function($r) {
            return $r->date_reservation && $r->date_reservation->isLastMonth();
        });

        // Évolution par rapport au mois dernier
        $revenuCeMois = $reservationsCeMois->where('est_paye', true)->sum('prix');
        $revenuMoisDernier = $reservationsMoisDernier->where('est_paye', true)->sum('prix');
        $evolutionRevenu = $revenuMoisDernier > 0 
            ? round((($revenuCeMois - $revenuMoisDernier) / $revenuMoisDernier) * 100, 1)
            : ($revenuCeMois > 0 ? 100 : 0);

        // Réservations acceptées uniquement (confirmées ou terminées)
        $reservationsAcceptees = $allReservations->filter(function($r) {
            return in_array($r->statut, ['confirmee', 'terminee']);
        });
        
        return [
            'total_reservations' => $allReservations->count(),
            'reservations_confirmees' => $allReservations->where('statut', 'confirmee')->count(),
            'reservations_en_attente' => $allReservations->where('statut', 'en_attente')->count(),
            'reservations_terminees' => $allReservations->where('statut', 'terminee')->count(),
            'reservations_annulees' => $allReservations->where('statut', 'annulee')->count(),
            'revenu_total' => $reservationsAcceptees->sum('prix'), // Uniquement les réservations acceptées
            'revenu_paye' => $allReservations->where('est_paye', true)->sum('prix'), // CA : paiements confirmés
            'revenu_en_attente' => $allReservations->where('est_paye', false)->sum('prix'),
            'reservations_ce_mois' => $reservationsCeMois->count(),
            'revenu_ce_mois' => $revenuCeMois,
            'evolution_revenu' => $evolutionRevenu,
            'note_moyenne' => $entreprise->note_moyenne,
            'nombre_avis' => $entreprise->nombre_avis,
        ];
    }

    /**
     * Récupérer les réservations en attente
     */
    private function getReservationsEnAttente(Entreprise $entreprise)
    {
        return Reservation::where('entreprise_id', $entreprise->id)
            ->where('statut', 'en_attente')
            ->with(['user', 'typeService', 'membre.user'])
            ->orderBy('date_reservation', 'asc')
            ->get();
    }

    /**
     * Récupérer les réservations groupées par statut
     */
    private function getReservationsGroupedByStatus(Request $request, Entreprise $entreprise)
    {
        $query = Reservation::where('entreprise_id', $entreprise->id)
            ->with(['user', 'typeService', 'facture', 'membre.user']);

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('type_service', 'like', "%{$search}%")
                  ->orWhere('lieu', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filtre par statut
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        // Filtre par paiement
        if ($request->filled('est_paye')) {
            $query->where('est_paye', $request->est_paye === '1');
        }

        // Filtre par date
        if ($request->filled('date_debut')) {
            $query->whereDate('date_reservation', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date_reservation', '<=', $request->date_fin);
        }

        $reservations = $query->orderBy('date_reservation', 'desc')->get();

        // Grouper par statut
        return $reservations->groupBy('statut');
    }

    /**
     * Récupérer les factures de l'entreprise
     */
    private function getFactures(Request $request, Entreprise $entreprise)
    {
        // Générer automatiquement les factures pour les réservations payées sans facture
        $reservationsPayeesSansFacture = Reservation::where('entreprise_id', $entreprise->id)
            ->where('est_paye', true)
            ->whereDoesntHave('facture')
            ->with(['user'])
            ->get();
        
        foreach ($reservationsPayeesSansFacture as $reservation) {
            try {
                Facture::generateFromReservation($reservation);
            } catch (\Exception $e) {
                \Log::error("Erreur lors de la génération automatique de facture pour la réservation #{$reservation->id}: " . $e->getMessage());
            }
        }

        $query = $entreprise->factures()->with(['user', 'reservation']);

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero_facture', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filtre par statut
        if ($request->filled('statut_facture')) {
            $query->where('statut', $request->statut_facture);
        }

        // Filtre par date
        if ($request->filled('facture_date_debut')) {
            $query->whereDate('date_facture', '>=', $request->facture_date_debut);
        }
        if ($request->filled('facture_date_fin')) {
            $query->whereDate('date_facture', '<=', $request->facture_date_fin);
        }

        return $query->orderBy('date_facture', 'desc')->paginate(15)->withQueryString();
    }

    /**
     * Récupérer les conversations de l'entreprise
     */
    private function getConversations(Entreprise $entreprise)
    {
        return Conversation::where('entreprise_id', $entreprise->id)
            ->where('est_archivee', false)
            ->with(['user', 'messages' => function($query) {
                $query->latest()->limit(1);
            }])
            ->orderBy('updated_at', 'desc')
            ->get();
    }
}
