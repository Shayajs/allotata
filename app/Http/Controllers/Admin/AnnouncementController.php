<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    /**
     * Liste des annonces
     */
    public function index()
    {
        $announcements = Announcement::with('createdBy')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.announcements.index', compact('announcements'));
    }

    /**
     * Formulaire de création
     */
    public function create()
    {
        return view('admin.announcements.create');
    }

    /**
     * Créer une annonce
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'type' => 'required|in:info,warning,success,danger',
            'cible' => 'required|in:tous,clients,gerants,admins',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'est_actif' => 'nullable|boolean',
            'afficher_banniere' => 'nullable|boolean',
        ]);

        $announcement = Announcement::create([
            ...$validated,
            'est_actif' => $validated['est_actif'] ?? true,
            'afficher_banniere' => $validated['afficher_banniere'] ?? true,
            'created_by' => auth()->id(),
        ]);

        ActivityLog::log('create', "Création de l'annonce: {$validated['titre']}", $announcement);

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Annonce créée avec succès.');
    }

    /**
     * Formulaire d'édition
     */
    public function edit(Announcement $announcement)
    {
        return view('admin.announcements.edit', compact('announcement'));
    }

    /**
     * Mettre à jour une annonce
     */
    public function update(Request $request, Announcement $announcement)
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'type' => 'required|in:info,warning,success,danger',
            'cible' => 'required|in:tous,clients,gerants,admins',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'est_actif' => 'nullable|boolean',
            'afficher_banniere' => 'nullable|boolean',
        ]);

        $announcement->update([
            ...$validated,
            'est_actif' => $validated['est_actif'] ?? false,
            'afficher_banniere' => $validated['afficher_banniere'] ?? false,
        ]);

        ActivityLog::log('update', "Modification de l'annonce: {$announcement->titre}", $announcement);

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Annonce mise à jour avec succès.');
    }

    /**
     * Supprimer une annonce
     */
    public function destroy(Announcement $announcement)
    {
        $titre = $announcement->titre;
        $announcement->delete();

        ActivityLog::log('delete', "Suppression de l'annonce: {$titre}");

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Annonce supprimée.');
    }
}
