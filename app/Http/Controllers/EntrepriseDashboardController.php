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
        $horaires = $entreprise->horairesOuverture()
            ->where('est_exceptionnel', false)
            ->orderBy('jour_semaine')
            ->orderBy('ordre_plage')
            ->get();
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

        // ===== Données pour l'onglet Statistiques =====
        $statsStatistiques = [];
        $visiteursSansReservation = collect([]);
        $topServices = [];
        $topProduits = [];
        if ($activeTab === 'statistiques') {
            $statsController = new \App\Http\Controllers\EntrepriseStatistiqueController();
            $statsStatistiques = $statsController->calculerStatistiques($entreprise);
            $visiteursSansReservation = \App\Models\EntrepriseVisite::visiteursSansReservation($entreprise->id, 30);
            $topServices = $statsController->getTopServices($entreprise->id, 30);
            $topProduits = $statsController->getTopProduits($entreprise->id, 30);
        }

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


        // ===== Données pour l'onglet Stock =====
        $produits = collect([]);
        
        // #region agent log
        try {
            $logData = [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'B1',
                'location' => 'EntrepriseDashboardController.php:' . __LINE__,
                'message' => 'Chargement produits stock',
                'data' => [
                    'activeTab' => $activeTab,
                    'tab_request' => $request->get('tab'),
                    'slug' => $slug,
                ],
                'timestamp' => time() * 1000,
            ];
            $logPath = base_path('.cursor/debug.log');
            $logDir = dirname($logPath);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            @file_put_contents($logPath, json_encode($logData) . "\n", FILE_APPEND);
        } catch (\Exception $e) {}
        // #endregion
        
        // ===== Données pour l'onglet Commandes =====
        $commandes = collect([]);
        $commandesEnAttente = 0;
        
        if ($activeTab === 'commandes') {
            $commandes = \App\Models\CommandeProduit::where('entreprise_id', $entreprise->id)
                ->with(['user', 'produit.stock', 'produit.images', 'membre.user'])
                ->orderBy('date_commande', 'desc')
                ->get();
            
            $commandesEnAttente = $commandes->where('statut', 'en_attente')->count();
            
            // Charger les produits pour les filtres
            if ($produits->isEmpty()) {
                $produits = $entreprise->produits()
                    ->where('est_actif', true)
                    ->orderBy('nom')
                    ->get();
            }
        }
        
        if ($activeTab === 'stock') {
            $produits = $entreprise->produits()
                ->with(['stock', 'images', 'imageCouverture', 'promotionActive'])
                ->orderBy('nom')
                ->get();
            
            // #region agent log
            try {
                $logData = [
                    'sessionId' => 'debug-session',
                    'runId' => 'run1',
                    'hypothesisId' => 'B1',
                    'location' => 'EntrepriseDashboardController.php:' . __LINE__,
                    'message' => 'Produits chargés pour stock',
                    'data' => [
                        'produits_count' => $produits->count(),
                        'produits_actifs' => $produits->where('est_actif', true)->count(),
                    ],
                    'timestamp' => time() * 1000,
                ];
                $logPath = base_path('.cursor/debug.log');
                $logDir = dirname($logPath);
                if (!is_dir($logDir)) {
                    @mkdir($logDir, 0755, true);
                }
                @file_put_contents($logPath, json_encode($logData) . "\n", FILE_APPEND);
            } catch (\Exception $e) {}
            // #endregion
        } else {
            // #region agent log
            try {
                $logData = [
                    'sessionId' => 'debug-session',
                    'runId' => 'run1',
                    'hypothesisId' => 'B1',
                    'location' => 'EntrepriseDashboardController.php:' . __LINE__,
                    'message' => 'Tab non stock - produits non chargés',
                    'data' => [
                        'activeTab' => $activeTab,
                    ],
                    'timestamp' => time() * 1000,
                ];
                $logPath = base_path('.cursor/debug.log');
            $logDir = dirname($logPath);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            @file_put_contents($logPath, json_encode($logData) . "\n", FILE_APPEND);
            } catch (\Exception $e) {}
            // #endregion
        }

        // ===== Données pour l'onglet Fidélisation =====
        $fidelisationData = [
            'norme_quartier' => 0,
            'clients' => [],
        ];
        $fidelisationClientsARisque = [
            'norme_quartier' => 0,
            'clients' => [],
        ];
        $fidelisationStats = [
            'norme_quartier' => 0,
            'stats' => [],
        ];
        if ($activeTab === 'fidelisation') {
            $fidelisationService = app(\App\Services\FidelisationService::class);
            
            // Données pour l'onglet Clients réguliers
            $filters = [
                'search' => $request->get('fidelisation_search'),
                'statut' => $request->get('fidelisation_statut'),
                'sort' => $request->get('fidelisation_sort', 'plus_present'),
            ];
            $fidelisationData = $fidelisationService->getClientsReguliers($entreprise, $filters);
            
            // Données pour l'onglet Clients à risque
            $joursSansReservation = $request->get('fidelisation_jours_risque', 90);
            $fidelisationClientsARisque = $fidelisationService->getClientsARisque($entreprise, $joursSansReservation);
            
            // Données pour l'onglet Statistiques
            $fidelisationStats = $fidelisationService->getStatistiques($entreprise);
        }

        // ===== Données pour l'onglet Abonnements (Prix depuis Tarifs + CustomPrice) =====
        $subscriptionPrices = [
            'site_web' => \App\Models\Tarif::displayForEntreprise($entreprise, 'site_web'),
            'multi_personnes' => \App\Models\Tarif::displayForEntreprise($entreprise, 'multi_personnes'),
        ];

        return view('entreprise.dashboard.index', [
            'user' => $user,
            'entreprise' => $entreprise,
            'autresEntreprises' => $autresEntreprises,
            'activeTab' => $activeTab,
            // Stats
            'stats' => $stats,
            'reservationsEnAttente' => $reservationsEnAttente,
            'statsStatistiques' => $statsStatistiques,
            'visiteursSansReservation' => $visiteursSansReservation,
            'topServices' => $topServices,
            'topProduits' => $topProduits,
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
            // Prix dynamiques
            'subscriptionPrices' => $subscriptionPrices,
            // Stock
            'produits' => $produits,
            // Commandes
            'commandes' => $commandes,
            'commandesEnAttente' => $commandesEnAttente,
            // Fidélisation
            'fidelisationData' => $fidelisationData,
            'fidelisationClientsARisque' => $fidelisationClientsARisque,
            'fidelisationStats' => $fidelisationStats,
            // Site Web
            'aSiteWebActif' => $entreprise->aSiteWebActif(),
        ]);
    }

    /**
     * Recharger le contenu d'un onglet spécifique (Ajax)
     */
    public function reloadTab(Request $request, $slug, $tab)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->with(['realisationPhotos', 'typesServices.images', 'typesServices.imageCouverture', 'horairesOuverture'])
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        // Préparer les données nécessaires pour l'onglet demandé
        $data = [
            'entreprise' => $entreprise, 
            'activeTab' => $tab,
            'errors' => new \Illuminate\Support\MessageBag(), // Pour éviter les erreurs dans les vues
            // Variables par défaut pour éviter les erreurs
            'produits' => collect([]),
            'typesServices' => collect([]),
            'commandes' => collect([]),
            'commandesEnAttente' => 0,
        ];
        
        // Charger les données spécifiques selon l'onglet
        switch ($tab) {
            case 'stock':
                $data['produits'] = $entreprise->produits()
                    ->with(['stock', 'images', 'imageCouverture', 'promotionActive'])
                    ->orderBy('nom')
                    ->get();
                break;
                
            case 'mes-services':
            case 'services':
                $data['typesServices'] = $entreprise->typesServices()
                    ->with(['images', 'imageCouverture'])
                    ->orderBy('nom')
                    ->get();
                break;
                
            case 'commandes':
                $data['commandes'] = \App\Models\CommandeProduit::where('entreprise_id', $entreprise->id)
                    ->with(['user', 'produit.stock', 'produit.images', 'membre.user'])
                    ->orderBy('date_commande', 'desc')
                    ->get();
                $data['commandesEnAttente'] = $data['commandes']->where('statut', 'en_attente')->count();
                $data['produits'] = $entreprise->produits()
                    ->where('est_actif', true)
                    ->orderBy('nom')
                    ->get();
                break;
                
            case 'reservations':
                // Les réservations sont gérées par ReservationController
                // On retourne juste un indicateur pour recharger
                break;
                
            case 'agenda':
                $data['horaires'] = $entreprise->horairesOuverture()
                    ->where('est_exceptionnel', false)
                    ->orderBy('jour_semaine')
                    ->orderBy('ordre_plage')
                    ->get();
                $data['typesServices'] = $entreprise->typesServices()
                    ->with(['images', 'imageCouverture'])
                    ->orderBy('nom')
                    ->get();
                break;

            case 'installer':
                // Pas de données spécifiques nécessaires pour l'instant
                break;
        }

        // Rendre la vue correspondante
        $viewMap = [
            'stock' => 'entreprise.dashboard.tabs.stock',
            'mes-services' => 'entreprise.dashboard.tabs.services',
            'services' => 'entreprise.dashboard.tabs.services',
            'commandes' => 'entreprise.dashboard.tabs.commandes',
            'reservations' => 'entreprise.dashboard.tabs.reservations',
            'agenda' => 'entreprise.dashboard.tabs.agenda',
            'installer' => 'entreprise.dashboard.tabs.installer',
        ];

        if (isset($viewMap[$tab])) {
            try {
                // Utiliser la méthode render() pour obtenir le HTML
                $html = view($viewMap[$tab], $data)->render();
                return response()->json(['success' => true, 'html' => $html]);
            } catch (\Exception $e) {
                \Log::error('Erreur lors du rechargement de l\'onglet', [
                    'tab' => $tab,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return response()->json(['error' => 'Erreur lors du chargement: ' . $e->getMessage()], 500);
            }
        }

        return response()->json(['error' => 'Onglet non trouvé'], 404);
    }

    /**
     * Calculer les statistiques de l'entreprise
     */
    private function getStats(Entreprise $entreprise): array
    {
        // Utiliser le cache pour les statistiques (5 minutes)
        return \Illuminate\Support\Facades\Cache::remember(
            "entreprise_stats_{$entreprise->id}",
            300,
            function () use ($entreprise) {
                return $this->calculateStats($entreprise);
            }
        );
    }

    private function calculateStats(Entreprise $entreprise): array
    {
        // Charger uniquement les colonnes nécessaires
        $allReservations = Reservation::where('entreprise_id', $entreprise->id)
            ->select('id', 'date_reservation', 'statut', 'est_paye', 'prix')
            ->get();
        
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
        
        // Données pour graphiques - 12 derniers mois
        $chartData = $this->getChartData($entreprise, $allReservations);
        
        // Données par jour de la semaine
        $dayOfWeekData = $this->getDayOfWeekData($allReservations);
        
        // Taux de conversion
        $tauxConversion = $allReservations->count() > 0 
            ? round(($allReservations->where('statut', 'confirmee')->count() / $allReservations->count()) * 100, 1)
            : 0;
        
        // Taux d'annulation
        $tauxAnnulation = $allReservations->count() > 0
            ? round(($allReservations->where('statut', 'annulee')->count() / $allReservations->count()) * 100, 1)
            : 0;
        
        $stats = [
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
            'taux_conversion' => $tauxConversion,
            'taux_annulation' => $tauxAnnulation,
            'chart_data' => $chartData,
            'day_of_week_data' => $dayOfWeekData,
        ];
        
        return $stats;
    }

    /**
     * Récupérer les réservations en attente
     */
    private function getReservationsEnAttente(Entreprise $entreprise)
    {
        // Limiter à 20 réservations pour éviter de surcharger
        return Reservation::where('entreprise_id', $entreprise->id)
            ->where('statut', 'en_attente')
            ->with(['user:id,name,email', 'typeService:id,nom,prix', 'membre.user:id,name'])
            ->orderBy('date_reservation', 'asc')
            ->limit(20)
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
                  ->orWhere('nom_client', 'like', "%{$search}%")
                  ->orWhere('email_client', 'like', "%{$search}%")
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

    /**
     * Générer les données pour les graphiques (12 derniers mois)
     */
    private function getChartData(Entreprise $entreprise, $allReservations): array
    {
        $labels = [];
        $revenus = [];
        $reservations = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $moisLabel = $date->format('M Y');
            $labels[] = $moisLabel;
            
            $reservationsMois = $allReservations->filter(function($r) use ($date) {
                return $r->date_reservation && 
                       $r->date_reservation->format('Y-m') === $date->format('Y-m');
            });
            
            $reservationsAcceptees = $reservationsMois->filter(function($r) {
                return in_array($r->statut, ['confirmee', 'terminee']);
            });
            
            $revenus[] = round($reservationsAcceptees->sum('prix'), 2);
            $reservations[] = $reservationsMois->count();
        }
        
        return [
            'labels' => $labels,
            'revenus' => $revenus,
            'reservations' => $reservations,
        ];
    }

    /**
     * Générer les données par jour de la semaine
     */
    private function getDayOfWeekData($allReservations): array
    {
        $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        $data = array_fill(0, 7, 0);
        
        foreach ($allReservations as $reservation) {
            if ($reservation->date_reservation) {
                $jourSemaine = $reservation->date_reservation->dayOfWeek; // 0 = dimanche, 1 = lundi, etc.
                // Convertir pour avoir lundi = 0
                $index = $jourSemaine === 0 ? 6 : $jourSemaine - 1;
                $data[$index]++;
            }
        }
        
        return [
            'labels' => $jours,
            'data' => $data,
        ];
    }

    /**
     * Mettre à jour le mode d'ordre (manuel, ventes, statistiques)
     */
    public function updateModeOrdre(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $validated = $request->validate([
            'type' => 'required|in:services,produits',
            'mode_ordre' => 'required|in:manuel,ventes,statistiques',
        ]);

        if ($validated['type'] === 'services') {
            $entreprise->mode_ordre_services = $validated['mode_ordre'];
        } else {
            $entreprise->mode_ordre_produits = $validated['mode_ordre'];
        }
        
        $entreprise->save();

        return back()->with('success', 'Mode d\'ordre mis à jour avec succès.');
    }

    /**
     * Mettre à jour les paramètres de prestation libre
     */
    public function updatePrestationLibre(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $isActive = $request->has('prestation_libre_active') && $request->prestation_libre_active == '1';

        if ($isActive) {
            $request->validate([
                'tarif_horaire' => 'required|numeric|min:0',
                'prestation_libre_description' => 'nullable|string|max:255',
            ]);
        }

        $entreprise->update([
            'prestation_libre_active' => $isActive,
            'tarif_horaire' => $isActive ? $request->tarif_horaire : $entreprise->tarif_horaire,
            'prestation_libre_description' => $isActive ? $request->prestation_libre_description : $entreprise->prestation_libre_description,
        ]);

        $message = $isActive ? 'Prestation libre activée (' . number_format($request->tarif_horaire, 0, ',', ' ') . ' €/h).' : 'Prestation libre désactivée.';

        return redirect()->route('entreprise.dashboard', ['slug' => $slug, 'tab' => 'services'])
            ->with('success', $message);
    }

    /**
     * Mettre à jour l'ordre manuel (drag & drop)
     */
    public function updateOrdreManuel(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        $validated = $request->validate([
            'type' => 'required|in:services,produits',
            'items' => 'required|array',
            'items.*.id' => 'required|integer',
            'items.*.ordre' => 'required|integer',
        ]);

        try {
            \DB::beginTransaction();

            if ($validated['type'] === 'services') {
                foreach ($validated['items'] as $item) {
                    \App\Models\TypeService::where('id', $item['id'])
                        ->where('entreprise_id', $entreprise->id)
                        ->update(['ordre_affichage' => $item['ordre']]);
                }
            } else {
                foreach ($validated['items'] as $item) {
                    \App\Models\Produit::where('id', $item['id'])
                        ->where('entreprise_id', $entreprise->id)
                        ->update(['ordre_affichage' => $item['ordre']]);
                }
            }

            \DB::commit();
            return response()->json(['success' => true, 'message' => 'Ordre mis à jour avec succès.']);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['error' => 'Erreur lors de la mise à jour'], 500);
        }
    }
}
