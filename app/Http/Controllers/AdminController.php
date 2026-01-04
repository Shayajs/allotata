<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Entreprise;
use App\Models\Reservation;
use App\Models\CustomPrice;
use App\Models\EntrepriseSubscription;
use Laravel\Cashier\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Price;
use Stripe\Product;

use App\Models\Ticket;
use App\Models\Contact;
use App\Models\EntrepriseFinance;

class AdminController extends Controller
{
    /**
     * Afficher le dashboard administrateur
     */
    public function index()
    {
        // Statistiques de base
        $stats = [
            'total_users' => User::count(),
            'total_clients' => User::where('est_client', true)->count(),
            'total_gerants' => User::where('est_gerant', true)->count(),
            'total_entreprises' => Entreprise::count(),
            'entreprises_verifiees' => Entreprise::where('est_verifiee', true)->count(),
            'entreprises_en_attente' => Entreprise::where('est_verifiee', false)->count(),
            'total_reservations' => Reservation::count(),
            'reservations_payees' => Reservation::where('est_paye', true)->count(),
            'abonnements_actifs' => User::where(function($q) {
                $q->where(function($q2) {
                    $q2->where('abonnement_manuel', true)
                       ->where('abonnement_manuel_actif_jusqu', '>=', now());
                })->orWhereHas('subscriptions', function($q3) {
                    $q3->where('stripe_status', 'active');
                });
            })->count(),
            'abonnements_manuels' => User::where('abonnement_manuel', true)
                ->where('abonnement_manuel_actif_jusqu', '>=', now())->count(),
            'abonnements_stripe' => DB::table('subscriptions')
                ->where('stripe_status', 'active')->count(),
        ];

        // Alertes prioritaires
        $alertes = [
            'entreprises_en_attente' => Entreprise::where('est_verifiee', false)->count(),
            'tickets_urgents' => Ticket::where('statut', 'ouvert')
                ->where('priorite', 'urgente')->count(),
            'contacts_non_lus' => Contact::where('est_lu', false)->count(),
        ];

        // Données pour les graphiques (30 derniers jours)
        $chartData = $this->getChartData();

        // Derniers utilisateurs inscrits
        $derniersUtilisateurs = User::orderBy('created_at', 'desc')
            ->take(5)
            ->get();
            
        // --- Feed d'activité (War Room) ---
        $reservations = Reservation::with('entreprise')->latest()->take(8)->get()->map(function($r) {
            return [
                'type' => 'reservation',
                'icon' => '📅',
                'color' => 'blue',
                'time' => $r->created_at,
                'text' => "Nouvelle réservation chez " . ($r->entreprise->nom ?? 'Une entreprise'),
                'subtext' => "Client : " . ($r->user->name ?? 'Invité')
            ];
        });
        
        $users = User::latest()->take(5)->get()->map(function($u) {
            return [
                'type' => 'user',
                'icon' => '👤',
                'color' => 'green',
                'time' => $u->created_at,
                'text' => "Inscription : " . $u->name,
                'subtext' => $u->email
            ];
        });
        
        $finances = EntrepriseFinance::with('entreprise')->where('type', 'income')->latest()->take(8)->get()->map(function($f) {
            return [
                'type' => 'finance',
                'icon' => '💰',
                'color' => 'yellow',
                'time' => $f->created_at,
                'text' => "Encaissement " . ($f->entreprise->nom ?? 'Inconnu'),
                'subtext' => "+ " . number_format($f->amount, 2) . '€'
            ];
        });

        $activityFeed = $reservations->concat($users)->concat($finances)->sortByDesc('time')->take(12);

        // --- Estimation MRR (Business Intelligence) ---
        // On assume un panier moyen de 29.99€ si on ne peut pas récupérer le prix exact
        $prixMoyenAbo = 29.99; 
        $stats['mrr'] = $stats['abonnements_actifs'] * $prixMoyenAbo;

        return view('admin.dashboard', compact('stats', 'alertes', 'chartData', 'derniersUtilisateurs', 'activityFeed'));
    }

    /**
     * Voir les finances de toutes les entreprises
     */
    public function finances(Request $request)
    {
        $query = EntrepriseFinance::with('entreprise');

        // Filtres
        if ($request->filled('month')) {
            $query->whereMonth('date_record', $request->month);
        }
        if ($request->filled('year')) {
            $query->whereYear('date_record', $request->year);
        }

        $finances = $query->orderBy('date_record', 'desc')->paginate(50);

        // Stats globales
        $totalIncome = EntrepriseFinance::where('type', 'income')->sum('amount');
        $totalExpense = EntrepriseFinance::where('type', 'expense')->sum('amount');

        return view('admin.finances.index', [
            'finances' => $finances,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
        ]);
    }

    /**
     * Générer les données pour les graphiques
     */
    private function getChartData(): array
    {
        $days = 30;
        $labels = [];
        $inscriptionsData = [];
        $reservationsData = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('d/m');
            
            $inscriptionsData[] = User::whereDate('created_at', $date)->count();
            $reservationsData[] = Reservation::whereDate('created_at', $date)->count();
        }

        // Tickets par statut
        $ticketsData = [
            Ticket::where('statut', 'ouvert')->count(),
            Ticket::where('statut', 'en_cours')->count(),
            Ticket::where('statut', 'resolu')->count(),
            Ticket::where('statut', 'ferme')->count(),
        ];

