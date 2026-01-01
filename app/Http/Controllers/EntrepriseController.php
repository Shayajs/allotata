<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EntrepriseController extends Controller
{
    /**
     * Afficher le formulaire de création d'entreprise
     */
    public function create()
    {
        return view('entreprise.create');
    }

    /**
     * Enregistrer une nouvelle entreprise
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'type_activite' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
            'mots_cles' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'], // Max 2MB
            'ville' => ['nullable', 'string', 'max:255'],
            'rayon_deplacement' => ['nullable', 'integer', 'min:0'],
            'siren' => ['nullable', 'string', 'size:9', 'regex:/^[0-9]{9}$/'],
            'status_juridique' => ['nullable', 'string', 'in:en_cours,auto_entrepreneur,sarl,eurl,sas'],
        ]);

        // Générer un slug unique à partir du nom
        $baseSlug = Str::slug($validated['nom']);
        $slug = $baseSlug;
        $counter = 1;
        
        while (Entreprise::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        // Gérer l'upload du logo
        $logoPath = null;
        if ($request->hasFile('logo')) {
            $imageService = app(ImageService::class);
            $logoPath = $imageService->processAndStore($request->file('logo'), 'logos');
        }

        // Nettoyer et formater les mots-clés (séparés par virgules)
        $motsCles = null;
        if (!empty($validated['mots_cles'])) {
            // Séparer par virgules, nettoyer les espaces, supprimer les doublons
            $motsClesArray = array_map('trim', explode(',', $validated['mots_cles']));
            $motsClesArray = array_filter($motsClesArray, function($mot) {
                return !empty($mot) && strlen($mot) >= 2;
            });
            $motsClesArray = array_unique($motsClesArray);
            $motsCles = implode(', ', $motsClesArray);
        }

        // Créer l'entreprise
        $entreprise = Entreprise::create([
            'user_id' => Auth::id(),
            'nom' => $validated['nom'],
            'slug' => $slug,
            'type_activite' => $validated['type_activite'],
            'email' => $validated['email'],
            'telephone' => $validated['telephone'] ?? null,
            'description' => $validated['description'] ?? null,
            'mots_cles' => $motsCles,
            'logo' => $logoPath,
            'ville' => $validated['ville'] ?? null,
            'rayon_deplacement' => $validated['rayon_deplacement'] ?? 0,
            'siren' => $validated['siren'] ?? null,
            'status_juridique' => $validated['status_juridique'] ?? 'en_cours',
            'est_verifiee' => false, // Par défaut non vérifiée
        ]);

        // Mettre à jour le statut du user pour qu'il devienne gérant
        $user = Auth::user();
        if (!$user->est_gerant) {
            $user->update(['est_gerant' => true]);
        }

        return redirect()->route('dashboard')
            ->with('success', 'Votre entreprise a été créée avec succès ! Elle sera vérifiée avant d\'être visible publiquement.');
    }
}
