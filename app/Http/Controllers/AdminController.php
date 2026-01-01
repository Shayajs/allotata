<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Entreprise;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Ticket;
use App\Models\Contact;

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

        return view('admin.dashboard', compact('stats', 'alertes', 'chartData', 'derniersUtilisateurs'));
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
        $user->load('entreprises');
        
        return view('admin.users.subscription', compact('user'));
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
            ]);

            $user->update([
                'abonnement_manuel' => true,
                'abonnement_manuel_actif_jusqu' => $validated['date_fin'],
                'abonnement_manuel_notes' => $validated['notes'] ?? null,
            ]);

            return back()->with('success', 'Abonnement manuel activé jusqu\'au ' . \Carbon\Carbon::parse($validated['date_fin'])->format('d/m/Y') . '.');
        } else {
            $user->update([
                'abonnement_manuel' => false,
                'abonnement_manuel_actif_jusqu' => null,
                'abonnement_manuel_notes' => null,
            ]);

            return back()->with('success', 'Abonnement manuel désactivé.');
        }
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
}
