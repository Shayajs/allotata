<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Models\NoteCollaborator;
use App\Models\NoteCursor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotesController extends Controller
{
    /**
     * Liste des notes
     */
    public function index()
    {
        $notes = Note::with(['creator', 'updater', 'collaborators.user'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        // Pour chaque note, déterminer les collaborateurs actuellement présents
        // (dernière activité < 10 secondes)
        $notes->getCollection()->transform(function ($note) {
            $note->activeCollaborators = $note->collaborators->filter(function ($collaborator) {
                // Considérer comme actif si dernière activité < 10 secondes
                return $collaborator->derniere_activite && 
                       $collaborator->derniere_activite->gt(now()->subSeconds(10));
            })->map(function ($collaborator) {
                return $collaborator->user;
            });
            return $note;
        });

        return view('admin.notes.index', compact('notes'));
    }

    /**
     * Création d'une note
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'contenu_markdown' => 'nullable|string',
        ]);

        $note = Note::create([
            'titre' => $validated['titre'],
            'contenu_markdown' => $validated['contenu_markdown'] ?? '',
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        // Ajouter le créateur comme collaborateur
        NoteCollaborator::create([
            'note_id' => $note->id,
            'user_id' => auth()->id(),
            'derniere_activite' => now(),
        ]);

        // Note: Plus besoin d'émettre UserJoinedNote car Pusher gère automatiquement
        // la présence via pusher:member_added quand on se connecte au canal Presence

        return redirect()->route('admin.notes.show', $note)
            ->with('success', 'Note créée avec succès.');
    }

    /**
     * Affichage d'une note
     */
    public function show($note)
    {
        // Si 'new', créer une nouvelle note
        if ($note === 'new') {
            $note = Note::create([
                'titre' => 'Nouvelle Note',
                'contenu_markdown' => '',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
                'master_user_id' => auth()->id(), // Le créateur est le premier Master
            ]);

            NoteCollaborator::create([
                'note_id' => $note->id,
                'user_id' => auth()->id(),
                'derniere_activite' => now(),
            ]);

            return redirect()->route('admin.notes.show', $note);
        }

        // Sinon, charger la note existante
        $note = Note::findOrFail($note);

        // Ajouter l'utilisateur comme collaborateur s'il ne l'est pas déjà
        NoteCollaborator::firstOrCreate(
            [
                'note_id' => $note->id,
                'user_id' => auth()->id(),
            ],
            [
                'derniere_activite' => now(),
            ]
        );

        // Mettre à jour la dernière activité
        NoteCollaborator::where('note_id', $note->id)
            ->where('user_id', auth()->id())
            ->update(['derniere_activite' => now()]);

        $note->load(['creator', 'updater', 'collaborators.user', 'cursors.user', 'master']);

        // Note: Plus besoin d'émettre UserJoinedNote car Pusher gère automatiquement
        // la présence via pusher:member_added quand on se connecte au canal Presence
        // Cela évite aussi l'erreur "data content exceeds 10240 bytes" de Pusher

        return view('admin.notes.show', compact('note'));
    }

    /**
     * Mise à jour du contenu
     */
    public function update(Request $request, Note $note)
    {
        $validated = $request->validate([
            'titre' => 'sometimes|required|string|max:255',
            'contenu_markdown' => 'nullable|string',
        ]);

        $note->update([
            ...$validated,
            'updated_by' => auth()->id(),
        ]);

        // Mettre à jour la dernière activité du collaborateur
        NoteCollaborator::where('note_id', $note->id)
            ->where('user_id', auth()->id())
            ->update(['derniere_activite' => now()]);

        // Note: Plus besoin d'émettre NoteContentUpdated car la synchronisation
        // se fait maintenant via whisper events (client-client) pour les modifications
        // Seul le Master sauvegarde en base via HTTP

        return response()->json([
            'success' => true,
            'note' => $note->fresh(['updater']),
        ]);
    }

    /**
     * Suppression
     */
    public function destroy(Note $note)
    {
        $note->delete();

        // Note: Plus besoin d'émettre UserLeftNote car Pusher gère automatiquement
        // la présence via pusher:member_removed quand on quitte le canal Presence

        return redirect()->route('admin.notes.index')
            ->with('success', 'Note supprimée avec succès.');
    }

    /**
     * Mise à jour de la position du curseur
     */
    public function updateCursor(Request $request, Note $note)
    {
        $validated = $request->validate([
            'position' => 'required|integer|min:0',
            'selection_start' => 'nullable|integer|min:0',
            'selection_end' => 'nullable|integer|min:0',
        ]);

        $cursor = NoteCursor::updateOrCreate(
            [
                'note_id' => $note->id,
                'user_id' => auth()->id(),
            ],
            [
                'position' => $validated['position'],
                'selection_start' => $validated['selection_start'] ?? null,
                'selection_end' => $validated['selection_end'] ?? null,
                'updated_at' => now(),
            ]
        );

        // Émettre l'événement de mouvement du curseur
        event(new \App\Events\NoteCursorMoved($note, auth()->user(), $cursor));

        return response()->json([
            'success' => true,
            'cursor' => $cursor->load('user'),
        ]);
    }

    /**
     * Heartbeat : signaler qu'on est toujours là et mettre à jour le Master si nécessaire
     */
    public function heartbeat(Request $request, Note $note)
    {
        $user = auth()->user();
        $isMaster = $request->input('is_master', false);

        // Mettre à jour la dernière activité du collaborateur
        NoteCollaborator::where('note_id', $note->id)
            ->where('user_id', $user->id)
            ->update(['derniere_activite' => now()]);

        // Si l'utilisateur est Master selon lui, vérifier et mettre à jour en base
        if ($isMaster) {
            // Résolution de conflit : si un autre utilisateur est déjà Master,
            // utiliser l'ID le plus petit comme critère de départage
            if ($note->master_user_id !== $user->id && $note->master_user_id !== null) {
                $currentMasterId = $note->master_user_id;
                
                // Si l'utilisateur actuel a un ID plus petit, il devient Master
                // Sinon, on garde le Master actuel
                if ($user->id < $currentMasterId) {
                    \Log::info('🔄 [Résolution conflit Master] Changement de Master', [
                        'note_id' => $note->id,
                        'ancien_master' => $currentMasterId,
                        'nouveau_master' => $user->id,
                        'raison' => 'ID plus petit'
                    ]);
                    $note->update(['master_user_id' => $user->id]);
                    event(new \App\Events\MasterChanged($note, $user));
                } else {
                    // L'autre utilisateur reste Master (ID plus petit)
                    \Log::info('🔄 [Résolution conflit Master] Master conservé', [
                        'note_id' => $note->id,
                        'master_actuel' => $currentMasterId,
                        'utilisateur_requerant' => $user->id,
                        'raison' => 'ID plus petit'
                    ]);
                }
            } elseif ($note->master_user_id !== $user->id) {
                // Pas de Master actuel, on peut devenir Master
                $note->update(['master_user_id' => $user->id]);
                event(new \App\Events\MasterChanged($note, $user));
            }
        }

        return response()->json([
            'success' => true,
            'current_master_id' => $note->fresh()->master_user_id,
        ]);
    }

    /**
     * Mettre à jour le Master de la note (appelé lors d'un changement détecté)
     */
    public function updateMaster(Request $request, Note $note)
    {
        $newMasterId = $request->input('master_user_id');

        // Vérifier que l'utilisateur proposé est bien un collaborateur
        $isCollaborator = NoteCollaborator::where('note_id', $note->id)
            ->where('user_id', $newMasterId)
            ->exists();

        if (!$isCollaborator) {
            return response()->json([
                'success' => false,
                'message' => 'L\'utilisateur n\'est pas un collaborateur de cette note.',
            ], 403);
        }

        $masterUser = \App\Models\User::find($newMasterId);
        
        $note->update(['master_user_id' => $newMasterId]);
        event(new \App\Events\MasterChanged($note, $masterUser));

        return response()->json([
            'success' => true,
            'master_user_id' => $newMasterId,
            'master_user_name' => $masterUser->name,
        ]);
    }

    /**
     * Retirer un collaborateur inactif de la note (appelé par le Master)
     */
    public function removeInactiveCollaborator(Request $request, Note $note)
    {
        $userId = $request->input('user_id');
        $user = auth()->user();

        // Seul le Master peut retirer un collaborateur inactif
        if ($note->master_user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Seul le Master peut retirer un collaborateur inactif.',
            ], 403);
        }

        $removedUser = \App\Models\User::find($userId);

        // Retirer le collaborateur inactif
        NoteCollaborator::where('note_id', $note->id)
            ->where('user_id', $userId)
            ->delete();

        \Log::info('🗑️ Collaborateur inactif retiré de la note', [
            'note_id' => $note->id,
            'user_id' => $userId,
            'removed_by' => $user->id,
        ]);

        // Émettre un événement pour notifier que le collaborateur a quitté
        event(new \App\Events\UserLeftNote($note, $removedUser));

        return response()->json([
            'success' => true,
            'message' => 'Collaborateur inactif retiré avec succès.',
        ]);
    }

    /**
     * Quitter une note (déconnexion normale)
     */
    public function leave(Request $request, Note $note)
    {
        $user = auth()->user();

        // Retirer le collaborateur
        NoteCollaborator::where('note_id', $note->id)
            ->where('user_id', $user->id)
            ->delete();

        \Log::info('👋 Utilisateur a quitté la note', [
            'note_id' => $note->id,
            'user_id' => $user->id,
        ]);

        // Émettre un événement pour notifier que l'utilisateur a quitté
        event(new \App\Events\UserLeftNote($note, $user));

        return response()->json([
            'success' => true,
            'message' => 'Vous avez quitté la note avec succès.',
        ]);
    }
}
