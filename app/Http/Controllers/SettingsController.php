<?php

namespace App\Http\Controllers;

use App\Models\Echeance;
use App\Models\Entreprise;
use App\Models\StripeTransaction;
use App\Services\ImageService;
use App\Services\NavigationService;
use App\Services\NotificationPreferenceService;
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
        $user->load(['enfants', 'filleuls']);
        $entreprises = $user->entreprises()->with(['realisationPhotos', 'abonnements'])->get();

        // Récupérer les informations d'abonnement Stripe
        $subscription = $user->subscription('default');
        $stripeSubscription = null;
        $invoices = collect([]);

        if ($subscription && $subscription->valid() && $user->stripe_id) {
            try {
                $stripeSubscription = $subscription->asStripeSubscription();
            } catch (\Exception $e) {
                // Ignorer
            }
        }
        if ($user->stripe_id) {
            try {
                $stripeInvoices = \Stripe\Invoice::all([
                    'customer' => $user->stripe_id,
                    'limit' => 12,
                ], ['api_key' => config('services.stripe.secret')]);
                $invoices = collect($stripeInvoices->data)
                    ->filter(fn ($i) => ($i->status ?? '') === 'paid' && ($i->amount_paid ?? 0) > 0)
                    ->values();
            } catch (\Exception $e) {
                // En cas d'erreur, on continue sans les factures Stripe
            }
        }

        // Derniers paiements : Echeance payées + StripeTransaction (hors doublons échéance)
        $echeancesPayees = Echeance::where('user_id', $user->id)
            ->where('statut', Echeance::STATUT_PAYE)
            ->orderByDesc('paye_at')
            ->limit(20)
            ->get();
        $echeanceIds = $echeancesPayees->pluck('id')->all();
        $transactions = StripeTransaction::where('user_id', $user->id)
            ->where('processed', true)
            ->orderByDesc('processed_at')
            ->limit(30)
            ->get();
        $lastPayments = collect();
        foreach ($echeancesPayees as $e) {
            $amt = (float) ($e->montant_final ?? $e->montant_du ?? 0);
            if ($amt <= 0) {
                continue;
            }
            $lastPayments->push((object) [
                'type' => 'echeance',
                'id' => $e->id,
                'date' => $e->paye_at ?? $e->updated_at,
                'amount' => $amt,
                'currency' => 'eur',
                'label' => $e->libelle(),
            ]);
        }
        foreach ($transactions as $t) {
            $meta = $t->metadata ?? [];
            $eid = (int) ($meta['echeance_id'] ?? 0);
            if ($eid && in_array($eid, $echeanceIds, true)) {
                continue;
            }
            $amount = (float) ($t->amount ?? 0);
            if ($amount <= 0) {
                continue;
            }
            $lastPayments->push((object) [
                'type' => 'transaction',
                'id' => $t->id,
                'date' => $t->processed_at ?? $t->created_at,
                'amount' => $amount,
                'currency' => $t->currency ?? 'eur',
                'label' => $t->description ?: 'Paiement',
            ]);
        }
        $lastPayments = $lastPayments->sortByDesc(fn ($p) => $p->date)->take(15)->values();

        // Prochaines échéances (a_payer, en_attente)
        $upcomingEcheances = Echeance::where('user_id', $user->id)
            ->whereIn('statut', [Echeance::STATUT_A_PAYER, Echeance::STATUT_EN_ATTENTE])
            ->requiringUserPayment($user)
            ->orderBy('periode_fin')
            ->get();

        // Données RGPD pour l'onglet Confidentialité
        $gdprData = \App\Http\Controllers\GdprController::getGdprDataForUser($user->id);

        $notificationChannelPrefs = app(NotificationPreferenceService::class)->allForUser($user);

        return view('settings.index', [
            'user' => $user,
            'notificationChannelPrefs' => $notificationChannelPrefs,
            'entreprises' => $entreprises,
            'subscription' => $subscription,
            'stripeSubscription' => $stripeSubscription,
            'invoices' => $invoices,
            'lastPayments' => $lastPayments,
            'upcomingEcheances' => $upcomingEcheances,
            'gdprData' => $gdprData,
            'navItems' => NavigationService::getSettingsItems($user, [
                'entreprises_count' => $entreprises->count(),
            ]),
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
            'surname' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'photo_profil' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'ville' => ['nullable', 'string', 'max:255'],
            'code_postal' => ['nullable', 'string', 'max:10'],
            // Enrichissement profil
            'genre' => ['nullable', 'in:homme,femme,non_precise'],
            'langue_preferee' => ['nullable', 'string', 'max:5'],
            'urgence_nom' => ['nullable', 'string', 'max:255'],
            'urgence_telephone' => ['nullable', 'string', 'max:20'],
            'allergies_notes' => ['nullable', 'string', 'max:2000'],
            'notes_prestataires' => ['nullable', 'string', 'max:2000'],
            'pref_prestataire_genre' => ['nullable', 'in:homme,femme,indifferent'],
            'pref_prestataire_experience_min' => ['nullable', 'integer', 'min:0', 'max:50'],
        ]);

        // Gérer les checkboxes de préférences horaires (absent = false)
        $validated['pref_horaire_matin'] = $request->has('pref_horaire_matin');
        $validated['pref_horaire_apres_midi'] = $request->has('pref_horaire_apres_midi');
        $validated['pref_horaire_soir'] = $request->has('pref_horaire_soir');
        $validated['pref_horaire_weekend'] = $request->has('pref_horaire_weekend');

        // Construire le nom complet pour la compatibilité (name = prénom + nom de famille)
        $fullName = trim($validated['name']);
        if (! empty($validated['surname'])) {
            $fullName = trim($validated['name']).' '.trim($validated['surname']);
        }
        $validated['name'] = $fullName;

        // Gérer l'upload de la photo de profil (atomicité : upload d'abord, suppression ensuite)
        if ($request->hasFile('photo_profil')) {
            $photo = $request->file('photo_profil');
            $photoName = time().'_'.Str::random(10).'.'.$photo->getClientOriginalExtension();

            // 1. Uploader la nouvelle photo d'abord
            $photoPath = $photo->storeAs('profils', $photoName, 'public');

            // 2. Vérifier que l'upload a réussi
            if (! Storage::disk('public')->exists($photoPath)) {
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
        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Le mot de passe actuel est incorrect.']);
        }

        // Mettre à jour le mot de passe
        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        // Déverrouiller le compte si il était verrouillé
        if ($user->accountLockout) {
            $user->accountLockout->unlock();
        }

        // Logger l'événement
        \App\Models\SecurityLog::log(
            $user->id,
            'password_changed',
            $request->ip(),
            $request->userAgent(),
            null,
            [],
            'medium',
            false
        );

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

        $entreprise = app(\App\Services\EntrepriseProfileService::class)->update($entreprise, $request);

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

            $request->validate([
                'logo' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
            ]);

            $result = app(\App\Services\EntrepriseProfileService::class)->uploadLogo($entreprise, $request->file('logo'));

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Logo mis à jour avec succès.',
                    'logo_url' => $result['logo_url'],
                ]);
            }

            return redirect(route('entreprise.dashboard', ['slug' => $entreprise->fresh()->slug]).'?tab=parametres')
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
            \Log::error('Erreur lors de l\'upload du logo : '.$e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'upload du logo : '.$e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Erreur lors de l\'upload du logo : '.$e->getMessage());
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

        app(\App\Services\EntrepriseProfileService::class)->deleteLogo($entreprise);

        return redirect(route('entreprise.dashboard', ['slug' => $slug]).'?tab=parametres')
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

            $request->validate([
                'image_fond' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
            ]);

            $result = app(\App\Services\EntrepriseProfileService::class)->uploadImageFond($entreprise, $request->file('image_fond'));

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Image de fond mise à jour avec succès.',
                    'image_fond_url' => $result['image_fond_url'],
                ]);
            }

            return redirect(route('entreprise.dashboard', ['slug' => $entreprise->fresh()->slug]).'?tab=parametres')
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
            \Log::error('Erreur lors de l\'upload de l\'image de fond : '.$e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'upload de l\'image de fond : '.$e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Erreur lors de l\'upload de l\'image de fond : '.$e->getMessage());
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

        app(\App\Services\EntrepriseProfileService::class)->deleteImageFond($entreprise);

        return redirect(route('entreprise.dashboard', ['slug' => $slug]).'?tab=parametres')
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
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
            'titre' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        app(\App\Services\EntrepriseProfileService::class)->addRealisationPhoto(
            $entreprise,
            $request->file('photo'),
            $validated['titre'] ?? null,
            $validated['description'] ?? null,
        );

        return redirect(route('entreprise.dashboard', ['slug' => $slug]).'?tab=parametres')
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

        app(\App\Services\EntrepriseProfileService::class)->deleteRealisationPhoto($entreprise, (int) $photoId);

        return redirect(route('entreprise.dashboard', ['slug' => $slug]).'?tab=parametres')
            ->with('success', 'La photo a été supprimée.');
    }

    /**
     * Mettre à jour les préférences de notifications d'erreurs (admin uniquement)
     */
    public function updateErrorNotifications(Request $request)
    {
        $user = Auth::user();

        if (! $user->is_admin) {
            return redirect()->route('settings.index')
                ->with('error', 'Accès refusé.');
        }

        // Vérifier si la colonne existe
        if (! Schema::hasColumn('users', 'notifications_erreurs_actives')) {
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
     * Mettre à jour les préférences de confidentialité
     */
    public function updateConfidentialite(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'tracking_consent' => ['nullable', 'boolean'],
        ]);

        // Par défaut à true si non fourni (slider activé par défaut)
        $user->update([
            'tracking_consent' => $validated['tracking_consent'] ?? true,
        ]);

        return redirect()->route('settings.index', ['tab' => 'confidentialite'])
            ->with('success', 'Vos préférences de confidentialité ont été mises à jour.');
    }

    /**
     * Mettre à jour le paramètre d'interblocage entre entreprises
     */
    public function updateInterblocage(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'interbloquer_entreprises' => ['required', 'boolean'],
        ]);

        $user->update($validated);

        return redirect()->route('settings.index', ['tab' => 'preferences'])
            ->with('success', 'Paramètre d\'interblocage mis à jour.');
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
        if (! $entreprise->canBeArchived()) {
            return back()->with('error', 'Impossible de supprimer cette entreprise car elle possède des abonnements actifs.');
        }

        $entreprise->delete(); // Soft delete

        return redirect()->route('dashboard')
            ->with('success', 'Votre entreprise a été archivée. Vous avez 30 jours pour annuler cette action.');
    }

    /**
     * Mettre à jour les préférences de notifications
     */
    public function updateNotificationPreferences(Request $request)
    {
        $user = Auth::user();

        $matrix = $request->input('notif', []);
        if (! is_array($matrix)) {
            $matrix = [];
        }

        app(NotificationPreferenceService::class)->saveForUser($user, $matrix);

        return redirect()->route('settings.index', ['tab' => 'notifications'])
            ->with('success', 'Préférences de notifications mises à jour.');
    }

    /**
     * Ajouter un enfant
     */
    public function storeEnfant(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'prenom' => ['required', 'string', 'max:255'],
            'date_naissance' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $user->enfants()->create($validated);

        return redirect()->route('settings.index', ['tab' => 'account'])
            ->with('success', 'Enfant ajouté avec succès.');
    }

    /**
     * Mettre à jour un enfant
     */
    public function updateEnfant(Request $request, int $id)
    {
        $user = Auth::user();
        $enfant = $user->enfants()->findOrFail($id);

        $validated = $request->validate([
            'prenom' => ['required', 'string', 'max:255'],
            'date_naissance' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $enfant->update($validated);

        return redirect()->route('settings.index', ['tab' => 'account'])
            ->with('success', 'Informations de l\'enfant mises à jour.');
    }

    /**
     * Supprimer un enfant
     */
    public function destroyEnfant(int $id)
    {
        $user = Auth::user();
        $enfant = $user->enfants()->findOrFail($id);
        $enfant->delete();

        return redirect()->route('settings.index', ['tab' => 'account'])
            ->with('success', 'Enfant supprimé.');
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
        if (! $entreprise->canBeRestoredByUser()) {
            return back()->with('error', 'Impossible de restaurer cette entreprise. Le délai de 30 jours est dépassé.');
        }

        $entreprise->restore();

        return redirect()->route('entreprise.dashboard', ['slug' => $entreprise->slug])
            ->with('success', 'Votre entreprise a été restaurée avec succès.');
    }
}
