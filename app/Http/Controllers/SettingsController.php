<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    /**
     * Afficher la page des paramètres
     */
    public function index()
    {
        $user = Auth::user();
        $entreprises = $user->entreprises()->with(['realisationPhotos', 'abonnements'])->get();

        // Récupérer les informations d'abonnement Stripe
        $subscription = $user->subscription('default');
        $stripeSubscription = null;
        $invoices = collect([]);
        
        if ($subscription && $subscription->valid() && $user->stripe_id) {
            try {
                $stripeSubscription = $subscription->asStripeSubscription();
                
                // Récupérer les factures Stripe
                $stripeInvoices = \Stripe\Invoice::all([
                    'customer' => $user->stripe_id,
                    'limit' => 12,
                ], ['api_key' => config('services.stripe.secret')]);
                
                $invoices = collect($stripeInvoices->data);
            } catch (\Exception $e) {
                // En cas d'erreur, on continue sans les données Stripe
            }
        }

        return view('settings.index', [
            'user' => $user,
            'entreprises' => $entreprises,
            'subscription' => $subscription,
            'stripeSubscription' => $stripeSubscription,
            'invoices' => $invoices,
        ]);
    }

    /**
     * Mettre à jour les informations du compte
     */
    public function updateAccount(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'photo_profil' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'ville' => ['nullable', 'string', 'max:255'],
            'code_postal' => ['nullable', 'string', 'max:10'],
        ]);

        // Gérer l'upload de la photo de profil (atomicité : upload d'abord, suppression ensuite)
        if ($request->hasFile('photo_profil')) {
            $photo = $request->file('photo_profil');
            $photoName = time() . '_' . Str::random(10) . '.' . $photo->getClientOriginalExtension();
            
            // 1. Uploader la nouvelle photo d'abord
            $photoPath = $photo->storeAs('profils', $photoName, 'public');
            
            // 2. Vérifier que l'upload a réussi
            if (!Storage::disk('public')->exists($photoPath)) {
                return back()->withErrors(['photo_profil' => 'Erreur lors de l\'upload de la photo.']);
            }
            
            // 3. Sauvegarder l'ancien chemin pour suppression après mise à jour
            $oldPhotoPath = $user->photo_profil;
            
            // 4. Mettre à jour avec le nouveau chemin
            $validated['photo_profil'] = $photoPath;
            
            // 5. Supprimer l'ancienne photo APRÈS la mise à jour réussie
            // (on le fait après la mise à jour pour garantir l'atomicité)
            if ($oldPhotoPath && Storage::disk('public')->exists($oldPhotoPath)) {
                try {
                    Storage::disk('public')->delete($oldPhotoPath);
                } catch (\Exception $e) {
                    // Log l'erreur mais ne bloque pas la mise à jour
                    \Log::warning('Erreur lors de la suppression de l\'ancienne photo de profil', [
                        'path' => $oldPhotoPath,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $user->update($validated);

        return redirect()->route('settings.index', ['tab' => 'account'])
            ->with('success', 'Vos informations de compte ont été mises à jour.');
    }

    /**
     * Mettre à jour le mot de passe
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'min:8', 'confirmed'],
        ]);

        // Vérifier le mot de passe actuel
        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Le mot de passe actuel est incorrect.']);
        }

        // Mettre à jour le mot de passe
        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return redirect()->route('settings.index', ['tab' => 'security'])
            ->with('success', 'Votre mot de passe a été mis à jour avec succès.');
    }

    /**
     * Mettre à jour les informations d'une entreprise
     */
    public function updateEntreprise(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'type_activite' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
            'mots_cles' => ['nullable', 'string', 'max:500'],
            'ville' => ['nullable', 'string', 'max:255'],
            'adresse_rue' => ['nullable', 'string', 'max:255'],
            'code_postal' => ['nullable', 'string', 'max:10'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'afficher_adresse_complete' => ['nullable'],
            'rayon_deplacement' => ['nullable', 'integer', 'min:0'],
            'siren' => ['nullable', 'string', 'max:9', 'regex:/^[0-9]{0,9}$/'],
            'status_juridique' => ['nullable', 'string', 'in:en_cours,auto_entrepreneur,sarl,eurl,sas'],
            'afficher_nom_gerant' => ['nullable'],
            'prix_negociables' => ['nullable'],
            'rdv_uniquement_messagerie' => ['nullable'],
            'site_web_externe' => ['nullable', 'url', 'max:255'],
        ]);

        // Générer un nouveau slug si le nom a changé
        if ($validated['nom'] !== $entreprise->nom) {
            $baseSlug = Str::slug($validated['nom']);
            $newSlug = $baseSlug;
            $counter = 1;
            
            while (Entreprise::where('slug', $newSlug)->where('id', '!=', $entreprise->id)->exists()) {
                $newSlug = $baseSlug . '-' . $counter;
                $counter++;
            }
            $validated['slug'] = $newSlug;
        }

        // Nettoyer et formater les mots-clés
        if (!empty($validated['mots_cles'])) {
            $motsClesArray = array_map('trim', explode(',', $validated['mots_cles']));
            $motsClesArray = array_filter($motsClesArray, function($mot) {
                return !empty($mot) && strlen($mot) >= 2;
            });
            $motsClesArray = array_unique($motsClesArray);
            $validated['mots_cles'] = implode(', ', $motsClesArray);
        }

        // Normaliser les valeurs des checkboxes (si non présentes, mettre à false)
        // Les checkboxes HTML envoient "1" quand cochées, rien quand non cochées
        $validated['afficher_nom_gerant'] = $request->has('afficher_nom_gerant') && $request->input('afficher_nom_gerant') == '1';
        $validated['prix_negociables'] = $request->has('prix_negociables') && $request->input('prix_negociables') == '1';
        $validated['rdv_uniquement_messagerie'] = $request->has('rdv_uniquement_messagerie') && $request->input('rdv_uniquement_messagerie') == '1';
        $validated['afficher_adresse_complete'] = $request->has('afficher_adresse_complete') && $request->input('afficher_adresse_complete') == '1';

        // Gérer les valeurs vides pour latitude/longitude
        if (empty($validated['latitude'])) {
            $validated['latitude'] = null;
        }
        if (empty($validated['longitude'])) {
            $validated['longitude'] = null;
        }

        $entreprise->update($validated);

        // Rediriger vers le dashboard de l'entreprise avec l'onglet paramètres
        return redirect()->route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'parametres'])
            ->with('success', 'Les informations de l\'entreprise ont été mises à jour.');
    }

    /**
     * Uploader le logo immédiatement (AJAX)
     */
    public function uploadLogo(Request $request, $slug)
    {
        try {
            $user = Auth::user();
            $entreprise = Entreprise::where('slug', $slug)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $validated = $request->validate([
                'logo' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            ]);

            $imageService = app(ImageService::class);

            // Atomicité : uploader d'abord, supprimer ensuite
            // 1. Uploader le nouveau logo
            $logoPath = $imageService->processAndStore($request->file('logo'), 'logos');
            
            // 2. Vérifier que l'upload a réussi
            if (!Storage::disk('public')->exists($logoPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'upload du logo.',
                ], 500);
            }
            
            // 3. Sauvegarder l'ancien chemin
            $oldLogoPath = $entreprise->logo;
            
            // 4. Mettre à jour avec le nouveau chemin
            $entreprise->update(['logo' => $logoPath]);
            
            // 5. Supprimer l'ancien logo APRÈS la mise à jour réussie
            if ($oldLogoPath) {
                try {
                    $imageService->delete($oldLogoPath);
                } catch (\Exception $e) {
                    // Log l'erreur mais ne bloque pas la mise à jour
                    \Log::warning('Erreur lors de la suppression de l\'ancien logo', [
                        'path' => $oldLogoPath,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Gérer les requêtes AJAX et les formulaires classiques
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Logo mis à jour avec succès.',
                    'logo_url' => asset('media/' . $logoPath),
                ]);
            }

            return redirect(route('entreprise.dashboard', ['slug' => $slug]) . '?tab=parametres')
                ->with('success', 'Logo mis à jour avec succès.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation.',
                    'errors' => $e->errors(),
                ], 422);
            }
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'upload du logo : ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'upload du logo : ' . $e->getMessage(),
                ], 500);
            }
            return back()->with('error', 'Erreur lors de l\'upload du logo : ' . $e->getMessage());
        }
    }

    /**
     * Supprimer le logo d'une entreprise
     */
    public function deleteLogo($slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $imageService = app(ImageService::class);
        if ($entreprise->logo) {
            $imageService->delete($entreprise->logo);
            $entreprise->update(['logo' => null]);
        }

        return redirect(route('entreprise.dashboard', ['slug' => $slug]) . '?tab=parametres')
            ->with('success', 'Le logo a été supprimé.');
    }

    /**
     * Uploader l'image de fond immédiatement (AJAX)
     */
    public function uploadImageFond(Request $request, $slug)
    {
        try {
            $user = Auth::user();
            $entreprise = Entreprise::where('slug', $slug)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $validated = $request->validate([
                'image_fond' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
            ]);

            $imageService = app(ImageService::class);

            // Atomicité : uploader d'abord, supprimer ensuite
            // 1. Uploader la nouvelle image de fond
            $imageFondPath = $imageService->processAndStore($request->file('image_fond'), 'images_fond');
            
            // 2. Vérifier que l'upload a réussi
            if (!Storage::disk('public')->exists($imageFondPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'upload de l\'image de fond.',
                ], 500);
            }
            
            // 3. Sauvegarder l'ancien chemin
            $oldImageFondPath = $entreprise->image_fond;
            
            // 4. Mettre à jour avec le nouveau chemin
            $entreprise->update(['image_fond' => $imageFondPath]);
            
            // 5. Supprimer l'ancienne image APRÈS la mise à jour réussie
            if ($oldImageFondPath) {
                try {
                    $imageService->delete($oldImageFondPath);
                } catch (\Exception $e) {
                    // Log l'erreur mais ne bloque pas la mise à jour
                    \Log::warning('Erreur lors de la suppression de l\'ancienne image de fond', [
                        'path' => $oldImageFondPath,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Gérer les requêtes AJAX et les formulaires classiques
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Image de fond mise à jour avec succès.',
                    'image_fond_url' => asset('media/' . $imageFondPath),
                ]);
            }

            return redirect(route('entreprise.dashboard', ['slug' => $slug]) . '?tab=parametres')
                ->with('success', 'Image de fond mise à jour avec succès.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation.',
                    'errors' => $e->errors(),
                ], 422);
            }
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'upload de l\'image de fond : ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'upload de l\'image de fond : ' . $e->getMessage(),
                ], 500);
            }
            return back()->with('error', 'Erreur lors de l\'upload de l\'image de fond : ' . $e->getMessage());
        }
    }

    /**
     * Supprimer l'image de fond d'une entreprise
     */
    public function deleteImageFond($slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $imageService = app(ImageService::class);
        if ($entreprise->image_fond) {
            $imageService->delete($entreprise->image_fond);
            $entreprise->update(['image_fond' => null]);
        }

        return redirect(route('entreprise.dashboard', ['slug' => $slug]) . '?tab=parametres')
            ->with('success', 'L\'image de fond a été supprimée.');
    }

    /**
     * Ajouter une photo de réalisation
     */
    public function addRealisationPhoto(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'], // 5MB max
            'titre' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $imageService = app(ImageService::class);
        $photoPath = $imageService->processAndStore($request->file('photo'), 'realisations');

        // Déterminer l'ordre (dernier + 1)
        $maxOrdre = $entreprise->realisationPhotos()->max('ordre') ?? 0;

        $entreprise->realisationPhotos()->create([
            'photo_path' => $photoPath,
            'titre' => $validated['titre'] ?? null,
            'description' => $validated['description'] ?? null,
            'ordre' => $maxOrdre + 1,
        ]);

        return redirect(route('entreprise.dashboard', ['slug' => $slug]) . '?tab=parametres')
            ->with('success', 'La photo a été ajoutée avec succès.');
    }

    /**
     * Supprimer une photo de réalisation
     */
    public function deleteRealisationPhoto($slug, $photoId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $photo = $entreprise->realisationPhotos()->findOrFail($photoId);

        $imageService = app(ImageService::class);
        if ($photo->photo_path) {
            $imageService->delete($photo->photo_path);
        }

        $photo->delete();

        return redirect(route('entreprise.dashboard', ['slug' => $slug]) . '?tab=parametres')
            ->with('success', 'La photo a été supprimée.');
    }

    /**
     * Mettre à jour les préférences de notifications d'erreurs (admin uniquement)
     */
    public function updateErrorNotifications(Request $request)
    {
        $user = Auth::user();

        if (!$user->is_admin) {
            return redirect()->route('settings.index')
                ->with('error', 'Accès refusé.');
        }

        // Vérifier si la colonne existe
        if (!Schema::hasColumn('users', 'notifications_erreurs_actives')) {
            return redirect()->route('settings.index', ['tab' => 'preferences'])
                ->with('error', 'La fonctionnalité n\'est pas encore disponible. Veuillez exécuter les migrations.');
        }

        $validated = $request->validate([
            'notifications_erreurs_actives' => ['required', 'boolean'],
        ]);

        $user->update($validated);

        return redirect()->route('settings.index', ['tab' => 'preferences'])
            ->with('success', 'Préférences de notifications mises à jour.');
    }


    /**
     * Archiver (supprimer) une entreprise
     */
    public function deleteEntreprise(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Vérifier si la suppression est possible
        if (!$entreprise->canBeArchived()) {
            return back()->with('error', 'Impossible de supprimer cette entreprise car elle possède des abonnements actifs.');
        }

        $entreprise->delete(); // Soft delete

        return redirect()->route('dashboard')
            ->with('success', 'Votre entreprise a été archivée. Vous avez 30 jours pour annuler cette action.');
    }

    /**
     * Restaurer une entreprise archivée
     */
    public function restoreEntreprise(Request $request, $slug)
    {
        $user = Auth::user();
        
        // Chercher parmi les entreprises supprimées (withTrashed)
        $entreprise = Entreprise::withTrashed()
            ->where('slug', $slug)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Vérifier si la restauration est possible par l'utilisateur
        if (!$entreprise->canBeRestoredByUser()) {
            return back()->with('error', 'Impossible de restaurer cette entreprise. Le délai de 30 jours est dépassé.');
        }

        $entreprise->restore();

        return redirect()->route('entreprise.dashboard', ['slug' => $entreprise->slug])
            ->with('success', 'Votre entreprise a été restaurée avec succès.');
    }
}
