<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\HorairesOuverture;
use App\Models\TypeService;
use App\Models\ServiceOption;
use App\Models\ServiceImage;
use App\Services\ImageService;
use App\Services\JoursFeriesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
            ->with(['images', 'imageCouverture', 'options.choices'])
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
                $isDateButoire = $reservation->typeService && $reservation->typeService->estDateButoire();
                $dateButoire = $reservation->date_butoire;

                if ($isDateButoire && $dateButoire) {
                    $debut = \Carbon\Carbon::parse($dateButoire)->startOfDay();
                    $fin = $debut->copy()->endOfDay();
                    $allDay = true;
                } else {
                    $debut = \Carbon\Carbon::parse($reservation->date_reservation);
                    $fin = $debut->copy()->addMinutes((int) ($reservation->duree_minutes ?? 30));
                    $allDay = false;
                }

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
                $clientName = $reservation->user ? $reservation->user->name : ($reservation->nom_client ?? 'Client');
                $title = ($reservation->typeService ? $reservation->typeService->nom : ($reservation->type_service ?? 'Réservation')) .
                         ' - ' . $clientName;
                if ($isDateButoire) {
                    $title .= ' (date butoire)';
                }
                if ($reservation->membre && $reservation->membre->user) {
                    $title .= ' [' . $reservation->membre->user->name . ']';
                }

                $extendedProps = [
                    'hash' => $reservation->hash ?? null,
                    'statut' => $reservation->statut,
                    'client' => $clientName,
                    'client_email' => $reservation->emailClientComplet ?? 'N/A',
                    'prix' => $reservation->prix,
                    'duree' => $reservation->duree_minutes,
                    'lieu' => $reservation->lieu,
                    'est_paye' => $reservation->est_paye,
                    'telephone' => $reservation->telephone_client ?? $reservation->telephone_client_non_inscrit ?? null,
                    'notes' => $reservation->notes,
                    'type_service' => $reservation->typeService ? $reservation->typeService->nom : ($reservation->type_service ?? 'N/A'),
                    'membre' => $reservation->membre && $reservation->membre->user ? $reservation->membre->user->name : null,
                    'creee_manuellement' => $reservation->creee_manuellement ?? false,
                    'date_butoire' => $isDateButoire,
                    'date_butoire_value' => $dateButoire ? \Carbon\Carbon::parse($dateButoire)->format('Y-m-d') : null,
                ];

                return array_filter([
                    'id' => $reservation->id,
                    'title' => $title,
                    'start' => $debut->toIso8601String(),
                    'end' => $fin->toIso8601String(),
                    'allDay' => $allDay,
                    'color' => $color,
                    'extendedProps' => $extendedProps,
                ]);
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
     * Supporte maintenant : jour, mois, plage, jours fériés
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

        $typeException = $request->input('type_exception', 'jour');
        
        // Validation selon le type
        $validated = $this->validateException($request, $typeException);
        
        // Préparer les données communes
        $heureOuverture = $validated['est_ferme'] ? null : ($validated['heure_ouverture'] ?? null);
        $heureFermeture = $validated['est_ferme'] ? null : ($validated['heure_fermeture'] ?? null);

        DB::beginTransaction();
        try {
            switch ($typeException) {
                case 'jour':
                    $this->createJourExceptionnel($entreprise, $validated, $heureOuverture, $heureFermeture);
                    break;
                    
                case 'mois':
                    $this->createMoisExceptionnel($entreprise, $validated, $heureOuverture, $heureFermeture);
                    break;
                    
                case 'plage':
                    $this->createPlageExceptionnelle($entreprise, $validated, $heureOuverture, $heureFermeture);
                    break;
                    
                case 'jours_feries':
                    $this->createJoursFeriesExceptionnels($entreprise, $validated, $heureOuverture, $heureFermeture);
                    break;
                    
                default:
                    throw new \InvalidArgumentException('Type d\'exception non valide');
            }
            
            DB::commit();
            
            $message = $this->getSuccessMessage($typeException);
            return redirect()->route('agenda.index', $slug)
                ->with('success', $message);
                
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors de la création d\'une exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()->route('agenda.index', $slug)
                ->with('error', 'Une erreur est survenue lors de l\'enregistrement : ' . $e->getMessage());
        }
    }

    /**
     * Valide les données selon le type d'exception
     */
    private function validateException(Request $request, string $type): array
    {
        $rules = [
            'type_exception' => 'required|in:jour,mois,plage,jours_feries',
            'heure_ouverture' => 'nullable|date_format:H:i',
            'heure_fermeture' => 'nullable|date_format:H:i',
            'est_ferme' => 'boolean',
        ];
        
        switch ($type) {
            case 'jour':
                $rules['date_exception'] = 'required|date|after_or_equal:today';
                break;
                
            case 'mois':
                $rules['mois'] = 'required|integer|min:1|max:12';
                $rules['annee'] = 'required|integer|min:' . date('Y') . '|max:' . (date('Y') + 5);
                $rules['jours_exclus'] = 'nullable|array';
                $rules['jours_exclus.*'] = 'integer|min:0|max:6';
                break;
                
            case 'plage':
                $rules['date_debut'] = 'required|date|after_or_equal:today';
                $rules['date_fin'] = 'required|date|after_or_equal:date_debut';
                break;
                
            case 'jours_feries':
                $rules['annee_jours_feries'] = 'required|integer|min:' . date('Y') . '|max:' . (date('Y') + 5);
                $rules['zone_jours_feries'] = 'nullable|string|max:50';
                break;
        }
        
        return $request->validate($rules);
    }

    /**
     * Crée une exception de type "jour"
     */
    private function createJourExceptionnel(Entreprise $entreprise, array $validated, $heureOuverture, $heureFermeture): void
    {
        $date = Carbon::parse($validated['date_exception']);
        
        // Vérifier si un jour exceptionnel existe déjà pour cette date
        $existing = HorairesOuverture::where('entreprise_id', $entreprise->id)
            ->where('type_exception', 'jour')
            ->where('date_exception', $validated['date_exception'])
            ->first();

        if ($existing) {
            $existing->update([
                'heure_ouverture' => $heureOuverture,
                'heure_fermeture' => $heureFermeture,
                'est_exceptionnel' => true,
                'type_exception' => 'jour',
            ]);
        } else {
            HorairesOuverture::create([
                'entreprise_id' => $entreprise->id,
                'jour_semaine' => $date->dayOfWeek,
                'heure_ouverture' => $heureOuverture,
                'heure_fermeture' => $heureFermeture,
                'est_exceptionnel' => true,
                'type_exception' => 'jour',
                'date_exception' => $validated['date_exception'],
            ]);
        }
    }

    /**
     * Crée une exception de type "mois"
     */
    private function createMoisExceptionnel(Entreprise $entreprise, array $validated, $heureOuverture, $heureFermeture): void
    {
        // Vérifier si une exception pour ce mois existe déjà
        $existing = HorairesOuverture::where('entreprise_id', $entreprise->id)
            ->where('type_exception', 'mois')
            ->where('mois', $validated['mois'])
            ->where('annee', $validated['annee'])
            ->first();

        $joursExclus = $validated['jours_exclus'] ?? [];
        
        if ($existing) {
            $existing->update([
                'heure_ouverture' => $heureOuverture,
                'heure_fermeture' => $heureFermeture,
                'jours_exclus' => $joursExclus,
            ]);
        } else {
            HorairesOuverture::create([
                'entreprise_id' => $entreprise->id,
                'jour_semaine' => 0, // Valeur par défaut, non utilisée pour les mois
                'heure_ouverture' => $heureOuverture,
                'heure_fermeture' => $heureFermeture,
                'est_exceptionnel' => true,
                'type_exception' => 'mois',
                'mois' => $validated['mois'],
                'annee' => $validated['annee'],
                'jours_exclus' => $joursExclus,
            ]);
        }
    }

    /**
     * Crée une exception de type "plage"
     */
    private function createPlageExceptionnelle(Entreprise $entreprise, array $validated, $heureOuverture, $heureFermeture): void
    {
        HorairesOuverture::create([
            'entreprise_id' => $entreprise->id,
            'jour_semaine' => 0, // Valeur par défaut, non utilisée pour les plages
            'heure_ouverture' => $heureOuverture,
            'heure_fermeture' => $heureFermeture,
            'est_exceptionnel' => true,
            'type_exception' => 'plage',
            'date_debut' => $validated['date_debut'],
            'date_fin' => $validated['date_fin'],
        ]);
    }

    /**
     * Crée des exceptions de type "jours fériés"
     */
    private function createJoursFeriesExceptionnels(Entreprise $entreprise, array $validated, $heureOuverture, $heureFermeture): void
    {
        $joursFeriesService = app(JoursFeriesService::class);
        $zone = $validated['zone_jours_feries'] ?? 'metropole';
        $annee = $validated['annee_jours_feries'];
        
        // Récupérer les jours fériés
        $joursFeries = $joursFeriesService->getJoursFeries($annee, $zone);
        
        if (empty($joursFeries)) {
            throw new \Exception('Aucun jour férié trouvé pour l\'année ' . $annee . ' dans la zone ' . $zone);
        }
        
        // Supprimer les anciens jours fériés pour cette année et zone
        HorairesOuverture::where('entreprise_id', $entreprise->id)
            ->where('type_exception', 'jours_feries')
            ->where('annee_jours_feries', $annee)
            ->where('zone_jours_feries', $zone)
            ->delete();
        
        // Créer un enregistrement pour chaque jour férié
        foreach ($joursFeries as $date => $nom) {
            $dateCarbon = Carbon::parse($date);
            
            HorairesOuverture::create([
                'entreprise_id' => $entreprise->id,
                'jour_semaine' => $dateCarbon->dayOfWeek,
                'heure_ouverture' => $heureOuverture,
                'heure_fermeture' => $heureFermeture,
                'est_exceptionnel' => true,
                'type_exception' => 'jours_feries',
                'date_exception' => $date,
                'est_jours_feries' => true,
                'annee_jours_feries' => $annee,
                'zone_jours_feries' => $zone,
            ]);
        }
    }

    /**
     * Retourne le message de succès selon le type
     */
    private function getSuccessMessage(string $type): string
    {
        switch ($type) {
            case 'jour':
                return 'Le jour exceptionnel a été enregistré avec succès.';
            case 'mois':
                return 'L\'exception pour le mois a été enregistrée avec succès.';
            case 'plage':
                return 'L\'exception pour la plage de dates a été enregistrée avec succès.';
            case 'jours_feries':
                return 'Les jours fériés ont été enregistrés avec succès.';
            default:
                return 'L\'exception a été enregistrée avec succès.';
        }
    }

    /**
     * Supprimer un jour exceptionnel
     * Gère la suppression selon le type :
     * - Jour : Supprime l'enregistrement unique
     * - Mois : Supprime l'enregistrement du mois
     * - Plage : Supprime l'enregistrement de la plage
     * - Jours fériés : Supprime tous les jours fériés de la même année/zone
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

        DB::beginTransaction();
        try {
            // Si c'est un groupe de jours fériés, supprimer tous les jours fériés de la même année/zone
            if ($horaire->isTypeJoursFeries() && $horaire->annee_jours_feries && $horaire->zone_jours_feries) {
                HorairesOuverture::where('entreprise_id', $entreprise->id)
                    ->where('type_exception', 'jours_feries')
                    ->where('annee_jours_feries', $horaire->annee_jours_feries)
                    ->where('zone_jours_feries', $horaire->zone_jours_feries)
                    ->delete();
                $message = 'Les jours fériés ont été supprimés.';
            } else {
                $horaire->delete();
                $message = 'L\'exception a été supprimée.';
            }
            
            DB::commit();
            
            return redirect()->route('agenda.index', $slug)
                ->with('success', $message);
                
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors de la suppression d\'une exception', [
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->route('agenda.index', $slug)
                ->with('error', 'Une erreur est survenue lors de la suppression.');
        }
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
            'type_structure' => 'required|in:ponctuel,multi_jours,multi_rendez_vous,date_butoire',
            'est_actif' => 'nullable|boolean',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'options' => 'nullable|array',
            'options.*.nom' => 'required|string|max:255',
            'options.*.type' => 'required|in:choix_unique,choix_multiple',
            'options.*.obligatoire' => 'nullable|boolean',
            'options.*.choices' => 'required|array',
            'options.*.choices.*.nom' => 'required|string|max:255',
            'options.*.choices.*.prix' => 'nullable|numeric|min:0',
            'options.*.choices.*.temps' => 'nullable|integer|min:0',
        ]);

        // Gérer le champ est_actif (checkbox : si présent = true, sinon = false)
        $validated['est_actif'] = $request->has('est_actif') && $request->est_actif == '1';

        try {
            $imageService = app(ImageService::class);
            
            DB::beginTransaction();

            if ($request->filled('type_service_id') && is_numeric($request->type_service_id)) {
                $typeService = TypeService::where('id', $request->type_service_id)
                    ->where('entreprise_id', $entreprise->id)
                    ->firstOrFail();
                
                // Mettre à jour les champs de base (exclure entreprise_id)
                $dataToUpdate = $validated;
                unset($dataToUpdate['entreprise_id']);
                
                $typeService->update($dataToUpdate);
                $message = 'Le type de service a été mis à jour avec succès.';
            } else {
                $validated['entreprise_id'] = $entreprise->id;
                $typeService = TypeService::create($validated);
                $message = 'Le type de service a été créé avec succès.';
            }

            // Gérer les options
            // On supprime toujours les anciennes options pour les remplacer par les nouvelles (ou rien)
            $typeService->options()->each(function($option) {
                $option->choices()->delete();
                $option->delete();
            });

            if ($request->has('options')) {
                foreach ($request->options as $optIdx => $optionData) {
                    $option = $typeService->options()->create([
                        'nom' => $optionData['nom'],
                        'type' => $optionData['type'],
                        'obligatoire' => isset($optionData['obligatoire']) && $optionData['obligatoire'] == '1',
                        'ordre' => $optIdx,
                    ]);

                    if (isset($optionData['choices'])) {
                        foreach ($optionData['choices'] as $choiceIdx => $choiceData) {
                            $option->choices()->create([
                                'nom' => $choiceData['nom'],
                                'prix_supplementaire' => $choiceData['prix'] ?? 0,
                                'temps_supplementaire' => $choiceData['temps'] ?? 0,
                                'ordre' => $choiceIdx,
                            ]);
                        }
                    }
                }
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

            DB::commit();

            return redirect()->route('entreprise.dashboard', ['slug' => $slug, 'tab' => 'services'])
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors de l\'enregistrement du service', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('entreprise.dashboard', ['slug' => $slug, 'tab' => 'services'])
                ->withInput()
                ->withErrors(['error' => 'Une erreur est survenue lors de l\'enregistrement du service : ' . $e->getMessage()]);
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

        // Retourner du JSON pour les requêtes Ajax (toujours pour les images)
        return response()->json([
            'success' => true,
            'message' => 'Image supprimée avec succès.',
        ]);
    }
}
