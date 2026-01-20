<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\EntrepriseVisite;
use App\Models\VisiteClic;
use App\Models\User;
use App\Models\Reservation;
use App\Models\Facture;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\EntrepriseFinance;
use App\Models\TypeService;
use App\Models\Produit;
use App\Models\Ticket;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminStatistiqueController extends Controller
{
    /**
     * Afficher les statistiques admin complètes
     */
    public function index(Request $request)
    {
        try {
            // Période par défaut : 30 jours
            $periodDays = $request->get('period', 30);
            $dateDebut = now()->subDays($periodDays);
            
            // ===== STATISTIQUES GLOBALES =====
            $statsGlobales = $this->getStatsGlobales($periodDays);
            
            // ===== STATISTIQUES DE VISITES =====
            $statsVisites = $this->getStatsVisites($dateDebut);
            
            // ===== STATISTIQUES DE CONVERSION =====
            $statsConversion = $this->getStatsConversion($dateDebut);
            
            // ===== STATISTIQUES PAR ENTREPRISE (limité à 50) =====
            $statsParEntreprise = $this->getStatsParEntreprise($dateDebut);
            
            // ===== STATISTIQUES FINANCIÈRES =====
            $statsFinances = $this->getStatsFinances($dateDebut);
            
            // ===== STATISTIQUES D'ABONNEMENTS =====
            $statsAbonnements = $this->getStatsAbonnements();
            
            // ===== STATISTIQUES TEMPORELLES =====
            $statsTemporelles = $this->getStatsTemporelles($periodDays);
            
            // ===== TOP SERVICES/PRODUITS CLIQUÉS =====
            $topServices = $this->getTopServicesGlobal($dateDebut, 20);
            $topProduits = $this->getTopProduitsGlobal($dateDebut, 20);
            
            // ===== STATISTIQUES RGPD/CONSENTEMENT =====
            $statsRGPD = $this->getStatsRGPD();
            
            // ===== STATISTIQUES D'ACTIVITÉ =====
            $statsActivite = $this->getStatsActivite($dateDebut);
            
            // ===== STATISTIQUES PAR TYPE DE PAGE =====
            $statsPages = $this->getStatsPages($dateDebut);
            
            // ===== STATISTIQUES GÉOGRAPHIQUES (par ville) =====
            $statsGeo = $this->getStatsGeo($dateDebut);
            
            // ===== STATISTIQUES PAR HEURE =====
            $statsHeure = $this->getStatsHeure($dateDebut);
            
            return view('admin.statistiques.index', compact(
                'periodDays',
                'statsGlobales',
                'statsVisites',
                'statsConversion',
                'statsParEntreprise',
                'statsFinances',
                'statsAbonnements',
                'statsTemporelles',
                'topServices',
                'topProduits',
                'statsRGPD',
                'statsActivite',
                'statsPages',
                'statsGeo',
                'statsHeure'
            ));
        } catch (\Exception $e) {
            \Log::error('Erreur dans AdminStatistiqueController::index: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Erreur lors du chargement des statistiques : ' . $e->getMessage());
        }
    }

    /**
     * API pour données en temps réel
     */
    public function api(Request $request)
    {
        $periodDays = $request->get('period', 30);
        $dateDebut = now()->subDays($periodDays);
        
        return response()->json([
            'stats_globales' => $this->getStatsGlobales($periodDays),
            'stats_visites' => $this->getStatsVisites($dateDebut),
            'stats_conversion' => $this->getStatsConversion($dateDebut),
            'updated_at' => now()->setTimezone('Europe/Paris')->format('H:i:s'),
        ]);
    }

    /**
     * Export des statistiques en CSV
     */
    public function export(Request $request)
    {
        $periodDays = $request->get('period', 30);
        $dateDebut = now()->subDays($periodDays);
        $type = $request->get('type', 'visites'); // visites, entreprises, finances
        
        $filename = 'statistiques_' . $type . '_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($type, $dateDebut) {
            $file = fopen('php://output', 'w');
            
            if ($type === 'visites') {
                fputcsv($file, ['Date', 'Entreprise', 'Type page', 'Durée (s)', 'Clics services', 'Clics produits', 'A réservé', 'Temps avant réservation (s)', 'User ID', 'IP']);
                
                EntrepriseVisite::where('created_at', '>=', $dateDebut)
                    ->with(['entreprise', 'user'])
                    ->orderBy('created_at', 'desc')
                    ->chunk(500, function($visites) use ($file) {
                        foreach ($visites as $visite) {
                            fputcsv($file, [
                                $visite->created_at->format('Y-m-d H:i:s'),
                                $visite->entreprise->nom ?? 'N/A',
                                $visite->page_type,
                                $visite->duree_seconde ?? 0,
                                $visite->nb_clics_services,
                                $visite->nb_clics_produits,
                                $visite->a_passe_commande ? 'Oui' : 'Non',
                                $visite->temps_avant_reservation_secondes ?? 0,
                                $visite->user_id ?? 'Anonyme',
                                $visite->ip_address,
                            ]);
                        }
                    });
            } elseif ($type === 'entreprises') {
                fputcsv($file, ['Nom', 'Slug', 'Ville', 'Total visites', 'Visites exploration', 'Taux conversion', 'Total réservations', 'Revenu']);
                
                $entreprises = Entreprise::withCount(['visites as total_visites' => function($q) use ($dateDebut) {
                    $q->where('created_at', '>=', $dateDebut);
                }])->get();
                
                foreach ($entreprises as $entreprise) {
                    $visites = EntrepriseVisite::where('entreprise_id', $entreprise->id)
                        ->where('created_at', '>=', $dateDebut)
                        ->get();
                    
                    $reservations = $visites->where('a_passe_commande', true)->count();
                    $explorations = $visites->where('a_quitte_apres_exploration', true)->count();
                    $tauxConversion = $visites->count() > 0 ? round(($reservations / $visites->count()) * 100, 2) : 0;
                    
                    $revenu = Reservation::where('entreprise_id', $entreprise->id)
                        ->where('created_at', '>=', $dateDebut)
                        ->where('est_paye', true)
                        ->sum('prix');
                    
                    fputcsv($file, [
                        $entreprise->nom,
                        $entreprise->slug,
                        $entreprise->ville ?? 'N/A',
                        $visites->count(),
                        $explorations,
                        $tauxConversion . '%',
                        $reservations,
                        number_format($revenu, 2, ',', ' ') . '€',
                    ]);
                }
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    // ===== MÉTHODES PRIVÉES =====

    private function getStatsGlobales($periodDays)
    {
        return Cache::remember("admin_stats_globales_{$periodDays}", 300, function () use ($periodDays) {
            $dateDebut = now()->subDays($periodDays);
            
            return [
                'total_users' => User::count(),
                'new_users' => User::where('created_at', '>=', $dateDebut)->count(),
                'total_clients' => User::where('est_client', true)->count(),
                'total_gerants' => User::where('est_gerant', true)->count(),
                'total_entreprises' => Entreprise::count(),
                'new_entreprises' => Entreprise::where('created_at', '>=', $dateDebut)->count(),
                'entreprises_verifiees' => Entreprise::where('est_verifiee', true)->count(),
                'total_reservations' => Reservation::count(),
                'new_reservations' => Reservation::where('created_at', '>=', $dateDebut)->count(),
                'total_factures' => Facture::count(),
                'total_conversations' => Conversation::count(),
                'total_messages' => Message::count(),
            ];
        });
    }

    private function getStatsVisites($dateDebut)
    {
        $cacheKey = 'admin_stats_visites_' . $dateDebut->format('Y-m-d');
        
        return Cache::remember($cacheKey, 300, function () use ($dateDebut) {
            // Utiliser des requêtes agrégées au lieu de charger toutes les visites
            $stats = EntrepriseVisite::where('created_at', '>=', $dateDebut)
                ->select(
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(CASE WHEN user_id IS NOT NULL THEN 1 ELSE 0 END) as avec_user'),
                    DB::raw('SUM(CASE WHEN user_id IS NULL THEN 1 ELSE 0 END) as anonymes'),
                    DB::raw('SUM(CASE WHEN a_passe_commande = 1 THEN 1 ELSE 0 END) as avec_reservation'),
                    DB::raw('SUM(CASE WHEN a_quitte_apres_exploration = 1 THEN 1 ELSE 0 END) as explorations'),
                    DB::raw('SUM(CASE WHEN a_quitte_rapidement = 1 THEN 1 ELSE 0 END) as rapides'),
                    DB::raw('AVG(CASE WHEN duree_seconde > 0 THEN duree_seconde ELSE NULL END) as duree_moyenne'),
                    DB::raw('AVG(CASE WHEN temps_avant_reservation_secondes > 0 THEN temps_avant_reservation_secondes ELSE NULL END) as temps_moyen_avant_reservation'),
                    DB::raw('SUM(nb_clics_services) as total_clics_services'),
                    DB::raw('SUM(nb_clics_produits) as total_clics_produits')
                )
                ->first();
            
            return [
                'total' => $stats->total ?? 0,
                'avec_user' => $stats->avec_user ?? 0,
                'anonymes' => $stats->anonymes ?? 0,
                'avec_reservation' => $stats->avec_reservation ?? 0,
                'explorations' => $stats->explorations ?? 0,
                'rapides' => $stats->rapides ?? 0,
                'duree_moyenne' => round($stats->duree_moyenne ?? 0),
                'temps_moyen_avant_reservation' => round($stats->temps_moyen_avant_reservation ?? 0),
                'total_clics_services' => $stats->total_clics_services ?? 0,
                'total_clics_produits' => $stats->total_clics_produits ?? 0,
            ];
        });
    }

    private function getStatsConversion($dateDebut)
    {
        $cacheKey = 'admin_stats_conversion_' . $dateDebut->format('Y-m-d');
        
        return Cache::remember($cacheKey, 300, function () use ($dateDebut) {
            // Utiliser des requêtes agrégées
            $stats = EntrepriseVisite::where('created_at', '>=', $dateDebut)
                ->select(
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(CASE WHEN a_passe_commande = 1 THEN 1 ELSE 0 END) as avec_reservation'),
                    DB::raw('SUM(CASE WHEN a_quitte_apres_exploration = 1 THEN 1 ELSE 0 END) as explorations'),
                    DB::raw('SUM(CASE WHEN a_quitte_rapidement = 1 THEN 1 ELSE 0 END) as rapides')
                )
                ->first();
            
            $total = $stats->total ?? 0;
            $avecReservation = $stats->avec_reservation ?? 0;
            $explorations = $stats->explorations ?? 0;
            $rapides = $stats->rapides ?? 0;
            
            return [
                'taux_conversion_global' => $total > 0 ? round(($avecReservation / $total) * 100, 2) : 0,
                'taux_rebond' => $total > 0 ? round(($rapides / $total) * 100, 2) : 0,
                'taux_exploration' => $total > 0 ? round(($explorations / $total) * 100, 2) : 0,
                'ratio_exploration_reservation' => $explorations > 0 ? round(($avecReservation / $explorations) * 100, 2) : 0,
            ];
        });
    }

    private function getStatsParEntreprise($dateDebut)
    {
        $cacheKey = 'admin_stats_par_entreprise_' . $dateDebut->format('Y-m-d');
        
        return Cache::remember($cacheKey, 300, function () use ($dateDebut) {
            // Utiliser des requêtes agrégées au lieu de charger toutes les visites
            $entreprises = Entreprise::select('id', 'nom', 'slug', 'ville')
                ->withCount([
                    'visites as total_visites' => function($q) use ($dateDebut) {
                        $q->where('created_at', '>=', $dateDebut);
                    },
                    'visites as visites_exploration' => function($q) use ($dateDebut) {
                        $q->where('created_at', '>=', $dateDebut)
                          ->where('a_quitte_apres_exploration', true);
                    },
                    'visites as visites_rapides' => function($q) use ($dateDebut) {
                        $q->where('created_at', '>=', $dateDebut)
                          ->where('a_quitte_rapidement', true);
                    },
                    'visites as reservations_count' => function($q) use ($dateDebut) {
                        $q->where('created_at', '>=', $dateDebut)
                          ->where('a_passe_commande', true);
                    }
                ])
                ->limit(50)
                ->get();
            
            // Calculer les revenus et autres métriques
            $revenus = Reservation::where('created_at', '>=', $dateDebut)
                ->where('est_paye', true)
                ->select('entreprise_id', DB::raw('SUM(prix) as total_revenu'))
                ->groupBy('entreprise_id')
                ->pluck('total_revenu', 'entreprise_id');
            
            $dureesMoyennes = EntrepriseVisite::where('created_at', '>=', $dateDebut)
                ->where('duree_seconde', '>', 0)
                ->select('entreprise_id', DB::raw('AVG(duree_seconde) as duree_moyenne'))
                ->groupBy('entreprise_id')
                ->pluck('duree_moyenne', 'entreprise_id');
            
            return $entreprises->map(function($entreprise) use ($revenus, $dureesMoyennes) {
                $totalVisites = $entreprise->total_visites ?? 0;
                $reservations = $entreprise->reservations_count ?? 0;
                $tauxConversion = $totalVisites > 0 ? round(($reservations / $totalVisites) * 100, 2) : 0;
                
                return [
                    'entreprise' => $entreprise,
                    'total_visites' => $totalVisites,
                    'visites_exploration' => $entreprise->visites_exploration ?? 0,
                    'visites_rapides' => $entreprise->visites_rapides ?? 0,
                    'reservations' => $reservations,
                    'taux_conversion' => $tauxConversion,
                    'duree_moyenne' => round($dureesMoyennes[$entreprise->id] ?? 0),
                    'revenu' => $revenus[$entreprise->id] ?? 0,
                ];
            })->sortByDesc('total_visites')->values();
        });
    }

    private function getStatsFinances($dateDebut)
    {
        return [
            'total_revenu' => Reservation::where('est_paye', true)
                ->where('created_at', '>=', $dateDebut)
                ->sum('prix'),
            'total_factures' => Facture::where('created_at', '>=', $dateDebut)->sum('montant_ht'),
            'total_finances_enregistrees' => EntrepriseFinance::where('type', 'income')
                ->where('date_record', '>=', $dateDebut)
                ->sum('amount'),
            'total_depenses' => EntrepriseFinance::where('type', 'expense')
                ->where('date_record', '>=', $dateDebut)
                ->sum('amount'),
        ];
    }

    private function getStatsAbonnements()
    {
        return Cache::remember('admin_stats_abonnements', 300, function () {
            return [
                'total_actifs' => User::where(function($q) {
                    $q->where(function($q2) {
                        $q2->where('abonnement_manuel', true)
                           ->where('abonnement_manuel_actif_jusqu', '>=', now());
                    })->orWhereHas('subscriptions', function($q3) {
                        $q3->where('stripe_status', 'active');
                    });
                })->count(),
                'manuels_actifs' => User::where('abonnement_manuel', true)
                    ->where('abonnement_manuel_actif_jusqu', '>=', now())
                    ->count(),
                'stripe_actifs' => DB::table('subscriptions')->where('stripe_status', 'active')->count(),
                'expires_bientot' => User::where('abonnement_manuel', true)
                    ->where('abonnement_manuel_actif_jusqu', '>=', now())
                    ->where('abonnement_manuel_actif_jusqu', '<=', now()->addDays(7))
                    ->count(),
            ];
        });
    }

    private function getStatsTemporelles($periodDays)
    {
        $cacheKey = 'admin_stats_temporelles_' . $periodDays;
        
        return Cache::remember($cacheKey, 300, function () use ($periodDays) {
            $labels = [];
            $visitesData = [];
            $reservationsData = [];
            $usersData = [];
            
            for ($i = $periodDays - 1; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $dateStr = $date->format('Y-m-d');
                $labels[] = $date->format('d/m');
                
                $visitesData[] = EntrepriseVisite::whereDate('created_at', $dateStr)->count();
                $reservationsData[] = Reservation::whereDate('created_at', $dateStr)->count();
                $usersData[] = User::whereDate('created_at', $dateStr)->count();
            }
            
            return [
                'labels' => $labels,
                'visites' => $visitesData,
                'reservations' => $reservationsData,
                'users' => $usersData,
            ];
        });
    }

    private function getTopServicesGlobal($dateDebut, $limit = 20)
    {
        return VisiteClic::where('type', 'service')
            ->where('created_at', '>=', $dateDebut)
            ->select('item_id', 'item_nom', DB::raw('count(*) as nb_clics'))
            ->groupBy('item_id', 'item_nom')
            ->orderBy('nb_clics', 'desc')
            ->limit($limit)
            ->get();
    }

    private function getTopProduitsGlobal($dateDebut, $limit = 20)
    {
        $cacheKey = 'admin_top_produits_' . $dateDebut->format('Y-m-d') . '_' . $limit;
        
        return Cache::remember($cacheKey, 300, function () use ($dateDebut, $limit) {
            return VisiteClic::where('type', 'produit')
                ->where('created_at', '>=', $dateDebut)
                ->select('item_id', 'item_nom', DB::raw('count(*) as nb_clics'))
                ->groupBy('item_id', 'item_nom')
                ->orderBy('nb_clics', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    private function getStatsRGPD()
    {
        $totalUsers = User::whereNotNull('tracking_consent')->count();
        
        return [
            'consentement_oui' => User::where('tracking_consent', true)->count(),
            'consentement_non' => User::where('tracking_consent', false)->count(),
            'consentement_par_defaut' => User::whereNull('tracking_consent')->count(),
            'taux_consentement' => $totalUsers > 0 ? round((User::where('tracking_consent', true)->count() / $totalUsers) * 100, 2) : 0,
        ];
    }

    private function getStatsActivite($dateDebut)
    {
        $cacheKey = 'admin_stats_activite_' . $dateDebut->format('Y-m-d');
        
        return Cache::remember($cacheKey, 300, function () use ($dateDebut) {
            return [
                'conversations' => Conversation::where('created_at', '>=', $dateDebut)->count(),
                'messages' => Message::where('created_at', '>=', $dateDebut)->count(),
                'tickets' => Ticket::where('created_at', '>=', $dateDebut)->count(),
                'contacts' => Contact::where('created_at', '>=', $dateDebut)->count(),
            ];
        });
    }

    private function getStatsPages($dateDebut)
    {
        $cacheKey = 'admin_stats_pages_' . $dateDebut->format('Y-m-d');
        
        return Cache::remember($cacheKey, 300, function () use ($dateDebut) {
            // Utiliser des requêtes agrégées
            $stats = EntrepriseVisite::where('created_at', '>=', $dateDebut)
                ->select(
                    'page_type',
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('page_type')
                ->pluck('count', 'page_type');
            
            return [
                'accueil' => $stats['accueil'] ?? 0,
                'agenda' => $stats['agenda'] ?? 0,
                'store' => $stats['store'] ?? 0,
                'services' => $stats['services'] ?? 0,
                'produits' => $stats['produits'] ?? 0,
            ];
        });
    }

    private function getStatsGeo($dateDebut)
    {
        $cacheKey = 'admin_stats_geo_' . $dateDebut->format('Y-m-d');
        
        return Cache::remember($cacheKey, 300, function () use ($dateDebut) {
            return Entreprise::withCount(['visites as total_visites' => function($q) use ($dateDebut) {
                $q->where('created_at', '>=', $dateDebut);
            }])->whereNotNull('ville')
                ->orderBy('total_visites', 'desc')
                ->get()
                ->groupBy('ville')
                ->map(function($entreprises) {
                    return [
                        'ville' => $entreprises->first()->ville,
                        'total_visites' => $entreprises->sum('total_visites'),
                        'nb_entreprises' => $entreprises->count(),
                    ];
                })
                ->sortByDesc('total_visites')
                ->take(20);
        });
    }

    private function getStatsHeure($dateDebut)
    {
        $cacheKey = 'admin_stats_heure_' . $dateDebut->format('Y-m-d');
        
        return Cache::remember($cacheKey, 300, function () use ($dateDebut) {
            $stats = [];
            for ($h = 0; $h < 24; $h++) {
                $stats[$h] = EntrepriseVisite::where('created_at', '>=', $dateDebut)
                    ->whereRaw('HOUR(created_at) = ?', [$h])
                    ->count();
            }
            return $stats;
        });
    }
}
