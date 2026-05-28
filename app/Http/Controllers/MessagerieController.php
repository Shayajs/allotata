<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Entreprise;
use App\Models\Message;
use App\Models\Notification;
use App\Models\PropositionRendezVous;
use App\Models\Reservation;
use App\Models\TypeService;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class MessagerieController extends Controller
{
    /**
     * Afficher la liste des conversations
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Récupérer les conversations de l'utilisateur (client)
        $conversationsClient = collect([]);
        if ($user->est_client) {
            $queryClient = Conversation::where('user_id', $user->id)
                ->where('est_archivee', false)
                ->with(['entreprise', 'dernierMessage.user']);

            // Recherche
            if ($request->filled('search_client')) {
                $search = $request->search_client;
                $queryClient->whereHas('entreprise', function ($q) use ($search) {
                    $q->where('nom', 'like', "%{$search}%")
                        ->orWhere('type_activite', 'like', "%{$search}%")
                        ->orWhere('ville', 'like', "%{$search}%");
                });
            }

            $conversationsClient = $queryClient->orderBy('dernier_message_at', 'desc')->get();
        }

        // Récupérer les conversations des entreprises du gérant
        $conversationsGerant = collect([]);
        if ($user->est_gerant) {
            $entreprisesIds = $user->entreprises()->pluck('id');
            $queryGerant = Conversation::whereIn('entreprise_id', $entreprisesIds)
                ->where('est_archivee', false)
                ->with(['user', 'entreprise', 'dernierMessage.user']);

            // Recherche
            if ($request->filled('search_gerant')) {
                $search = $request->search_gerant;
                $queryGerant->where(function ($q) use ($search) {
                    $q->whereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                        ->orWhereHas('entreprise', function ($entrepriseQuery) use ($search) {
                            $entrepriseQuery->where('nom', 'like', "%{$search}%");
                        });
                });
            }

            $conversationsGerant = $queryGerant->orderBy('dernier_message_at', 'desc')->get();
        }

        return view('messagerie.index', [
            'conversationsClient' => $conversationsClient,
            'conversationsGerant' => $conversationsGerant,
        ]);
    }

    /**
     * Créer ou accéder à une conversation pour commander un produit
     */
    public function commanderProduit($slug, $produitId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        $produit = \App\Models\Produit::where('id', $produitId)
            ->where('entreprise_id', $entreprise->id)
            ->where('est_actif', true)
            ->firstOrFail();

        // Vérifier s'il existe déjà une conversation active pour ce produit
        $conversation = Conversation::where('user_id', $user->id)
            ->where('entreprise_id', $entreprise->id)
            ->where('produit_id', $produit->id)
            ->where('est_archivee', false)
            ->first();

        if (! $conversation) {
            // Archiver TOUTES les conversations actives entre cet utilisateur et cette entreprise
            Conversation::where('user_id', $user->id)
                ->where('entreprise_id', $entreprise->id)
                ->where('est_archivee', false)
                ->update(['est_archivee' => true]);

            // Créer une nouvelle conversation avec le contexte produit
            $conversation = Conversation::create([
                'user_id' => $user->id,
                'entreprise_id' => $entreprise->id,
                'produit_id' => $produit->id,
                'est_archivee' => false,
            ]);

            // Créer un message automatique pour informer de la demande
            $promotion = $produit->promotionActive()->first();
            $prixActuel = $promotion ? $promotion->prix_promotion : $produit->prix;
            $prixTexte = $promotion
                ? number_format($produit->prix, 2, ',', ' ').' € (Prix barré) '.number_format($prixActuel, 2, ',', ' ').' € (PROMO)'
                : number_format($prixActuel, 2, ',', ' ').' €';

            $messageContenu = '🛒 Bonjour, je souhaiterais commander : '.$produit->nom."\n\n";
            $messageContenu .= 'Prix : '.$prixTexte."\n\n";
            if ($produit->description) {
                $messageContenu .= 'Description : '.$produit->description."\n\n";
            }
            $messageContenu .= 'Merci de me confirmer la disponibilité et les modalités de commande.';

            \App\Models\Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'contenu' => $messageContenu,
                'est_lu' => false,
            ]);

            $conversation->update(['dernier_message_at' => now()]);
        }

        return redirect()->route('messagerie.show', $slug);
    }

    /**
     * Créer ou accéder à une conversation pour demander un service
     */
    public function demanderService($slug, $serviceId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        $service = \App\Models\TypeService::where('id', $serviceId)
            ->where('entreprise_id', $entreprise->id)
            ->where('est_actif', true)
            ->firstOrFail();

        // Vérifier s'il existe déjà une conversation active pour ce service
        $conversation = Conversation::where('user_id', $user->id)
            ->where('entreprise_id', $entreprise->id)
            ->where('type_service_id', $service->id)
            ->where('est_archivee', false)
            ->first();

        if (! $conversation) {
            // Archiver TOUTES les conversations actives entre cet utilisateur et cette entreprise
            // (pas seulement celles avec le même type_service_id)
            Conversation::where('user_id', $user->id)
                ->where('entreprise_id', $entreprise->id)
                ->where('est_archivee', false)
                ->update(['est_archivee' => true]);

            // Créer une nouvelle conversation avec le contexte service
            $conversation = Conversation::create([
                'user_id' => $user->id,
                'entreprise_id' => $entreprise->id,
                'type_service_id' => $service->id,
                'est_archivee' => false,
            ]);

            // Créer un message automatique pour informer de la demande
            $messageContenu = '📅 Bonjour, je souhaiterais réserver : '.$service->nom."\n\n";
            $messageContenu .= 'Prix : '.number_format($service->prix, 2, ',', ' ')." €\n";
            $messageContenu .= ($service->estDateButoire() ? 'Délai : ' : 'Durée : ').$service->duree_formatee."\n\n";
            if ($service->description) {
                $messageContenu .= 'Description : '.$service->description."\n\n";
            }
            $messageContenu .= 'Merci de me proposer des créneaux disponibles.';

            \App\Models\Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'contenu' => $messageContenu,
                'est_lu' => false,
            ]);

            $conversation->update(['dernier_message_at' => now()]);
        }

        return redirect()->route('messagerie.show', $slug);
    }

    /**
     * Afficher ou créer une conversation avec une entreprise
     */
    public function show($slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        // Vérifier si une conversation existe déjà (non archivée)
        $conversation = Conversation::where('user_id', $user->id)
            ->where('entreprise_id', $entreprise->id)
            ->where('est_archivee', false)
            ->first();

        // Créer la conversation si elle n'existe pas
        if (! $conversation) {
            $conversation = Conversation::create([
                'user_id' => $user->id,
                'entreprise_id' => $entreprise->id,
            ]);
        }

        // Charger la réservation si la colonne existe
        if (Schema::hasColumn('conversations', 'reservation_id') && $conversation->reservation_id) {
            $conversation->load(['reservation.typeService', 'reservation.user']);
        }

        // Charger les messages avec leurs propositions
        $messages = $conversation->messages()
            ->with(['user', 'propositionRdv.auteur', 'propositionRdv.entreprise', 'propositionRdv.reservation'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Charger les propositions de rendez-vous actives avec les relations nécessaires
        $propositionActive = $conversation->propositionRendezVousActive();
        if ($propositionActive) {
            $propositionActive->load(['user', 'entreprise.user', 'conversation.user', 'reservation']);
        }

        // Charger les prestations disponibles de l'entreprise
        $prestations = $entreprise->typesServices()
            ->where('est_actif', true)
            ->with(['images', 'imageCouverture'])
            ->orderBy('nom')
            ->get();

        // Charger les produits disponibles de l'entreprise
        $produits = $entreprise->produits()
            ->where('est_actif', true)
            ->with(['stock', 'images', 'imageCouverture', 'promotionActive'])
            ->get()
            ->filter(function ($produit) {
                return $produit->estDisponible();
            });

        // Marquer les messages comme lus
        $conversation->messages()
            ->where('user_id', '!=', $user->id)
            ->where('est_lu', false)
            ->update(['est_lu' => true]);

        return view('messagerie.show', [
            'conversation' => $conversation,
            'entreprise' => $entreprise,
            'messages' => $messages,
            'isGerant' => false, // C'est la vue client
            'propositionActive' => $propositionActive,
            'prestations' => $prestations ?? collect(),
            'produits' => $produits ?? collect(),
        ]);
    }

    /**
     * Envoyer un message
     */
    public function sendMessage(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'contenu' => 'nullable|string|max:2000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ]);

        $contenu = Message::sanitizeContenuForStorage($validated['contenu'] ?? null);

        // Vérifier qu'il y a au moins du contenu ou une image
        if ($contenu === null && ! $request->hasFile('image')) {
            $err = (! empty($validated['contenu']) && str_contains($validated['contenu'], 'data:image'))
                ? 'Coller une image dans le texte ne fonctionne pas. Utilisez le bouton photo à droite du champ, puis Envoyez.'
                : 'Vous devez envoyer un message ou une image.';

            return back()->withErrors(['error' => $err]);
        }

        // Récupérer ou créer la conversation
        $conversation = Conversation::firstOrCreate(
            [
                'user_id' => $user->id,
                'entreprise_id' => $entreprise->id,
            ],
            [
                'est_archivee' => false,
            ]
        );

        $imagePath = null;

        // Traiter l'image si présente
        if ($request->hasFile('image')) {
            $imageService = app(ImageService::class);
            $imagePath = $imageService->processAndStore($request->file('image'), 'messages');
        }

        // Créer le message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'contenu' => $contenu,
            'image' => $imagePath,
            'est_lu' => false,
        ]);

        // Mettre à jour la date du dernier message
        $conversation->update([
            'dernier_message_at' => now(),
        ]);

        $this->notifierDestinataireMessage($conversation, $message, $user, $entreprise, false);

        return back()->with('success', 'Message envoyé !');
    }

    /**
     * Afficher une conversation (pour les gérants)
     */
    public function showGerant($slug, $conversationId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();

        // Vérifier les permissions
        if (! $entreprise->peutEtreGereePar($user) && ! $user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $conversation = Conversation::where('id', $conversationId)
            ->where('entreprise_id', $entreprise->id)
            ->with(['user', 'entreprise'])
            ->firstOrFail();

        // Charger les relations du contexte
        if ($conversation->reservation_id) {
            $conversation->load(['reservation.typeService', 'reservation.user']);
        }
        if ($conversation->produit_id) {
            $conversation->load(['produit.stock', 'produit.images', 'produit.imageCouverture', 'produit.promotionActive']);
        }
        if ($conversation->type_service_id) {
            $conversation->load(['typeService.images', 'typeService.imageCouverture']);
        }

        // Charger les messages avec leurs propositions
        $messages = $conversation->messages()
            ->with(['user', 'propositionRdv.auteur', 'propositionRdv.entreprise', 'propositionRdv.reservation'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Charger les propositions de rendez-vous actives avec les relations nécessaires
        $propositionActive = $conversation->propositionRendezVousActive();
        if ($propositionActive) {
            $propositionActive->load(['user', 'entreprise.user', 'conversation.user', 'reservation']);
        }

        // Charger les prestations disponibles de l'entreprise
        $prestations = $entreprise->typesServices()
            ->where('est_actif', true)
            ->with(['images', 'imageCouverture'])
            ->orderBy('nom')
            ->get();

        // Charger les produits disponibles de l'entreprise
        $produits = $entreprise->produits()
            ->where('est_actif', true)
            ->with(['stock', 'images', 'imageCouverture', 'promotionActive'])
            ->get()
            ->filter(function ($produit) {
                return $produit->estDisponible();
            });

        // Marquer les messages comme lus
        $conversation->messages()
            ->where('user_id', '!=', $user->id)
            ->where('est_lu', false)
            ->update(['est_lu' => true]);

        return view('messagerie.show', [
            'conversation' => $conversation,
            'entreprise' => $entreprise,
            'messages' => $messages,
            'isGerant' => true,
            'propositionActive' => $propositionActive,
            'prestations' => $prestations ?? collect(),
            'produits' => $produits ?? collect(),
        ]);
    }

    /**
     * Envoyer un message (pour les gérants)
     */
    public function sendMessageGerant(Request $request, $slug, $conversationId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();

        // Vérifier les permissions
        if (! $entreprise->peutEtreGereePar($user) && ! $user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $conversation = Conversation::where('id', $conversationId)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $validated = $request->validate([
            'contenu' => 'nullable|string|max:2000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $contenu = Message::sanitizeContenuForStorage($validated['contenu'] ?? null);

        if ($contenu === null && ! $request->hasFile('image')) {
            $err = (! empty($validated['contenu']) && str_contains($validated['contenu'], 'data:image'))
                ? 'Coller une image dans le texte ne fonctionne pas. Utilisez le bouton photo à droite du champ, puis Envoyez.'
                : 'Vous devez envoyer un message ou une image.';

            return back()->withErrors(['error' => $err]);
        }

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imageService = app(ImageService::class);
            $imagePath = $imageService->processAndStore($request->file('image'), 'messages');
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'contenu' => $contenu,
            'image' => $imagePath,
            'est_lu' => false,
        ]);

        $conversation->update([
            'dernier_message_at' => now(),
        ]);

        $this->notifierDestinataireMessage($conversation, $message, $user, $entreprise, true);

        return back()->with('success', 'Message envoyé !');
    }

    /**
     * Notifie le destinataire (in-app + push ; email si activé dans les réglages).
     */
    private function notifierDestinataireMessage(
        Conversation $conversation,
        Message $message,
        $expediteur,
        Entreprise $entreprise,
        bool $expediteurEstGerant,
    ): void {
        try {
            $conversation->refresh();
            if ($expediteurEstGerant) {
                $recipient = $conversation->user;
                if (! $recipient || $recipient->id === $expediteur->id) {
                    return;
                }
                $lien = route('messagerie.show', $entreprise->slug);
            } else {
                $recipient = $entreprise->user;
                if (! $recipient || $recipient->id === $expediteur->id) {
                    return;
                }
                $lien = route('messagerie.show-gerant', [$entreprise->slug, $conversation->id]);
            }

            $preview = $message->contenu
                ? \Illuminate\Support\Str::limit(strip_tags($message->contenu), 120)
                : 'Image envoyée';

            app(\App\Services\UserNotificationService::class)->notifyNewMessage(
                $recipient,
                'Nouveau message — '.$entreprise->nom,
                $expediteur->name.' : '.$preview,
                $lien,
                [
                    'conversation_id' => $conversation->id,
                    'message_id' => $message->id,
                    'entreprise_slug' => $entreprise->slug,
                ],
                fn () => \App\Helpers\EmailHelper::sendNewMessage($message, $conversation),
            );
        } catch (\Exception $e) {
            \Log::error("Erreur notification nouveau message : ".$e->getMessage());
        }
    }

    /**
     * Proposer un rendez-vous (pour les clients)
     */
    public function proposerRendezVousClient(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        // Récupérer la conversation existante (archivée ou non)
        $conversation = Conversation::where('user_id', $user->id)
            ->where('entreprise_id', $entreprise->id)
            ->first();

        if (! $conversation) {
            // Créer une nouvelle conversation si elle n'existe pas
            $conversation = Conversation::create([
                'user_id' => $user->id,
                'entreprise_id' => $entreprise->id,
                'est_archivee' => false,
            ]);
        } elseif ($conversation->est_archivee) {
            // Si la conversation existe mais est archivée, la réactiver
            $conversation->update(['est_archivee' => false]);
        }

        $validated = $request->validate([
            'date_rdv' => 'required|date|after_or_equal:today',
            'heure_debut' => 'required|date_format:H:i',
            'duree_minutes' => 'required|integer|min:15|max:480',
            'prix' => 'required|numeric|min:0',
            'lieu' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'type_service_id' => 'nullable|exists:types_services,id',
        ]);

        // Vérifier que le type de service appartient à l'entreprise si fourni
        $typeService = null;
        if (! empty($validated['type_service_id'])) {
            $typeService = TypeService::where('id', $validated['type_service_id'])
                ->where('entreprise_id', $entreprise->id)
                ->where('est_actif', true)
                ->first();

            if (! $typeService) {
                return back()->withErrors(['error' => 'La prestation sélectionnée n\'est pas valide.']);
            }
        }

        // Calculer l'heure de fin
        $heureDebut = \Carbon\Carbon::parse($validated['date_rdv'].' '.$validated['heure_debut']);
        $dureeMinutes = (int) $validated['duree_minutes'];
        $heureFin = $heureDebut->copy()->addMinutes($dureeMinutes);

        // Créer la proposition (liée à la réservation si la conversation est liée à une réservation)
        $reservationId = null;
        if (\Schema::hasColumn('conversations', 'reservation_id') && $conversation->reservation_id) {
            $reservationId = $conversation->reservation_id;
        }

        $proposition = PropositionRendezVous::create([
            'conversation_id' => $conversation->id,
            'auteur_user_id' => $user->id,
            'auteur_type' => 'client', // Le client fait la proposition
            'entreprise_id' => $entreprise->id,
            'type_service_id' => isset($typeService) ? $typeService->id : null,
            'reservation_id' => $reservationId, // Lier à la réservation si présente
            'date_rdv' => $validated['date_rdv'],
            'heure_debut' => $heureDebut,
            'heure_fin' => $heureFin,
            'duree_minutes' => $dureeMinutes,
            'prix_propose' => $validated['prix'],
            'prix_final' => $validated['prix'],
            'statut' => 'proposee',
            'notes' => $validated['notes'] ?? null,
            'lieu' => $validated['lieu'] ?? null,
        ]);

        // Créer le message associé
        $serviceNom = isset($typeService) ? $typeService->nom : 'Service personnalisé';
        $messageContenu = $reservationId
            ? "📝 Proposition de modification pour la réservation #{$reservationId} : {$serviceNom} pour le {$validated['date_rdv']} à {$validated['heure_debut']} - Prix : {$validated['prix']} €"
            : "Proposition de rendez-vous : {$serviceNom} pour le {$validated['date_rdv']} à {$validated['heure_debut']} - Prix : {$validated['prix']} €";

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'type_message' => 'proposition_rdv',
            'proposition_rdv_id' => $proposition->id,
            'contenu' => $messageContenu,
            'est_lu' => false,
        ]);

        // Lier le message à la proposition
        $proposition->update(['message_id' => $message->id]);

        $conversation->update(['dernier_message_at' => now()]);

        // Créer une notification pour l'entreprise
        Notification::creer(
            $entreprise->user_id,
            'reservation',
            'Nouvelle proposition de rendez-vous',
            "{$user->name} vous propose un rendez-vous le {$validated['date_rdv']} à {$validated['heure_debut']} pour {$validated['prix']} €.",
            route('messagerie.show-gerant', [$entreprise->slug, $conversation->id]),
            ['conversation_id' => $conversation->id, 'proposition_id' => $proposition->id]
        );

        return back()->with('success', 'Votre proposition de rendez-vous a été envoyée !');
    }

    /**
     * Proposer un rendez-vous (pour les gérants)
     */
    public function proposerRendezVous(Request $request, $slug, $conversationId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();

        // Vérifier les permissions
        if (! $entreprise->peutEtreGereePar($user) && ! $user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $conversation = Conversation::where('id', $conversationId)
            ->where('entreprise_id', $entreprise->id)
            ->with(['user', 'entreprise'])
            ->firstOrFail();

        // Charger la réservation si la colonne existe
        if (Schema::hasColumn('conversations', 'reservation_id') && $conversation->reservation_id) {
            $conversation->load(['reservation.typeService', 'reservation.user']);
        }

        $validated = $request->validate([
            'date_rdv' => 'required|date|after_or_equal:today',
            'heure_debut' => 'required|date_format:H:i',
            'duree_minutes' => 'required|integer|min:15|max:480',
            'prix' => 'required|numeric|min:0',
            'lieu' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Calculer l'heure de fin
        $heureDebut = \Carbon\Carbon::parse($validated['date_rdv'].' '.$validated['heure_debut']);
        $dureeMinutes = (int) $validated['duree_minutes'];
        $heureFin = $heureDebut->copy()->addMinutes($dureeMinutes);

        // Récupérer le type de service si la réservation en a un
        $typeService = null;
        if ($conversation->reservation && $conversation->reservation->type_service_id) {
            $typeService = TypeService::where('id', $conversation->reservation->type_service_id)
                ->where('entreprise_id', $entreprise->id)
                ->where('est_actif', true)
                ->first();
        }

        // Créer la proposition (liée à la réservation si la conversation est liée à une réservation)
        // auteur_user_id = auteur de la proposition (ici le gérant)
        $proposition = PropositionRendezVous::create([
            'conversation_id' => $conversation->id,
            'auteur_user_id' => $user->id, // L'auteur est le gérant qui propose
            'auteur_type' => 'gerant', // Le gérant fait la proposition
            'entreprise_id' => $entreprise->id,
            'type_service_id' => $typeService ? $typeService->id : null,
            'reservation_id' => $conversation->reservation_id ?? null, // Lier à la réservation si présente
            'date_rdv' => $validated['date_rdv'],
            'heure_debut' => $heureDebut, // Format datetime complet
            'heure_fin' => $heureFin, // Format datetime complet
            'duree_minutes' => $dureeMinutes,
            'prix_propose' => $validated['prix'],
            'prix_final' => $validated['prix'],
            'statut' => 'proposee',
            'notes' => $validated['notes'] ?? null,
            'lieu' => $validated['lieu'] ?? null,
        ]);

        // Créer le message associé
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'type_message' => 'proposition_rdv',
            'proposition_rdv_id' => $proposition->id,
            'contenu' => "Proposition de rendez-vous pour le {$validated['date_rdv']} à {$validated['heure_debut']} - Prix : {$validated['prix']} €",
            'est_lu' => false,
        ]);

        // Lier le message à la proposition
        $proposition->update(['message_id' => $message->id]);

        $conversation->update(['dernier_message_at' => now()]);

        // Créer une notification pour le client
        Notification::creer(
            $conversation->user_id,
            'reservation',
            'Nouvelle proposition de rendez-vous',
            "{$entreprise->nom} vous propose un rendez-vous le {$validated['date_rdv']} à {$validated['heure_debut']} pour {$validated['prix']} €.",
            route('messagerie.show', $entreprise->slug),
            ['conversation_id' => $conversation->id, 'proposition_id' => $proposition->id]
        );

        return back()->with('success', 'Proposition de rendez-vous envoyée !');
    }

    /**
     * Négocier le prix d'une proposition (pour les clients)
     */
    public function negocierPrix(Request $request, $slug, $propositionId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        $proposition = PropositionRendezVous::where('id', $propositionId)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        // Vérifier que l'utilisateur fait partie de la conversation (client ou gérant)
        $estClient = $proposition->conversation->user_id === $user->id;
        $estGerant = $entreprise->user_id === $user->id;

        if (! $estClient && ! $estGerant) {
            return back()->withErrors(['error' => 'Vous n\'avez pas le droit de négocier cette proposition.']);
        }

        if (! $proposition->peutEtreNegociee()) {
            return back()->withErrors(['error' => 'Cette proposition ne peut pas être négociée.']);
        }

        $validated = $request->validate([
            'nouveau_prix' => 'required|numeric|min:0',
        ]);

        $proposition->update([
            'prix_final' => $validated['nouveau_prix'],
            'statut' => 'negociee',
        ]);

        // Créer un message de négociation
        $message = Message::create([
            'conversation_id' => $proposition->conversation_id,
            'user_id' => $user->id,
            'contenu' => "Proposition de prix modifiée : {$validated['nouveau_prix']} € (au lieu de {$proposition->prix_propose} €)",
            'est_lu' => false,
        ]);

        $proposition->conversation->update(['dernier_message_at' => now()]);

        // Notifier l'entreprise
        Notification::creer(
            $entreprise->user_id,
            'reservation',
            'Négociation de prix',
            "{$user->name} propose un nouveau prix : {$validated['nouveau_prix']} € pour le rendez-vous du {$proposition->date_rdv->format('d/m/Y')}.",
            route('messagerie.show-gerant', [$entreprise->slug, $proposition->conversation_id]),
            ['conversation_id' => $proposition->conversation_id, 'proposition_id' => $proposition->id]
        );

        return back()->with('success', 'Votre proposition de prix a été envoyée !');
    }

    /**
     * Modifier une proposition de réservation (côté client)
     */
    public function modifyPropositionClient(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        // Vérifier que l'entreprise autorise les négociations
        if (! $entreprise->prix_negociables) {
            return back()->withErrors(['error' => 'Cette entreprise n\'autorise pas les modifications de propositions par les clients.']);
        }

        $validated = $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
            'date_rdv' => 'required|date|after:now',
            'heure_debut' => 'required|date_format:H:i',
            'duree_minutes' => 'required|integer|min:15',
            'prix' => 'required|numeric|min:0',
            'lieu' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Vérifier que la réservation appartient au client
        $reservation = Reservation::where('id', $validated['reservation_id'])
            ->where('user_id', $user->id)
            ->where('entreprise_id', $entreprise->id)
            ->where('statut', 'en_attente')
            ->firstOrFail();

        // Récupérer ou créer la conversation
        // Chercher d'abord une conversation existante (archivée ou non)
        $conversation = Conversation::where('user_id', $user->id)
            ->where('entreprise_id', $entreprise->id)
            ->first();

        if (! $conversation) {
            // Créer une nouvelle conversation si elle n'existe pas
            $conversation = Conversation::create([
                'user_id' => $user->id,
                'entreprise_id' => $entreprise->id,
            ]);
        } elseif ($conversation->est_archivee) {
            // Si la conversation existe mais est archivée, la réactiver
            $conversation->update(['est_archivee' => false]);
        }

        // Lier la conversation à la réservation si la colonne existe
        if (Schema::hasColumn('conversations', 'reservation_id') && ! $conversation->reservation_id) {
            $conversation->update(['reservation_id' => $reservation->id]);
        }

        // Calculer l'heure de fin
        $heureDebut = \Carbon\Carbon::parse($validated['date_rdv'].' '.$validated['heure_debut']);
        $dureeMinutes = (int) $validated['duree_minutes'];
        $heureFin = $heureDebut->copy()->addMinutes($dureeMinutes);

        // Créer une nouvelle proposition
        $proposition = PropositionRendezVous::create([
            'conversation_id' => $conversation->id,
            'auteur_user_id' => $user->id,
            'auteur_type' => 'client', // Le client fait la proposition
            'entreprise_id' => $entreprise->id,
            'type_service_id' => $reservation->type_service_id,
            'reservation_id' => $reservation->id,
            'date_rdv' => $validated['date_rdv'],
            'heure_debut' => $heureDebut,
            'heure_fin' => $heureFin,
            'duree_minutes' => $dureeMinutes,
            'prix_propose' => $validated['prix'],
            'prix_final' => $validated['prix'],
            'statut' => 'proposee',
            'notes' => $validated['notes'] ?? null,
            'lieu' => $validated['lieu'] ?? null,
        ]);

        // Créer le message associé (message système pour la proposition)
        $messageContenu = "📝 Proposition de modification pour la réservation #{$reservation->id} : {$validated['date_rdv']} à {$validated['heure_debut']} - Durée : {$dureeMinutes} min - Prix : {$validated['prix']} €";

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'type_message' => 'proposition_rdv',
            'proposition_rdv_id' => $proposition->id,
            'contenu' => $messageContenu,
            'est_lu' => false,
        ]);

        $proposition->update(['message_id' => $message->id]);
        $conversation->update(['dernier_message_at' => now()]);

        // Notifier l'entreprise
        Notification::creer(
            $entreprise->user_id,
            'reservation',
            'Nouvelle proposition de modification',
            "{$user->name} propose une modification pour la réservation #{$reservation->id} : {$validated['date_rdv']} à {$validated['heure_debut']} - Prix : {$validated['prix']} €",
            route('messagerie.show-gerant', [$entreprise->slug, $conversation->id]),
            ['reservation_id' => $reservation->id, 'proposition_id' => $proposition->id]
        );

        return back()->with('success', 'Votre proposition de modification a été envoyée !');
    }

    /**
     * Modifier une proposition de réservation (côté gérant)
     */
    public function modifyPropositionGerant(Request $request, $slug, $conversationId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $conversation = Conversation::where('id', $conversationId)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $validated = $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
            'type_service_id' => 'nullable|exists:types_services,id',
            'date_rdv' => 'required|date|after:now',
            'heure_debut' => 'required|date_format:H:i',
            'duree_minutes' => 'required|integer|min:15',
            'prix' => 'required|numeric|min:0',
            'lieu' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Vérifier que la réservation appartient à cette entreprise
        $reservation = Reservation::where('id', $validated['reservation_id'])
            ->where('entreprise_id', $entreprise->id)
            ->where('statut', 'en_attente')
            ->firstOrFail();

        // Lier la conversation à la réservation si la colonne existe
        if (Schema::hasColumn('conversations', 'reservation_id') && ! $conversation->reservation_id) {
            $conversation->update(['reservation_id' => $reservation->id]);
        }

        // Récupérer le service si spécifié
        $typeService = null;
        if ($validated['type_service_id']) {
            $typeService = TypeService::where('id', $validated['type_service_id'])
                ->where('entreprise_id', $entreprise->id)
                ->where('est_actif', true)
                ->first();
        }

        // Calculer l'heure de fin
        $heureDebut = \Carbon\Carbon::parse($validated['date_rdv'].' '.$validated['heure_debut']);
        $dureeMinutes = (int) $validated['duree_minutes'];
        $heureFin = $heureDebut->copy()->addMinutes($dureeMinutes);

        // Créer une nouvelle proposition
        // auteur_user_id doit être celui qui fait la proposition (le gérant dans ce cas)
        $proposition = PropositionRendezVous::create([
            'conversation_id' => $conversation->id,
            'auteur_user_id' => $user->id, // Le gérant qui fait la proposition
            'auteur_type' => 'gerant', // Le gérant fait la proposition
            'entreprise_id' => $entreprise->id,
            'type_service_id' => $typeService ? $typeService->id : $reservation->type_service_id,
            'reservation_id' => $reservation->id,
            'date_rdv' => $validated['date_rdv'],
            'heure_debut' => $heureDebut,
            'heure_fin' => $heureFin,
            'duree_minutes' => $dureeMinutes,
            'prix_propose' => $validated['prix'],
            'prix_final' => $validated['prix'],
            'statut' => 'proposee',
            'notes' => $validated['notes'] ?? null,
            'lieu' => $validated['lieu'] ?? null,
        ]);

        // Créer le message associé (message système pour la proposition)
        $serviceNom = $typeService ? $typeService->nom : ($reservation->type_service ?? 'Service');
        $messageContenu = "📝 Proposition de modification pour la réservation #{$reservation->id} : {$serviceNom} le {$validated['date_rdv']} à {$validated['heure_debut']} - Durée : {$dureeMinutes} min - Prix : {$validated['prix']} €";

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'type_message' => 'proposition_rdv',
            'proposition_rdv_id' => $proposition->id,
            'contenu' => $messageContenu,
            'est_lu' => false,
        ]);

        $proposition->update(['message_id' => $message->id]);
        $conversation->update(['dernier_message_at' => now()]);

        // Notifier le client
        Notification::creer(
            $conversation->user_id,
            'reservation',
            'Nouvelle proposition de modification',
            "{$entreprise->nom} propose une modification pour votre réservation #{$reservation->id} : {$validated['date_rdv']} à {$validated['heure_debut']} - Prix : {$validated['prix']} €",
            route('messagerie.show', $entreprise->slug),
            ['reservation_id' => $reservation->id, 'proposition_id' => $proposition->id]
        );

        return back()->with('success', 'Votre proposition de modification a été envoyée !');
    }

    /**
     * Accepter une proposition de rendez-vous
     */
    public function accepterProposition(Request $request, $slug, $propositionId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        // Vérifier si c'est le client ou l'entreprise qui accepte
        $proposition = PropositionRendezVous::where('id', $propositionId)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        // Vérifier que l'utilisateur est le destinataire (pas l'auteur)
        if ($proposition->estAuteurPar($user)) {
            return back()->withErrors(['error' => 'Vous ne pouvez pas accepter votre propre proposition.']);
        }

        if (! $proposition->estDestinatairePar($user)) {
            return back()->withErrors(['error' => 'Vous n\'avez pas le droit d\'accepter cette proposition.']);
        }

        if ($proposition->statut === 'acceptee') {
            return back()->withErrors(['error' => 'Cette proposition a déjà été acceptée.']);
        }

        // Formater la date et l'heure correctement
        // heure_debut est stocké comme time dans la DB mais casté en datetime dans le modèle
        $heureDebutCarbon = \Carbon\Carbon::parse($proposition->heure_debut);
        $dateTime = $proposition->date_rdv->format('Y-m-d').' '.$heureDebutCarbon->format('H:i:s');
        $prixFinal = $proposition->prix_final ?? $proposition->prix_propose;

        // Si la proposition est liée à une réservation existante, la mettre à jour
        if ($proposition->reservation_id) {
            $reservation = Reservation::where('id', $proposition->reservation_id)
                ->where('entreprise_id', $entreprise->id)
                ->firstOrFail();

            // Mettre à jour la réservation existante
            $debutMsg = \Carbon\Carbon::parse($dateTime);
            $updateData = [
                'date_reservation' => $dateTime,
                'date_fin' => $debutMsg->copy()->addMinutes((int) $proposition->duree_minutes),
                'lieu' => $proposition->lieu ?? $reservation->lieu,
                'prix' => $prixFinal,
                'duree_minutes' => $proposition->duree_minutes,
                'statut' => 'confirmee', // Confirmer la réservation modifiée
                'notes' => $proposition->notes ? ($reservation->notes ? $reservation->notes."\n\n[Modifiée] ".$proposition->notes : $proposition->notes) : $reservation->notes,
            ];

            // Mettre à jour le type de service si spécifié dans la proposition
            if ($proposition->type_service_id) {
                $updateData['type_service_id'] = $proposition->type_service_id;
                // Charger le typeService pour obtenir le nom
                $proposition->load('typeService');
                if ($proposition->typeService) {
                    $updateData['type_service'] = $proposition->typeService->nom;
                }
            }

            $reservation->update($updateData);
        } else {
            // Créer une nouvelle réservation
            $debutMsg = \Carbon\Carbon::parse($dateTime);
            $reservation = Reservation::create([
                'user_id' => $proposition->conversation->user_id, // Le client de la conversation
                'entreprise_id' => $entreprise->id,
                'date_reservation' => $dateTime,
                'date_fin' => $debutMsg->copy()->addMinutes((int) $proposition->duree_minutes),
                'type_service' => 'Rendez-vous via messagerie',
                'lieu' => $proposition->lieu ?? null,
                'prix' => $prixFinal,
                'duree_minutes' => $proposition->duree_minutes,
                'statut' => 'confirmee', // Directement confirmée car acceptée dans la messagerie
                'notes' => $proposition->notes ?? null,
                'telephone_client' => ($proposition->auteur && $proposition->auteur_type === 'client') ? ($proposition->auteur->telephone ?? 'Non renseigné') : ($proposition->conversation->user->telephone ?? 'Non renseigné'),
                'telephone_cache' => false,
            ]);
        }

        // Mettre à jour la proposition acceptée
        $proposition->update([
            'statut' => 'acceptee',
            'reservation_id' => $reservation->id,
            'prix_final' => $prixFinal,
        ]);

        // Fermer toutes les autres propositions actives pour cette réservation/conversation
        // (pour empêcher toute nouvelle négociation une fois qu'une proposition est acceptée)
        PropositionRendezVous::where('conversation_id', $proposition->conversation_id)
            ->where('id', '!=', $proposition->id)
            ->whereIn('statut', ['proposee', 'negociee'])
            ->update(['statut' => 'refusee']);

        // Archiver la conversation pour qu'elle reprenne son cours normal
        // (plus de négociation active, la conversation n'apparaît plus dans les listes actives
        // mais reste accessible si on y accède directement - comme une conversation normale)
        $proposition->conversation->update(['est_archivee' => true]);

        // Note: La réservation est déjà confirmée (statut 'confirmee') dans le code ci-dessus,
        // ce qui empêche la création de nouvelles propositions via les conditions dans les vues
        // qui vérifient $reservation->statut === 'en_attente'

        // Déterminer si c'est le client ou le gérant qui accepte
        $isClient = $proposition->conversation->user_id === $user->id;
        $isGerant = $entreprise->user_id === $user->id;

        // Créer un message de confirmation
        $isModification = $proposition->reservation_id !== null;
        $heureDebutCarbon = \Carbon\Carbon::parse($proposition->heure_debut);
        $heureDebutFormatee = $heureDebutCarbon->format('H:i');

        $messageContenu = $isModification
            ? ($isClient
                ? "✓ Modification acceptée ! La réservation #{$reservation->id} a été mise à jour : {$proposition->date_rdv->format('d/m/Y')} à {$heureDebutFormatee} - Prix : {$prixFinal} €"
                : "✓ J'ai accepté votre proposition de modification pour la réservation #{$reservation->id} : {$proposition->date_rdv->format('d/m/Y')} à {$heureDebutFormatee} - Prix : {$prixFinal} €")
            : ($isClient
                ? "✓ Rendez-vous accepté ! Le rendez-vous est confirmé pour le {$proposition->date_rdv->format('d/m/Y')} à {$heureDebutFormatee} - Prix : {$prixFinal} €"
                : "✓ J'ai accepté votre demande de rendez-vous pour le {$proposition->date_rdv->format('d/m/Y')} à {$heureDebutFormatee} - Prix : {$prixFinal} €");

        $message = Message::create([
            'conversation_id' => $proposition->conversation_id,
            'user_id' => $user->id,
            'contenu' => $messageContenu,
            'est_lu' => false,
        ]);

        $proposition->conversation->update(['dernier_message_at' => now()]);

        // Notifier l'autre partie
        $autreUserId = $isClient ? $entreprise->user_id : ($proposition->auteur_user_id ?? $proposition->conversation->user_id);
        $notificationTitre = $isModification ? 'Modification de réservation acceptée' : 'Rendez-vous accepté';
        $notificationMessage = $isModification
            ? ($isClient
                ? "{$user->name} a accepté votre proposition de modification pour la réservation #{$reservation->id}."
                : "{$entreprise->nom} a accepté votre proposition de modification pour la réservation #{$reservation->id}.")
            : ($isClient
                ? "{$user->name} a accepté votre proposition de rendez-vous pour le {$proposition->date_rdv->format('d/m/Y')}."
                : "{$entreprise->nom} a accepté votre demande de rendez-vous pour le {$proposition->date_rdv->format('d/m/Y')}.");

        Notification::creer(
            $autreUserId,
            'reservation',
            $notificationTitre,
            $notificationMessage,
            route($isClient ? 'messagerie.show-gerant' : 'messagerie.show', $isClient ? [$entreprise->slug, $proposition->conversation_id] : $entreprise->slug),
            ['reservation_id' => $reservation->id, 'proposition_id' => $proposition->id]
        );

        $successMessage = $isModification
            ? 'Modification acceptée ! La réservation a été mise à jour avec succès.'
            : 'Rendez-vous accepté et créé avec succès !';

        return back()->with('success', $successMessage);
    }

    /**
     * Refuser une proposition de rendez-vous (pour les clients)
     */
    public function refuserProposition(Request $request, $slug, $propositionId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        $proposition = PropositionRendezVous::where('id', $propositionId)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        // Vérifier que l'utilisateur fait partie de la conversation (client ou gérant)
        $estClient = $proposition->conversation->user_id === $user->id;
        $estGerant = $entreprise->user_id === $user->id;

        if (! $estClient && ! $estGerant) {
            return back()->withErrors(['error' => 'Vous n\'avez pas le droit de refuser cette proposition.']);
        }

        // On peut refuser même si on est l'auteur (cas où on change d'avis)

        if ($proposition->statut === 'refusee' || $proposition->statut === 'acceptee') {
            return back()->withErrors(['error' => 'Cette proposition a déjà été traitée.']);
        }

        $validated = $request->validate([
            'raison' => 'nullable|string|max:500',
            'creer_contre_proposition' => 'nullable|boolean',
        ]);

        $proposition->update([
            'statut' => 'refusee',
            'notes' => ($proposition->notes ?? '').($validated['raison'] ? "\n\n[Raison du refus] ".$validated['raison'] : ''),
        ]);

        // Créer un message de refus
        $message = Message::create([
            'conversation_id' => $proposition->conversation_id,
            'user_id' => $user->id,
            'contenu' => '✗ Proposition de rendez-vous refusée'.($validated['raison'] ? " : {$validated['raison']}" : ''),
            'est_lu' => false,
        ]);

        $proposition->conversation->update(['dernier_message_at' => now()]);

        // Déterminer qui est le client et qui est le gérant
        $isClient = $proposition->conversation->user_id === $user->id;
        $isGerant = $entreprise->user_id === $user->id;

        // Notifier l'autre partie
        $autreUserId = $isClient ? $entreprise->user_id : ($proposition->auteur_user_id ?? $proposition->conversation->user_id);
        Notification::creer(
            $autreUserId,
            'reservation',
            'Proposition de rendez-vous refusée',
            $isClient
                ? "{$user->name} a refusé votre proposition de rendez-vous pour le {$proposition->date_rdv->format('d/m/Y')}."
                : "{$entreprise->nom} a refusé votre demande de rendez-vous pour le {$proposition->date_rdv->format('d/m/Y')}.",
            route($isClient ? 'messagerie.show-gerant' : 'messagerie.show', $isClient ? [$entreprise->slug, $proposition->conversation_id] : $entreprise->slug),
            ['proposition_id' => $proposition->id]
        );

        // Si l'utilisateur veut créer une contre-proposition, rediriger avec les données de la proposition
        if ($request->has('creer_contre_proposition') && $proposition->reservation_id) {
            $reservation = Reservation::find($proposition->reservation_id);
            if ($reservation && $reservation->statut === 'en_attente') {
                // Préparer les données de la proposition pour pré-remplir le formulaire
                // Utiliser les données de la proposition refusée (effet d'ancrage)
                $heureDebut = \Carbon\Carbon::parse($proposition->heure_debut);

                // Nettoyer les notes : enlever la raison du refus si elle a été ajoutée
                $notes = $proposition->notes ?? $reservation->notes ?? '';
                if (strpos($notes, '[Raison du refus]') !== false) {
                    $notes = trim(explode('[Raison du refus]', $notes)[0]);
                }

                return back()->with([
                    'success' => 'Proposition refusée.',
                    'open_contre_proposition' => true,
                    'contre_proposition_data' => [
                        'reservation_id' => $reservation->id,
                        'date' => $proposition->date_rdv->format('Y-m-d'),
                        'heure' => $heureDebut->format('H:i'),
                        'duree' => $proposition->duree_minutes,
                        'prix' => $proposition->prix_propose,
                        'lieu' => $proposition->lieu ?? $reservation->lieu ?? '',
                        'notes' => $notes,
                        'type_service_id' => $proposition->type_service_id ?? $reservation->type_service_id,
                    ],
                ]);
            }
        }

        return back()->with('success', 'Proposition refusée.');
    }

    /**
     * Refuser une proposition de rendez-vous (pour les gérants)
     */
    public function refuserPropositionGerant(Request $request, $slug, $conversationId, $propositionId)
    {
        // Rediriger vers la méthode principale avec les bons paramètres
        return $this->refuserProposition($request, $slug, $propositionId);
    }

    /**
     * Vérifier s'il y a de nouveaux messages (API)
     */
    public function checkNewMessages(Request $request)
    {
        $user = Auth::user();
        $conversationId = $request->get('conversation_id');
        $lastMessageId = $request->get('last_id', 0);

        if (! $conversationId) {
            return response()->json(['has_new' => false]);
        }

        $conversation = Conversation::find($conversationId);

        if (! $conversation) {
            return response()->json(['has_new' => false]);
        }

        // Vérifier que l'utilisateur a accès à cette conversation
        $hasAccess = false;
        if ($user->est_client && $conversation->user_id === $user->id) {
            $hasAccess = true;
        } elseif ($user->est_gerant) {
            $hasAccess = $user->entreprises()->where('id', $conversation->entreprise_id)->exists();
        }

        if (! $hasAccess) {
            return response()->json(['has_new' => false]);
        }

        // Vérifier s'il y a de nouveaux messages
        $newMessagesCount = Message::where('conversation_id', $conversationId)
            ->where('id', '>', $lastMessageId)
            ->where('user_id', '!=', $user->id)
            ->count();

        $lastMessage = Message::where('conversation_id', $conversationId)
            ->orderBy('id', 'desc')
            ->first();

        return response()->json([
            'has_new' => $newMessagesCount > 0,
            'last_message_id' => $lastMessage ? $lastMessage->id : $lastMessageId,
        ]);
    }

    /**
     * Récupérer les disponibilités et réservations pour une date (pour l'agenda dans la modale)
     */
    public function getAgendaForDate(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        // Vérifier les permissions (client ou gérant)
        $isClient = ! $entreprise->peutEtreGereePar($user) && ! $user->is_admin;
        $isGerant = $entreprise->peutEtreGereePar($user) || $user->is_admin;

        if (! $isClient && ! $isGerant) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'reservation_id' => 'nullable|exists:reservations,id', // Pour exclure la réservation en cours de modification
        ]);

        $date = \Carbon\Carbon::parse($validated['date']);

        // Utiliser le service ExceptionDateService pour récupérer les horaires applicables
        $exceptionDateService = app(\App\Services\ExceptionDateService::class);
        $horaires = $exceptionDateService->getHorairesForDate($entreprise, $date);
        $horaire = $horaires->first(); // Prendre le premier horaire (ou null si aucun)

        // Récupérer les réservations pour ce jour (exclure la réservation en cours de modification)
        $reservations = Reservation::where('entreprise_id', $entreprise->id)
            ->whereDate('date_reservation', $date->format('Y-m-d'))
            ->whereIn('statut', ['en_attente', 'confirmee', 'terminee'])
            ->when($validated['reservation_id'] ?? null, function ($q, $reservationId) {
                $q->where('id', '!=', $reservationId);
            })
            ->with(['user', 'typeService'])
            ->get()
            ->map(function ($reservation) {
                $debut = \Carbon\Carbon::parse($reservation->date_reservation);
                $fin = $debut->copy()->addMinutes((int) ($reservation->duree_minutes ?? 30));

                return [
                    'id' => $reservation->id,
                    'title' => ($reservation->typeService ? $reservation->typeService->nom : ($reservation->type_service ?? 'Réservation')).
                              ($reservation->user ? ' - '.$reservation->user->name : ''),
                    'start' => $debut->format('H:i'),
                    'end' => $fin->format('H:i'),
                    'start_datetime' => $debut->toIso8601String(),
                    'end_datetime' => $fin->toIso8601String(),
                    'statut' => $reservation->statut,
                    'color' => $reservation->statut === 'confirmee' ? '#10b981' : ($reservation->statut === 'en_attente' ? '#f59e0b' : '#6b7280'),
                ];
            });

        // Horaires d'ouverture
        $horaires = [
            'heure_ouverture' => $horaire && $horaire->heure_ouverture ? \Carbon\Carbon::parse($horaire->heure_ouverture)->format('H:i') : null,
            'heure_fermeture' => $horaire && $horaire->heure_fermeture ? \Carbon\Carbon::parse($horaire->heure_fermeture)->format('H:i') : null,
            'est_ferme' => ! $horaire || ! $horaire->heure_ouverture || ! $horaire->heure_fermeture,
        ];

        return response()->json([
            'date' => $date->format('Y-m-d'),
            'date_formatee' => $date->format('d/m/Y'),
            'jour_semaine' => $date->locale('fr')->dayName,
            'horaires' => $horaires,
            'reservations' => $reservations,
        ]);
    }

    /**
     * Vérifier les conflits pour une proposition
     */
    public function checkConflict(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'date' => 'required|date',
            'heure_debut' => 'required|date_format:H:i',
            'duree_minutes' => 'required|integer|min:15',
            'reservation_id' => 'nullable|exists:reservations,id',
        ]);

        $date = \Carbon\Carbon::parse($validated['date']);
        $heureDebut = $date->copy()->setTimeFromTimeString($validated['heure_debut']);
        $heureFin = $heureDebut->copy()->addMinutes((int) $validated['duree_minutes']);

        // Récupérer les réservations qui pourraient entrer en conflit
        $reservations = Reservation::where('entreprise_id', $entreprise->id)
            ->whereDate('date_reservation', $date->format('Y-m-d'))
            ->whereIn('statut', ['en_attente', 'confirmee'])
            ->when($validated['reservation_id'] ?? null, function ($q, $reservationId) {
                $q->where('id', '!=', $reservationId);
            })
            ->get();

        $conflits = [];
        foreach ($reservations as $reservation) {
            $debutReservation = \Carbon\Carbon::parse($reservation->date_reservation);
            $finReservation = $debutReservation->copy()->addMinutes((int) ($reservation->duree_minutes ?? 30));

            // Vérifier le chevauchement
            if ($heureDebut->lt($finReservation) && $heureFin->gt($debutReservation)) {
                $conflits[] = [
                    'id' => $reservation->id,
                    'title' => ($reservation->typeService ? $reservation->typeService->nom : ($reservation->type_service ?? 'Réservation')).
                              ($reservation->user ? ' - '.$reservation->user->name : ''),
                    'start' => $debutReservation->format('H:i'),
                    'end' => $finReservation->format('H:i'),
                    'statut' => $reservation->statut,
                ];
            }
        }

        return response()->json([
            'has_conflict' => count($conflits) > 0,
            'conflits' => $conflits,
        ]);
    }
}
