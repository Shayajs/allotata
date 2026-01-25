<?php

namespace App\Http\Controllers;

use App\Models\Echeance;
use App\Models\Reservation;
use App\Models\StripeTransaction;
use App\Models\Notification;
use App\Models\LoginAttempt;
use App\Models\SecurityLog;
use App\Models\UserIpHistory;
use App\Models\AccountLockout;
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
        
        // Récupérer les entreprises (y compris celles archivées récemment)
        $entreprises = $user->entreprises()
            ->withTrashed()
            ->withCount('reservations')
            ->get()
            ->filter(function ($entreprise) {
                // Garder les actives
                if (!$entreprise->trashed()) {
                    return true;
                }
                // Garder les archivées restaurables (< 30 jours)
                return $entreprise->canBeRestoredByUser();
            });
        
        // Charger les réservations du client (si c'est un client)
        $reservations = collect([]);
        if ($user->est_client) {
            $query = $user->reservations()
                ->with(['entreprise', 'facture', 'typeService']);

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

            // Filtrer les réservations passées selon le paramètre
            // Si le paramètre "passees" est présent, on affiche les passées, sinon on affiche les futures
            if (!$request->has('passees')) {
                $query->where('date_reservation', '>=', now());
            } else {
                $query->where('date_reservation', '<', now());
            }

            $reservations = $query->orderBy('date_reservation', 'desc')->get();
        }

        // Statistiques et réservations en attente pour les gérants
        $entrepriseStats = null;
        $reservationsEnAttente = collect([]);
        
        if ($user->est_gerant && $entreprises->count() > 0) {
            // Calculer les statistiques globales de toutes les entreprises
            $allReservations = Reservation::whereIn('entreprise_id', $entreprises->pluck('id'))
                ->get();
            
            // Réservations acceptées uniquement (confirmées ou terminées)
            $reservationsAcceptees = $allReservations->filter(function($r) {
                return in_array($r->statut, ['confirmee', 'terminee']);
            });
            
            $entrepriseStats = [
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

        // Récupérer les informations d'abonnement Stripe
        $subscription = $user->subscription('default');
        $stripeSubscription = null;
        $invoices = collect([]);
        
        if ($subscription && $subscription->valid() && $user->stripe_id) {
            try {
                $stripeSubscription = $subscription->asStripeSubscription();
            } catch (\Exception $e) {
                // Ignorer
            }
        }
        if ($user->stripe_id) {
            try {
                $stripeInvoices = \Stripe\Invoice::all([
                    'customer' => $user->stripe_id,
                    'limit' => 12,
                ], ['api_key' => config('services.stripe.secret')]);
                $invoices = collect($stripeInvoices->data);
            } catch (\Exception $e) {
                // Ignorer
            }
        }

        // Derniers paiements et prochaines échéances (onglet abonnements)
        $echeancesPayees = Echeance::where('user_id', $user->id)
            ->where('statut', Echeance::STATUT_PAYE)
            ->orderByDesc('paye_at')
            ->limit(20)
            ->get();
        $echeanceIds = $echeancesPayees->pluck('id')->all();
        $transactions = StripeTransaction::where('user_id', $user->id)
            ->where('processed', true)
            ->orderByDesc('processed_at')
            ->limit(30)
            ->get();
        $lastPayments = collect();
        foreach ($echeancesPayees as $e) {
            $lastPayments->push((object) [
                'type' => 'echeance',
                'id' => $e->id,
                'date' => $e->paye_at ?? $e->updated_at,
                'amount' => (float) ($e->montant_final ?? $e->montant_du ?? 0),
                'currency' => 'eur',
                'label' => $e->libelle(),
            ]);
        }
        foreach ($transactions as $t) {
            $meta = $t->metadata ?? [];
            $eid = (int) ($meta['echeance_id'] ?? 0);
            if ($eid && in_array($eid, $echeanceIds, true)) {
                continue;
            }
            $lastPayments->push((object) [
                'type' => 'transaction',
                'id' => $t->id,
                'date' => $t->processed_at ?? $t->created_at,
                'amount' => (float) $t->amount,
                'currency' => $t->currency ?? 'eur',
                'label' => $t->description ? "Paiement – {$t->description}" : 'Paiement',
            ]);
        }
        $lastPayments = $lastPayments->sortByDesc(fn ($p) => $p->date)->take(15)->values();
        $upcomingEcheances = Echeance::where('user_id', $user->id)
            ->whereIn('statut', [Echeance::STATUT_A_PAYER, Echeance::STATUT_EN_ATTENTE])
            ->orderBy('periode_fin')
            ->get();

        // Variables pour l'onglet Sécurité
        $loginAttempts = LoginAttempt::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->orderBy('attempted_at', 'desc')
            ->limit(50)
            ->get();

        $securityLogs = SecurityLog::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $ipHistory = UserIpHistory::where('user_id', $user->id)
            ->orderBy('last_seen_at', 'desc')
            ->get();

        $lockout = $user->accountLockout;
        $isLocked = $lockout && $lockout->isCurrentlyLocked();

        $hasSuspiciousActivity = SecurityLog::where('user_id', $user->id)
            ->where('is_suspicious', true)
            ->where('created_at', '>=', now()->subDays(7))
            ->exists();

        $stats = [
            'total_login_attempts' => $loginAttempts->count(),
            'failed_attempts' => $loginAttempts->where('success', false)->count(),
            'successful_logins' => $loginAttempts->where('success', true)->count(),
            'suspicious_logs' => SecurityLog::where('user_id', $user->id)
                ->where('is_suspicious', true)
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
            'unique_ips' => $ipHistory->count(),
        ];

        return view('dashboard.index', [
            'user' => $user,
            'entreprises' => $entreprises,
            'entreprisesAutres' => $entreprisesAutres,
            'reservations' => $reservations,
            'stats' => $entrepriseStats, // Stats des entreprises (pour l'onglet accueil)
            'reservationsEnAttente' => $reservationsEnAttente,
            'subscription' => $subscription,
            'stripeSubscription' => $stripeSubscription,
            'invoices' => $invoices,
            'lastPayments' => $lastPayments,
            'upcomingEcheances' => $upcomingEcheances,
            // Variables pour l'onglet Sécurité
            'loginAttempts' => $loginAttempts,
            'securityLogs' => $securityLogs,
            'ipHistory' => $ipHistory,
            'lockout' => $lockout,
            'isLocked' => $isLocked,
            'hasSuspiciousActivity' => $hasSuspiciousActivity,
            'securityStats' => $stats, // Stats de sécurité
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
