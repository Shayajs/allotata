<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\HorairesOuverture;
use App\Models\TypeService;
use App\Models\ServiceImage;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AgendaController extends Controller
{
    /**
     * Afficher la page de gestion de l'agenda
     */
    public function index($slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $horaires = $entreprise->horairesOuverture()
            ->where('est_exceptionnel', false)
            ->orderBy('jour_semaine')
            ->orderBy('ordre_plage')
            ->get();

        $typesServices = $entreprise->typesServices()
            ->with(['images', 'imageCouverture'])
            ->orderBy('nom')
            ->get();

        // Si pas d'horaires, créer les horaires par défaut (fermés)
        if ($horaires->isEmpty()) {
            $horaires = collect();
            for ($i = 0; $i < 7; $i++) {
                $horaires->push(new HorairesOuverture([
                    'entreprise_id' => $entreprise->id,
                    'jour_semaine' => $i,
                    'heure_ouverture' => null,
                    'heure_fermeture' => null,
                ]));
            }
        }

        // Détecter si on vient de la route service pour scroller automatiquement
        $showServices = request()->routeIs('agenda.service.index');

        return view('agenda.index', [
            'entreprise' => $entreprise,
            'horaires' => $horaires,
            'typesServices' => $typesServices,
            'showServices' => $showServices,
        ]);
    }

    /**
     * API : Récupérer les réservations pour l'agenda gérant (avec tous les détails)
     */
    public function getReservations($slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        // Récupérer toutes les réservations (y compris terminées pour historique)
        $reservations = \App\Models\Reservation::where('entreprise_id', $entreprise->id)
            ->with(['user', 'typeService', 'membre.user'])
            ->get()
            ->map(function($reservation) {
                $debut = \Carbon\Carbon::parse($reservation->date_reservation);
                $fin = $debut->copy()->addMinutes($reservation->duree_minutes ?? 30);
                
                // Couleur selon le statut
                $color = '#9ca3af'; // Gris par défaut
                if ($reservation->statut === 'confirmee') {
                    $color = $reservation->est_paye ? '#10b981' : '#3b82f6'; // Vert si payée, bleu si confirmée
                } elseif ($reservation->statut === 'en_attente') {
                    $color = '#f59e0b'; // Orange
                } elseif ($reservation->statut === 'annulee') {
                    $color = '#ef4444'; // Rouge
                } elseif ($reservation->statut === 'terminee') {
                    $color = '#6b7280'; // Gris foncé
                }
                
                // Titre avec membre si assigné
                $title = ($reservation->typeService ? $reservation->typeService->nom : ($reservation->type_service ?? 'Réservation')) . 
                         ($reservation->user ? ' - ' . $reservation->user->name : '');
                if ($reservation->membre && $reservation->membre->user) {
                    $title .= ' [' . $reservation->membre->user->name . ']';
                }
                
                return [
                    'id' => $reservation->id,
                    'title' => $title,
                    'start' => $debut->toIso8601String(),
                    'end' => $fin->toIso8601String(),
                    'color' => $color,
                    'extendedProps' => [
                        'statut' => $reservation->statut,
                        'client' => $reservation->user ? $reservation->user->name : 'N/A',
                        'client_email' => $reservation->user ? $reservation->user->email : 'N/A',
                        'prix' => $reservation->prix,
                        'duree' => $reservation->duree_minutes,
                        'lieu' => $reservation->lieu,
                        'est_paye' => $reservation->est_paye,
                        'telephone' => $reservation->telephone_client,
                        'notes' => $reservation->notes,
                        'type_service' => $reservation->typeService ? $reservation->typeService->nom : ($reservation->type_service ?? 'N/A'),
                        'membre' => $reservation->membre && $reservation->membre->user ? $reservation->membre->user->name : null,
                    ],
                ];
            });

        return response()->json($reservations);
    }

    /**
     * Sauvegarder les horaires d'ouverture
     */
    public function storeHoraires(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $request->validate([
            'horaires' => 'required|array',
            'horaires.*.jour_semaine' => 'required|integer|min:0|max:6',
            'horaires.*.plages' => 'sometimes|nullable|array',
            'horaires.*.plages.*.heure_ouverture' => 'nullable|date_format:H:i',
            'horaires.*.plages.*.heure_fermeture' => 'nullable|date_format:H:i',
        ]);

        // Supprimer les anciens horaires réguliers (pas les exceptionnels)
        $entreprise->horairesOuverture()
            ->where('est_exceptionnel', false)
            ->delete();

        // Créer les nouveaux horaires
        foreach ($request->horaires as $horaireJour) {
            $jourSemaine = $horaireJour['jour_semaine'];
            $plages = $horaireJour['plages'] ?? [];
            
            // Si le jour n'est pas marqué comme fermé et qu'il y a des plages
            if (!empty($plages)) {
                $ordrePlage = 0;
                foreach ($plages as $plage) {
                    // Vérifier que les heures sont définies et valides
                    if (isset($plage['heure_ouverture']) && isset($plage['heure_fermeture']) && 
                        !empty($plage['heure_ouverture']) && !empty($plage['heure_fermeture'])) {
                        // Vérifier que l'heure de fermeture est après l'heure d'ouverture
                        if ($plage['heure_ouverture'] < $plage['heure_fermeture']) {
                            HorairesOuverture::create([
                                'entreprise_id' => $entreprise->id,
                                'jour_semaine' => $jourSemaine,
                                'ordre_plage' => $ordrePlage,
                                'heure_ouverture' => $plage['heure_ouverture'],
                                'heure_fermeture' => $plage['heure_fermeture'],
                                'est_exceptionnel' => false,
                            ]);
                            $ordrePlage++;
                        }
                    }
                }
            }
        }

        return redirect()->route('agenda.index', $slug)
            ->with('success', 'Les horaires ont été mis à jour avec succès.');
    }

    /**
     * Créer un jour exceptionnel (fermeture ou horaire spécial)
     */
    public function storeJourExceptionnel(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $validated = $request->validate([
            'date_exception' => 'required|date|after_or_equal:today',
            'heure_ouverture' => 'nullable|date_format:H:i',
            'heure_fermeture' => 'nullable|date_format:H:i',
            'est_ferme' => 'boolean',
        ]);

        // Vérifier si un jour exceptionnel existe déjà pour cette date
        $existing = HorairesOuverture::where('entreprise_id', $entreprise->id)
            ->where('date_exception', $validated['date_exception'])
            ->first();

        if ($existing) {
            $existing->update([
                'heure_ouverture' => $validated['est_ferme'] ? null : $validated['heure_ouverture'],
                'heure_fermeture' => $validated['est_ferme'] ? null : $validated['heure_fermeture'],
                'est_exceptionnel' => true,
            ]);
        } else {
            HorairesOuverture::create([
                'entreprise_id' => $entreprise->id,
                'jour_semaine' => \Carbon\Carbon::parse($validated['date_exception'])->dayOfWeek,
                'heure_ouverture' => $validated['est_ferme'] ? null : $validated['heure_ouverture'],
                'heure_fermeture' => $validated['est_ferme'] ? null : $validated['heure_fermeture'],
                'est_exceptionnel' => true,
                'date_exception' => $validated['date_exception'],
            ]);
        }

        return redirect()->route('agenda.index', $slug)
            ->with('success', 'Le jour exceptionnel a été enregistré avec succès.');
    }

    /**
     * Supprimer un jour exceptionnel
     */
    public function deleteJourExceptionnel($slug, $horaireId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $horaire = HorairesOuverture::where('id', $horaireId)
            ->where('entreprise_id', $entreprise->id)
            ->where('est_exceptionnel', true)
            ->firstOrFail();

        $horaire->delete();

        return redirect()->route('agenda.index', $slug)
            ->with('success', 'Le jour exceptionnel a été supprimé.');
    }

    /**
     * Créer ou mettre à jour un type de service
     */
    public function storeTypeService(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duree_minutes' => 'required|integer|min:1',
            'prix' => 'required|numeric|min:0',
            'est_actif' => 'nullable|boolean',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        // Gérer le champ est_actif (checkbox : si présent = true, sinon = false)
        $validated['est_actif'] = $request->has('est_actif') && $request->est_actif == '1';

        try {
            $imageService = app(ImageService::class);
            
            if ($request->has('type_service_id') && !empty($request->type_service_id)) {
                $typeService = TypeService::where('id', $request->type_service_id)
                    ->where('entreprise_id', $entreprise->id)
                    ->firstOrFail();
                $typeService->update($validated);
                $message = 'Le type de service a été mis à jour avec succès.';
            } else {
                $validated['entreprise_id'] = $entreprise->id;
                $typeService = TypeService::create($validated);
                $message = 'Le type de service a été créé avec succès.';
            }

            // Gérer l'upload des images
            if ($request->hasFile('images')) {
                $images = $request->file('images');
                $maxOrdre = ServiceImage::where('type_service_id', $typeService->id)->max('ordre') ?? 0;
                $hasCouverture = ServiceImage::where('type_service_id', $typeService->id)->where('est_couverture', true)->exists();
                
                foreach ($images as $index => $image) {
                    $imagePath = $imageService->processAndStore($image, 'services');
                    $estCouverture = !$hasCouverture && $index === 0; // La première image devient couverture si aucune n'existe
                    
                    ServiceImage::create([
                        'type_service_id' => $typeService->id,
                        'image_path' => $imagePath,
                        'est_couverture' => $estCouverture,
                        'ordre' => $maxOrdre + $index + 1,
                    ]);
                    
                    if ($estCouverture) {
                        $hasCouverture = true;
                    }
                }
            }

            return redirect()->route('entreprise.dashboard', ['slug' => $slug, 'tab' => 'services'])
                ->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'enregistrement du service', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('entreprise.dashboard', ['slug' => $slug, 'tab' => 'services'])
                ->withInput()
                ->withErrors(['error' => 'Une erreur est survenue lors de l\'enregistrement du service. Veuillez réessayer.']);
        }
    }

    /**
     * Supprimer un type de service
     */
    public function deleteTypeService(Request $request, $slug, $typeServiceId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $typeService = TypeService::where('id', $typeServiceId)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $typeService->delete();

        return redirect()->route('entreprise.dashboard', ['slug' => $slug, 'tab' => 'services'])
            ->with('success', 'Le type de service a été supprimé.');
    }

    /**
     * Uploader une image pour un service
     */
    public function uploadServiceImage(Request $request, $slug, $typeServiceId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $typeService = TypeService::where('id', $typeServiceId)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $imageService = app(ImageService::class);
        $imagePath = $imageService->processAndStore($request->file('image'), 'services');

        // Déterminer l'ordre (dernier + 1)
        $maxOrdre = ServiceImage::where('type_service_id', $typeService->id)->max('ordre') ?? 0;

        // Si c'est la première image, la définir comme couverture
        $estCouverture = ServiceImage::where('type_service_id', $typeService->id)->count() === 0;

        $serviceImage = ServiceImage::create([
            'type_service_id' => $typeService->id,
            'image_path' => $imagePath,
            'est_couverture' => $estCouverture,
            'ordre' => $maxOrdre + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Image uploadée avec succès.',
            'image' => [
                'id' => $serviceImage->id,
                'path' => asset('media/' . $serviceImage->image_path),
                'est_couverture' => $serviceImage->est_couverture,
            ],
        ]);
    }

    /**
     * Définir une image comme couverture
     */
    public function setServiceImageCover(Request $request, $slug, $typeServiceId, $imageId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $typeService = TypeService::where('id', $typeServiceId)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $image = ServiceImage::where('id', $imageId)
            ->where('type_service_id', $typeService->id)
            ->firstOrFail();

        // Retirer la couverture actuelle
        ServiceImage::where('type_service_id', $typeService->id)
            ->update(['est_couverture' => false]);

        // Définir la nouvelle couverture
        $image->update(['est_couverture' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Image de couverture mise à jour.',
        ]);
    }

    /**
     * Supprimer une image de service
     */
    public function deleteServiceImage(Request $request, $slug, $typeServiceId, $imageId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $typeService = TypeService::where('id', $typeServiceId)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $image = ServiceImage::where('id', $imageId)
            ->where('type_service_id', $typeService->id)
            ->firstOrFail();

        $imagePath = $image->image_path;
        $estCouverture = $image->est_couverture;

        // Supprimer l'image
        $image->delete();

        // Supprimer le fichier
        if ($imagePath && Storage::disk('public')->exists($imagePath)) {
            try {
                $imageService = app(ImageService::class);
                $imageService->delete($imagePath);
            } catch (\Exception $e) {
                \Log::warning('Erreur lors de la suppression de l\'image de service', [
                    'path' => $imagePath,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Si c'était l'image de couverture, définir la première image restante comme couverture
        if ($estCouverture) {
            $premiereImage = ServiceImage::where('type_service_id', $typeService->id)
                ->orderBy('ordre')
                ->first();
            
            if ($premiereImage) {
                $premiereImage->update(['est_couverture' => true]);
            }
        }

        // Si on vient du dashboard, rediriger vers l'onglet services
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Image supprimée avec succès.',
            ]);
        }
        
        return redirect()->route('entreprise.dashboard', ['slug' => $slug, 'tab' => 'services'])
            ->with('success', 'Image supprimée avec succès.');
    }
}
