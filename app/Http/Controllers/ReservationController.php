<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Entreprise;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ReservationController extends Controller
{
    /**
     * Afficher les réservations en attente pour une entreprise
     */
    public function index(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $query = $entreprise->reservations()
            ->with(['user', 'typeService']);

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('type_service', 'like', "%{$search}%")
                  ->orWhere('lieu', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
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

        $reservations = $query->with('membre.user')
            ->orderBy('date_reservation', 'asc')
            ->get()
            ->groupBy('statut');

        // Charger les membres si multi-personnes
        $membres = collect([]);
        if ($entreprise->aGestionMultiPersonnes()) {
            $membres = $entreprise->membres()
                ->where('est_actif', true)
                ->with('user')
                ->get();
        }

        return view('reservations.index', [
            'entreprise' => $entreprise,
            'reservations' => $reservations,
            'membres' => $membres,
            'aGestionMultiPersonnes' => $entreprise->aGestionMultiPersonnes(),
        ]);
    }

    /**
     * Afficher une réservation
     */
    public function show($slug, $id)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $reservation = Reservation::where('id', $id)
            ->where('entreprise_id', $entreprise->id)
            ->with(['user', 'typeService', 'membre.user'])
            ->firstOrFail();

        // Charger les membres si multi-personnes
        $membres = collect([]);
        if ($entreprise->aGestionMultiPersonnes()) {
            $membres = $entreprise->membres()
                ->where('est_actif', true)
                ->with('user')
                ->get();
        }

        // Vérifier si une conversation existe déjà pour cette réservation
        // Vérifier d'abord si la colonne existe (pour éviter l'erreur si la migration n'est pas exécutée)
        $conversation = null;
        if (Schema::hasColumn('conversations', 'reservation_id')) {
            $conversation = \App\Models\Conversation::where('reservation_id', $reservation->id)->first();
        }

        return view('reservations.show', [
            'entreprise' => $entreprise,
            'reservation' => $reservation,
            'membres' => $membres,
            'aGestionMultiPersonnes' => $entreprise->aGestionMultiPersonnes(),
            'conversation' => $conversation,
        ]);
    }

    /**
     * Démarrer une conversation depuis une réservation
     */
    public function startConversation($slug, $id)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $reservation = Reservation::where('id', $id)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        // Vérifier si la réservation a un user_id (cliente inscrite)
        if (!$reservation->user_id) {
            return back()->with('error', 'Impossible de démarrer une conversation pour une cliente non inscrite.');
        }

        // Vérifier si une conversation existe déjà pour cette réservation
        // Vérifier d'abord si la colonne existe (pour éviter l'erreur si la migration n'est pas exécutée)
        $hasReservationIdColumn = \Schema::hasColumn('conversations', 'reservation_id');
        
        $conversation = null;
        if ($hasReservationIdColumn) {
            $conversation = \App\Models\Conversation::where('reservation_id', $reservation->id)->first();
        }

        if (!$conversation) {
            // Vérifier si une conversation existe déjà entre le client et l'entreprise
            $existingConversation = \App\Models\Conversation::where('user_id', $reservation->user_id)
                ->where('entreprise_id', $entreprise->id)
                ->where('est_archivee', false)
                ->first();

            if ($existingConversation) {
                // Lier la réservation à la conversation existante si la colonne existe
                if ($hasReservationIdColumn) {
                    $existingConversation->update(['reservation_id' => $reservation->id]);
                }
                $conversation = $existingConversation;
            } else {
                // Créer une nouvelle conversation liée à la réservation
                $conversationData = [
                    'user_id' => $reservation->user_id,
                    'entreprise_id' => $entreprise->id,
                ];
                if ($hasReservationIdColumn) {
                    $conversationData['reservation_id'] = $reservation->id;
                }
                $conversation = \App\Models\Conversation::create($conversationData);
            }

            // Créer un message initial pour expliquer la conversation
            \App\Models\Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'contenu' => "💬 Conversation démarrée à propos de la réservation #{$reservation->id} du {$reservation->date_reservation->format('d/m/Y à H:i')}. Vous pouvez discuter et proposer des modifications.",
                'est_lu' => false,
            ]);

            $conversation->update(['dernier_message_at' => now()]);
        }

        // Rediriger vers la conversation (pour le gérant)
        return redirect()->route('messagerie.show-gerant', [$entreprise->slug, $conversation->id])
            ->with('success', 'Conversation démarrée ! Vous pouvez maintenant discuter et proposer des modifications à la réservation.');
    }

    /**
     * Accepter une réservation
     */
    public function accept(Request $request, $slug, $id)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $reservation = Reservation::where('id', $id)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $validated = $request->validate([
            'notes_gerant' => 'nullable|string|max:1000',
        ]);

        $reservation->update([
            'statut' => 'confirmee',
            'notes' => $reservation->notes . ($validated['notes_gerant'] ? "\n\n[Note de la tata] " . $validated['notes_gerant'] : ''),
        ]);

        // Créer une notification pour le client
        Notification::creer(
            $reservation->user_id,
            'reservation',
            'Réservation confirmée',
            "Votre réservation pour {$entreprise->nom} le {$reservation->date_reservation->format('d/m/Y à H:i')} a été confirmée !",
            route('dashboard'),
            ['reservation_id' => $reservation->id, 'entreprise_id' => $entreprise->id]
        );

        return redirect()->route('reservations.show', [$slug, $id])
            ->with('success', 'La réservation a été acceptée avec succès.');
    }

    /**
     * Refuser une réservation
     */
    public function reject(Request $request, $slug, $id)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $reservation = Reservation::where('id', $id)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $validated = $request->validate([
            'raison_refus' => 'nullable|string|max:500',
        ]);

        $reservation->update([
            'statut' => 'annulee',
            'notes' => $reservation->notes . ($validated['raison_refus'] ? "\n\n[Raison du refus] " . $validated['raison_refus'] : ''),
        ]);

        // Créer une notification pour le client
        $raison = $validated['raison_refus'] ? " Raison : {$validated['raison_refus']}" : '';
        Notification::creer(
            $reservation->user_id,
            'reservation',
            'Réservation annulée',
            "Votre réservation pour {$entreprise->nom} le {$reservation->date_reservation->format('d/m/Y à H:i')} a été annulée.{$raison}",
            route('dashboard'),
            ['reservation_id' => $reservation->id, 'entreprise_id' => $entreprise->id]
        );

        return redirect()->route('reservations.index', $slug)
            ->with('success', 'La réservation a été refusée.');
    }

    /**
     * Ajouter des notes à une réservation
     */
    public function addNotes(Request $request, $slug, $id)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $reservation = Reservation::where('id', $id)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $validated = $request->validate([
            'notes_gerant' => 'required|string|max:1000',
        ]);

        $notesActuelles = $reservation->notes ?? '';
        $reservation->update([
            'notes' => $notesActuelles . ($notesActuelles ? "\n\n" : '') . "[Note de la tata] " . $validated['notes_gerant'],
        ]);

        return redirect()->route('reservations.show', [$slug, $id])
            ->with('success', 'Les notes ont été ajoutées avec succès.');
    }

    /**
     * Marquer une réservation comme payée
     */
    public function marquerPayee(Request $request, $slug, $id)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $reservation = Reservation::where('id', $id)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $validated = $request->validate([
            'date_paiement' => 'nullable|date',
            'notes_paiement' => 'nullable|string|max:500',
        ]);

        $datePaiement = $validated['date_paiement'] ?? now();

        $reservation->update([
            'est_paye' => true,
            'date_paiement' => $datePaiement,
            'notes' => $reservation->notes . ($validated['notes_paiement'] ? "\n\n[Paiement] " . $validated['notes_paiement'] : ''),
        ]);

        // Recharger la réservation pour avoir les dernières valeurs
        $reservation->refresh();
        
        // La facture sera générée automatiquement par l'observer ReservationObserver
        // Vérifier si une facture a été créée
        $factureGeneree = $reservation->facture;
        $message = 'Le paiement a été marqué comme effectué. Le client a été notifié.';
        if ($factureGeneree) {
            $message .= ' Une facture a été générée automatiquement.';
        } else {
            // Si l'observer n'a pas fonctionné, essayer de générer la facture manuellement
            try {
                $facture = \App\Models\Facture::generateFromReservation($reservation);
                if ($facture) {
                    $message .= ' Une facture a été générée.';
                }
            } catch (\Exception $e) {
                \Log::error('Erreur lors de la génération manuelle de la facture : ' . $e->getMessage());
            }
        }

        // Créer une notification pour le client
        Notification::creer(
            $reservation->user_id,
            'paiement',
            'Paiement confirmé',
            "Votre paiement de {$reservation->prix} € pour la réservation du {$reservation->date_reservation->format('d/m/Y')} a été confirmé par {$entreprise->nom}.",
            route('dashboard'),
            ['reservation_id' => $reservation->id, 'entreprise_id' => $entreprise->id]
        );

        return redirect()->route('reservations.show', [$slug, $id])
            ->with('success', $message);
    }

    /**
     * Recherche floue de clientes pour création manuelle de réservation
     */
    public function searchClients(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        // Récupérer tous les clients
        $clients = User::where('est_client', true)
            ->select('id', 'name', 'email', 'telephone')
            ->get();

        $results = [];
        $queryLower = mb_strtolower($query, 'UTF-8');

        foreach ($clients as $client) {
            $nameLower = mb_strtolower($client->name, 'UTF-8');
            $emailLower = mb_strtolower($client->email, 'UTF-8');

            // Calculer la similarité avec le nom
            $similarityName = 0;
            similar_text($queryLower, $nameLower, $similarityName);

            // Calculer la similarité avec l'email
            $similarityEmail = 0;
            similar_text($queryLower, $emailLower, $similarityEmail);

            // Prendre la meilleure similarité
            $similarity = max($similarityName, $similarityEmail);

            // Vérifier aussi si le nom ou l'email contient la requête (pour les correspondances partielles)
            $containsName = str_contains($nameLower, $queryLower);
            $containsEmail = str_contains($emailLower, $queryLower);

            // Si similarité >= 70% ou contient la requête
            if ($similarity >= 70 || $containsName || $containsEmail) {
                // Si contient la requête, on donne une similarité de 100%
                if ($containsName || $containsEmail) {
                    $similarity = 100;
                }

                $results[] = [
                    'id' => $client->id,
                    'name' => $client->name,
                    'email' => $client->email,
                    'telephone' => $client->telephone ?? null,
                    'similarity' => $similarity,
                ];
            }
        }

        // Trier par similarité décroissante
        usort($results, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        // Limiter à 10 résultats
        $results = array_slice($results, 0, 10);

        return response()->json($results);
    }

    /**
     * Créer une réservation manuellement (par l'entreprise)
     */
    public function storeManuelle(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'nom_client' => 'required_if:user_id,null|string|max:255',
            'email_client' => 'required_if:user_id,null|email|max:255',
            'telephone_client_non_inscrit' => 'required_if:user_id,null|string|max:20',
            'date_reservation' => 'required|date',
            'heure_reservation' => 'required|date_format:H:i',
            'type_service_id' => 'nullable|exists:types_services,id',
            'type_service' => 'required_without:type_service_id|string|max:255',
            'membre_id' => 'nullable|exists:entreprise_membres,id',
            'lieu' => 'nullable|string|max:255',
            'prix' => 'required|numeric|min:0',
            'duree_minutes' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'statut' => 'required|in:en_attente,confirmee,terminee',
            'est_paye' => 'boolean',
            'date_paiement' => 'nullable|date',
        ]);

        // Si user_id est fourni, vérifier que c'est bien un client
        if ($validated['user_id']) {
            $client = User::where('id', $validated['user_id'])
                ->where('est_client', true)
                ->first();
            
            if (!$client) {
                return back()->withErrors(['user_id' => 'L\'utilisateur sélectionné n\'est pas un client.']);
            }
        }

        // Vérifier le type de service si type_service_id est fourni
        $typeService = null;
        if ($validated['type_service_id']) {
            $typeService = \App\Models\TypeService::where('id', $validated['type_service_id'])
                ->where('entreprise_id', $entreprise->id)
                ->where('est_actif', true)
                ->first();
            
            if (!$typeService) {
                return back()->withErrors(['type_service_id' => 'Type de service invalide.']);
            }
        }

        // Combiner date et heure
        $dateTime = $validated['date_reservation'] . ' ' . $validated['heure_reservation'];
        $debutReservation = \Carbon\Carbon::parse($dateTime);

        // Gérer la sélection du membre
        $membreId = null;
        if (!empty($validated['membre_id'])) {
            $membre = \App\Models\EntrepriseMembre::where('id', $validated['membre_id'])
                ->where('entreprise_id', $entreprise->id)
                ->where('est_actif', true)
                ->first();
            
            if (!$membre) {
                return back()->withErrors(['membre_id' => 'Membre invalide.']);
            }
            
            $membreId = $membre->id;
        }

        // Vérifier chevauchement uniquement si la date est dans le futur
        if ($debutReservation->isFuture()) {
            $finReservation = $debutReservation->copy()->addMinutes((int) $validated['duree_minutes']);
            
            $queryReservations = Reservation::where('entreprise_id', $entreprise->id)
                ->whereIn('statut', ['en_attente', 'confirmee']);
            
            if ($membreId) {
                $queryReservations->where('membre_id', $membreId);
            }
            
            $creneauDejaPris = $queryReservations->get()
                ->filter(function($r) use ($debutReservation, $finReservation) {
                    $debutR = \Carbon\Carbon::parse($r->date_reservation);
                    $finR = $debutR->copy()->addMinutes((int) ($r->duree_minutes ?? 30));
                    return $debutReservation->lt($finR) && $finReservation->gt($debutR);
                })
                ->isNotEmpty();

            if ($creneauDejaPris) {
                return back()->withErrors(['error' => 'Ce créneau est déjà réservé. Veuillez choisir un autre horaire.']);
            }
        }

        // Préparer les données de la réservation
        $reservationData = [
            'user_id' => $validated['user_id'] ?? null,
            'entreprise_id' => $entreprise->id,
            'membre_id' => $membreId,
            'date_reservation' => $dateTime,
            'lieu' => $validated['lieu'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'prix' => $validated['prix'],
            'duree_minutes' => $validated['duree_minutes'],
            'statut' => $validated['statut'],
            'creee_manuellement' => true,
            'est_paye' => $validated['est_paye'] ?? false,
            'date_paiement' => ($validated['est_paye'] ?? false) ? ($validated['date_paiement'] ?? now()) : null,
        ];

        // Si cliente non inscrite, ajouter les informations
        if (!$validated['user_id']) {
            $reservationData['nom_client'] = $validated['nom_client'];
            $reservationData['email_client'] = $validated['email_client'];
            $reservationData['telephone_client_non_inscrit'] = $validated['telephone_client_non_inscrit'];
        } else {
            // Pour les clientes inscrites, récupérer le téléphone si disponible
            $client = User::find($validated['user_id']);
            if ($client && $client->telephone) {
                $reservationData['telephone_client'] = $client->telephone;
            }
        }

        // Gérer le type de service
        if ($typeService) {
            $reservationData['type_service_id'] = $typeService->id;
            $reservationData['type_service'] = $typeService->nom;
        } else {
            $reservationData['type_service'] = $validated['type_service'];
        }

        // Créer la réservation
        $reservation = Reservation::create($reservationData);

        // Si la réservation est confirmée et créée manuellement, pas de notification
        // (l'entreprise a déjà accepté en créant la réservation)
        // Si la cliente est inscrite et la réservation est confirmée, on peut créer une notification
        if ($reservation->user_id && $reservation->statut === 'confirmee') {
            Notification::creer(
                $reservation->user_id,
                'reservation',
                'Réservation confirmée',
                "Votre réservation pour {$entreprise->nom} le {$reservation->date_reservation->format('d/m/Y à H:i')} a été confirmée !",
                route('dashboard'),
                ['reservation_id' => $reservation->id, 'entreprise_id' => $entreprise->id]
            );
        }

        return redirect()->route('entreprise.dashboard', ['slug' => $slug, 'tab' => 'reservations'])
            ->with('success', 'La réservation a été créée avec succès.');
    }
}
