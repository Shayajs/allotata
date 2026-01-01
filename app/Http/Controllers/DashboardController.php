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

            // Filtrer les réservations passées (ne pas afficher les rendez-vous qui sont déjà passés)
            $query->where('date_reservation', '>=', now());

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
     * Annuler une réservation (côté client)
     */
    public function cancel(Reservation $reservation)
    {
        $user = Auth::user();

        // Vérifier que la réservation appartient à l'utilisateur (client)
        if ($reservation->user_id !== $user->id) {
            return back()->withErrors(['error' => 'Vous n\'avez pas le droit de modifier cette réservation.']);
        }

        // Vérifier que la réservation peut être annulée
        if (!in_array($reservation->statut, ['en_attente', 'confirmee'])) {
            return back()->withErrors(['error' => 'Cette réservation ne peut pas être annulée.']);
        }

        // Empêcher l'annulation si la réservation est payée
        if ($reservation->est_paye) {
            return back()->withErrors(['error' => 'Une réservation payée ne peut pas être annulée.']);
        }

        // Annuler la réservation
        $reservation->update([
            'statut' => 'annulee',
        ]);

        // Créer une notification pour l'entreprise
        if ($reservation->entreprise && $reservation->entreprise->user) {
            Notification::creer(
                $reservation->entreprise->user_id,
                'reservation',
                'Réservation annulée',
                "Le client {$user->name} a annulé la réservation du {$reservation->date_reservation->format('d/m/Y à H:i')}.",
                route('reservations.show', [$reservation->entreprise->slug, $reservation->id]),
                ['reservation_id' => $reservation->id]
            );
        }

        return back()->with('success', 'La réservation a été annulée avec succès. L\'entreprise a été notifiée.');
    }

    /**
     * Modifier une réservation (côté client)
     */
    public function modify(Request $request, Reservation $reservation)
    {
        $user = Auth::user();

        // Vérifier que la réservation appartient à l'utilisateur (client)
        if ($reservation->user_id !== $user->id) {
            return back()->withErrors(['error' => 'Vous n\'avez pas le droit de modifier cette réservation.']);
        }

        // Vérifier que la réservation peut être modifiée (seulement en attente)
        if ($reservation->statut !== 'en_attente') {
            return back()->withErrors(['error' => 'Seules les réservations en attente peuvent être modifiées.']);
        }

        $validated = $request->validate([
            'date_reservation' => ['required', 'date', 'after:now'],
            'heure_reservation' => ['required', 'date_format:H:i'],
            'lieu' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        // Combiner date et heure
        $dateTime = $validated['date_reservation'] . ' ' . $validated['heure_reservation'];
        $dateReservation = \Carbon\Carbon::parse($dateTime);

        // Sauvegarder les anciennes valeurs pour la notification
        $ancienneDate = $reservation->date_reservation->format('d/m/Y à H:i');
        $ancienLieu = $reservation->lieu;
        $anciennesNotes = $reservation->notes;

        // Mettre à jour la réservation
        $reservation->update([
            'date_reservation' => $dateReservation,
            'lieu' => $validated['lieu'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Construire le message de notification
        $changements = [];
        if ($ancienneDate !== $dateReservation->format('d/m/Y à H:i')) {
            $changements[] = "Date/heure : {$ancienneDate} → {$dateReservation->format('d/m/Y à H:i')}";
        }
        if ($ancienLieu !== ($validated['lieu'] ?? null)) {
            $changements[] = "Lieu modifié";
        }
        if ($anciennesNotes !== ($validated['notes'] ?? null)) {
            $changements[] = "Notes modifiées";
        }

        // Créer une notification pour l'entreprise
        if ($reservation->entreprise && $reservation->entreprise->user && !empty($changements)) {
            Notification::creer(
                $reservation->entreprise->user_id,
                'reservation',
                'Réservation modifiée',
                "Le client {$user->name} a modifié la réservation : " . implode(', ', $changements),
                route('reservations.show', [$reservation->entreprise->slug, $reservation->id]),
                ['reservation_id' => $reservation->id]
            );
        }

        return back()->with('success', 'La réservation a été modifiée avec succès. L\'entreprise a été notifiée.');
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
