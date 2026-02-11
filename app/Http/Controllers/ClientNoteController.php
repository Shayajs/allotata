<?php

namespace App\Http\Controllers;

use App\Models\ClientNote;
use App\Models\Entreprise;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientNoteController extends Controller
{
    /**
     * Afficher les notes d'un client
     */
    public function index(Request $request, $slug, $userId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $client = \App\Models\User::findOrFail($userId);
        
        $notes = ClientNote::where('entreprise_id', $entreprise->id)
            ->where('user_id', $userId)
            ->with('createdBy')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'notes' => $notes,
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
            ]
        ]);
    }

    /**
     * Créer une nouvelle note
     */
    public function store(Request $request, $slug, $userId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $validated = $request->validate([
            'note' => 'required|string|max:5000',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'is_important' => 'boolean',
        ]);

        $note = ClientNote::create([
            'entreprise_id' => $entreprise->id,
            'user_id' => $userId,
            'created_by_user_id' => $user->id,
            'note' => $validated['note'],
            'tags' => $validated['tags'] ?? [],
            'is_important' => $validated['is_important'] ?? false,
        ]);

        $note->load('createdBy');

        return response()->json([
            'success' => true,
            'note' => $note,
        ]);
    }

    /**
     * Mettre à jour une note
     */
    public function update(Request $request, $slug, $noteId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $note = ClientNote::where('id', $noteId)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $validated = $request->validate([
            'note' => 'required|string|max:5000',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'is_important' => 'boolean',
        ]);

        $note->update([
            'note' => $validated['note'],
            'tags' => $validated['tags'] ?? [],
            'is_important' => $validated['is_important'] ?? false,
        ]);

        $note->load('createdBy');

        return response()->json([
            'success' => true,
            'note' => $note,
        ]);
    }

    /**
     * Supprimer une note
     */
    public function destroy($slug, $noteId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $note = ClientNote::where('id', $noteId)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $note->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Obtenir toutes les notes pour une entreprise (avec filtres)
     */
    public function all(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $query = ClientNote::where('entreprise_id', $entreprise->id)
            ->with(['client', 'createdBy']);

        // Filtre par client
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filtre par tag
        if ($request->filled('tag')) {
            $query->whereJsonContains('tags', $request->tag);
        }

        // Filtre par important
        if ($request->filled('is_important')) {
            $query->where('is_important', $request->is_important);
        }

        $notes = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'notes' => $notes,
        ]);
    }
}
