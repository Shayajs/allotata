<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\EntrepriseMembre;
use App\Models\Reservation;
use App\Models\SiteWebPage;
use App\Models\SiteWebVersion;
use App\Models\TypeService;
use App\Services\ExceptionDateService;
use App\Services\ImageService;
use App\Services\SiteWebTemplateService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SiteWebController extends Controller
{
    /**
     * Résoudre l'entreprise par slug (public).
     */
    private function resolveEntreprise(string $slug): ?Entreprise
    {
        $entreprise = Entreprise::where('slug_web', $slug)->first();
        if (!$entreprise) {
            $entreprise = Entreprise::where('slug', $slug)->first();
        }
        return $entreprise;
    }

    /**
     * Afficher le site web vitrine d'une entreprise
     */
    public function show(Request $request, $slug)
    {
        $entreprise = $this->resolveEntreprise($slug);

        if (!$entreprise) {
            abort(404, 'Site web introuvable. Vérifiez que le slug est correct.');
        }

        $user = Auth::user();
        $isOwner = $user && $entreprise->user_id === $user->id;

        // Si ce n'est pas le propriétaire, vérifier les conditions strictes
        if (!$isOwner) {
            if (!$entreprise->est_verifiee) {
                abort(404, 'Site web non disponible.');
            }
            if (!$entreprise->aSiteWebActif()) {
                abort(404, 'Site web non disponible.');
            }
            $expectedSlug = $entreprise->slug_web ?? $entreprise->slug;
            if ($slug !== $expectedSlug) {
                abort(404, 'Site web introuvable.');
            }
        }

        // Déterminer le mode
        $requestedMode = $request->query('mode');

        if ($isOwner) {
            $mode = ($requestedMode === 'view') ? 'view' : 'edit';
        } else {
            $mode = 'view';
        }

        if ($mode === 'edit') {
            $entreprise->load(['realisationPhotos', 'typesServices', 'avis', 'siteWebPages']);

            if (empty($entreprise->contenu_site_web) || empty($entreprise->contenu_site_web['blocks'])) {
                $templateService = app(SiteWebTemplateService::class);
                $templateService->applyTemplate($entreprise, 'default');
                $entreprise->refresh();
            }

            return view('public.site-web-edit', [
                'entreprise' => $entreprise,
                'isOwner' => $isOwner,
            ]);
        }

        // ── Mode view : résoudre l'onglet actif ──────────────
        $pages = $entreprise->siteWebPagesActives;
        $tabSlug = $request->query('tab');

        // ── Migration paresseuse V1 → V2 ──
        // Si des pages système existent mais que le contenu V1 (blocs JSON) n'a pas encore
        // été migré dans une page "Accueil", le faire automatiquement une seule fois.
        if ($pages->count() > 0 && !$pages->firstWhere('type', 'custom')) {
            $v1Blocks = $entreprise->getSiteWebBlocks();
            if (!empty($v1Blocks)) {
                $minOrdre = $pages->min('ordre') ?? 0;
                SiteWebPage::create([
                    'entreprise_id' => $entreprise->id,
                    'nom'           => 'Accueil',
                    'slug'          => 'accueil',
                    'type'          => 'custom',
                    'blocs'         => $v1Blocks,
                    'ordre'         => $minOrdre - 1,
                    'est_actif'     => true,
                    'icone'         => 'home',
                ]);
                // Rafraîchir la collection
                $pages = $entreprise->siteWebPagesActives()->get();
            }
        }

        // Si l'entreprise a des pages en BDD, les utiliser
        if ($pages->count() > 0) {
            $currentPage = $tabSlug
                ? $pages->firstWhere('slug', $tabSlug)
                : $pages->first();

            if (!$currentPage) {
                $currentPage = $pages->first();
            }

            // Données supplémentaires pour les onglets système
            $extraData = $this->loadSystemTabData($entreprise, $currentPage);

            return view('public.site-web', array_merge([
                'entreprise' => $entreprise,
                'isOwner'    => $isOwner,
                'pages'      => $pages,
                'currentPage' => $currentPage,
            ], $extraData));
        }

        // Fallback : pas de pages en BDD → rendu classique (contenu_site_web)
        return view('public.site-web', [
            'entreprise'  => $entreprise,
            'isOwner'     => $isOwner,
            'pages'       => collect(),
            'currentPage' => null,
        ]);
    }

    /**
     * Charger les données nécessaires aux onglets système.
     */
    private function loadSystemTabData(Entreprise $entreprise, SiteWebPage $page): array
    {
        if (!$page->isSystemTab()) {
            return [];
        }

        switch ($page->type) {
            case 'reservation':
            case 'agenda':
                return $this->getAgendaData($entreprise);

            case 'services':
                $entreprise->load(['typesServices' => fn($q) => $q->where('est_actif', true)->with('options.choices', 'imageCouverture', 'serviceAvis')]);
                return ['services' => $entreprise->typesServices];

            case 'contact':
                return [];

            default:
                return [];
        }
    }

    /**
     * Données pour l'onglet agenda / réservation.
     */
    private function getAgendaData(Entreprise $entreprise): array
    {
        $entreprise->load(['typesServices' => fn($q) => $q->where('est_actif', true)->with('options.choices')]);

        $membres = collect();
        $aGestionMultiPersonnes = $entreprise->aGestionMultiPersonnes();
        if ($aGestionMultiPersonnes) {
            $membres = $entreprise->membres()->where('est_actif', true)->with('user')->get();
        }

        $horairesRaw = $entreprise->horairesOuverture()->orderBy('jour_semaine')->orderBy('ordre_plage')->get();
        $horaires = $horairesRaw->map(fn($h) => [
            'id'               => $h->id,
            'jour_semaine'     => $h->jour_semaine,
            'heure_ouverture'  => $h->heure_ouverture ? Carbon::parse($h->heure_ouverture)->format('H:i') : null,
            'heure_fermeture'  => $h->heure_fermeture ? Carbon::parse($h->heure_fermeture)->format('H:i') : null,
            'est_exceptionnel' => $h->est_exceptionnel,
            'date_exception'   => $h->date_exception ? $h->date_exception->format('Y-m-d') : null,
        ]);

        // Info utilisateur connecté pour pré-remplissage
        $user = Auth::user();
        $userInfo = null;
        if ($user) {
            $userInfo = [
                'name'      => $user->name,
                'email'     => $user->email,
                'telephone' => $user->telephone ?? '',
            ];
        }

        return [
            'horaires'                => $horaires,
            'jours'                   => [],
            'membres'                 => $membres,
            'aGestionMultiPersonnes'  => $aGestionMultiPersonnes,
            'userInfo'                => $userInfo,
        ];
    }

    /**
     * Retourne le partial HTML du formulaire de réservation (AJAX).
     */
    public function reservationForm(Request $request, $slug)
    {
        $entreprise = $this->resolveEntreprise($slug);
        if (!$entreprise) {
            abort(404);
        }

        $data = $this->getAgendaData($entreprise);

        return view('components.site-web.partials.reservation-form', array_merge(
            [
                'entreprise' => $entreprise,
                'slug' => $slug,
            ],
            $data,
        ));
    }

    /**
     * Stocker une réservation depuis le site vitrine (guest ou connecté).
     */
    public function storeReservationWeb(Request $request, $slug)
    {
        $entreprise = $this->resolveEntreprise($slug);
        if (!$entreprise) {
            abort(404);
        }

        $typeService = TypeService::where('id', $request->input('type_service_id'))
            ->where('entreprise_id', $entreprise->id)
            ->where('est_actif', true)
            ->firstOrFail();

        $isDateButoire = $typeService->estDateButoire();
        $isRecurrent = $typeService->estRecurrent();
        $isEvenement = $typeService->estEvenement();
        $isSurDevis = $typeService->estSurDevis();

        // Sur devis : rediriger vers DevisController
        if ($isSurDevis) {
            return app(\App\Http\Controllers\DevisController::class)->store($request, $slug);
        }

        $rules = [
            'type_service_id'    => 'required|exists:types_services,id',
            'membre_id'          => 'nullable|exists:entreprise_membres,id',
            'lieu'               => 'nullable|string|max:255',
            'telephone_client'   => 'required|string|max:20',
            'telephone_cache'    => 'boolean',
            'notes'              => 'nullable|string',
            // Champs guest
            'nom_client'         => 'nullable|string|max:255',
            'email_client'       => 'nullable|email|max:255',
        ];

        if ($isRecurrent) {
            $rules['frequence'] = 'required|in:hebdomadaire,bimensuel,mensuel,personnalise';
            $rules['intervalle_jours'] = 'nullable|integer|min:1';
            $rules['date_debut'] = 'required|date|after_or_equal:today';
            $rules['date_fin'] = 'required|date|after:date_debut';
            $rules['heure_reservation'] = 'required|date_format:H:i';
        } elseif ($isDateButoire) {
            $rules['date_butoire'] = 'required|date|after_or_equal:today';
        } else {
            $rules['date_reservation'] = 'required|date|after:now';
            $rules['heure_reservation'] = 'required|date_format:H:i';
        }

        $validated = $request->validate($rules);

        // Calculer la date de début
        if ($isRecurrent) {
            $debutReservation = Carbon::parse($validated['date_debut'] . ' ' . $validated['heure_reservation']);
        } elseif ($isDateButoire) {
            $debutReservation = Carbon::parse($validated['date_butoire'] . ' 00:00:00');
        } else {
            $debutReservation = Carbon::parse($validated['date_reservation'] . ' ' . $validated['heure_reservation']);
        }

        $userId = Auth::id();

        // Gérer le membre
        $membreId = null;
        if (!empty($validated['membre_id'])) {
            $membre = EntrepriseMembre::where('id', $validated['membre_id'])
                ->where('entreprise_id', $entreprise->id)
                ->where('est_actif', true)
                ->first();
            if ($membre) {
                $membreId = $membre->id;
            }
        }

        // Prix et durée
        $prixTotal = $typeService->prix ?? 0;
        $dureeTotal = $typeService->duree_minutes ?? 30;

        // Traiter les options de service
        $serviceOptions = $request->input('service_options', []);
        foreach ($serviceOptions as $optionId => $choiceId) {
            $choice = \App\Models\ServiceOptionChoice::find($choiceId);
            if ($choice) {
                $prixTotal += $choice->prix_supplementaire ?? 0;
                $dureeTotal += $choice->temps_supplementaire ?? 0;
            }
        }

        $statutInitial = $entreprise->accepter_reservations_auto ? 'confirmee' : 'en_attente';
        $redirectUrl = route('site-web.show', ['slug' => $slug]) . '?tab=reservation';

        // ── Branche RÉCURRENT ──
        if ($isRecurrent) {
            $recurrence = \App\Models\Recurrence::create([
                'entreprise_id' => $entreprise->id,
                'user_id' => $userId,
                'type_service_id' => $typeService->id,
                'membre_id' => $membreId,
                'frequence' => $validated['frequence'],
                'intervalle_jours' => $validated['intervalle_jours'] ?? null,
                'date_debut' => $validated['date_debut'],
                'date_fin' => $validated['date_fin'],
                'heure' => $validated['heure_reservation'],
                'lieu' => $validated['lieu'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'prix_par_occurrence' => $prixTotal,
                'telephone_client' => $validated['telephone_client'],
                'nom_client' => !$userId ? ($validated['nom_client'] ?? null) : null,
                'email_client' => !$userId ? ($validated['email_client'] ?? null) : null,
            ]);

            $recurrenceService = app(\App\Services\RecurrenceService::class);
            $reservations = $recurrenceService->genererOccurrences($recurrence, $statutInitial);

            if (empty($reservations)) {
                $recurrence->delete();
                return back()->withErrors(['error' => 'Aucun créneau disponible n\'a pu être réservé.']);
            }

            return redirect($redirectUrl)
                ->with('success', count($reservations) . ' séances récurrentes ont été réservées !');
        }

        // ── Branche ÉVÉNEMENT (vérifier capacité) ──
        if ($isEvenement && $typeService->capacite_max) {
            $disponible = \Illuminate\Support\Facades\DB::transaction(function () use ($entreprise, $typeService, $debutReservation, $dureeTotal) {
                return \App\Services\ReservationSlotService::estEvenementDisponible(
                    $entreprise->id,
                    $typeService->id,
                    $debutReservation,
                    $dureeTotal,
                    $typeService->capacite_max
                );
            });

            if (!$disponible) {
                return back()->withErrors(['error' => 'Cet événement est complet.']);
            }
        }

        // Vérifier la disponibilité ET créer dans une transaction atomique (anti-doublon)
        $skipSlotCheck = $isDateButoire || $isEvenement;

        $reservationData = [
            'user_id'                     => $userId,
            'entreprise_id'               => $entreprise->id,
            'type_service_id'             => $typeService->id,
            'membre_id'                   => $membreId,
            'date_reservation'            => $isDateButoire ? null : $debutReservation,
            'date_fin'                    => $isDateButoire ? null : $debutReservation->copy()->addMinutes($dureeTotal),
            'date_butoire'                => $isDateButoire ? $debutReservation : null,
            'lieu'                        => $validated['lieu'] ?? null,
            'telephone_client'            => $userId ? $validated['telephone_client'] : null,
            'telephone_client_non_inscrit' => !$userId ? $validated['telephone_client'] : null,
            'telephone_cache'             => $validated['telephone_cache'] ?? false,
            'notes'                       => $validated['notes'] ?? null,
            'nom_client'                  => !$userId ? ($validated['nom_client'] ?? null) : null,
            'email_client'                => !$userId ? ($validated['email_client'] ?? null) : null,
            'prix'                        => $isEvenement && !$typeService->est_prix_par_personne ? 0 : $prixTotal,
            'duree_minutes'               => $dureeTotal,
            'statut'                      => $statutInitial,
            'hash'                        => Str::random(64),
        ];

        $reservation = \App\Services\ReservationSlotService::reserverSiDisponible(
            $entreprise->id,
            $membreId,
            $debutReservation,
            $dureeTotal,
            fn () => Reservation::create($reservationData),
            $skipSlotCheck
        );

        if (!$reservation) {
            return back()->withErrors(['error' => 'Ce créneau est déjà réservé. Veuillez choisir un autre horaire.']);
        }

        return redirect($redirectUrl)
            ->with('success', 'Votre réservation a bien été enregistrée !');
    }

    /**
     * Retourne les réservations en JSON pour le calendrier embarqué.
     */
    public function getReservationsWeb($slug)
    {
        $entreprise = $this->resolveEntreprise($slug);
        if (!$entreprise) {
            return response()->json([]);
        }

        $membreId = request()->get('membre_id');

        $query = Reservation::where('entreprise_id', $entreprise->id)
            ->whereIn('statut', ['en_attente', 'confirmee']);

        if ($membreId && $entreprise->aGestionMultiPersonnes()) {
            $query->where('membre_id', $membreId);
        }

        $reservations = $query->get()->map(fn($r) => [
            'id'    => $r->id,
            'title' => 'Indisponible',
            'start' => Carbon::parse($r->date_reservation)->toIso8601String(),
            'end'   => Carbon::parse($r->date_reservation)->addMinutes((int) ($r->duree_minutes ?? 30))->toIso8601String(),
            'color' => '#9ca3af',
        ]);

        return response()->json($reservations);
    }

    /**
     * Mettre à jour le contenu du site web vitrine
     */
    public function update(Request $request, $slug)
    {
        $user = Auth::user();
        
        if (!$user) {
            abort(403, 'Vous devez être connecté pour modifier ce site.');
        }

        // Chercher d'abord par slug_web
        $entreprise = Entreprise::where('slug_web', $slug)->first();
        
        // Si pas trouvé, chercher par slug (pour permettre au propriétaire d'accéder)
        if (!$entreprise) {
            $entreprise = Entreprise::where('slug', $slug)
                ->where('user_id', $user->id)
                ->first();
        }

        if (!$entreprise) {
            abort(404, 'Site web introuvable.');
        }

        // Vérifier que l'utilisateur est le propriétaire
        if ($entreprise->user_id !== $user->id) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier ce site.');
        }

        $validated = $request->validate([
            'phrase_accroche' => ['nullable', 'string', 'max:500'],
            'slug_web' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', 'unique:entreprises,slug_web,' . $entreprise->id],
            'contenu_site_web' => ['nullable', 'json'],
        ]);

        // Si le slug_web change, vérifier qu'il n'existe pas déjà
        if (isset($validated['slug_web']) && $validated['slug_web'] !== $entreprise->slug_web) {
            $existing = Entreprise::where('slug_web', $validated['slug_web'])
                ->where('id', '!=', $entreprise->id)
                ->first();
            
            if ($existing) {
                return back()->withErrors(['slug_web' => 'Ce slug est déjà utilisé.']);
            }
        }

        // Décoder le JSON si fourni
        if (isset($validated['contenu_site_web'])) {
            $validated['contenu_site_web'] = json_decode($validated['contenu_site_web'], true);
        }

        $entreprise->update($validated);
        
        // Recharger l'entreprise pour avoir le nouveau slug_web
        $entreprise->refresh();

        return redirect()->route('site-web.show', ['slug' => $entreprise->slug_web ?? $entreprise->slug])
            ->with('success', 'Votre site web a été mis à jour.');
    }

    /**
     * Trouver l'entreprise par slug (helper)
     */
    private function findEntrepriseBySlug($slug)
    {
        $user = Auth::user();
        
        if (!$user) {
            return null;
        }

        // Chercher par slug_web ou slug
        $entreprise = Entreprise::where('slug_web', $slug)->first();
        
        if (!$entreprise) {
            $entreprise = Entreprise::where('slug', $slug)
                ->where('user_id', $user->id)
                ->first();
        }

        // Vérifier que l'utilisateur est le propriétaire
        if ($entreprise && $entreprise->user_id !== $user->id) {
            return null;
        }

        return $entreprise;
    }

    /**
     * Sauvegarder le contenu du site web (API AJAX)
     */
    public function saveContent(Request $request, $slug)
    {
        $entreprise = $this->findEntrepriseBySlug($slug);
        
        if (!$entreprise) {
            return response()->json(['error' => 'Site introuvable ou accès non autorisé'], 404);
        }
        
        // DEBUG: Loguer le contenu brut AVANT validation
        \Illuminate\Support\Facades\Log::info('SiteWebController::saveContent START', [
            'slug' => $slug,
            'request_all_keys' => array_keys($request->all()),
            'content_type' => gettype($request->input('content')),
            'theme_exists' => $request->has('content.theme'),
        ]);

        try {
            $validated = $request->validate([
                'content' => ['required', 'array'],
                'content.theme' => ['required', 'array'],
                'content.blocks' => ['required', 'array'],
                'is_auto_save' => ['boolean'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::error('SiteWebController::saveContent VALIDATION ERROR', [
                'errors' => $e->errors(),
                'slug' => $slug
            ]);
            throw $e;
        }

        // Utiliser directement l'input pour éviter tout filtrage potentiel par le validateur
        $content = $request->input('content');
        
        $content['lastSaved'] = now()->toIso8601String();
        $content['version'] = ($entreprise->contenu_site_web['version'] ?? 0) + 1;

        // Log the theme being saved
        \Illuminate\Support\Facades\Log::info('SiteWebController::saveContent SAVING', [
            'slug' => $slug,
            'version' => $content['version'],
            'theme_colors' => $content['theme']['colors'] ?? 'MISSING',
        ]);

        // Créer une version de sauvegarde
        SiteWebVersion::createVersion($entreprise, $validated['is_auto_save'] ?? true);
        
        // Nettoyer les anciennes versions (garder les 50 dernières)
        SiteWebVersion::cleanOldVersions($entreprise, 50);

        $entreprise->update([
            'contenu_site_web' => $content,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contenu sauvegardé',
            'version' => $content['version'],
            'lastSaved' => $content['lastSaved'],
        ]);
    }

    /**
     * Upload d'image pour l'éditeur
     */
    public function uploadImage(Request $request, $slug)
    {
        $entreprise = $this->findEntrepriseBySlug($slug);
        
        if (!$entreprise) {
            return response()->json(['error' => 'Site introuvable ou accès non autorisé'], 404);
        }

        $validated = $request->validate([
            'image' => ['required', 'image', 'max:5120'], // Max 5MB
            'block_id' => ['nullable', 'string'],
            'field' => ['nullable', 'string'],
        ]);

        try {
            $imageService = app(ImageService::class);
            $path = $imageService->processAndStore($request->file('image'), 'site-web/' . $entreprise->id);

            return response()->json([
                'success' => true,
                'url' => asset('storage/' . $path),
                'path' => $path,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de l\'upload: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Charger un template prédéfini
     */
    public function loadTemplate(Request $request, $slug)
    {
        $entreprise = $this->findEntrepriseBySlug($slug);
        
        if (!$entreprise) {
            return response()->json(['error' => 'Site introuvable ou accès non autorisé'], 404);
        }

        $validated = $request->validate([
            'template' => ['required', 'string', 'in:default,services,portfolio,minimal'],
        ]);

        // Charger les données de l'entreprise
        $entreprise->load(['typesServices', 'realisationPhotos', 'avis']);

        $template = $this->generateTemplate($entreprise, $validated['template']);

        return response()->json([
            'success' => true,
            'content' => $template,
        ]);
    }

    /**
     * Générer un template de base
     */
    private function generateTemplate(Entreprise $entreprise, string $templateName): array
    {
        $blocks = [];

        // Hero avec les infos de l'entreprise
        $blocks[] = [
            'id' => 'hero-' . Str::random(8),
            'type' => 'hero',
            'content' => [
                'title' => $entreprise->nom,
                'subtitle' => $entreprise->phrase_accroche ?? $entreprise->type_activite,
                'buttonText' => 'Nous contacter',
                'buttonLink' => '#contact',
                'backgroundImage' => $entreprise->image_fond,
                'overlay' => true,
            ],
            'settings' => [
                'height' => 'large',
                'alignment' => 'center',
                'overlayOpacity' => 50,
            ],
            'animation' => 'fadeIn',
        ];

        // Description si disponible
        if ($entreprise->description) {
            $blocks[] = [
                'id' => 'text-' . Str::random(8),
                'type' => 'text',
                'content' => [
                    'html' => '<p>' . nl2br(e($entreprise->description)) . '</p>',
                ],
                'settings' => [
                    'alignment' => 'center',
                    'maxWidth' => 'prose',
                ],
                'animation' => 'slideUp',
            ];
        }

        // Services si disponibles
        if ($entreprise->typesServices && $entreprise->typesServices->count() > 0) {
            $blocks[] = [
                'id' => 'services-' . Str::random(8),
                'type' => 'services',
                'content' => [
                    'title' => 'Nos Services',
                    'items' => $entreprise->typesServices->map(fn($s) => [
                        'name' => $s->nom,
                        'description' => $s->description ?? '',
                        'price' => $s->prix ? $s->prix . '€' : '',
                        'duration' => $s->duree ? $s->duree . ' min' : '',
                    ])->toArray(),
                ],
                'settings' => [
                    'layout' => 'grid',
                    'columns' => 3,
                ],
                'animation' => 'slideUp',
            ];
        }

        // Galerie si photos disponibles
        if ($entreprise->realisationPhotos && $entreprise->realisationPhotos->count() > 0) {
            $blocks[] = [
                'id' => 'gallery-' . Str::random(8),
                'type' => 'gallery',
                'content' => [
                    'title' => 'Nos Réalisations',
                    'columns' => 3,
                    'images' => $entreprise->realisationPhotos->map(fn($p) => [
                        'src' => $p->photo_path,
                        'alt' => $p->titre ?? 'Réalisation',
                        'title' => $p->titre,
                    ])->toArray(),
                ],
                'settings' => [
                    'gap' => 'medium',
                    'rounded' => true,
                ],
                'animation' => 'fadeIn',
            ];
        }

        // Témoignages si avis disponibles
        if ($entreprise->avis && $entreprise->avis->count() > 0) {
            $blocks[] = [
                'id' => 'testimonials-' . Str::random(8),
                'type' => 'testimonials',
                'content' => [
                    'title' => 'Ce que disent nos clients',
                    'items' => $entreprise->avis->take(6)->map(fn($a) => [
                        'text' => $a->commentaire,
                        'author' => $a->user->name ?? 'Client',
                        'rating' => $a->note,
                    ])->toArray(),
                ],
                'settings' => [
                    'layout' => 'carousel',
                    'autoplay' => true,
                ],
                'animation' => 'slideUp',
            ];
        }

        // Contact
        $blocks[] = [
            'id' => 'contact-' . Str::random(8),
            'type' => 'contact',
            'content' => [
                'title' => 'Contactez-nous',
                'showEmail' => true,
                'showPhone' => true,
                'showAddress' => true,
                'showMap' => false,
            ],
            'settings' => [
                'layout' => 'centered',
            ],
            'animation' => 'slideUp',
        ];

        return [
            'theme' => Entreprise::getDefaultSiteWebContent()['theme'],
            'blocks' => $blocks,
            'version' => 1,
            'lastSaved' => now()->toIso8601String(),
        ];
    }

    /**
     * Récupérer l'historique des versions
     */
    public function getVersions(Request $request, $slug)
    {
        $entreprise = $this->findEntrepriseBySlug($slug);
        
        if (!$entreprise) {
            return response()->json(['error' => 'Site introuvable ou accès non autorisé'], 404);
        }

        $versions = SiteWebVersion::where('entreprise_id', $entreprise->id)
            ->orderBy('version_number', 'desc')
            ->take(20)
            ->get()
            ->map(function ($version) {
                return [
                    'id' => $version->id,
                    'version_number' => $version->version_number,
                    'is_auto_save' => $version->is_auto_save,
                    'description' => $version->description,
                    'created_at' => $version->created_at->format('d/m/Y H:i'),
                    'created_at_human' => $version->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'versions' => $versions,
        ]);
    }

    /**
     * Restaurer une version précédente
     */
    public function restoreVersion(Request $request, $slug, $versionId)
    {
        $entreprise = $this->findEntrepriseBySlug($slug);
        
        if (!$entreprise) {
            return response()->json(['error' => 'Site introuvable ou accès non autorisé'], 404);
        }

        $version = SiteWebVersion::where('id', $versionId)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        // Sauvegarder la version actuelle avant restauration
        SiteWebVersion::createVersion($entreprise, false, 'Avant restauration de la version ' . $version->version_number);

        // Restaurer la version
        $content = $version->contenu;
        $content['version'] = ($entreprise->contenu_site_web['version'] ?? 0) + 1;
        $content['lastSaved'] = now()->toIso8601String();

        $entreprise->update([
            'contenu_site_web' => $content,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Version restaurée avec succès',
            'content' => $content,
        ]);
    }

    /**
     * Rendre un bloc en HTML (pour insertion AJAX)
     */
    public function renderBlock(Request $request, $slug)
    {
        $entreprise = $this->findEntrepriseBySlug($slug);
        
        if (!$entreprise) {
            return response()->json(['error' => 'Site introuvable ou accès non autorisé'], 403);
        }

        $validated = $request->validate([
            'block' => ['required', 'array'],
            'block.id' => ['required', 'string'],
            'block.type' => ['required', 'string', 'in:hero,text,image,gallery,contact,video,services,testimonials,cta,divider,iframe,faq,team,stats,features,map,columns,reservation,agenda,login-cta'],
            'block.content' => ['required', 'array'],
            'block.settings' => ['nullable', 'array'],
            'block.animation' => ['nullable', 'string'],
        ]);

        $block = $validated['block'];
        
        // Charger les relations nécessaires pour certains blocs
        $entreprise->load(['typesServices', 'realisationPhotos', 'avis', 'user']);

        try {
            $html = view('components.site-web.blocks.' . $block['type'], [
                'block' => $block,
                'entreprise' => $entreprise,
                'editMode' => true,
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du rendu du bloc: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  CRUD Pages (Onglets)
    // ═══════════════════════════════════════════════════════════

    /**
     * Créer une nouvelle page (onglet).
     * Si c'est la première page créée et que l'entreprise a du contenu V1 (JSON),
     * on migre automatiquement l'ancien contenu comme page "Accueil" en position 0.
     */
    public function storePage(Request $request, $slug)
    {
        $entreprise = $this->findEntrepriseBySlug($slug);
        if (!$entreprise) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'nom'  => 'required|string|max:255',
            'type' => 'required|string|in:custom,reservation,agenda,contact,services',
            'icone' => 'nullable|string|max:50',
        ]);

        // Vérifier l'unicité des types systeme (un seul onglet reservation, etc.)
        if (in_array($validated['type'], SiteWebPage::SYSTEM_TYPES)) {
            $exists = $entreprise->siteWebPages()->where('type', $validated['type'])->exists();
            if ($exists) {
                return response()->json([
                    'error' => 'Un onglet de type "' . $validated['type'] . '" existe déjà.',
                ], 422);
            }
        }

        // ── Transition V1 → V2 : migrer le contenu JSON existant ──
        $migratedPage = null;
        $isFirstPage = $entreprise->siteWebPages()->count() === 0;

        if ($isFirstPage) {
            $v1Blocks = $entreprise->getSiteWebBlocks();
            if (!empty($v1Blocks)) {
                $migratedPage = $entreprise->siteWebPages()->create([
                    'nom'      => 'Accueil',
                    'slug'     => 'accueil',
                    'type'     => 'custom',
                    'blocs'    => $v1Blocks,
                    'ordre'    => 0,
                    'est_actif' => true,
                    'icone'    => 'home',
                ]);
            }
        }

        $pageSlug = Str::slug($validated['nom']);
        // Assurer l'unicité du slug pour cette entreprise
        $baseSlug = $pageSlug;
        $counter = 1;
        while ($entreprise->siteWebPages()->where('slug', $pageSlug)->exists()) {
            $pageSlug = $baseSlug . '-' . $counter++;
        }

        $maxOrdre = $entreprise->siteWebPages()->max('ordre') ?? -1;

        $page = $entreprise->siteWebPages()->create([
            'nom'      => $validated['nom'],
            'slug'     => $pageSlug,
            'type'     => $validated['type'],
            'blocs'    => $validated['type'] === 'custom' ? [] : null,
            'ordre'    => $maxOrdre + 1,
            'est_actif' => true,
            'icone'    => $validated['icone'] ?? null,
        ]);

        // Retourner toutes les pages (y compris la page Accueil migrée) pour que l'éditeur se mette à jour
        $allPages = $entreprise->siteWebPages()->orderBy('ordre')->get();

        return response()->json([
            'success' => true,
            'page' => $page,
            'all_pages' => $allPages,
            'migrated' => $migratedPage !== null,
        ]);
    }

    /**
     * Mettre à jour une page.
     */
    public function updatePage(Request $request, $slug, $pageId)
    {
        $entreprise = $this->findEntrepriseBySlug($slug);
        if (!$entreprise) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $page = $entreprise->siteWebPages()->findOrFail($pageId);

        $validated = $request->validate([
            'nom'      => 'sometimes|string|max:255',
            'blocs'    => 'sometimes|nullable|array',
            'est_actif' => 'sometimes|boolean',
            'icone'    => 'sometimes|nullable|string|max:50',
        ]);

        if (isset($validated['nom']) && $validated['nom'] !== $page->nom) {
            $newSlug = Str::slug($validated['nom']);
            $base = $newSlug;
            $c = 1;
            while ($entreprise->siteWebPages()->where('slug', $newSlug)->where('id', '!=', $page->id)->exists()) {
                $newSlug = $base . '-' . $c++;
            }
            $validated['slug'] = $newSlug;
        }

        $page->update($validated);

        return response()->json(['success' => true, 'page' => $page->fresh()]);
    }

    /**
     * Supprimer une page.
     */
    public function deletePage(Request $request, $slug, $pageId)
    {
        $entreprise = $this->findEntrepriseBySlug($slug);
        if (!$entreprise) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $page = $entreprise->siteWebPages()->findOrFail($pageId);
        $page->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Réordonner les pages.
     */
    public function reorderPages(Request $request, $slug)
    {
        $entreprise = $this->findEntrepriseBySlug($slug);
        if (!$entreprise) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:site_web_pages,id',
        ]);

        foreach ($validated['order'] as $index => $pageId) {
            $entreprise->siteWebPages()->where('id', $pageId)->update(['ordre' => $index]);
        }

        return response()->json(['success' => true]);
    }
}
