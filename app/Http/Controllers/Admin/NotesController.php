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
        $notes = Note::with(['creator', 'updater'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

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

        // Émettre l'événement de création
        event(new \App\Events\UserJoinedNote($note, auth()->user()));

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

        $note->load(['creator', 'updater', 'collaborators.user', 'cursors.user']);

        // Émettre l'événement de connexion
        event(new \App\Events\UserJoinedNote($note, auth()->user()));

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

        // Émettre l'événement de suppression
        event(new \App\Events\UserLeftNote($note, auth()->user()));

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
}
