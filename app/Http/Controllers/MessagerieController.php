<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Entreprise;
use App\Models\PropositionRendezVous;
use App\Models\Reservation;
use App\Models\Notification;
use App\Models\TypeService;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
                $queryClient->whereHas('entreprise', function($q) use ($search) {
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
                $queryGerant->where(function($q) use ($search) {
                    $q->whereHas('user', function($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('entreprise', function($entrepriseQuery) use ($search) {
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
     * Afficher ou créer une conversation avec une entreprise
     */
    public function show($slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        
        // Vérifier si une conversation existe déjà
        $conversation = Conversation::where('user_id', $user->id)
            ->where('entreprise_id', $entreprise->id)
            ->where('est_archivee', false)
            ->first();
        
        // Créer la conversation si elle n'existe pas
        if (!$conversation) {
            $conversation = Conversation::create([
                'user_id' => $user->id,
                'entreprise_id' => $entreprise->id,
            ]);
        }
        
        // Charger les messages avec leurs propositions
        $messages = $conversation->messages()
            ->with(['user', 'propositionRdv'])
            ->orderBy('created_at', 'asc')
            ->get();
        
        // Charger les propositions de rendez-vous actives
        $propositionActive = $conversation->propositionRendezVousActive();
        
        // Charger les prestations disponibles de l'entreprise
        $prestations = $entreprise->typesServices()
            ->where('est_actif', true)
            ->orderBy('nom')
            ->get();
        
        // Marquer les messages comme lus
        $conversation->messages()
            ->where('user_id', '!=', $user->id)
            ->where('est_lu', false)
            ->update(['est_lu' => true]);
        
        return view('messagerie.show', [
            'conversation' => $conversation,
            'entreprise' => $entreprise,
            'messages' => $messages,
            'propositionActive' => $propositionActive,
            'prestations' => $prestations ?? collect(),
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ]);
        
        // Vérifier qu'il y a au moins du contenu ou une image
        if (empty($validated['contenu']) && !$request->hasFile('image')) {
            return back()->withErrors(['error' => 'Vous devez envoyer un message ou une image.']);
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
            'contenu' => $validated['contenu'] ?? null,
            'image' => $imagePath,
            'est_lu' => false,
        ]);
        
        // Mettre à jour la date du dernier message
        $conversation->update([
            'dernier_message_at' => now(),
        ]);
        
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
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }
        
        $conversation = Conversation::where('id', $conversationId)
            ->where('entreprise_id', $entreprise->id)
            ->with(['user', 'entreprise'])
            ->firstOrFail();
        
        // Charger les messages avec leurs propositions
        $messages = $conversation->messages()
            ->with(['user', 'propositionRdv'])
            ->orderBy('created_at', 'asc')
            ->get();
        
        // Charger les propositions de rendez-vous actives
        $propositionActive = $conversation->propositionRendezVousActive();
        
        // Charger les prestations disponibles de l'entreprise
        $prestations = $entreprise->typesServices()
            ->where('est_actif', true)
            ->orderBy('nom')
            ->get();
        
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
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }
        
        $conversation = Conversation::where('id', $conversationId)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();
        
        $validated = $request->validate([
            'contenu' => 'nullable|string|max:2000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);
        
        if (empty($validated['contenu']) && !$request->hasFile('image')) {
            return back()->withErrors(['error' => 'Vous devez envoyer un message ou une image.']);
        }
        
        $imagePath = null;
        
        if ($request->hasFile('image')) {
            $imageService = app(ImageService::class);
            $imagePath = $imageService->processAndStore($request->file('image'), 'messages');
        }
        
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'contenu' => $validated['contenu'] ?? null,
            'image' => $imagePath,
            'est_lu' => false,
        ]);
        
        $conversation->update([
            'dernier_message_at' => now(),
        ]);
        
        return back()->with('success', 'Message envoyé !');
    }

    /**
     * Proposer un rendez-vous (pour les clients)
     */
    public function proposerRendezVousClient(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        
        // Récupérer ou créer la conversation
        $conversation = Conversation::where('user_id', $user->id)
            ->where('entreprise_id', $entreprise->id)
            ->where('est_archivee', false)
            ->first();
        
        if (!$conversation) {
            $conversation = Conversation::create([
                'user_id' => $user->id,
                'entreprise_id' => $entreprise->id,
                'est_archivee' => false,
            ]);
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
        if (!empty($validated['type_service_id'])) {
            $typeService = TypeService::where('id', $validated['type_service_id'])
                ->where('entreprise_id', $entreprise->id)
                ->where('est_actif', true)
                ->first();
            
            if (!$typeService) {
                return back()->withErrors(['error' => 'La prestation sélectionnée n\'est pas valide.']);
            }
        }

        // Calculer l'heure de fin
        $heureDebut = \Carbon\Carbon::parse($validated['date_rdv'] . ' ' . $validated['heure_debut']);
        $dureeMinutes = (int) $validated['duree_minutes'];
        $heureFin = $heureDebut->copy()->addMinutes($dureeMinutes);

        // Créer la proposition (liée à la réservation si la conversation est liée à une réservation)
        $proposition = PropositionRendezVous::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'reservation_id' => $conversation->reservation_id, // Lier à la réservation si présente
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
        $messageContenu = $conversation->reservation_id 
            ? "📝 Proposition de modification pour la réservation #{$conversation->reservation_id} : {$serviceNom} pour le {$validated['date_rdv']} à {$validated['heure_debut']} - Prix : {$validated['prix']} €"
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
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }
        
        $conversation = Conversation::where('id', $conversationId)
            ->where('entreprise_id', $entreprise->id)
            ->with(['user', 'reservation'])
            ->firstOrFail();

        $validated = $request->validate([
            'date_rdv' => 'required|date|after_or_equal:today',
            'heure_debut' => 'required|date_format:H:i',
            'duree_minutes' => 'required|integer|min:15|max:480',
            'prix' => 'required|numeric|min:0',
            'lieu' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Calculer l'heure de fin
        $heureDebut = \Carbon\Carbon::parse($validated['date_rdv'] . ' ' . $validated['heure_debut']);
        $dureeMinutes = (int) $validated['duree_minutes'];
        $heureFin = $heureDebut->copy()->addMinutes($dureeMinutes);

        // Créer la proposition (liée à la réservation si la conversation est liée à une réservation)
        $proposition = PropositionRendezVous::create([
            'conversation_id' => $conversation->id,
            'user_id' => $conversation->user_id,
            'entreprise_id' => $entreprise->id,
            'reservation_id' => $conversation->reservation_id, // Lier à la réservation si présente
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
            ->where('user_id', $user->id)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        if (!$proposition->peutEtreNegociee()) {
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

        $isClient = $proposition->user_id === $user->id;
        $isGerant = $entreprise->user_id === $user->id;

        if (!$isClient && !$isGerant) {
            return back()->withErrors(['error' => 'Vous n\'avez pas le droit d\'accepter cette proposition.']);
        }

        if ($proposition->statut === 'acceptee') {
            return back()->withErrors(['error' => 'Cette proposition a déjà été acceptée.']);
        }

        $dateTime = $proposition->date_rdv->format('Y-m-d') . ' ' . $proposition->heure_debut->format('H:i');
        $prixFinal = $proposition->prix_final ?? $proposition->prix_propose;

        // Si la proposition est liée à une réservation existante, la mettre à jour
        if ($proposition->reservation_id) {
            $reservation = Reservation::where('id', $proposition->reservation_id)
                ->where('entreprise_id', $entreprise->id)
                ->firstOrFail();
            
            // Mettre à jour la réservation existante
            $reservation->update([
                'date_reservation' => $dateTime,
                'lieu' => $proposition->lieu ?? $reservation->lieu,
                'prix' => $prixFinal,
                'duree_minutes' => $proposition->duree_minutes,
                'statut' => 'confirmee', // Confirmer la réservation modifiée
                'notes' => $proposition->notes ? ($reservation->notes ? $reservation->notes . "\n\n[Modifiée] " . $proposition->notes : $proposition->notes) : $reservation->notes,
            ]);
        } else {
            // Créer une nouvelle réservation
            $reservation = Reservation::create([
                'user_id' => $proposition->user_id,
                'entreprise_id' => $entreprise->id,
                'date_reservation' => $dateTime,
                'type_service' => 'Rendez-vous via messagerie',
                'lieu' => $proposition->lieu ?? null,
                'prix' => $prixFinal,
                'duree_minutes' => $proposition->duree_minutes,
                'statut' => 'confirmee', // Directement confirmée car acceptée dans la messagerie
                'notes' => $proposition->notes ?? null,
                'telephone_client' => $proposition->user->telephone ?? 'Non renseigné',
                'telephone_cache' => false,
            ]);
        }

        // Mettre à jour la proposition
        $proposition->update([
            'statut' => 'acceptee',
            'reservation_id' => $reservation->id,
            'prix_final' => $prixFinal,
        ]);

        // Créer un message de confirmation
        $isModification = $proposition->reservation_id !== null;
        $messageContenu = $isModification
            ? ($isClient 
                ? "✓ Modification acceptée ! La réservation #{$reservation->id} a été mise à jour : {$proposition->date_rdv->format('d/m/Y')} à {$proposition->heure_debut->format('H:i')} - Prix : {$prixFinal} €"
                : "✓ J'ai accepté votre proposition de modification pour la réservation #{$reservation->id} : {$proposition->date_rdv->format('d/m/Y')} à {$proposition->heure_debut->format('H:i')} - Prix : {$prixFinal} €")
            : ($isClient 
                ? "✓ Rendez-vous accepté ! Le rendez-vous est confirmé pour le {$proposition->date_rdv->format('d/m/Y')} à {$proposition->heure_debut->format('H:i')} - Prix : {$prixFinal} €"
                : "✓ J'ai accepté votre demande de rendez-vous pour le {$proposition->date_rdv->format('d/m/Y')} à {$proposition->heure_debut->format('H:i')} - Prix : {$prixFinal} €");
        
        $message = Message::create([
            'conversation_id' => $proposition->conversation_id,
            'user_id' => $user->id,
            'contenu' => $messageContenu,
            'est_lu' => false,
        ]);

        $proposition->conversation->update(['dernier_message_at' => now()]);

        // Notifier l'autre partie
        $autreUserId = $isClient ? $entreprise->user_id : $proposition->user_id;
        $isModification = $proposition->reservation_id !== null;
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
     * Refuser une proposition de rendez-vous
     */
    public function refuserProposition(Request $request, $slug, $propositionId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        
        $proposition = PropositionRendezVous::where('id', $propositionId)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $isClient = $proposition->user_id === $user->id;
        $isGerant = $entreprise->peutEtreGereePar($user) || $user->is_admin;

        if (!$isClient && !$isGerant) {
            return back()->withErrors(['error' => 'Vous n\'avez pas le droit de refuser cette proposition.']);
        }

        if ($proposition->statut === 'refusee' || $proposition->statut === 'acceptee') {
            return back()->withErrors(['error' => 'Cette proposition a déjà été traitée.']);
        }

        $validated = $request->validate([
            'raison' => 'nullable|string|max:500',
        ]);

        $proposition->update([
            'statut' => 'refusee',
            'notes' => ($proposition->notes ?? '') . ($validated['raison'] ? "\n\n[Raison du refus] " . $validated['raison'] : ''),
        ]);

        // Créer un message de refus
        $message = Message::create([
            'conversation_id' => $proposition->conversation_id,
            'user_id' => $user->id,
            'contenu' => "✗ Proposition de rendez-vous refusée" . ($validated['raison'] ? " : {$validated['raison']}" : ''),
            'est_lu' => false,
        ]);

        $proposition->conversation->update(['dernier_message_at' => now()]);

        // Notifier l'autre partie
        $autreUserId = $isClient ? $entreprise->user_id : $proposition->user_id;
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

        return back()->with('success', 'Proposition refusée.');
    }

    /**
     * Vérifier s'il y a de nouveaux messages (API)
     */
    public function checkNewMessages(Request $request)
    {
        $user = Auth::user();
        $conversationId = $request->get('conversation_id');
        $lastMessageId = $request->get('last_id', 0);

        if (!$conversationId) {
            return response()->json(['has_new' => false]);
        }

        $conversation = Conversation::find($conversationId);
        
        if (!$conversation) {
            return response()->json(['has_new' => false]);
        }

        // Vérifier que l'utilisateur a accès à cette conversation
        $hasAccess = false;
        if ($user->est_client && $conversation->user_id === $user->id) {
            $hasAccess = true;
        } elseif ($user->est_gerant) {
            $hasAccess = $user->entreprises()->where('id', $conversation->entreprise_id)->exists();
        }

        if (!$hasAccess) {
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
}
