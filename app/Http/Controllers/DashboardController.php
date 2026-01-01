<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Afficher le dashboard du membre
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $entreprises = $user->entreprises()->withCount('reservations')->get();
        
        // Charger les réservations du client (si c'est un client)
        $reservations = collect([]);
        if ($user->est_client) {
            $query = $user->reservations()
                ->with(['entreprise', 'facture']);

            // Recherche
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('type_service', 'like', "%{$search}%")
                      ->orWhere('lieu', 'like', "%{$search}%")
                      ->orWhereHas('entreprise', function($entrepriseQuery) use ($search) {
                          $entrepriseQuery->where('nom', 'like', "%{$search}%")
                                          ->orWhere('type_activite', 'like', "%{$search}%");
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

            $reservations = $query->orderBy('date_reservation', 'desc')->get();
        }

        // Statistiques et réservations en attente pour les gérants
        $stats = null;
        $reservationsEnAttente = collect([]);
        
        if ($user->est_gerant && $entreprises->count() > 0) {
            // Calculer les statistiques globales de toutes les entreprises
            $allReservations = Reservation::whereIn('entreprise_id', $entreprises->pluck('id'))
                ->get();
            
            // Réservations acceptées uniquement (confirmées ou terminées)
            $reservationsAcceptees = $allReservations->filter(function($r) {
                return in_array($r->statut, ['confirmee', 'terminee']);
            });
            
            $stats = [
                'total_reservations' => $allReservations->count(),
                'reservations_confirmees' => $allReservations->where('statut', 'confirmee')->count(),
                'reservations_en_attente' => $allReservations->where('statut', 'en_attente')->count(),
                'reservations_terminees' => $allReservations->where('statut', 'terminee')->count(),
                'revenu_total' => $reservationsAcceptees->sum('prix'), // Uniquement les réservations acceptées
                'revenu_paye' => $allReservations->where('est_paye', true)->sum('prix'), // CA : paiements confirmés
                'revenu_en_attente' => $allReservations->where('est_paye', false)->sum('prix'),
                'reservations_ce_mois' => $allReservations->filter(function($r) {
                    return $r->date_reservation->isCurrentMonth();
                })->count(),
                'revenu_ce_mois' => $reservationsAcceptees->filter(function($r) {
                    return $r->date_reservation->isCurrentMonth();
                })->sum('prix'),
            ];

            // Récupérer les réservations en attente pour toutes les entreprises
            $reservationsEnAttente = Reservation::whereIn('entreprise_id', $entreprises->pluck('id'))
                ->where('statut', 'en_attente')
                ->with(['user', 'typeService', 'entreprise'])
                ->orderBy('date_reservation', 'asc')
                ->get();

            // Statistiques par entreprise
            foreach ($entreprises as $entreprise) {
                $entrepriseReservations = Reservation::where('entreprise_id', $entreprise->id)->get();
                // Réservations acceptées uniquement (confirmées ou terminées)
                $entrepriseReservationsAcceptees = $entrepriseReservations->filter(function($r) {
                    return in_array($r->statut, ['confirmee', 'terminee']);
                });
                
                $entreprise->stats = [
                    'total_reservations' => $entrepriseReservations->count(),
                    'revenu_total' => $entrepriseReservationsAcceptees->sum('prix'), // Uniquement les réservations acceptées
                    'revenu_paye' => $entrepriseReservations->where('est_paye', true)->sum('prix'), // CA : paiements confirmés
                    'reservations_ce_mois' => $entrepriseReservations->filter(function($r) {
                        return $r->date_reservation->isCurrentMonth();
                    })->count(),
                    'reservations_en_attente' => $entrepriseReservations->where('statut', 'en_attente')->count(),
                ];
            }
        }

        // Récupérer les entreprises où l'utilisateur est membre (mais pas propriétaire)
        $entreprisesAutres = \App\Models\EntrepriseMembre::where('user_id', $user->id)
            ->where('est_actif', true)
            ->with(['entreprise'])
            ->get()
            ->map(function($membre) {
                return $membre->entreprise;
            })
            ->filter(); // Filtrer les nulls si l'entreprise n'existe plus

        return view('dashboard.index', [
            'user' => $user,
            'entreprises' => $entreprises,
            'entreprisesAutres' => $entreprisesAutres,
            'reservations' => $reservations,
            'stats' => $stats,
            'reservationsEnAttente' => $reservationsEnAttente,
        ]);
    }

    /**
     * Marquer une réservation comme payée depuis le dashboard
     */
    public function marquerPayee(Request $request, Reservation $reservation)
    {
        $user = Auth::user();

        // Vérifier que la réservation appartient à l'utilisateur (client)
        if ($reservation->user_id !== $user->id) {
            return back()->withErrors(['error' => 'Vous n\'avez pas le droit de modifier cette réservation.']);
        }

        // Vérifier que la réservation n'est pas déjà payée
        if ($reservation->est_paye) {
            return back()->withErrors(['error' => 'Cette réservation est déjà marquée comme payée.']);
        }

        // Marquer comme payée
        $reservation->update([
            'est_paye' => true,
            'date_paiement' => now(),
        ]);

        // Créer une notification pour l'entreprise
        if ($reservation->entreprise && $reservation->entreprise->user) {
            Notification::creer(
                $reservation->entreprise->user_id,
                'paiement',
                'Paiement confirmé',
                "Le client {$user->name} a marqué la réservation du {$reservation->date_reservation->format('d/m/Y')} comme payée.",
                route('reservations.show', [$reservation->entreprise->slug, $reservation->id]),
                ['reservation_id' => $reservation->id]
            );
        }

        return back()->with('success', 'La réservation a été marquée comme payée avec succès.');
    }

    /**
     * Afficher les entreprises où l'utilisateur est membre (mais pas propriétaire)
     */
    public function entreprisesAutres()
    {
        $user = Auth::user();
        
        // Récupérer les entreprises où l'utilisateur est membre actif
        $membres = \App\Models\EntrepriseMembre::where('user_id', $user->id)
            ->where('est_actif', true)
            ->with(['entreprise'])
            ->get();

        $entreprisesAvecStats = $membres->map(function($membre) use ($user) {
            $entreprise = $membre->entreprise;
            if (!$entreprise) {
                return null;
            }

            $data = [
                'entreprise' => $entreprise,
                'membre' => $membre,
                'estAdmin' => $entreprise->aAdministrateur($user),
            ];

            // Calculer les stats uniquement si l'utilisateur est admin
            if ($data['estAdmin']) {
                $entrepriseReservations = Reservation::where('entreprise_id', $entreprise->id)->get();
                // Réservations acceptées uniquement (confirmées ou terminées)
                $entrepriseReservationsAcceptees = $entrepriseReservations->filter(function($r) {
                    return in_array($r->statut, ['confirmee', 'terminee']);
                });
                
                $data['stats'] = [
                    'total_reservations' => $entrepriseReservations->count(),
                    'revenu_total' => $entrepriseReservationsAcceptees->sum('prix'), // Uniquement les réservations acceptées
                    'revenu_paye' => $entrepriseReservations->where('est_paye', true)->sum('prix'), // CA : paiements confirmés
                    'reservations_ce_mois' => $entrepriseReservations->filter(function($r) {
                        return $r->date_reservation->isCurrentMonth();
                    })->count(),
                    'reservations_en_attente' => $entrepriseReservations->where('statut', 'en_attente')->count(),
                ];
            }

            return $data;
        })->filter();

        return view('dashboard.entreprises-autres', [
            'user' => $user,
            'entreprisesAvecStats' => $entreprisesAvecStats,
        ]);
    }
}
