<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
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
        
        // Récupérer l'entreprise avec vérification des droits
        $entreprise = Entreprise::where('slug', $slug)
            ->where(function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhere(fn($q) => $q->whereRaw('1=1')->where(fn($sq) => $sq->whereRaw($user->is_admin ? '1=1' : '0=1')));
            })
            ->with(['realisationPhotos', 'typesServices.images', 'typesServices.imageCouverture'])
            ->firstOrFail();

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

        // Onglet actif (par défaut: accueil)
        $activeTab = $request->get('tab', 'accueil');

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

        return [
            'total_reservations' => $allReservations->count(),
            'reservations_confirmees' => $allReservations->where('statut', 'confirmee')->count(),
            'reservations_en_attente' => $allReservations->where('statut', 'en_attente')->count(),
            'reservations_terminees' => $allReservations->where('statut', 'terminee')->count(),
            'reservations_annulees' => $allReservations->where('statut', 'annulee')->count(),
            'revenu_total' => $allReservations->sum('prix'),
            'revenu_paye' => $allReservations->where('est_paye', true)->sum('prix'),
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
            ->with(['user', 'typeService'])
            ->orderBy('date_reservation', 'asc')
            ->get();
    }

    /**
     * Récupérer les réservations groupées par statut
     */
    private function getReservationsGroupedByStatus(Request $request, Entreprise $entreprise)
    {
        $query = Reservation::where('entreprise_id', $entreprise->id)
            ->with(['user', 'typeService', 'facture']);

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