        return [
            'inscriptions' => [
                'labels' => $labels,
                'data' => $inscriptionsData,
            ],
            'reservations' => [
                'labels' => $labels,
                'data' => $reservationsData,
            ],
            'tickets' => $ticketsData,
        ];
    }

    /**
     * Liste des utilisateurs
     */
    public function users(Request $request)
    {
        $query = User::withCount(['entreprises', 'reservations']);

        // Recherche par nom ou email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filtre par rôle
        if ($request->filled('role')) {
            $role = $request->role;
            if ($role === 'client') {
                $query->where('est_client', true);
            } elseif ($role === 'gerant') {
                $query->where('est_gerant', true);
            } elseif ($role === 'admin') {
                $query->where('is_admin', true);
            }
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    /**
     * Afficher un utilisateur
     */
    public function showUser(User $user)
    {
        $user->load(['entreprises', 'reservations.entreprise']);
        
        return view('admin.users.show', compact('user'));
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'est_client' => ['boolean'],
            'est_gerant' => ['boolean'],
            'is_admin' => ['boolean'],
        ]);

        $user->update($validated);

        return back()->with('success', 'Utilisateur mis à jour avec succès.');
    }

    /**
     * Liste des entreprises
     */
    public function entreprises(Request $request)
    {
        $query = Entreprise::with(['user'])
            ->withCount('reservations');

        // Recherche par nom, type, ville, email, téléphone
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('type_activite', 'like', "%{$search}%")
                  ->orWhere('ville', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('telephone', 'like', "%{$search}%")
                  ->orWhere('siren', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filtre par statut de vérification
        if ($request->filled('statut')) {
            if ($request->statut === 'verifiee') {
                $query->where('est_verifiee', true);
            } elseif ($request->statut === 'en_attente') {
                $query->where('est_verifiee', false);
            }
        }

        // Filtre par SIREN vérifié
        if ($request->filled('siren_verifie')) {
            $query->where('siren_verifie', $request->siren_verifie === '1');
        }

        $entreprises = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('admin.entreprises.index', compact('entreprises'));
    }

    /**
     * Afficher une entreprise
     */
    public function showEntreprise(Entreprise $entreprise)
    {
        $entreprise->load(['user', 'reservations.user']);
        
        return view('admin.entreprises.show', compact('entreprise'));
    }

    /**
     * Valider le nom de l'entreprise
     */
    public function validateNom(Request $request, Entreprise $entreprise)
    {
        $entreprise->update([
            'nom_valide' => true,
            'nom_refus_raison' => null,
        ]);

        return back()->with('success', 'Le nom a été validé.');
    }

    /**
     * Refuser le nom de l'entreprise
     */
    public function rejectNom(Request $request, Entreprise $entreprise)
    {
        $validated = $request->validate([
            'raison' => 'required|string|max:500',
        ]);

        $entreprise->update([
            'nom_valide' => false,
            'nom_refus_raison' => $validated['raison'],
            'est_verifiee' => false, // Si le nom est refusé, l'entreprise ne peut pas être vérifiée
        ]);

        return back()->with('success', 'Le nom a été refusé.');
    }

    /**
     * Valider le SIREN
     */
    public function validateSiren(Entreprise $entreprise)
    {
        if (empty($entreprise->siren)) {
            return back()->with('error', 'L\'entreprise n\'a pas de SIREN renseigné.');
        }

        $entreprise->update([
            'siren_valide' => true,
            'siren_refus_raison' => null,
            'siren_verifie' => true, // Compatibilité avec l'ancien système
        ]);

        return back()->with('success', 'Le SIREN a été validé.');
    }

    /**
     * Refuser le SIREN
     */
    public function rejectSiren(Request $request, Entreprise $entreprise)
    {
        $validated = $request->validate([
            'raison' => 'required|string|max:500',
        ]);

        $entreprise->update([
            'siren_valide' => false,
            'siren_refus_raison' => $validated['raison'],
            'siren_verifie' => false,
            'est_verifiee' => false, // Si le SIREN est refusé, l'entreprise ne peut pas être vérifiée
        ]);

        return back()->with('success', 'Le SIREN a été refusé.');
    }

    /**
     * Valider l'entreprise globalement (si tous les éléments sont validés)
     */
    public function validateEntreprise(Entreprise $entreprise)
    {
        // Recharger l'entreprise pour avoir les dernières valeurs
        $entreprise->refresh();
        
        if (!$entreprise->tousElementsValides()) {
            $errors = [];
            if ($entreprise->nom_valide !== true) {
                $errors[] = 'Le nom de l\'entreprise doit être validé.';
            }
            if ($entreprise->siren && !empty($entreprise->siren) && $entreprise->siren_valide !== true) {
                $errors[] = 'Le SIREN doit être validé si un SIREN est fourni.';
            }
            return back()->withErrors(['error' => implode(' ', $errors)]);
        }

        $entreprise->update([
            'est_verifiee' => true,
            'raison_refus_globale' => null,
        ]);

        return back()->with('success', 'L\'entreprise a été validée avec succès.');
    }

    /**
     * Refuser l'entreprise globalement
     */
    public function rejectEntreprise(Request $request, Entreprise $entreprise)
    {
        $validated = $request->validate([
            'raison' => 'required|string|max:1000',
        ]);

        $entreprise->update([
            'est_verifiee' => false,
            'raison_refus_globale' => $validated['raison'],
        ]);

        return back()->with('success', 'L\'entreprise a été refusée.');
    }

    /**
     * Renvoyer l'entreprise pour correction
     */
    public function renvoyerEntreprise(Entreprise $entreprise)
    {
        // Réinitialiser tous les statuts de vérification
        $entreprise->update([
            'nom_valide' => null,
            'nom_refus_raison' => null,
            'siren_valide' => null,
            'siren_refus_raison' => null,
            'raison_refus_globale' => null,
            'est_verifiee' => false,
            'siren_verifie' => false,
        ]);

        return back()->with('success', 'L\'entreprise a été renvoyée pour correction.');
    }

    /**
     * Vérifier une entreprise (ancienne méthode - gardée pour compatibilité)
     */
    public function verifyEntreprise(Entreprise $entreprise)
    {
        // Recharger l'entreprise pour avoir les dernières valeurs
        $entreprise->refresh();
        
        if (!$entreprise->tousElementsValides()) {
            $errors = [];
            if ($entreprise->nom_valide !== true) {
                $errors[] = 'Le nom de l\'entreprise doit être validé.';
            }
            if ($entreprise->siren && !empty($entreprise->siren) && $entreprise->siren_valide !== true) {
                $errors[] = 'Le SIREN doit être validé si un SIREN est fourni.';
            }
            return back()->withErrors(['error' => implode(' ', $errors)]);
        }

        $entreprise->update(['est_verifiee' => true]);
        $entreprise->refresh(); // Recharger pour vérifier que la mise à jour a fonctionné

        return back()->with('success', 'Entreprise vérifiée avec succès.');
    }

    /**
     * Désactiver une entreprise (ancienne méthode - gardée pour compatibilité)
     */
    public function unverifyEntreprise(Entreprise $entreprise)
    {
        $entreprise->update(['est_verifiee' => false]);

        return back()->with('success', 'Vérification de l\'entreprise retirée.');
    }

    /**
     * Liste des réservations
     */
    public function reservations(Request $request)
    {
        $query = Reservation::with(['user', 'entreprise']);

        // Recherche par nom client, nom entreprise, type service, lieu
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('type_service', 'like', "%{$search}%")
                  ->orWhere('lieu', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('entreprise', function($entrepriseQuery) use ($search) {
                      $entrepriseQuery->where('nom', 'like', "%{$search}%");
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

        $reservations = $query->orderBy('date_reservation', 'desc')->paginate(20)->withQueryString();

        return view('admin.reservations.index', compact('reservations'));
    }

    /**
     * Afficher une réservation
     */
    public function showReservation(Reservation $reservation)
    {
        $reservation->load(['user', 'entreprise']);
        
        return view('admin.reservations.show', compact('reservation'));
    }

    /**
     * Marquer une réservation comme payée
     */
    public function markReservationPaid(Reservation $reservation)
    {
        $reservation->update([
            'est_paye' => true,
            'date_paiement' => now(),
        ]);

        // Recharger la réservation pour avoir les dernières valeurs
        $reservation->refresh();
        
        // La facture sera générée automatiquement par l'observer ReservationObserver
        // Vérifier si une facture a été créée
        $factureGeneree = $reservation->facture;
        $message = 'Réservation marquée comme payée.';
        if ($factureGeneree) {
            $message .= ' Une facture a été générée automatiquement.';
        } else {
            // Si l'observer n'a pas fonctionné, essayer de générer la facture manuellement
            try {
                $facture = \App\Models\Facture::generateFromReservation($reservation);
                if ($facture) {
                    $message .= ' Une facture a été générée.';
                } else {
                    $message .= ' Attention : la facture n\'a pas pu être générée automatiquement.';
                }
            } catch (\Exception $e) {
                \Log::error('Erreur lors de la génération manuelle de la facture : ' . $e->getMessage());
                $message .= ' Erreur lors de la génération de la facture.';
            }
        }

        return back()->with('success', $message);
    }

    /**
     * Vérifier le SIREN d'une entreprise
     */
    public function verifySiren(Entreprise $entreprise)
    {
        if (empty($entreprise->siren)) {
            return back()->with('error', 'L\'entreprise n\'a pas de SIREN renseigné.');
        }

        // Vérification basique du format SIREN (9 chiffres)
        if (!preg_match('/^[0-9]{9}$/', $entreprise->siren)) {
            return back()->with('error', 'Le format du SIREN est invalide (doit contenir 9 chiffres).');
        }

        // TODO: Intégrer une API de vérification SIREN (ex: API Entreprise, Sirene API)
        // Pour l'instant, on fait une vérification manuelle
        // L'administrateur peut vérifier manuellement et marquer comme vérifié
        
        $entreprise->update(['siren_verifie' => true]);

        return back()->with('success', 'Le SIREN a été vérifié et marqué comme valide.');
    }

    /**
     * Retirer la vérification du SIREN
     */
    public function unverifySiren(Entreprise $entreprise)
    {
        $entreprise->update(['siren_verifie' => false]);

        return back()->with('success', 'La vérification du SIREN a été retirée.');
    }

    /**
     * Gérer l'abonnement manuel d'un utilisateur
     */
    public function showSubscription(User $user)
    {
        return redirect()->route('admin.users.show', ['user' => $user, 'tab' => 'subscription']);
    }

    /**
     * Activer un abonnement manuel
     */
    public function toggleManualSubscription(Request $request, User $user)
    {
        // Vérifier si l'utilisateur a un abonnement Stripe actif
        $subscription = $user->subscription('default');
        if ($subscription && $subscription->valid() && !$subscription->onGracePeriod()) {
            return back()->with('error', 'Impossible d\'activer un abonnement manuel : l\'utilisateur a un abonnement Stripe actif. Vous pouvez uniquement annuler l\'abonnement Stripe depuis la page de l\'utilisateur.');
        }

        if ($request->has('activer')) {
            $validated = $request->validate([
                'date_fin' => 'required|date|after:today',
                'notes' => 'nullable|string|max:500',
                'type_renouvellement' => 'required|in:mensuel,annuel',
                'jour_renouvellement' => 'required|integer|min:1|max:31',
                'date_debut' => 'required|date|before_or_equal:date_fin',
                'montant' => 'required|numeric|min:0.01',
            ]);

            // Calculer la date de fin basée sur le renouvellement si nécessaire
            $dateDebut = \Carbon\Carbon::parse($validated['date_debut']);
            $dateFin = \Carbon\Carbon::parse($validated['date_fin']);
            
            // Si la date de fin n'est pas cohérente avec le type de renouvellement, on la recalcule
            if ($validated['type_renouvellement'] === 'mensuel') {
                // Pour mensuel, on peut ajuster la date de fin pour qu'elle corresponde à un mois complet
                // Mais on garde la date fournie par l'admin
            } elseif ($validated['type_renouvellement'] === 'annuel') {
                // Pour annuel, on peut ajuster la date de fin pour qu'elle corresponde à une année complète
            }

            $user->update([
                'abonnement_manuel' => true,
                'abonnement_manuel_actif_jusqu' => $validated['date_fin'],
                'abonnement_manuel_notes' => $validated['notes'] ?? null,
                'abonnement_manuel_type_renouvellement' => $validated['type_renouvellement'],
                'abonnement_manuel_jour_renouvellement' => $validated['jour_renouvellement'],
                'abonnement_manuel_date_debut' => $validated['date_debut'],
                'abonnement_manuel_montant' => $validated['montant'],
            ]);

            // Générer la première facture si la date de début est aujourd'hui ou dans le passé
            if ($dateDebut->isToday() || $dateDebut->isPast()) {
                try {
                    \App\Models\Facture::generateFromManualSubscription($user);
                } catch (\Exception $e) {
                    \Log::error('Erreur lors de la génération de la première facture d\'abonnement manuel: ' . $e->getMessage());
                }
            }

            return back()->with('success', 'Abonnement manuel activé. Type: ' . ($validated['type_renouvellement'] === 'mensuel' ? 'Mensuel' : 'Annuel') . ', renouvellement le ' . $validated['jour_renouvellement'] . ' de chaque ' . ($validated['type_renouvellement'] === 'mensuel' ? 'mois' : 'année') . '.');
        } else {
            $user->update([
                'abonnement_manuel' => false,
                'abonnement_manuel_actif_jusqu' => null,
                'abonnement_manuel_notes' => null,
                'abonnement_manuel_type_renouvellement' => null,
                'abonnement_manuel_jour_renouvellement' => null,
                'abonnement_manuel_date_debut' => null,
                'abonnement_manuel_montant' => null,
            ]);

            return back()->with('success', 'Abonnement manuel désactivé.');
        }
    }

    /**
     * Activer un abonnement manuel pour une entreprise
     */
    public function activateEntrepriseSubscription(Request $request, Entreprise $entreprise)
    {
        $validated = $request->validate([
            'type' => 'required|in:site_web,multi_personnes',
            'date_fin' => 'required|date|after:today',
            'notes' => 'nullable|string|max:500',
            'type_renouvellement' => 'required|in:mensuel,annuel',
            'jour_renouvellement' => 'required|integer|min:1|max:31',
            'date_debut' => 'required|date|before_or_equal:date_fin',
            'montant' => 'required|numeric|min:0.01',
        ]);

        // Vérifier si l'entreprise a déjà un abonnement Stripe actif de ce type
        $existingSubscription = EntrepriseSubscription::where('entreprise_id', $entreprise->id)
            ->where('type', $validated['type'])
            ->where('est_manuel', false)
            ->whereIn('stripe_status', ['active', 'trialing'])
            ->first();

        if ($existingSubscription) {
            return back()->with('error', 'L\'entreprise a déjà un abonnement Stripe actif pour ce type. Vous devez d\'abord annuler l\'abonnement Stripe.');
        }

        // Créer ou mettre à jour l'abonnement manuel
        $subscription = EntrepriseSubscription::updateOrCreate(
            [
                'entreprise_id' => $entreprise->id,
                'type' => $validated['type'],
            ],
            [
                'name' => 'Abonnement manuel ' . $validated['type'],
                'est_manuel' => true,
                'actif_jusqu' => $validated['date_fin'],
                'notes_manuel' => $validated['notes'] ?? null,
                'type_renouvellement' => $validated['type_renouvellement'],
                'jour_renouvellement' => $validated['jour_renouvellement'],
                'date_debut' => $validated['date_debut'],
                'montant' => $validated['montant'],
            ]
        );

        // Générer la première facture si la date de début est aujourd'hui ou dans le passé
        $dateDebut = \Carbon\Carbon::parse($validated['date_debut']);
        if ($dateDebut->isToday() || $dateDebut->isPast()) {
            try {
                \App\Models\Facture::generateFromManualEntrepriseSubscription($subscription);
            } catch (\Exception $e) {
                \Log::error('Erreur lors de la génération de la première facture d\'abonnement manuel entreprise: ' . $e->getMessage());
            }
        }

        $typeLabel = $validated['type'] === 'site_web' ? 'Site Web Vitrine' : 'Gestion Multi-Personnes';
        
        return back()->with('success', "Abonnement manuel {$typeLabel} activé. Type: " . ($validated['type_renouvellement'] === 'mensuel' ? 'Mensuel' : 'Annuel') . ', renouvellement le ' . $validated['jour_renouvellement'] . ' de chaque ' . ($validated['type_renouvellement'] === 'mensuel' ? 'mois' : 'année') . '.');
    }

    /**
     * Afficher la page de gestion d'abonnement manuel pour une entreprise
     */
    public function showEntrepriseSubscription(Entreprise $entreprise)
    {
        $entreprise->load('user', 'abonnements');
        return view('admin.entreprises.subscription', compact('entreprise'));
    }

    /**
     * Annuler l'abonnement Stripe d'un utilisateur (admin uniquement)
     */
    public function cancelStripeSubscription(User $user)
    {
        $subscription = $user->subscription('default');
        
        if (!$subscription || !$subscription->valid()) {
            return back()->with('error', 'Aucun abonnement Stripe actif trouvé.');
        }

        if ($subscription->onGracePeriod()) {
            return back()->with('error', 'L\'abonnement Stripe est déjà annulé.');
        }

        $subscription->cancel();
        
        return back()->with('success', 'L\'abonnement Stripe a été annulé. Il restera actif jusqu\'à la fin de la période payée.');
    }

    /**
     * Afficher la page de gestion des options d'entreprise
     */
    public function optionsEntreprise(Entreprise $entreprise)
    {
        $abonnementSiteWeb = $entreprise->abonnementSiteWeb();
        $abonnementMultiPersonnes = $entreprise->abonnementMultiPersonnes();
        // Charger tous les membres (actifs et inactifs) pour l'admin
        $membres = $entreprise->tousMembres()->with('user')->get();

        return view('admin.entreprises.options', [
            'entreprise' => $entreprise,
            'abonnementSiteWeb' => $abonnementSiteWeb,
            'abonnementMultiPersonnes' => $abonnementMultiPersonnes,
            'membres' => $membres,
        ]);
    }

    /**
     * Activer manuellement une option pour une entreprise
     */
    public function activerOptionEntreprise(Request $request, Entreprise $entreprise)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:site_web,multi_personnes'],
            'date_fin' => ['required', 'date', 'after:today'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // Créer ou mettre à jour l'abonnement manuel
        $abonnement = \App\Models\EntrepriseSubscription::updateOrCreate(
            [
                'entreprise_id' => $entreprise->id,
                'type' => $validated['type'],
            ],
            [
                'name' => 'manuel_' . $validated['type'],
                'est_manuel' => true,
                'actif_jusqu' => $validated['date_fin'],
                'notes_manuel' => $validated['notes'] ?? null,
                'stripe_id' => null,
                'stripe_status' => null,
                'stripe_price' => null,
            ]
        );

        return back()->with('success', 'L\'option ' . ($validated['type'] === 'site_web' ? 'Site Web Vitrine' : 'Gestion Multi-Personnes') . ' a été activée jusqu\'au ' . \Carbon\Carbon::parse($validated['date_fin'])->format('d/m/Y') . '.');
    }

    /**
     * Désactiver une option d'entreprise
     */
    public function desactiverOptionEntreprise(Request $request, Entreprise $entreprise, $type)
    {
        $abonnement = $entreprise->abonnements()->where('type', $type)->first();

        if (!$abonnement) {
            return back()->with('error', 'Option introuvable.');
        }

        // Si c'est un abonnement manuel, on le supprime ou on le désactive
        if ($abonnement->est_manuel) {
            $abonnement->update([
                'actif_jusqu' => now()->subDay(), // Désactiver immédiatement
            ]);
        } else {
            // Si c'est un abonnement Stripe, on ne peut que le marquer comme terminé
            // L'utilisateur devra l'annuler depuis son compte
            return back()->with('error', 'Cet abonnement est géré via Stripe. L\'utilisateur doit l\'annuler depuis son compte.');
        }

        return back()->with('success', 'L\'option a été désactivée.');
    }

    /**
     * Ajouter un membre administrateur à une entreprise (admin uniquement)
     */
    public function ajouterMembreEntreprise(Request $request, Entreprise $entreprise)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', 'in:administrateur,membre'],
        ]);

        // Vérifier que l'email n'est pas celui du propriétaire
        if ($entreprise->email === $validated['email'] || $entreprise->user->email === $validated['email']) {
            return back()->withErrors(['error' => 'Le propriétaire de l\'entreprise est automatiquement administrateur.']);
        }

        // Vérifier qu'il n'y a pas déjà une invitation en attente pour cet email
        $invitationExistante = \App\Models\EntrepriseInvitation::where('entreprise_id', $entreprise->id)
            ->where('email', $validated['email'])
            ->whereIn('statut', ['en_attente_compte', 'en_attente_acceptation'])
            ->first();

        if ($invitationExistante) {
            return back()->withErrors(['error' => 'Une invitation est déjà en cours pour cet email.']);
        }

        $invitationService = app(\App\Services\InvitationService::class);

        // Chercher l'utilisateur par email
        $userInvite = User::where('email', $validated['email'])->first();

        if ($userInvite) {
            // Utilisateur existe déjà
            // Vérifier qu'il n'est pas déjà membre actif
            $membreExistant = \App\Models\EntrepriseMembre::where('entreprise_id', $entreprise->id)
                ->where('user_id', $userInvite->id)
                ->where('est_actif', true)
                ->first();

            if ($membreExistant) {
                return back()->withErrors(['error' => 'Cet utilisateur est déjà membre de cette entreprise.']);
            }

            // Créer une invitation pour utilisateur existant
            $invitation = $invitationService->creerInvitationPourUtilisateurExistant(
                $entreprise,
                $userInvite,
                $validated['role'],
                $user
            );

            // Envoyer l'email d'invitation
            $invitationService->envoyerEmailInvitation($invitation);

            return back()->with('success', 'Une invitation a été envoyée à ' . $validated['email'] . '.');
        } else {
            // Utilisateur n'existe pas, créer une invitation en attente de compte
            $invitation = $invitationService->creerInvitation(
                $entreprise,
                $validated['email'],
                $validated['role'],
                $user
            );

            // Envoyer l'email d'invitation pour créer un compte
            $invitationService->envoyerEmailInvitation($invitation);

            return back()->with('success', 'Une invitation a été envoyée à ' . $validated['email'] . '. L\'utilisateur devra créer un compte pour accepter.');
        }
    }

    /**
     * Mettre à jour le rôle d'un membre (admin uniquement)
     */
    public function mettreAJourRoleMembre(Request $request, Entreprise $entreprise, EntrepriseMembre $membre)
    {
        $validated = $request->validate([
            'role' => ['required', 'in:administrateur,membre'],
        ]);

        // Vérifier que le membre appartient à cette entreprise
        if ($membre->entreprise_id !== $entreprise->id) {
            return back()->withErrors(['error' => 'Membre introuvable.']);
        }

        // Ne pas permettre de modifier le propriétaire
        if ($membre->user_id === $entreprise->user_id) {
            return back()->withErrors(['error' => 'Le propriétaire de l\'entreprise ne peut pas être modifié.']);
        }

        $membre->update([
            'role' => $validated['role'],
        ]);

        return back()->with('success', 'Le rôle du membre a été mis à jour.');
    }

    /**
     * Supprimer un membre (admin uniquement)
     */
    public function supprimerMembreEntreprise(Entreprise $entreprise, EntrepriseMembre $membre)
    {
        // Vérifier que le membre appartient à cette entreprise
        if ($membre->entreprise_id !== $entreprise->id) {
            return back()->withErrors(['error' => 'Membre introuvable.']);
        }

        // Ne pas permettre de supprimer le propriétaire
        if ($membre->user_id === $entreprise->user_id) {
            return back()->withErrors(['error' => 'Le propriétaire de l\'entreprise ne peut pas être supprimé.']);
        }

        // Désactiver le membre
        $membre->update([
            'est_actif' => false,
        ]);

        return back()->with('success', 'Le membre a été retiré de l\'entreprise.');
    }

    /**
     * Afficher la page de gestion des prix Stripe
     */
    public function stripePrices()
    {
        // Initialiser Stripe
        Stripe::setApiKey(config('services.stripe.secret'));

        // Récupérer les prix configurés
        $prices = [
            'default' => [
                'id' => config('services.stripe.price_id'),
                'label' => 'Abonnement utilisateur (15€/mois)',
                'type' => 'default',
            ],
            'site_web' => [
                'id' => config('services.stripe.price_id_site_web'),
                'label' => 'Site Web Vitrine (2€/mois)',
                'type' => 'site_web',
            ],
            'multi_personnes' => [
                'id' => config('services.stripe.price_id_multi_personnes'),
                'label' => 'Gestion Multi-Personnes (20€/mois)',
                'type' => 'multi_personnes',
            ],
        ];

        // Récupérer les détails depuis Stripe si les prix existent
        foreach ($prices as $key => &$price) {
            if ($price['id']) {
                try {
                    $stripePrice = Price::retrieve($price['id']);
                    $price['stripe_data'] = [
                        'id' => $stripePrice->id,
                        'amount' => $stripePrice->unit_amount / 100, // Convertir centimes en euros
                        'currency' => $stripePrice->currency,
                        'recurring' => $stripePrice->recurring ? [
                            'interval' => $stripePrice->recurring->interval,
                            'interval_count' => $stripePrice->recurring->interval_count,
                        ] : null,
                        'active' => $stripePrice->active,
                        'product' => $stripePrice->product,
                    ];
                } catch (\Exception $e) {
                    Log::error('Erreur lors de la récupération du prix Stripe: ' . $e->getMessage());
                    $price['error'] = 'Prix introuvable sur Stripe';
                }
            }
        }

        return view('admin.stripe-prices', [
            'prices' => $prices,
        ]);
    }

    /**
     * Créer un nouveau prix Stripe
     */
    public function createStripePrice(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:default,site_web,multi_personnes',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'interval' => 'required|in:day,week,month,year',
            'product_name' => 'required|string|max:255',
            'product_description' => 'nullable|string',
        ]);

        // Initialiser Stripe
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            // Créer ou récupérer le produit
            $productName = $validated['product_name'];
            $products = Product::all(['limit' => 100]);
            $product = null;

            foreach ($products->data as $p) {
                if ($p->name === $productName) {
                    $product = $p;
                    break;
                }
            }

            if (!$product) {
                $product = Product::create([
                    'name' => $productName,
                    'description' => $validated['product_description'] ?? '',
                ]);
            }

            // Créer le prix
            // Utiliser round() pour éviter les problèmes d'arrondi avec les floats
            $unitAmount = (int)round($validated['amount'] * 100, 0);
            
            $price = Price::create([
                'product' => $product->id,
                'unit_amount' => $unitAmount,
                'currency' => strtolower($validated['currency']),
                'recurring' => [
                    'interval' => $validated['interval'],
                ],
            ]);

            // Mettre à jour le fichier .env ou la configuration
            $envKey = match($validated['type']) {
                'default' => 'STRIPE_PRICE_ID',
                'site_web' => 'STRIPE_PRICE_ID_SITE_WEB',
                'multi_personnes' => 'STRIPE_PRICE_ID_MULTI_PERSONNES',
            };

            // Mettre à jour le fichier .env
            $this->updateEnvFile($envKey, $price->id);

            // Mettre à jour la configuration en cache
            if ($validated['type'] === 'default') {
                config(['services.stripe.price_id' => $price->id]);
            } else {
                config(["services.stripe.price_id_{$validated['type']}" => $price->id]);
            }

            return back()->with('success', "Prix créé avec succès ! ID: {$price->id}. Le fichier .env a été mis à jour.");

        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du prix Stripe: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Erreur lors de la création du prix: ' . $e->getMessage()]);
        }
    }

    /**
     * Modifier un prix Stripe (créer un nouveau prix et désactiver l'ancien)
     */
    public function updateStripePrice(Request $request, $type)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'interval' => 'required|in:day,week,month,year',
            'product_name' => 'required|string|max:255',
            'product_description' => 'nullable|string',
        ]);

        // Initialiser Stripe
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            // Récupérer l'ancien prix
            $envKey = match($type) {
                'default' => 'STRIPE_PRICE_ID',
                'site_web' => 'STRIPE_PRICE_ID_SITE_WEB',
                'multi_personnes' => 'STRIPE_PRICE_ID_MULTI_PERSONNES',
            };

            $oldPriceId = config("services.stripe.price_id_{$type}");
            if ($type === 'default') {
                $oldPriceId = config('services.stripe.price_id');
            }

            // Désactiver l'ancien prix s'il existe
            if ($oldPriceId) {
                try {
                    $oldPrice = Price::retrieve($oldPriceId);
                    $oldPrice->active = false;
                    $oldPrice->save();
                } catch (\Exception $e) {
                    Log::warning('Impossible de désactiver l\'ancien prix: ' . $e->getMessage());
                }
            }

            // Créer ou récupérer le produit
            $productName = $validated['product_name'];
            $products = Product::all(['limit' => 100]);
            $product = null;

            foreach ($products->data as $p) {
                if ($p->name === $productName) {
                    $product = $p;
                    break;
                }
            }

            if (!$product) {
                $product = Product::create([
                    'name' => $productName,
                    'description' => $validated['product_description'] ?? '',
                ]);
            }

            // Créer le nouveau prix
            $price = Price::create([
                'product' => $product->id,
                'unit_amount' => (int)($validated['amount'] * 100), // Convertir en centimes
                'currency' => strtolower($validated['currency']),
                'recurring' => [
                    'interval' => $validated['interval'],
                ],
            ]);

            // Mettre à jour le fichier .env
            $this->updateEnvFile($envKey, $price->id);

            // Mettre à jour la configuration en cache
            if ($type === 'default') {
                config(['services.stripe.price_id' => $price->id]);
            } else {
                config(["services.stripe.price_id_{$type}" => $price->id]);
            }

            // Vider le cache de configuration pour que les changements soient pris en compte immédiatement
            \Artisan::call('config:clear');

            return back()->with('success', "Prix modifié avec succès ! Nouveau prix ID: {$price->id}. L'ancien prix a été désactivé.");

        } catch (\Exception $e) {
            Log::error('Erreur lors de la modification du prix Stripe: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Erreur lors de la modification du prix: ' . $e->getMessage()]);
        }
    }

    /**
     * Créer un prix manquant pour un type d'abonnement
     */
    public function createMissingPrice(Request $request, $type)
    {
        // Valeurs par défaut selon le type
        $defaults = [
            'default' => [
                'amount' => 15.00,
                'label' => 'Abonnement utilisateur',
            ],
            'site_web' => [
                'amount' => 2.00,
                'label' => 'Site Web Vitrine',
            ],
            'multi_personnes' => [
                'amount' => 20.00,
                'label' => 'Gestion Multi-Personnes',
            ],
        ];

        $default = $defaults[$type] ?? null;
        if (!$default) {
            return back()->withErrors(['error' => 'Type d\'abonnement invalide.']);
        }

        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'interval' => 'nullable|in:day,week,month,year',
            'product_name' => 'nullable|string|max:255',
            'product_description' => 'nullable|string',
        ]);

        // Utiliser les valeurs par défaut si non fournies
        $amount = $validated['amount'] ?? $default['amount'];
        $currency = $validated['currency'] ?? 'eur';
        $interval = $validated['interval'] ?? 'month';
        $productName = $validated['product_name'] ?? $default['label'];
        $productDescription = $validated['product_description'] ?? '';

        // Initialiser Stripe
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            // Créer ou récupérer le produit
            $products = Product::all(['limit' => 100]);
            $product = null;

            foreach ($products->data as $p) {
                if ($p->name === $productName) {
                    $product = $p;
                    break;
                }
            }

            if (!$product) {
                $product = Product::create([
                    'name' => $productName,
                    'description' => $productDescription,
                ]);
            }

            // Créer le prix
            // Utiliser round() pour éviter les problèmes d'arrondi avec les floats
            $unitAmount = (int)round($amount * 100, 0);
            
            $price = Price::create([
                'product' => $product->id,
                'unit_amount' => $unitAmount,
                'currency' => strtolower($currency),
                'recurring' => [
                    'interval' => $interval,
                ],
            ]);

            // Mettre à jour le fichier .env
            $envKey = match($type) {
                'default' => 'STRIPE_PRICE_ID',
                'site_web' => 'STRIPE_PRICE_ID_SITE_WEB',
                'multi_personnes' => 'STRIPE_PRICE_ID_MULTI_PERSONNES',
            };

            $this->updateEnvFile($envKey, $price->id);

            // Mettre à jour la configuration en cache
            if ($type === 'default') {
                config(['services.stripe.price_id' => $price->id]);
            } else {
                config(["services.stripe.price_id_{$type}" => $price->id]);
            }

            return back()->with('success', "Prix créé avec succès ! ID: {$price->id}. Le fichier .env a été mis à jour.");

        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du prix Stripe: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Erreur lors de la création du prix: ' . $e->getMessage()]);
        }
    }

    /**
     * Mettre à jour le fichier .env
     */
    private function updateEnvFile($key, $value)
    {
        $envFile = base_path('.env');
        
        if (!file_exists($envFile)) {
            Log::warning('Fichier .env introuvable');
            return false;
        }

        $envContent = file_get_contents($envFile);
        
        // Vérifier si la clé existe déjà
        if (preg_match("/^{$key}=.*/m", $envContent)) {
            // Remplacer la valeur existante
            $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
        } else {
            // Ajouter la nouvelle clé
            $envContent .= "\n{$key}={$value}\n";
        }

        file_put_contents($envFile, $envContent);
        
        return true;
    }

    /**
     * Afficher la page de gestion des prix personnalisés
     */
    public function customPrices()
    {
        // Récupérer tous les prix personnalisés avec leurs relations
        $customPrices = CustomPrice::with(['user', 'entreprise', 'creator'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Récupérer les utilisateurs et entreprises pour les formulaires
        $users = User::where('est_gerant', true)->orderBy('name')->get();
        $entreprises = Entreprise::orderBy('nom')->get();

        return view('admin.custom-prices', [
            'customPrices' => $customPrices,
            'users' => $users,
            'entreprises' => $entreprises,
        ]);
    }

    /**
     * Créer un prix personnalisé pour un utilisateur ou une entreprise
     */
    public function createCustomPrice(Request $request)
    {
        $validated = $request->validate([
            'target_type' => 'required|in:user,entreprise',
            'user_id' => 'required_if:target_type,user|nullable|exists:users,id',
            'entreprise_id' => 'required_if:target_type,entreprise|nullable|exists:entreprises,id',
            'subscription_type' => 'required|in:default,site_web,multi_personnes',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'interval' => 'required|in:day,week,month,year',
            'product_name' => 'required|string|max:255',
            'product_description' => 'nullable|string',
            'notes' => 'nullable|string',
            'expires_at' => 'nullable|date|after:today',
        ]);

        // Initialiser Stripe
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            // Créer ou récupérer le produit
            $productName = $validated['product_name'];
            $products = Product::all(['limit' => 100]);
            $product = null;

            foreach ($products->data as $p) {
                if ($p->name === $productName) {
                    $product = $p;
                    break;
                }
            }

            if (!$product) {
                $product = Product::create([
                    'name' => $productName,
                    'description' => $validated['product_description'] ?? '',
                ]);
            }

            // Créer le prix Stripe
            // Utiliser round() pour éviter les problèmes d'arrondi avec les floats
            // Ex: 19.99 * 100 peut donner 1998.9999999999998, round() corrige cela
            $unitAmount = (int)round($validated['amount'] * 100, 0);
            
            // Vérification de la conversion pour le debug
            $amountEntered = $validated['amount'];
            $amountInCents = $amountEntered * 100;
            $roundedAmount = round($amountInCents, 0);
            $finalUnitAmount = (int)$roundedAmount;
            
            Log::info('Création prix personnalisé - Conversion', [
                'montant_saisi' => $amountEntered,
                'montant_en_centimes_brut' => $amountInCents,
                'montant_arrondi' => $roundedAmount,
                'montant_final_stripe' => $finalUnitAmount,
                'montant_final_euros' => $finalUnitAmount / 100,
            ]);
            
            $price = Price::create([
                'product' => $product->id,
                'unit_amount' => $finalUnitAmount,
                'currency' => strtolower($validated['currency']),
                'recurring' => [
                    'interval' => $validated['interval'],
                ],
            ]);

            // Vérifier que le prix créé correspond bien
            $priceRetrieved = Price::retrieve($price->id);
            Log::info('Prix Stripe créé - Vérification', [
                'stripe_price_id' => $price->id,
                'unit_amount_stripe' => $priceRetrieved->unit_amount,
                'unit_amount_euros' => $priceRetrieved->unit_amount / 100,
                'montant_attendu_euros' => $amountEntered,
            ]);

            // Créer l'entrée dans custom_prices
            $customPrice = CustomPrice::create([
                'user_id' => $validated['target_type'] === 'user' ? $validated['user_id'] : null,
                'entreprise_id' => $validated['target_type'] === 'entreprise' ? $validated['entreprise_id'] : null,
                'subscription_type' => $validated['subscription_type'],
                'stripe_price_id' => $price->id,
                'amount' => $validated['amount'],
                'currency' => strtolower($validated['currency']),
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
                'expires_at' => $validated['expires_at'] ? \Carbon\Carbon::parse($validated['expires_at']) : null,
                'is_active' => true,
            ]);

            Log::info('Prix personnalisé créé', [
                'custom_price_id' => $customPrice->id,
                'stripe_price_id' => $price->id,
                'target_type' => $validated['target_type'],
                'user_id' => $customPrice->user_id,
                'entreprise_id' => $customPrice->entreprise_id,
                'montant_saisi' => $amountEntered,
                'montant_stripe' => $priceRetrieved->unit_amount / 100,
            ]);

            // Vérifier que le prix créé correspond exactement au montant saisi
            $priceInEuros = $priceRetrieved->unit_amount / 100;
            $difference = abs($priceInEuros - $amountEntered);
            
            if ($difference > 0.001) { // Tolérance de 0.001€ pour les erreurs d'arrondi
                Log::warning('Écart détecté entre le prix saisi et le prix Stripe', [
                    'prix_saisi' => $amountEntered,
                    'prix_stripe' => $priceInEuros,
                    'difference' => $difference,
                ]);
                
                return back()->withErrors([
                    'error' => "Attention : Le prix créé sur Stripe ({$priceInEuros}€) ne correspond pas exactement au prix saisi ({$amountEntered}€). Différence : " . number_format($difference, 2, ',', ' ') . "€. Veuillez vérifier le prix sur Stripe."
                ]);
            }

            return back()->with('success', "Prix personnalisé créé avec succès ! ID Stripe: {$price->id}. Montant: {$priceInEuros}€/mois");

        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du prix personnalisé: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Erreur lors de la création du prix personnalisé: ' . $e->getMessage()]);
        }
    }

    /**
     * Désactiver/Activer un prix personnalisé
     */
    public function toggleCustomPrice(CustomPrice $customPrice)
    {
        $customPrice->update([
            'is_active' => !$customPrice->is_active,
        ]);

        return back()->with('success', $customPrice->is_active ? 'Prix personnalisé activé.' : 'Prix personnalisé désactivé.');
    }

    /**
     * Supprimer un prix personnalisé
     */
    public function deleteCustomPrice(CustomPrice $customPrice)
    {
        // Note : On ne supprime pas le prix Stripe, juste l'entrée locale
        // Le prix Stripe peut être désactivé si nécessaire
        $customPrice->delete();

            return back()->with('success', 'Prix personnalisé supprimé.');
    }

    /**
     * Afficher tous les abonnements en cours
     */
    public function subscriptions(Request $request)
    {
        // Récupérer les abonnements utilisateurs (Stripe)
        $userSubscriptions = Subscription::with('user')
            ->whereIn('stripe_status', ['active', 'trialing', 'past_due'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Récupérer les abonnements d'entreprise (Stripe)
        $entrepriseSubscriptions = EntrepriseSubscription::with('entreprise')
            ->where('est_manuel', false)
            ->whereIn('stripe_status', ['active', 'trialing', 'past_due'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Récupérer les abonnements manuels utilisateurs
        $manualUserSubscriptions = User::where('abonnement_manuel', true)
            ->whereNotNull('abonnement_manuel_actif_jusqu')
            ->where('abonnement_manuel_actif_jusqu', '>=', now())
            ->orderBy('abonnement_manuel_actif_jusqu', 'desc')
            ->get();

        // Récupérer les abonnements manuels entreprises
        $manualEntrepriseSubscriptions = EntrepriseSubscription::with('entreprise')
            ->where('est_manuel', true)
            ->whereNotNull('actif_jusqu')
            ->where('actif_jusqu', '>=', now())
            ->orderBy('actif_jusqu', 'desc')
            ->get();

        // Filtrer par type si demandé
        $filter = $request->get('filter', 'all');
        if ($filter === 'users') {
            $entrepriseSubscriptions = collect();
            $manualEntrepriseSubscriptions = collect();
        } elseif ($filter === 'entreprises') {
            $userSubscriptions = collect();
            $manualUserSubscriptions = collect();
        } elseif ($filter === 'stripe') {
            $manualUserSubscriptions = collect();
            $manualEntrepriseSubscriptions = collect();
        } elseif ($filter === 'manual') {
            $userSubscriptions = collect();
            $entrepriseSubscriptions = collect();
        }

        return view('admin.subscriptions.index', [
            'userSubscriptions' => $userSubscriptions,
            'entrepriseSubscriptions' => $entrepriseSubscriptions,
            'manualUserSubscriptions' => $manualUserSubscriptions,
            'manualEntrepriseSubscriptions' => $manualEntrepriseSubscriptions,
            'filter' => $filter,
        ]);
    }

    /**
     * Synchroniser tous les abonnements depuis Stripe
     */
    public function syncSubscriptions()
    {
        try {
            // Exécuter la commande Artisan de synchronisation
            $exitCode = \Artisan::call('stripe:sync-subscriptions', ['--from-stripe' => true]);
            $output = \Artisan::output();
            
            Log::info('Synchronisation Stripe lancée depuis l\'admin', [
                'exit_code' => $exitCode,
                'output_length' => strlen($output),
            ]);
            
            if ($exitCode === 0) {
                return back()->with('sync_success', 'Synchronisation terminée avec succès ! Tous les abonnements Stripe ont été mis à jour.');
            } else {
                return back()->withErrors(['error' => 'La synchronisation a rencontré des erreurs. Consultez les logs pour plus de détails.']);
            }
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la synchronisation Stripe depuis l\'admin', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return back()->withErrors(['error' => 'Erreur lors de la synchronisation: ' . $e->getMessage()]);
        }
    }

    /**
     * Synchroniser un abonnement utilisateur individuel depuis Stripe
     */
    public function syncUserSubscription(Subscription $subscription)
    {
        if (!$subscription->stripe_id) {
            return back()->withErrors(['error' => 'Cet abonnement n\'a pas d\'ID Stripe.']);
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            
            // Récupérer l'abonnement depuis Stripe
            $stripeSubscription = \Stripe\Subscription::retrieve($subscription->stripe_id);
            
            // Calculer ends_at
            $endsAt = null;
            if ($stripeSubscription->status === 'canceled' && $stripeSubscription->ended_at) {
                $endsAt = \Carbon\Carbon::createFromTimestamp($stripeSubscription->ended_at);
            } elseif ($stripeSubscription->cancel_at_period_end && $stripeSubscription->current_period_end) {
                $endsAt = \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end);
            } elseif ($stripeSubscription->cancel_at) {
                $endsAt = \Carbon\Carbon::createFromTimestamp($stripeSubscription->cancel_at);
            }
            
            $oldStatus = $subscription->stripe_status;
            $newStatus = $stripeSubscription->status;
            
            // Mettre à jour l'abonnement local
            $subscription->update([
                'stripe_status' => $newStatus,
                'stripe_price' => $stripeSubscription->items->data[0]->price->id ?? $subscription->stripe_price,
                'ends_at' => $endsAt,
            ]);
            
            Log::info('Abonnement utilisateur synchronisé depuis Stripe', [
                'subscription_id' => $subscription->id,
                'stripe_id' => $subscription->stripe_id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);
            
            if ($oldStatus !== $newStatus) {
                return back()->with('success', "Abonnement synchronisé : statut mis à jour de \"{$oldStatus}\" vers \"{$newStatus}\".");
            } else {
                return back()->with('success', 'Abonnement synchronisé : déjà à jour.');
            }
            
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // L'abonnement n'existe plus sur Stripe
            $subscription->update([
                'stripe_status' => 'canceled',
                'ends_at' => now(),
            ]);
            
            return back()->with('success', 'Abonnement synchronisé : n\'existe plus sur Stripe, marqué comme annulé.');
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la synchronisation de l\'abonnement utilisateur', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors(['error' => 'Erreur lors de la synchronisation: ' . $e->getMessage()]);
        }
    }

    /**
     * Synchroniser un abonnement entreprise individuel depuis Stripe
     */
    public function syncEntrepriseSubscription(EntrepriseSubscription $subscription)
    {
        if (!$subscription->stripe_id) {
            return back()->withErrors(['error' => 'Cet abonnement n\'a pas d\'ID Stripe.']);
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            
            // Récupérer l'abonnement depuis Stripe
            $stripeSubscription = \Stripe\Subscription::retrieve($subscription->stripe_id);
            
            // Calculer ends_at
            $endsAt = null;
            if ($stripeSubscription->status === 'canceled' && $stripeSubscription->ended_at) {
                $endsAt = \Carbon\Carbon::createFromTimestamp($stripeSubscription->ended_at);
            } elseif ($stripeSubscription->cancel_at_period_end && $stripeSubscription->current_period_end) {
                $endsAt = \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end);
            } elseif ($stripeSubscription->cancel_at) {
                $endsAt = \Carbon\Carbon::createFromTimestamp($stripeSubscription->cancel_at);
            }
            
            $oldStatus = $subscription->stripe_status;
            $newStatus = $stripeSubscription->status;
            
            // Mettre à jour l'abonnement local
            $subscription->update([
                'stripe_status' => $newStatus,
                'stripe_price' => $stripeSubscription->items->data[0]->price->id ?? $subscription->stripe_price,
                'ends_at' => $endsAt,
            ]);
            
            // Mettre à jour aussi dans la table subscriptions de Cashier si existe
            $cashierSubscription = Subscription::where('stripe_id', $subscription->stripe_id)->first();
            if ($cashierSubscription) {
                $cashierSubscription->update([
                    'stripe_status' => $newStatus,
                    'ends_at' => $endsAt,
                ]);
            }
            
            Log::info('Abonnement entreprise synchronisé depuis Stripe', [
                'subscription_id' => $subscription->id,
                'stripe_id' => $subscription->stripe_id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);
            
            if ($oldStatus !== $newStatus) {
                return back()->with('success', "Abonnement synchronisé : statut mis à jour de \"{$oldStatus}\" vers \"{$newStatus}\".");
            } else {
                return back()->with('success', 'Abonnement synchronisé : déjà à jour.');
            }
            
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // L'abonnement n'existe plus sur Stripe
            $subscription->update([
                'stripe_status' => 'canceled',
                'ends_at' => now(),
            ]);
            
            // Mettre à jour aussi dans Cashier
            $cashierSubscription = Subscription::where('stripe_id', $subscription->stripe_id)->first();
            if ($cashierSubscription) {
                $cashierSubscription->update([
                    'stripe_status' => 'canceled',
                    'ends_at' => now(),
                ]);
            }
            
            return back()->with('success', 'Abonnement synchronisé : n\'existe plus sur Stripe, marqué comme annulé.');
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la synchronisation de l\'abonnement entreprise', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors(['error' => 'Erreur lors de la synchronisation: ' . $e->getMessage()]);
        }
    }

    /**
     * Annuler un abonnement utilisateur depuis l'admin
     */
    public function cancelUserSubscription(Subscription $subscription)
    {
        try {
            // Initialiser Stripe
            Stripe::setApiKey(config('services.stripe.secret'));

            // Récupérer l'abonnement Stripe
            $stripeSubscription = \Stripe\Subscription::retrieve($subscription->stripe_id);
            
            // Annuler immédiatement (pas de période de grâce)
            $stripeSubscription->cancel();

            // Mettre à jour l'abonnement local
            $subscription->update([
                'stripe_status' => 'canceled',
                'ends_at' => now(),
            ]);

            Log::info('Abonnement utilisateur annulé par admin', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'stripe_id' => $subscription->stripe_id,
            ]);

            return back()->with('success', "Abonnement utilisateur annulé avec succès. L'abonnement a été immédiatement désactivé.");

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'annulation de l\'abonnement utilisateur: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Erreur lors de l\'annulation: ' . $e->getMessage()]);
        }
    }

    /**
     * Annuler un abonnement entreprise depuis l'admin
     */
    public function cancelEntrepriseSubscription(EntrepriseSubscription $subscription)
    {
        try {
            // Si c'est un abonnement manuel, on peut juste le désactiver
            if ($subscription->est_manuel) {
                $subscription->update([
                    'actif_jusqu' => now()->subDay(), // Mettre à hier pour désactiver immédiatement
                ]);

                Log::info('Abonnement entreprise manuel annulé par admin', [
                    'subscription_id' => $subscription->id,
                    'entreprise_id' => $subscription->entreprise_id,
                ]);

                return back()->with('success', "Abonnement entreprise manuel annulé avec succès.");
            }

            // Si c'est un abonnement Stripe
            if ($subscription->stripe_id) {
                // Initialiser Stripe
                Stripe::setApiKey(config('services.stripe.secret'));

                // Récupérer l'abonnement Stripe
                $stripeSubscription = \Stripe\Subscription::retrieve($subscription->stripe_id);
                
                // Annuler immédiatement (pas de période de grâce)
                $stripeSubscription->cancel();

                // Mettre à jour l'abonnement local
                $subscription->update([
                    'stripe_status' => 'canceled',
                    'ends_at' => now(),
                ]);

                Log::info('Abonnement entreprise Stripe annulé par admin', [
                    'subscription_id' => $subscription->id,
                    'entreprise_id' => $subscription->entreprise_id,
                    'stripe_id' => $subscription->stripe_id,
                ]);

                return back()->with('success', "Abonnement entreprise annulé avec succès. L'abonnement a été immédiatement désactivé.");
            }

            return back()->withErrors(['error' => 'Impossible d\'annuler cet abonnement.']);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'annulation de l\'abonnement entreprise: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Erreur lors de l\'annulation: ' . $e->getMessage()]);
        }
    }

    /**
     * Se connecter en tant qu'un autre utilisateur (Impersonation)
     */
    public function impersonate($userId)
    {
        $user = User::findOrFail($userId);
        $originalAdminId = auth()->id();

        // Sécurité : Empêcher de s'impersonate soi-même
        if ($user->id === $originalAdminId) {
            return redirect()->back()->with('error', 'Inutile de vous connecter en tant que vous-même.');
        }
        
        // Stocker l'ID de l'admin original en session
        session(['original_admin_id' => $originalAdminId]);
        session(['impersonated_at' => now()]);
        
        // Déconnecter l'admin et connecter l'utilisateur cible sans mot de passe
        \Illuminate\Support\Facades\Auth::login($user);
        
        return redirect()->route('dashboard')->with('flash.banner', "Mode Super-User activé : Vous voyez le site en tant que {$user->name}");
    }

    /**
     * Arrêter l'impersonation et revenir au compte admin
     */
    public function stopImpersonating()
    {
        // Vérifier si une session d'impersonation est active
        if (!session()->has('original_admin_id')) {
            return redirect()->route('dashboard');
        }

        $adminId = session('original_admin_id');
        
        // Reconnecter l'admin original
        \Illuminate\Support\Facades\Auth::loginUsingId($adminId);
        
        // Nettoyer la session
        session()->forget('original_admin_id');
        session()->forget('impersonated_at');
        
        return redirect()->route('admin.users.index')->with('success', 'Mode Super-User désactivé. Retour au panneau administrateur.');
    }
}
