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
use App\Models\Facture;

class AdminController extends Controller
{
    /**
     * Purger un abonnement orphelin pour un utilisateur (Admin)
     */
    public function purgeSubscription(Request $request, User $user, $id)
    {
        $sub = \Laravel\Cashier\Subscription::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$sub) {
             return back()->with('error', "Abonnement introuvable.");
        }

        try {
            // Tentative de vérification ultime avec l'API Stripe
            if ($sub->stripe_id) {
                try {
                    \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                    \Stripe\Subscription::retrieve($sub->stripe_id);
                    // Si on arrive ici, il existe encore !
                    return back()->with('error', "Cet abonnement existe encore chez Stripe. Veuillez l'annuler normalement ou vérifier l'environnement.");
                } catch (\Stripe\Exception\InvalidRequestException $e) {
                     // C'est bon, il n'existe plus, on peut purger
                }
            }
            
            // Suppression
            $sub->delete();
            
            // Nettoyage user si nécessaire (flags manuels, etc - ici on ne touche pas au manuel admin sauf si lié)
            // On peut reset les flags abonnement_manuel si c'était un conflit avec Stripe mais le code admin gère les deux séparément.
            
            return back()->with('success', "Abonnement nettoyé de la base de données.");

        } catch (\Exception $e) {
            return back()->with('error', "Erreur lors du nettoyage : " . $e->getMessage());
        }
    }

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
                'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>',
                'color' => 'blue',
                'time' => $r->created_at,
                'text' => "Nouvelle réservation chez " . ($r->entreprise->nom ?? 'Une entreprise'),
                'subtext' => "Client : " . ($r->user->name ?? 'Invité')
            ];
        });
        
        $users = User::latest()->take(5)->get()->map(function($u) {
            return [
                'type' => 'user',
                'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>',
                'color' => 'green',
                'time' => $u->created_at,
                'text' => "Inscription : " . $u->name,
                'subtext' => $u->email
            ];
        });
        
        $finances = EntrepriseFinance::with('entreprise')->where('type', 'income')->latest()->take(8)->get()->map(function($f) {
            return [
                'type' => 'finance',
                'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
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

        // Exclure les comptes supprimés par défaut
        $query->where(function($q) {
            $q->where('statut_compte', '!=', 'supprime')
              ->orWhereNull('statut_compte');
        });

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

        // Filtre par statut
        if ($request->filled('statut')) {
            $statut = $request->statut;
            if ($statut === 'normal') {
                $query->where(function($q) {
                    $q->where('statut_compte', 'normal')
                      ->orWhereNull('statut_compte');
                });
            } else {
                $query->where('statut_compte', $statut);
            }
        }

        // Filtre par email vérifié
        if ($request->filled('email_verified')) {
            if ($request->email_verified === '1') {
                $query->whereNotNull('email_verified_at');
            } elseif ($request->email_verified === '0') {
                $query->whereNull('email_verified_at');
            }
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    /**
     * Liste des utilisateurs supprimés (archivés)
     */
    public function usersDeleted(Request $request)
    {
        $query = User::withCount(['entreprises', 'reservations'])
            ->where('statut_compte', 'supprime');

        // Recherche par nom ou email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('admin.users.deleted', compact('users'));
    }

    /**
     * Changer le statut d'un compte utilisateur
     */
    public function updateUserStatus(Request $request, User $user)
    {
        $validated = $request->validate([
            'statut_compte' => ['required', 'in:normal,limite,interdit,supprime'],
        ]);

        $oldStatus = $user->statut_compte ?? 'normal';
        $newStatus = $validated['statut_compte'];

        $user->update([
            'statut_compte' => $newStatus,
        ]);

        // Logger le changement de statut
        \App\Models\SecurityLog::log(
            $user->id,
            'account_status_changed',
            $request->ip(),
            $request->userAgent(),
            auth()->id(),
            [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ],
            'medium',
            false,
            "Statut du compte changé de '{$oldStatus}' à '{$newStatus}' par l'administrateur"
        );

        $statusLabels = [
            'normal' => 'Normal',
            'limite' => 'Limité',
            'interdit' => 'Interdit',
            'supprime' => 'Supprimé',
        ];

        return back()->with('success', "Le statut du compte a été changé en : {$statusLabels[$newStatus]}");
    }

    /**
     * Afficher un utilisateur
     */
    public function showUser(User $user)
    {
        $user->load(['entreprises', 'reservations.entreprise']);
        
        // Charger les données de sécurité
        $lockout = $user->accountLockout;
        $isLocked = $lockout && $lockout->isCurrentlyLocked();
        
        $loginAttempts = \App\Models\LoginAttempt::where('user_id', $user->id)
            ->orderBy('attempted_at', 'desc')
            ->limit(50)
            ->get();
        
        $securityLogs = \App\Models\SecurityLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
        
        $ipHistory = \App\Models\UserIpHistory::where('user_id', $user->id)
            ->orderBy('last_seen_at', 'desc')
            ->get();
        
        $hasSuspiciousActivity = \App\Models\SecurityLog::where('user_id', $user->id)
            ->where('is_suspicious', true)
            ->where('created_at', '>=', now()->subDays(7))
            ->exists();
        
        $securityStats = [
            'total_login_attempts' => $loginAttempts->count(),
            'failed_attempts' => $loginAttempts->where('success', false)->count(),
            'successful_logins' => $loginAttempts->where('success', true)->count(),
            'suspicious_logs' => \App\Models\SecurityLog::where('user_id', $user->id)
                ->where('is_suspicious', true)
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
            'unique_ips' => $ipHistory->count(),
        ];
        
        // Charger l'historique de sécurité
        $securityHistory = \App\Models\UserSecurityHistory::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return view('admin.users.show', compact(
            'user',
            'lockout',
            'isLocked',
            'loginAttempts',
            'securityLogs',
            'ipHistory',
            'hasSuspiciousActivity',
            'securityStats',
            'securityHistory'
        ));
    }

    /**
     * Générer un nouveau mot de passe aléatoire pour un utilisateur
     */
    public function generatePasswordForUser(Request $request, User $user)
    {
        $request->validate([
            'send_email' => ['boolean'],
        ]);

        // Générer un mot de passe aléatoire sécurisé
        $newPassword = \Illuminate\Support\Str::password(16);
        $oldPasswordHash = $user->password;

        // Mettre à jour le mot de passe
        $user->password = \Hash::make($newPassword);
        $user->save();

        // Enregistrer dans l'historique
        \App\Models\UserSecurityHistory::recordPasswordChange(
            $user,
            $oldPasswordHash,
            auth()->id(),
            $request->ip(),
            $request->userAgent(),
            'Mot de passe généré par l\'administrateur'
        );

        // Logger l'action
        \App\Models\SecurityLog::log(
            $user->id,
            'admin_password_reset',
            $request->ip(),
            $request->userAgent(),
            null,
            ['admin_id' => auth()->id()],
            'high',
            false,
            "Mot de passe régénéré par l'administrateur"
        );

        // Envoyer l'email si demandé
        if ($request->boolean('send_email')) {
            try {
                \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\PasswordGeneratedEmail($user, $newPassword));
                return back()->with('success', 'Mot de passe généré avec succès. Un email a été envoyé à l\'utilisateur.');
            } catch (\Exception $e) {
                \Log::error("Erreur lors de l'envoi de l'email de mot de passe : " . $e->getMessage());
                return back()->with('success', 'Mot de passe généré avec succès, mais l\'envoi de l\'email a échoué. Mot de passe : ' . $newPassword)->with('warning', 'Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Mot de passe généré avec succès. N\'oubliez pas de communiquer le mot de passe à l\'utilisateur : ' . $newPassword);
    }

    /**
     * Modifier l'email d'un utilisateur
     */
    public function updateUserEmail(Request $request, User $user)
    {
        $request->validate([
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $oldEmail = $user->email;
        $newEmail = $request->email;

        // Mettre à jour l'email
        $user->email = $newEmail;
        $user->email_verified_at = null; // Réinitialiser la vérification
        $user->save();

        // Enregistrer dans l'historique
        \App\Models\UserSecurityHistory::recordEmailChange(
            $user,
            $oldEmail,
            $newEmail,
            auth()->id(),
            $request->ip(),
            $request->userAgent(),
            $request->input('reason', 'Modification par l\'administrateur')
        );

        // Logger l'action
        \App\Models\SecurityLog::log(
            $user->id,
            'admin_email_change',
            $request->ip(),
            $request->userAgent(),
            null,
            ['admin_id' => auth()->id(), 'old_email' => $oldEmail, 'new_email' => $newEmail],
            'high',
            false,
            "Email modifié par l'administrateur"
        );

        return back()->with('success', 'Email modifié avec succès. L\'utilisateur devra vérifier son nouveau email.');
    }

    /**
     * Bloquer un utilisateur
     */
    public function blockUser(Request $request, User $user)
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        // Bloquer l'utilisateur
        $user->update([
            'statut_compte' => 'interdit',
        ]);

        // Créer un verrouillage permanent
        $lockout = \App\Models\AccountLockout::firstOrCreate(
            ['user_id' => $user->id],
            ['failed_attempts' => 0, 'is_locked' => false]
        );
        $lockout->lockPermanently();

        // Logger l'action
        \App\Models\SecurityLog::log(
            $user->id,
            'admin_account_blocked',
            $request->ip(),
            $request->userAgent(),
            null,
            ['admin_id' => auth()->id(), 'reason' => $request->input('reason')],
            'critical',
            true,
            "Compte bloqué par l'administrateur : " . ($request->input('reason') ?? 'Aucune raison spécifiée')
        );

        return back()->with('success', 'Utilisateur bloqué avec succès.');
    }

    /**
     * Débloquer un utilisateur
     */
    public function unblockUser(Request $request, User $user)
    {
        // Débloquer l'utilisateur
        $user->update([
            'statut_compte' => 'actif',
        ]);

        // Déverrouiller le compte
        if ($user->accountLockout) {
            $user->accountLockout->unlock();
        }

        // Logger l'action
        \App\Models\SecurityLog::log(
            $user->id,
            'admin_account_unblocked',
            $request->ip(),
            $request->userAgent(),
            null,
            ['admin_id' => auth()->id()],
            'medium',
            false,
            "Compte débloqué par l'administrateur"
        );

        return back()->with('success', 'Utilisateur débloqué avec succès.');
    }

    /**
     * Archiver (soft delete) un utilisateur
     */
    public function archiveUser(Request $request, User $user)
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        // Logger l'action avant d'archiver
        \App\Models\SecurityLog::log(
            $user->id,
            'admin_account_archived',
            $request->ip(),
            $request->userAgent(),
            null,
            ['admin_id' => auth()->id(), 'reason' => $request->input('reason'), 'archived_user_id' => $user->id],
            'high',
            false,
            "Compte archivé par l'administrateur : " . ($request->input('reason') ?? 'Aucune raison spécifiée')
        );

        // Archiver l'utilisateur en changeant son statut (pas de suppression physique)
        $user->update([
            'statut_compte' => 'supprime',
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Utilisateur archivé avec succès.');
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
        
        // Charger l'historique de sécurité
        $securityHistory = \App\Models\EntrepriseSecurityHistory::where('entreprise_id', $entreprise->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
        
        return view('admin.entreprises.show', compact('entreprise', 'securityHistory'));
    }

    /**
     * Modifier l'email d'une entreprise
     */
    public function updateEntrepriseEmail(Request $request, Entreprise $entreprise)
    {
        $request->validate([
            'email' => ['required', 'email', 'unique:entreprises,email,' . $entreprise->id],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $oldEmail = $entreprise->email;
        $newEmail = $request->email;

        // Mettre à jour l'email
        $entreprise->email = $newEmail;
        $entreprise->save();

        // Enregistrer dans l'historique
        \App\Models\EntrepriseSecurityHistory::recordEmailChange(
            $entreprise,
            $oldEmail,
            $newEmail,
            auth()->id(),
            $request->ip(),
            $request->userAgent(),
            $request->input('reason', 'Modification par l\'administrateur')
        );

        // Logger l'action
        \App\Models\SecurityLog::log(
            $entreprise->user_id,
            'admin_entreprise_email_change',
            $request->ip(),
            $request->userAgent(),
            null,
            ['admin_id' => auth()->id(), 'entreprise_id' => $entreprise->id, 'old_email' => $oldEmail, 'new_email' => $newEmail],
            'high',
            false,
            "Email de l'entreprise modifié par l'administrateur"
        );

        return back()->with('success', 'Email modifié avec succès.');
    }

    /**
     * Archiver (soft delete) une entreprise
     */
    public function archiveEntreprise(Request $request, Entreprise $entreprise)
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        // Archiver l'entreprise (soft delete)
        $entreprise->delete();

        // Logger l'action
        \App\Models\SecurityLog::log(
            $entreprise->user_id,
            'admin_entreprise_archived',
            $request->ip(),
            $request->userAgent(),
            null,
            ['admin_id' => auth()->id(), 'entreprise_id' => $entreprise->id, 'reason' => $request->input('reason')],
            'high',
            false,
            "Entreprise archivée par l'administrateur : " . ($request->input('reason') ?? 'Aucune raison spécifiée')
        );

        return redirect()->route('admin.entreprises.index')->with('success', 'Entreprise archivée avec succès.');
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
                'date_fin' => 'required|date|after_or_equal:today',
                'notes' => 'nullable|string|max:500',
                'type_renouvellement' => 'required|in:mensuel,annuel',
                'jour_renouvellement' => 'required|numeric|min:1|max:31',
                'date_debut' => 'required|date|before_or_equal:date_fin',
                'montant' => 'required|numeric|min:0',
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

        try {
            $subscription->cancel();
            return back()->with('success', 'L\'abonnement Stripe a été annulé. Il restera actif jusqu\'à la fin de la période payée.');
        } catch (\Exception $e) {
            // Si l'abonnement n'existe plus chez Stripe, on force un status d'erreur local (ou on le marque comme annulé localement)
            if (str_contains($e->getMessage(), 'No such subscription')) {
                 $subscription->update(['stripe_status' => 'canceled']);
                 return back()->with('success', "Abonnement inexistant chez Stripe. Marqué comme annulé localement.");
            }
            return back()->with('error', 'Erreur lors de l\'annulation Stripe: ' . $e->getMessage());
        }
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

        // Récupérer les prix dynamiques depuis Stripe (cache 1h)
        $subscriptionPrices = \Illuminate\Support\Facades\Cache::remember('stripe_subscription_prices', 3600, function () {
            try {
                \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                
                $prices = [];
                $configs = [
                    'site_web' => config('services.stripe.price_id_site_web'),
                    'multi_personnes' => config('services.stripe.price_id_multi_personnes')
                ];

                foreach ($configs as $key => $id) {
                    if ($id) {
                        try {
                            $p = \Stripe\Price::retrieve($id);
                            $amount = $p->unit_amount / 100;
                            // Formatage simple
                            $prices[$key] = [
                                'formatted' => number_format($amount, 2, '.', '') . '€'
                            ];
                        } catch (\Exception $e) {
                            $prices[$key] = $key === 'site_web' 
                                ? ['formatted' => '2.00€']
                                : ['formatted' => '20.00€'];
                        }
                    } else {
                        $prices[$key] = $key === 'site_web' 
                            ? ['formatted' => '2.00€']
                            : ['formatted' => '20.00€'];
                    }
                }
                return $prices;
            } catch (\Exception $e) {
                return [
                    'site_web' => ['formatted' => '2.00€'],
                    'multi_personnes' => ['formatted' => '20.00€']
                ];
            }
        });

        return view('admin.entreprises.options', [
            'entreprise' => $entreprise,
            'abonnementSiteWeb' => $abonnementSiteWeb,
            'abonnementMultiPersonnes' => $abonnementMultiPersonnes,
            'membres' => $membres,
            'subscriptionPrices' => $subscriptionPrices,
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
                'label' => 'Abonnement utilisateur',
                'type' => 'default',
            ],
            'site_web' => [
                'id' => config('services.stripe.price_id_site_web'),
                'label' => 'Site Web Vitrine',
                'type' => 'site_web',
            ],
            'multi_personnes' => [
                'id' => config('services.stripe.price_id_multi_personnes'),
                'label' => 'Gestion Multi-Personnes',
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

            // Vider le cache des prix affichés sur le dashboard
            \Illuminate\Support\Facades\Cache::forget('stripe_subscription_prices');

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

            // Vider le cache des prix affichés sur le dashboard
            \Illuminate\Support\Facades\Cache::forget('stripe_subscription_prices');

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

            // Vider le cache des prix affichés sur le dashboard
            \Illuminate\Support\Facades\Cache::forget('stripe_subscription_prices');

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

    /**
     * Afficher la page de gestion des webhooks Stripe
     */
    public function stripeWebhooks(Request $request)
    {
        // Récupérer la configuration du webhook
        $webhookSecret = config('services.stripe.webhook.secret');
        $webhookUrl = route('cashier.webhook');
        $webhookTolerance = config('services.stripe.webhook.tolerance', 300);
        
        // Vérifier si le secret est configuré
        $webhookConfigured = !empty($webhookSecret);
        
        // Récupérer les transactions/webhooks avec pagination
        $query = \App\Models\StripeTransaction::with('user')
            ->orderBy('created_at', 'desc');
        
        // Filtres
        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }
        
        if ($request->filled('processed')) {
            $query->where('processed', $request->processed === '1');
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('stripe_event_id', 'like', "%{$search}%")
                  ->orWhere('stripe_customer_id', 'like', "%{$search}%")
                  ->orWhere('stripe_subscription_id', 'like', "%{$search}%")
                  ->orWhere('stripe_payment_intent_id', 'like', "%{$search}%")
                  ->orWhere('event_type', 'like', "%{$search}%");
            });
        }
        
        $transactions = $query->paginate(50)->withQueryString();
        
        // Statistiques
        $stats = [
            'total' => \App\Models\StripeTransaction::count(),
            'processed' => \App\Models\StripeTransaction::where('processed', true)->count(),
            'pending' => \App\Models\StripeTransaction::where('processed', false)->count(),
            'today' => \App\Models\StripeTransaction::whereDate('created_at', today())->count(),
            'last_24h' => \App\Models\StripeTransaction::where('created_at', '>=', now()->subDay())->count(),
        ];
        
        // Statistiques par type d'événement
        $eventTypes = \App\Models\StripeTransaction::select('event_type', DB::raw('count(*) as count'))
            ->groupBy('event_type')
            ->orderBy('count', 'desc')
            ->get();
        
        // Récupérer les derniers événements non traités
        $recentPending = \App\Models\StripeTransaction::where('processed', false)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        
        return view('admin.stripe-webhooks', [
            'webhookSecret' => $webhookSecret,
            'webhookUrl' => $webhookUrl,
            'webhookTolerance' => $webhookTolerance,
            'webhookConfigured' => $webhookConfigured,
            'transactions' => $transactions,
            'stats' => $stats,
            'eventTypes' => $eventTypes,
            'recentPending' => $recentPending,
        ]);
    }

    /**
     * Afficher les détails d'une transaction webhook (API JSON)
     */
    public function stripeWebhookDetails(\App\Models\StripeTransaction $transaction)
    {
        return response()->json([
            'id' => $transaction->id,
            'event_type' => $transaction->event_type,
            'stripe_event_id' => $transaction->stripe_event_id,
            'stripe_customer_id' => $transaction->stripe_customer_id,
            'stripe_subscription_id' => $transaction->stripe_subscription_id,
            'stripe_payment_intent_id' => $transaction->stripe_payment_intent_id,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'status' => $transaction->status,
            'processed' => $transaction->processed,
            'processed_at' => $transaction->processed_at,
            'metadata' => $transaction->metadata,
            'raw_data' => $transaction->raw_data,
            'created_at' => $transaction->created_at->toISOString(),
        ]);
    }

    /**
     * Afficher la liste des factures (admin)
     */
    public function factures(Request $request)
    {
        $query = Facture::with(['entreprise', 'user', 'entrepriseSubscription', 'reservation']);

        // Filtres
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero_facture', 'like', "%{$search}%")
                  ->orWhereHas('entreprise', function($entrepriseQuery) use ($search) {
                      $entrepriseQuery->where('nom', 'like', "%{$search}%");
                  })
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('type_facture')) {
            $query->where('type_facture', $request->type_facture);
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('date_debut')) {
            $query->whereDate('date_facture', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date_facture', '<=', $request->date_fin);
        }

        $factures = $query->orderBy('date_facture', 'desc')->paginate(20)->withQueryString();

        return view('admin.factures.index', [
            'factures' => $factures,
        ]);
    }

    /**
     * Afficher le formulaire de création de facture manuelle (admin)
     */
    public function createFacture()
    {
        $entreprises = Entreprise::orderBy('nom')->get();
        $users = User::orderBy('name')->get();
        $subscriptions = EntrepriseSubscription::with('entreprise')
            ->where(function($q) {
                $q->where('est_manuel', true)
                  ->orWhere(function($q2) {
                      $q2->where('est_manuel', false)
                         ->whereIn('stripe_status', ['active', 'trialing']);
                  });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.factures.create', [
            'entreprises' => $entreprises,
            'users' => $users,
            'subscriptions' => $subscriptions,
        ]);
    }

    /**
     * Stocker une facture manuelle (admin)
     */
    public function storeFacture(Request $request)
    {
        $validated = $request->validate([
            'type_facture' => 'required|in:reservation,abonnement_manuel,abonnement_entreprise',
            'entreprise_id' => 'nullable|exists:entreprises,id',
            'user_id' => 'nullable|exists:users,id',
            'entreprise_subscription_id' => 'nullable|exists:entreprise_subscriptions,id',
            'montant_ht' => 'required|numeric|min:0.01',
            'taux_tva' => 'nullable|numeric|min:0|max:100',
            'date_facture' => 'required|date',
            'date_echeance' => 'nullable|date|after_or_equal:date_facture',
            'notes' => 'nullable|string|max:1000',
            'statut' => 'nullable|in:brouillon,emise,payee,annulee',
        ]);

        // Vérifier qu'au moins entreprise_id ou user_id est fourni
        if (!$validated['entreprise_id'] && !$validated['user_id']) {
            return back()->withErrors(['error' => 'Au moins une entreprise ou un utilisateur doit être sélectionné.'])->withInput();
        }

        try {
            $facture = Facture::generateManualInvoice($validated);

            return redirect()->route('admin.factures.show', $facture->id)
                ->with('success', 'Facture créée avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de facture manuelle: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Une erreur est survenue lors de la création de la facture.'])->withInput();
        }
    }

    /**
     * Afficher une facture (admin)
     */
    public function showFacture(Facture $facture)
    {
        $facture->load(['entreprise', 'user', 'entrepriseSubscription', 'reservation', 'reservations']);

        return view('admin.factures.show', [
            'facture' => $facture,
        ]);
    }

    /**
     * Générer automatiquement les factures d'abonnement manquantes (admin)
     */
    public function generateSubscriptionInvoices(Request $request)
    {
        $dateFacture = $request->filled('date') ? \Carbon\Carbon::parse($request->date) : now();
        $force = $request->boolean('force', false);

        $facturesGenerees = 0;
        $erreurs = 0;

        // Générer pour les abonnements manuels entreprises
        $subscriptionsManuelles = EntrepriseSubscription::where('est_manuel', true)
            ->whereNotNull('type_renouvellement')
            ->whereNotNull('jour_renouvellement')
            ->whereNotNull('montant')
            ->where(function($query) {
                $query->whereNull('actif_jusqu')
                      ->orWhere('actif_jusqu', '>=', now());
            })
            ->with('entreprise')
            ->get();

        foreach ($subscriptionsManuelles as $subscription) {
            if ($force || $subscription->jour_renouvellement == $dateFacture->day) {
                try {
                    $facture = Facture::generateFromManualEntrepriseSubscription($subscription, $dateFacture);
                    if ($facture) {
                        $facturesGenerees++;
                    }
                } catch (\Exception $e) {
                    $erreurs++;
                    Log::error('Erreur génération facture abonnement manuel ' . $subscription->id . ': ' . $e->getMessage());
                }
            }
        }

        // Générer pour les abonnements Stripe entreprises
        $subscriptionsStripe = EntrepriseSubscription::where('est_manuel', false)
            ->whereIn('stripe_status', ['active', 'trialing'])
            ->whereNotNull('stripe_id')
            ->with('entreprise')
            ->get();

        foreach ($subscriptionsStripe as $subscription) {
            if ($force || $subscription->jour_renouvellement == $dateFacture->day) {
                try {
                    $facture = Facture::generateFromStripeEntrepriseSubscription($subscription, $dateFacture);
                    if ($facture) {
                        $facturesGenerees++;
                    }
                } catch (\Exception $e) {
                    $erreurs++;
                    Log::error('Erreur génération facture abonnement Stripe ' . $subscription->id . ': ' . $e->getMessage());
                }
            }
        }

        $message = "Génération terminée : {$facturesGenerees} facture(s) générée(s)";
        if ($erreurs > 0) {
            $message .= ", {$erreurs} erreur(s)";
        }

        return back()->with('success', $message);
    }
}
