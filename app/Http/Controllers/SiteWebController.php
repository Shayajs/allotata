<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\SiteWebVersion;
use App\Services\ImageService;
use App\Services\SiteWebTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SiteWebController extends Controller
{
    /**
     * Afficher le site web vitrine d'une entreprise
     */
    public function show(Request $request, $slug)
    {
        // Chercher d'abord par slug_web
        $entreprise = Entreprise::where('slug_web', $slug)->first();

        // Si pas trouvé par slug_web, chercher par slug classique
        if (!$entreprise) {
            $entreprise = Entreprise::where('slug', $slug)->first();
        }
        
        if (!$entreprise) {
            abort(404, 'Site web introuvable. Vérifiez que le slug est correct.');
        }

        $user = Auth::user();
        $isOwner = $user && $entreprise->user_id === $user->id;

        // Si ce n'est pas le propriétaire, vérifier les conditions strictes
        if (!$isOwner) {
            // L'entreprise doit être vérifiée pour les visiteurs
            if (!$entreprise->est_verifiee) {
                abort(404, 'Site web non disponible.');
            }

            // L'entreprise doit avoir un abonnement site web actif pour les visiteurs
            if (!$entreprise->aSiteWebActif()) {
                abort(404, 'Site web non disponible.');
            }
            
            // Les visiteurs doivent accéder via le bon slug (slug_web si défini, sinon slug)
            $expectedSlug = $entreprise->slug_web ?? $entreprise->slug;
            
            if ($slug !== $expectedSlug) {
                // Si le slug utilisé n'est pas celui attendu, on redirige ou 404
                // Ici 404 pour sécurité
                abort(404, 'Site web introuvable.');
            }
        } else {
            // Le propriétaire peut accéder même si l'entreprise n'est pas vérifiée ou n'a pas d'abonnement
            // Mais on affiche un avertissement si nécessaire
        }
        
        // Déterminer le mode
        $requestedMode = $request->query('mode');
        
        if ($isOwner) {
            // Si le propriétaire accède sans paramètre ?mode=, mode édition par défaut
            if ($requestedMode === null) {
                $mode = 'edit';
            } 
            // Si le propriétaire force le mode view avec ?mode=view
            else if ($requestedMode === 'view') {
                $mode = 'view';
            }
            // Si le propriétaire force le mode edit avec ?mode=edit (redondant mais possible)
            else {
                $mode = 'edit';
            }
        } else {
            // Si ce n'est pas le propriétaire, toujours en mode view
            $mode = 'view';
        }

        if ($mode === 'edit') {
            // Charger les relations nécessaires
            $entreprise->load(['realisationPhotos', 'typesServices', 'avis']);
            
            // Générer le contenu initial si vide
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

        return view('public.site-web', [
            'entreprise' => $entreprise,
            'isOwner' => $isOwner,
        ]);
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

        $validated = $request->validate([
            'content' => ['required', 'array'],
            'content.theme' => ['required', 'array'],
            'content.blocks' => ['required', 'array'],
            'is_auto_save' => ['boolean'],
        ]);

        $content = $validated['content'];
        $content['lastSaved'] = now()->toIso8601String();
        $content['version'] = ($entreprise->contenu_site_web['version'] ?? 0) + 1;

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
                'url' => route('storage.serve', ['path' => $path]),
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
            'block.type' => ['required', 'string', 'in:hero,text,image,gallery,contact,video,services,testimonials,cta,divider,iframe,faq,team,stats,features,map,columns'],
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
}
