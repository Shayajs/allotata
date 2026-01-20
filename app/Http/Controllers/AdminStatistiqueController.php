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
        // Période par défaut : 30 jours
        $periodDays = $request->get('period', 30);
        $dateDebut = now()->subDays($periodDays);
        
        // ===== STATISTIQUES GLOBALES =====
        $statsGlobales = $this->getStatsGlobales($periodDays);
        
        // ===== STATISTIQUES DE VISITES =====
        $statsVisites = $this->getStatsVisites($dateDebut);
        
        // ===== STATISTIQUES DE CONVERSION =====
        $statsConversion = $this->getStatsConversion($dateDebut);
        
        // ===== STATISTIQUES PAR ENTREPRISE =====
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
    }

    private function getStatsVisites($dateDebut)
    {
        $visites = EntrepriseVisite::where('created_at', '>=', $dateDebut)->get();
        
        return [
            'total' => $visites->count(),
            'avec_user' => $visites->whereNotNull('user_id')->count(),
            'anonymes' => $visites->whereNull('user_id')->count(),
            'avec_reservation' => $visites->where('a_passe_commande', true)->count(),
            'explorations' => $visites->where('a_quitte_apres_exploration', true)->count(),
            'rapides' => $visites->where('a_quitte_rapidement', true)->count(),
            'duree_moyenne' => round($visites->where('duree_seconde', '>', 0)->avg('duree_seconde') ?? 0),
            'temps_moyen_avant_reservation' => round($visites->where('temps_avant_reservation_secondes', '>', 0)->avg('temps_avant_reservation_secondes') ?? 0),
            'total_clics_services' => $visites->sum('nb_clics_services'),
            'total_clics_produits' => $visites->sum('nb_clics_produits'),
        ];
    }

    private function getStatsConversion($dateDebut)
    {
        $visites = EntrepriseVisite::where('created_at', '>=', $dateDebut)->get();
        
        $total = $visites->count();
        $avecReservation = $visites->where('a_passe_commande', true)->count();
        $explorations = $visites->where('a_quitte_apres_exploration', true)->count();
        $rapides = $visites->where('a_quitte_rapidement', true)->count();
        
        return [
            'taux_conversion_global' => $total > 0 ? round(($avecReservation / $total) * 100, 2) : 0,
            'taux_rebond' => $total > 0 ? round(($rapides / $total) * 100, 2) : 0,
            'taux_exploration' => $total > 0 ? round(($explorations / $total) * 100, 2) : 0,
            'ratio_exploration_reservation' => $explorations > 0 ? round(($avecReservation / $explorations) * 100, 2) : 0,
        ];
    }

    private function getStatsParEntreprise($dateDebut)
    {
        return Entreprise::with(['visites' => function($q) use ($dateDebut) {
            $q->where('created_at', '>=', $dateDebut);
        }])->get()->map(function($entreprise) use ($dateDebut) {
            $visites = $entreprise->visites;
            
            $reservations = $visites->where('a_passe_commande', true)->count();
            $tauxConversion = $visites->count() > 0 ? round(($reservations / $visites->count()) * 100, 2) : 0;
            
            $revenu = Reservation::where('entreprise_id', $entreprise->id)
                ->where('created_at', '>=', $dateDebut)
                ->where('est_paye', true)
                ->sum('prix');
            
            return [
                'entreprise' => $entreprise,
                'total_visites' => $visites->count(),
                'visites_exploration' => $visites->where('a_quitte_apres_exploration', true)->count(),
                'visites_rapides' => $visites->where('a_quitte_rapidement', true)->count(),
                'reservations' => $reservations,
                'taux_conversion' => $tauxConversion,
                'duree_moyenne' => round($visites->where('duree_seconde', '>', 0)->avg('duree_seconde') ?? 0),
                'revenu' => $revenu,
            ];
        })->sortByDesc('total_visites')->take(50);
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
    }

    private function getStatsTemporelles($periodDays)
    {
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
        return VisiteClic::where('type', 'produit')
            ->where('created_at', '>=', $dateDebut)
            ->select('item_id', 'item_nom', DB::raw('count(*) as nb_clics'))
            ->groupBy('item_id', 'item_nom')
            ->orderBy('nb_clics', 'desc')
            ->limit($limit)
            ->get();
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
        return [
            'conversations' => Conversation::where('created_at', '>=', $dateDebut)->count(),
            'messages' => Message::where('created_at', '>=', $dateDebut)->count(),
            'tickets' => Ticket::where('created_at', '>=', $dateDebut)->count(),
            'contacts' => Contact::where('created_at', '>=', $dateDebut)->count(),
        ];
    }

    private function getStatsPages($dateDebut)
    {
        $visites = EntrepriseVisite::where('created_at', '>=', $dateDebut)->get();
        
        return [
            'accueil' => $visites->where('page_type', 'accueil')->count(),
            'agenda' => $visites->where('page_type', 'agenda')->count(),
            'store' => $visites->where('page_type', 'store')->count(),
            'services' => $visites->where('page_type', 'services')->count(),
            'produits' => $visites->where('page_type', 'produits')->count(),
        ];
    }

    private function getStatsGeo($dateDebut)
    {
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
    }

    private function getStatsHeure($dateDebut)
    {
        $stats = [];
        for ($h = 0; $h < 24; $h++) {
            $stats[$h] = EntrepriseVisite::where('created_at', '>=', $dateDebut)
                ->whereRaw('HOUR(created_at) = ?', [$h])
                ->count();
        }
        return $stats;
    }
}
