<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminConversation;
use App\Models\AdminMessage;
use App\Models\AdminMessageReaction;
use App\Models\AdminTypingIndicator;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class InternalMessagingController extends Controller
{
    /**
     * Afficher la liste des conversations
     */
    public function index()
    {
        $user = Auth::user();
        
        // Récupérer toutes les conversations où l'utilisateur est membre
        $conversations = AdminConversation::whereHas('members', function($query) use ($user) {
            $query->where('users.id', $user->id);
        })
        ->with(['dernierMessage.user', 'members'])
        ->orderBy('dernier_message_at', 'desc')
        ->get();

        // Récupérer tous les admins pour la création de nouvelles conversations
        $admins = User::where('is_admin', true)
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get();

        return view('admin.messagerie-interne.index', [
            'conversations' => $conversations,
            'admins' => $admins,
        ]);
    }

    /**
     * Afficher une conversation spécifique
     */
    public function show(AdminConversation $conversation)
    {
        $user = Auth::user();

        // Vérifier que l'utilisateur est membre de cette conversation
        if (!$conversation->isMember($user->id)) {
            abort(403, 'Vous n\'avez pas accès à cette conversation.');
        }

        // Charger les messages avec leurs relations
        $messages = $conversation->messages()
            ->with(['user', 'reactions.user'])
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Mettre à jour le dernier_vu_at
        $conversation->members()->updateExistingPivot($user->id, [
            'dernier_vu_at' => now(),
        ]);

        // Récupérer tous les admins pour la création de nouvelles conversations
        $admins = User::where('is_admin', true)
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get();

        return view('admin.messagerie-interne.show', [
            'conversation' => $conversation,
            'messages' => $messages,
            'admins' => $admins,
        ]);
    }

    /**
     * Créer ou obtenir une conversation avec un autre admin
     */
    public function createOrGetConversation(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $otherUserId = $validated['user_id'];

        // Vérifier que c'est un admin
        $otherUser = User::findOrFail($otherUserId);
        if (!$otherUser->is_admin) {
            return response()->json(['error' => 'L\'utilisateur n\'est pas un administrateur.'], 403);
        }

        if ($otherUserId == $user->id) {
            return response()->json(['error' => 'Vous ne pouvez pas créer une conversation avec vous-même.'], 400);
        }

        // Chercher une conversation existante entre ces deux admins (non-groupe)
        $conversation = AdminConversation::where('est_groupe', false)
            ->whereHas('members', function($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->whereHas('members', function($query) use ($otherUserId) {
                $query->where('users.id', $otherUserId);
            })
            ->whereDoesntHave('members', function($query) use ($user, $otherUserId) {
                $query->whereNotIn('users.id', [$user->id, $otherUserId]);
            })
            ->first();

        // Si pas de conversation, en créer une nouvelle
        if (!$conversation) {
            $conversation = AdminConversation::create([
                'est_groupe' => false,
            ]);

            // Ajouter les deux membres
            $conversation->addMember($user->id);
            $conversation->addMember($otherUserId);
        }

        return response()->json([
            'conversation' => $conversation->load(['members', 'dernierMessage.user']),
        ]);
    }

    /**
     * Envoyer un message
     */
    public function storeMessage(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'conversation_id' => 'required|exists:admin_conversations,id',
            'contenu' => 'nullable|string|max:5000',
            'type' => 'required|in:texte,image,video',
            'fichier' => 'nullable|string', // Chemin du fichier uploadé précédemment
        ]);

        $conversation = AdminConversation::findOrFail($validated['conversation_id']);

        // Vérifier que l'utilisateur est membre
        if (!$conversation->isMember($user->id)) {
            return response()->json(['error' => 'Vous n\'avez pas accès à cette conversation.'], 403);
        }

        // Vérifier qu'il y a au moins du contenu ou un fichier
        if (empty($validated['contenu']) && empty($validated['fichier'])) {
            return response()->json(['error' => 'Vous devez envoyer un message ou un fichier.'], 400);
        }

        // Créer le message
        $message = AdminMessage::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'contenu' => $validated['contenu'] ?? null,
            'type' => $validated['type'],
            'fichier' => $validated['fichier'] ?? null,
        ]);

        // Mettre à jour la date du dernier message
        $conversation->update([
            'dernier_message_at' => now(),
        ]);

        // Charger les relations
        $message->load(['user', 'reactions.user']);

        return response()->json([
            'message' => $message,
        ]);
    }

    /**
     * Récupérer les messages (polling)
     */
    public function getMessages(Request $request, AdminConversation $conversation)
    {
        $user = Auth::user();

        // Vérifier que l'utilisateur est membre
        if (!$conversation->isMember($user->id)) {
            return response()->json(['error' => 'Vous n\'avez pas accès à cette conversation.'], 403);
        }

        $afterId = $request->get('after_id', 0);

        // Récupérer les nouveaux messages
        $messages = AdminMessage::where('conversation_id', $conversation->id)
            ->where('id', '>', $afterId)
            ->with(['user', 'reactions.user'])
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Mettre à jour le dernier_vu_at
        $conversation->members()->updateExistingPivot($user->id, [
            'dernier_vu_at' => now(),
        ]);

        return response()->json([
            'messages' => $messages,
        ]);
    }

    /**
     * Mettre à jour l'indicateur de frappe
     */
    public function updateTyping(Request $request, AdminConversation $conversation)
    {
        $user = Auth::user();

        // Vérifier que l'utilisateur est membre
        if (!$conversation->isMember($user->id)) {
            return response()->json(['error' => 'Vous n\'avez pas accès à cette conversation.'], 403);
        }

        // Mettre à jour l'indicateur de frappe
        AdminTypingIndicator::updateTyping($conversation->id, $user->id);

        return response()->json(['success' => true]);
    }

    /**
     * Récupérer les utilisateurs en train d'écrire (polling)
     */
    public function getTyping(Request $request, AdminConversation $conversation)
    {
        $user = Auth::user();

        // Vérifier que l'utilisateur est membre
        if (!$conversation->isMember($user->id)) {
            return response()->json(['error' => 'Vous n\'avez pas accès à cette conversation.'], 403);
        }

        // Récupérer les utilisateurs en train d'écrire (exclure l'utilisateur actuel)
        $typingUsers = AdminTypingIndicator::getTypingUsers($conversation->id, $user->id);

        // Nettoyer les indicateurs anciens
        AdminTypingIndicator::cleanup();

        return response()->json([
            'typing_users' => $typingUsers->map(function($indicator) {
                return [
                    'id' => $indicator->user->id,
                    'name' => $indicator->user->name,
                ];
            }),
        ]);
    }

    /**
     * Ajouter une réaction à un message
     */
    public function addReaction(Request $request, AdminMessage $message)
    {
        $user = Auth::user();

        // Vérifier que l'utilisateur est membre de la conversation
        if (!$message->conversation->isMember($user->id)) {
            return response()->json(['error' => 'Vous n\'avez pas accès à cette conversation.'], 403);
        }

        $validated = $request->validate([
            'emoji' => 'required|string|max:10',
        ]);

        // Vérifier si la réaction existe déjà
        $reaction = AdminMessageReaction::where('message_id', $message->id)
            ->where('user_id', $user->id)
            ->where('emoji', $validated['emoji'])
            ->first();

        if ($reaction) {
            return response()->json(['error' => 'Vous avez déjà ajouté cette réaction.'], 400);
        }

        // Créer la réaction
        $reaction = AdminMessageReaction::create([
            'message_id' => $message->id,
            'user_id' => $user->id,
            'emoji' => $validated['emoji'],
        ]);

        $reaction->load('user');

        return response()->json([
            'reaction' => $reaction,
        ]);
    }

    /**
     * Supprimer une réaction
     */
    public function removeReaction(AdminMessage $message, AdminMessageReaction $reaction)
    {
        $user = Auth::user();

        // Vérifier que la réaction appartient au message
        if ($reaction->message_id != $message->id) {
            return response()->json(['error' => 'Réaction invalide.'], 400);
        }

        // Vérifier que c'est la réaction de l'utilisateur
        if ($reaction->user_id != $user->id) {
            return response()->json(['error' => 'Vous ne pouvez pas supprimer la réaction d\'un autre utilisateur.'], 403);
        }

        // Vérifier que l'utilisateur est membre de la conversation
        if (!$message->conversation->isMember($user->id)) {
            return response()->json(['error' => 'Vous n\'avez pas accès à cette conversation.'], 403);
        }

        $reaction->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Toggle une réaction (ajouter ou supprimer)
     */
    public function toggleReaction(Request $request, AdminMessage $message)
    {
        $user = Auth::user();

        // Vérifier que l'utilisateur est membre de la conversation
        if (!$message->conversation->isMember($user->id)) {
            return response()->json(['error' => 'Vous n\'avez pas accès à cette conversation.'], 403);
        }

        $validated = $request->validate([
            'emoji' => 'required|string|max:10',
        ]);

        // Chercher si la réaction existe déjà
        $reaction = AdminMessageReaction::where('message_id', $message->id)
            ->where('user_id', $user->id)
            ->where('emoji', $validated['emoji'])
            ->first();

        if ($reaction) {
            // Supprimer la réaction (toggle off)
            $reaction->delete();
            return response()->json([
                'action' => 'removed',
                'success' => true,
            ]);
        } else {
            // Ajouter la réaction (toggle on)
            $reaction = AdminMessageReaction::create([
                'message_id' => $message->id,
                'user_id' => $user->id,
                'emoji' => $validated['emoji'],
            ]);

            $reaction->load('user');

            return response()->json([
                'action' => 'added',
                'reaction' => $reaction,
            ]);
        }
    }

    /**
     * Modifier un message
     */
    public function updateMessage(Request $request, AdminMessage $message)
    {
        $user = Auth::user();

        // Vérifier que l'utilisateur est l'auteur du message
        if ($message->user_id != $user->id) {
            return response()->json(['error' => 'Vous ne pouvez modifier que vos propres messages.'], 403);
        }

        // Vérifier que l'utilisateur est membre de la conversation
        if (!$message->conversation->isMember($user->id)) {
            return response()->json(['error' => 'Vous n\'avez pas accès à cette conversation.'], 403);
        }

        $validated = $request->validate([
            'contenu' => 'required|string|max:5000',
        ]);

        // Mettre à jour le message
        $message->update([
            'contenu' => $validated['contenu'],
        ]);

        $message->load(['user', 'reactions.user']);

        return response()->json([
            'message' => $message,
        ]);
    }

    /**
     * Upload d'image ou vidéo
     */
    public function upload(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'fichier' => 'required|file|mimes:jpeg,png,jpg,gif,webp,mp4,webm|max:51200', // 50MB max
            'conversation_id' => 'required|exists:admin_conversations,id',
        ]);

        $conversation = AdminConversation::findOrFail($validated['conversation_id']);

        // Vérifier que l'utilisateur est membre
        if (!$conversation->isMember($user->id)) {
            return response()->json(['error' => 'Vous n\'avez pas accès à cette conversation.'], 403);
        }

        $file = $request->file('fichier');
        $mimeType = $file->getMimeType();

        // Déterminer le type (image ou video)
        $type = 'texte';
        if (str_starts_with($mimeType, 'image/')) {
            $type = 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            $type = 'video';
        } else {
            return response()->json(['error' => 'Type de fichier non supporté.'], 400);
        }

        // Stocker le fichier
        $path = $file->store('admin-messages/' . $conversation->id, 'public');

        // Pour les images, on peut utiliser ImageService pour optimiser
        if ($type === 'image') {
            try {
                $imageService = app(ImageService::class);
                $optimizedPath = $imageService->processAndStore($file, 'admin-messages/' . $conversation->id);
                if ($optimizedPath) {
                    // Supprimer le fichier original si l'optimisation a réussi
                    Storage::disk('public')->delete($path);
                    $path = $optimizedPath;
                }
            } catch (\Exception $e) {
                // Si l'optimisation échoue, continuer avec le fichier original
                \Log::warning('Erreur lors de l\'optimisation de l\'image: ' . $e->getMessage());
            }
        }

        return response()->json([
            'fichier' => $path,
            'type' => $type,
            'url' => Storage::disk('public')->url($path),
        ]);
    }
}
