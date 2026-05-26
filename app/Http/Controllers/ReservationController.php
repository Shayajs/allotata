<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\Notification;
use App\Models\Reservation;
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
        if (! $entreprise->peutEtreGereePar($user) && ! $user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $query = $entreprise->reservations()
            ->with(['user', 'typeService']);

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('type_service', 'like', "%{$search}%")
                    ->orWhere('lieu', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhere('nom_client', 'like', "%{$search}%")
                    ->orWhere('email_client', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
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
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        if (! $entreprise->peutEtreGereePar($user) && ! $user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

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
        if (! $reservation->user_id) {
            return back()->with('error', 'Impossible de démarrer une conversation pour une cliente non inscrite.');
        }

        // Vérifier si une conversation existe déjà pour cette réservation
        // Vérifier d'abord si la colonne existe (pour éviter l'erreur si la migration n'est pas exécutée)
        $hasReservationIdColumn = \Schema::hasColumn('conversations', 'reservation_id');

        $conversation = null;
        if ($hasReservationIdColumn) {
            $conversation = \App\Models\Conversation::where('reservation_id', $reservation->id)->first();
        }

        if (! $conversation) {
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
        if (! $entreprise->peutEtreGereePar($user) && ! $user->is_admin) {
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
            'notes' => $reservation->notes.($validated['notes_gerant'] ? "\n\n[Note de la tata] ".$validated['notes_gerant'] : ''),
        ]);

        // Invalider le cache des statistiques
        \App\Services\CacheService::clearEntrepriseCache($entreprise->id, $entreprise->slug);

        // Créer une notification pour le client (uniquement si inscrit)
        if ($reservation->user_id) {
            Notification::creer(
                $reservation->user_id,
                'reservation',
                'Réservation confirmée',
                "Votre réservation pour {$entreprise->nom} le {$reservation->date_reservation->format('d/m/Y à H:i')} a été confirmée !",
                route('dashboard'),
                ['reservation_id' => $reservation->id, 'entreprise_id' => $entreprise->id]
            );
        }

        // Envoyer un email de confirmation au client (inscrit ou invité avec email)
        if ($reservation->user_id || ! empty($reservation->email_client)) {
            try {
                $reservation->refresh();
                \App\Helpers\EmailHelper::sendReservationConfirmationClient($reservation);
            } catch (\Exception $e) {
                \Log::error("Erreur lors de l'envoi de l'email de confirmation : ".$e->getMessage());
            }
        }

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
        if (! $entreprise->peutEtreGereePar($user) && ! $user->is_admin) {
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
            'notes' => $reservation->notes.($validated['raison_refus'] ? "\n\n[Raison du refus] ".$validated['raison_refus'] : ''),
        ]);

        // Créer une notification pour le client (uniquement si inscrit)
        if ($reservation->user_id) {
            $raison = $validated['raison_refus'] ? " Raison : {$validated['raison_refus']}" : '';
            Notification::creer(
                $reservation->user_id,
                'reservation',
                'Réservation annulée',
                "Votre réservation pour {$entreprise->nom} le {$reservation->date_reservation->format('d/m/Y à H:i')} a été annulée.{$raison}",
                route('dashboard'),
                ['reservation_id' => $reservation->id, 'entreprise_id' => $entreprise->id]
            );
        }

        // Envoyer un email d'annulation au client (inscrit ou invité avec email)
        if ($reservation->user_id || ! empty($reservation->email_client)) {
            try {
                $reservation->refresh();
                \App\Helpers\EmailHelper::sendReservationCancelledClient($reservation, 'gerant');
            } catch (\Exception $e) {
                \Log::error("Erreur lors de l'envoi de l'email d'annulation : ".$e->getMessage());
            }
        }

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
            'notes' => $notesActuelles.($notesActuelles ? "\n\n" : '').'[Note de la tata] '.$validated['notes_gerant'],
        ]);

        return redirect()->route('reservations.show', [$slug, $id])
            ->with('success', 'Les notes ont été ajoutées avec succès.');
    }

    /**
     * Modifier une réservation (côté entreprise) — uniquement si statut en_attente
     */
    public function update(Request $request, $slug, $id)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        if (! $entreprise->peutEtreGereePar($user) && ! $user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $reservation = Reservation::where('id', $id)
            ->where('entreprise_id', $entreprise->id)
            ->with('typeService')
            ->firstOrFail();

        if ($reservation->statut !== 'en_attente') {
            return redirect()->route('reservations.show', [$slug, $id])
                ->with('error', 'Seules les réservations en attente peuvent être modifiées. Une fois acceptée, contactez le client pour toute modification.');
        }

        $isDateButoire = $reservation->typeService && $reservation->typeService->estDateButoire();
        $rules = [
            'lieu' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ];
        if ($isDateButoire) {
            $rules['date_butoire'] = 'required|date|after_or_equal:today';
        } else {
            $rules['date_reservation'] = 'required|date|after:now';
            $rules['heure_reservation'] = 'required|date_format:H:i';
        }
        if ($entreprise->prix_negociables ?? false) {
            $rules['prix'] = 'required|numeric|min:0';
        }
        $validated = $request->validate($rules);

        if ($isDateButoire) {
            $reservation->update([
                'date_butoire' => $validated['date_butoire'],
                'date_reservation' => $validated['date_butoire'].' 00:00:00',
                'lieu' => $validated['lieu'] ?? null,
                'notes' => $validated['notes'] ?? $reservation->notes,
            ]);
        } else {
            $dateTime = $validated['date_reservation'].' '.$validated['heure_reservation'];
            $debutUpdate = \Carbon\Carbon::parse($dateTime);
            $reservation->update([
                'date_reservation' => $dateTime,
                'date_fin' => $debutUpdate->copy()->addMinutes((int) ($reservation->duree_minutes ?? 60)),
                'lieu' => $validated['lieu'] ?? null,
                'notes' => $validated['notes'] ?? $reservation->notes,
            ]);
        }
        if (isset($validated['prix'])) {
            $reservation->update(['prix' => $validated['prix']]);
        }

        if ($reservation->user_id) {
            Notification::creer(
                $reservation->user_id,
                'reservation',
                'Réservation modifiée',
                "L'entreprise {$entreprise->nom} a modifié les informations de votre réservation.",
                route('dashboard'),
                ['reservation_id' => $reservation->id, 'entreprise_id' => $entreprise->id]
            );
        }

        return redirect()->route('reservations.show', [$slug, $id])
            ->with('success', 'La réservation a été mise à jour.');
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
        if (! $entreprise->peutEtreGereePar($user) && ! $user->is_admin) {
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
            'notes' => $reservation->notes.($validated['notes_paiement'] ? "\n\n[Paiement] ".$validated['notes_paiement'] : ''),
        ]);

        // Recharger la réservation pour avoir les dernières valeurs
        $reservation->refresh();

        // Envoyer un email au client pour confirmer le paiement
        if ($reservation->user_id) {
            try {
                \App\Helpers\EmailHelper::sendPaymentReceived($reservation);
            } catch (\Exception $e) {
                \Log::error("Erreur lors de l'envoi de l'email de paiement : ".$e->getMessage());
            }
        }

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
                \Log::error('Erreur lors de la génération manuelle de la facture : '.$e->getMessage());
            }
        }

        // Créer une notification pour le client (uniquement si inscrit)
        if ($reservation->user_id) {
            Notification::creer(
                $reservation->user_id,
                'paiement',
                'Paiement confirmé',
                "Votre paiement de {$reservation->prix} € pour la réservation du {$reservation->date_reservation->format('d/m/Y')} a été confirmé par {$entreprise->nom}.",
                route('dashboard'),
                ['reservation_id' => $reservation->id, 'entreprise_id' => $entreprise->id]
            );
        }

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
        if (! $entreprise->peutEtreGereePar($user) && ! $user->is_admin) {
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
        usort($results, function ($a, $b) {
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
        if (! $entreprise->peutEtreGereePar($user) && ! $user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        // Déterminer le type de structure du service sélectionné
        $typeService = null;
        $isDateButoire = false;
        $isEvenement = false;
        if ($request->filled('type_service_id')) {
            $typeService = \App\Models\TypeService::where('id', $request->input('type_service_id'))
                ->where('entreprise_id', $entreprise->id)
                ->where('est_actif', true)
                ->first();

            if (! $typeService) {
                return back()->withErrors(['type_service_id' => 'Type de service invalide.']);
            }

            $isDateButoire = $typeService->estDateButoire();
            $isEvenement = $typeService->estEvenement();
        }

        // Règles de validation conditionnelles selon le type de structure
        $rules = [
            'user_id' => 'nullable|exists:users,id',
            'nom_client' => 'required_if:user_id,null|string|max:255',
            'email_client' => 'nullable|email|max:255',
            'telephone_client_non_inscrit' => 'required_if:user_id,null|string|max:20',
            'type_service_id' => 'nullable|exists:types_services,id',
            'type_service' => 'required_without:type_service_id|string|max:255',
            'membre_id' => 'nullable|exists:entreprise_membres,id',
            'lieu' => 'nullable|string|max:255',
            'prix' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'statut' => 'required|in:en_attente,confirmee,terminee',
            'est_paye' => 'boolean',
            'date_paiement' => 'nullable|date',
        ];

        if ($isDateButoire) {
            $rules['date_butoire'] = 'required|date|after_or_equal:today';
            // date_reservation et heure_reservation ne sont pas requis pour date_butoire
            $rules['date_reservation'] = 'nullable|date';
            $rules['heure_reservation'] = 'nullable|date_format:H:i';
            $rules['duree_minutes'] = 'nullable|integer|min:1';
        } else {
            $rules['date_reservation'] = 'required|date';
            $rules['heure_reservation'] = 'required|date_format:H:i';
            $rules['duree_minutes'] = 'required|integer|min:1';
        }

        $validated = $request->validate($rules);

        // Si user_id est fourni, vérifier que c'est bien un client
        if ($validated['user_id'] ?? null) {
            $client = User::where('id', $validated['user_id'])
                ->where('est_client', true)
                ->first();

            if (! $client) {
                return back()->withErrors(['user_id' => 'L\'utilisateur sélectionné n\'est pas un client.']);
            }
        }

        // Construire date/heure selon le type de structure
        if ($isDateButoire) {
            $dateButoire = $validated['date_butoire'];
            $dateTime = $dateButoire.' 00:00:00';
            $debutReservation = \Carbon\Carbon::parse($dateTime);
            $dureeMinutes = $typeService->duree_minutes;
        } else {
            $dateTime = $validated['date_reservation'].' '.$validated['heure_reservation'];
            $debutReservation = \Carbon\Carbon::parse($dateTime);
            $dureeMinutes = (int) $validated['duree_minutes'];
        }

        // Gérer la sélection du membre
        $membreId = null;
        if (! empty($validated['membre_id'])) {
            $membre = \App\Models\EntrepriseMembre::where('id', $validated['membre_id'])
                ->where('entreprise_id', $entreprise->id)
                ->where('est_actif', true)
                ->first();

            if (! $membre) {
                return back()->withErrors(['membre_id' => 'Membre invalide.']);
            }

            $membreId = $membre->id;
        }

        // Préparer les données de la réservation
        $reservationData = [
            'user_id' => $validated['user_id'] ?? null,
            'entreprise_id' => $entreprise->id,
            'membre_id' => $membreId,
            'date_reservation' => $dateTime,
            'date_fin' => $isDateButoire ? null : $debutReservation->copy()->addMinutes($dureeMinutes),
            'lieu' => $validated['lieu'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'prix' => $validated['prix'],
            'duree_minutes' => $dureeMinutes,
            'statut' => $validated['statut'],
            'creee_manuellement' => true,
            'est_paye' => $validated['est_paye'] ?? false,
            'date_paiement' => ($validated['est_paye'] ?? false) ? ($validated['date_paiement'] ?? now()) : null,
        ];

        if ($isDateButoire) {
            $reservationData['date_butoire'] = $validated['date_butoire'];
        }

        // Si cliente non inscrite, ajouter les informations
        if (! ($validated['user_id'] ?? null)) {
            $reservationData['nom_client'] = $validated['nom_client'];
            $reservationData['email_client'] = $validated['email_client'] ?? null;
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

        // Vérifier la disponibilité ET créer dans une transaction atomique (anti-doublon)
        // On ne vérifie le chevauchement que pour les dates futures, et on skip pour date_butoire et événements
        $skipCheck = $isDateButoire || $isEvenement || ! $debutReservation->isFuture();

        // Pour les événements, vérifier la capacité
        if ($isEvenement && $typeService && $typeService->capacite_max) {
            $disponible = \Illuminate\Support\Facades\DB::transaction(function () use ($entreprise, $typeService, $debutReservation) {
                return \App\Services\ReservationSlotService::estEvenementDisponible(
                    $entreprise->id,
                    $typeService->id,
                    $debutReservation,
                    $typeService->duree_minutes,
                    $typeService->capacite_max
                );
            });

            if (! $disponible) {
                return back()->withErrors(['error' => 'Cet événement est complet.']);
            }
        }

        $reservation = \App\Services\ReservationSlotService::reserverSiDisponible(
            $entreprise->id,
            $membreId,
            $debutReservation,
            $dureeMinutes,
            fn () => Reservation::create($reservationData),
            $skipCheck
        );

        if (! $reservation) {
            return back()->withErrors(['error' => 'Ce créneau est déjà réservé. Veuillez choisir un autre horaire.']);
        }

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
