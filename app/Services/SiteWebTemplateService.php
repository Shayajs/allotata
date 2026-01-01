<?php

namespace App\Services;

use App\Models\Entreprise;
use Illuminate\Support\Str;

class SiteWebTemplateService
{
    /**
     * Générer un template initial complet pour une entreprise
     */
    public function generateInitialTemplate(Entreprise $entreprise, string $style = 'default'): array
    {
        // Charger les relations si pas déjà chargées
        $entreprise->loadMissing(['typesServices', 'realisationPhotos', 'avis', 'user']);

        $theme = $this->getThemeForStyle($style);
        $blocks = $this->generateBlocksForEntreprise($entreprise, $style);

        return [
            'theme' => $theme,
            'blocks' => $blocks,
            'version' => 1,
            'lastSaved' => now()->toIso8601String(),
        ];
    }

    /**
     * Obtenir le thème selon le style
     */
    private function getThemeForStyle(string $style): array
    {
        $themes = [
            'default' => [
                'colors' => [
                    'primary' => '#22c55e',
                    'secondary' => '#f97316',
                    'accent' => '#3b82f6',
                    'background' => '#ffffff',
                    'text' => '#1e293b',
                ],
                'fonts' => [
                    'heading' => 'Poppins',
                    'body' => 'Inter',
                ],
                'buttons' => [
                    'style' => 'rounded',
                    'shadow' => true,
                ],
            ],
            'elegant' => [
                'colors' => [
                    'primary' => '#1e40af',
                    'secondary' => '#b45309',
                    'accent' => '#6366f1',
                    'background' => '#fafaf9',
                    'text' => '#292524',
                ],
                'fonts' => [
                    'heading' => 'Playfair Display',
                    'body' => 'Lora',
                ],
                'buttons' => [
                    'style' => 'square',
                    'shadow' => false,
                ],
            ],
            'modern' => [
                'colors' => [
                    'primary' => '#7c3aed',
                    'secondary' => '#06b6d4',
                    'accent' => '#f43f5e',
                    'background' => '#ffffff',
                    'text' => '#0f172a',
                ],
                'fonts' => [
                    'heading' => 'Space Grotesk',
                    'body' => 'IBM Plex Sans',
                ],
                'buttons' => [
                    'style' => 'pill',
                    'shadow' => true,
                ],
            ],
            'minimal' => [
                'colors' => [
                    'primary' => '#171717',
                    'secondary' => '#737373',
                    'accent' => '#171717',
                    'background' => '#ffffff',
                    'text' => '#171717',
                ],
                'fonts' => [
                    'heading' => 'DM Sans',
                    'body' => 'DM Sans',
                ],
                'buttons' => [
                    'style' => 'square',
                    'shadow' => false,
                ],
            ],
            'nature' => [
                'colors' => [
                    'primary' => '#15803d',
                    'secondary' => '#a16207',
                    'accent' => '#0d9488',
                    'background' => '#f0fdf4',
                    'text' => '#14532d',
                ],
                'fonts' => [
                    'heading' => 'Merriweather',
                    'body' => 'Source Sans Pro',
                ],
                'buttons' => [
                    'style' => 'rounded',
                    'shadow' => false,
                ],
            ],
        ];

        return $themes[$style] ?? $themes['default'];
    }

    /**
     * Générer les blocs pour une entreprise
     */
    private function generateBlocksForEntreprise(Entreprise $entreprise, string $style): array
    {
        $blocks = [];

        // 1. Hero - Toujours présent
        $blocks[] = $this->createHeroBlock($entreprise);

        // 2. Texte de présentation - si description disponible
        if (!empty($entreprise->description)) {
            $blocks[] = $this->createTextBlock($entreprise);
        }

        // 3. Statistiques - données générées
        $blocks[] = $this->createStatsBlock($entreprise);

        // 4. Services - si disponibles
        if ($entreprise->typesServices && $entreprise->typesServices->count() > 0) {
            $blocks[] = $this->createServicesBlock($entreprise);
        }

        // 5. Galerie - si photos disponibles
        if ($entreprise->realisationPhotos && $entreprise->realisationPhotos->count() > 0) {
            $blocks[] = $this->createGalleryBlock($entreprise);
        }

        // 6. Appel à l'action intermédiaire
        $blocks[] = $this->createCtaBlock($entreprise);

        // 7. Témoignages - si avis disponibles
        if ($entreprise->avis && $entreprise->avis->count() > 0) {
            $blocks[] = $this->createTestimonialsBlock($entreprise);
        }

        // 8. Séparateur
        $blocks[] = $this->createDividerBlock();

        // 9. Contact - Toujours présent
        $blocks[] = $this->createContactBlock($entreprise);

        return $blocks;
    }

    /**
     * Créer le bloc Hero
     */
    private function createHeroBlock(Entreprise $entreprise): array
    {
        return [
            'id' => 'hero-' . Str::random(8),
            'type' => 'hero',
            'content' => [
                'title' => $entreprise->nom,
                'subtitle' => $entreprise->phrase_accroche ?? $this->generateSubtitle($entreprise),
                'buttonText' => 'Découvrir nos services',
                'buttonLink' => '#services',
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
    }

    /**
     * Générer un sous-titre par défaut
     */
    private function generateSubtitle(Entreprise $entreprise): string
    {
        $typeActivite = $entreprise->type_activite;
        $ville = $entreprise->ville;

        if ($ville) {
            return "{$typeActivite} à {$ville}";
        }

        return "Votre {$typeActivite} de confiance";
    }

    /**
     * Créer le bloc texte de présentation
     */
    private function createTextBlock(Entreprise $entreprise): array
    {
        $description = $entreprise->description;
        
        // Formater la description en HTML
        $html = '<h2 class="text-center">À propos de nous</h2>';
        $html .= '<p>' . nl2br(e($description)) . '</p>';

        return [
            'id' => 'about-' . Str::random(8),
            'type' => 'text',
            'content' => [
                'html' => $html,
            ],
            'settings' => [
                'alignment' => 'center',
                'maxWidth' => 'prose',
            ],
            'animation' => 'slideUp',
        ];
    }

    /**
     * Créer le bloc statistiques
     */
    private function createStatsBlock(Entreprise $entreprise): array
    {
        $stats = [];

        // Nombre d'avis
        $nbAvis = $entreprise->avis ? $entreprise->avis->count() : 0;
        if ($nbAvis > 0) {
            $stats[] = [
                'value' => $nbAvis . '+',
                'label' => 'Avis clients',
            ];
        }

        // Note moyenne
        $noteMoyenne = $entreprise->note_moyenne ?? 0;
        if ($noteMoyenne > 0) {
            $stats[] = [
                'value' => number_format($noteMoyenne, 1) . '/5',
                'label' => 'Note moyenne',
            ];
        }

        // Nombre de services
        $nbServices = $entreprise->typesServices ? $entreprise->typesServices->count() : 0;
        if ($nbServices > 0) {
            $stats[] = [
                'value' => $nbServices,
                'label' => 'Services proposés',
            ];
        }

        // Si pas assez de stats, ajouter des valeurs par défaut
        if (count($stats) < 3) {
            $defaults = [
                ['value' => '100%', 'label' => 'Satisfaction client'],
                ['value' => '5+', 'label' => 'Années d\'expérience'],
                ['value' => '7j/7', 'label' => 'Disponibilité'],
            ];

            foreach ($defaults as $default) {
                if (count($stats) >= 3) break;
                $stats[] = $default;
            }
        }

        return [
            'id' => 'stats-' . Str::random(8),
            'type' => 'stats',
            'content' => [
                'items' => array_slice($stats, 0, 4),
            ],
            'settings' => [
                'animated' => true,
                'layout' => 'horizontal',
            ],
            'animation' => 'zoomIn',
        ];
    }

    /**
     * Créer le bloc services
     */
    private function createServicesBlock(Entreprise $entreprise): array
    {
        $services = $entreprise->typesServices->take(6)->map(function ($service) {
            return [
                'name' => $service->nom,
                'description' => $service->description ?? '',
                'price' => $service->prix ? number_format($service->prix, 2) . ' €' : '',
                'duration' => $service->duree ? $service->duree . ' min' : '',
                'icon' => 'star', // Icon par défaut
            ];
        })->toArray();

        return [
            'id' => 'services-' . Str::random(8),
            'type' => 'services',
            'content' => [
                'title' => 'Nos Services',
                'subtitle' => 'Découvrez notre gamme de prestations',
                'items' => $services,
            ],
            'settings' => [
                'layout' => 'grid',
                'columns' => min(3, count($services)),
            ],
            'animation' => 'slideUp',
        ];
    }

    /**
     * Créer le bloc galerie
     */
    private function createGalleryBlock(Entreprise $entreprise): array
    {
        $images = $entreprise->realisationPhotos->take(9)->map(function ($photo) {
            return [
                'src' => $photo->photo_path,
                'alt' => $photo->titre ?? 'Réalisation',
                'title' => $photo->titre,
            ];
        })->toArray();

        return [
            'id' => 'gallery-' . Str::random(8),
            'type' => 'gallery',
            'content' => [
                'title' => 'Nos Réalisations',
                'columns' => 3,
                'images' => $images,
            ],
            'settings' => [
                'gap' => 'medium',
                'rounded' => true,
            ],
            'animation' => 'fadeIn',
        ];
    }

    /**
     * Créer le bloc CTA
     */
    private function createCtaBlock(Entreprise $entreprise): array
    {
        return [
            'id' => 'cta-' . Str::random(8),
            'type' => 'cta',
            'content' => [
                'title' => 'Prêt à prendre rendez-vous ?',
                'subtitle' => 'Contactez-nous dès aujourd\'hui pour discuter de votre projet',
                'buttonText' => 'Nous contacter',
                'buttonLink' => '#contact',
            ],
            'settings' => [
                'style' => 'gradient',
                'alignment' => 'center',
            ],
            'animation' => 'slideUp',
        ];
    }

    /**
     * Créer le bloc témoignages
     */
    private function createTestimonialsBlock(Entreprise $entreprise): array
    {
        $testimonials = $entreprise->avis->take(6)->map(function ($avis) {
            return [
                'text' => $avis->commentaire,
                'author' => $avis->user->name ?? 'Client anonyme',
                'rating' => $avis->note,
                'date' => $avis->created_at->format('F Y'),
            ];
        })->toArray();

        return [
            'id' => 'testimonials-' . Str::random(8),
            'type' => 'testimonials',
            'content' => [
                'title' => 'Ce que disent nos clients',
                'items' => $testimonials,
            ],
            'settings' => [
                'layout' => 'carousel',
                'autoplay' => true,
            ],
            'animation' => 'slideUp',
        ];
    }

    /**
     * Créer le bloc séparateur
     */
    private function createDividerBlock(): array
    {
        return [
            'id' => 'divider-' . Str::random(8),
            'type' => 'divider',
            'content' => [
                'style' => 'dots',
            ],
            'settings' => [
                'spacing' => 'medium',
            ],
            'animation' => 'none',
        ];
    }

    /**
     * Créer le bloc contact
     */
    private function createContactBlock(Entreprise $entreprise): array
    {
        return [
            'id' => 'contact-' . Str::random(8),
            'type' => 'contact',
            'content' => [
                'title' => 'Contactez-nous',
                'subtitle' => 'Nous sommes à votre écoute',
                'showEmail' => !empty($entreprise->email),
                'showPhone' => !empty($entreprise->telephone),
                'showAddress' => !empty($entreprise->ville),
                'showMap' => !empty($entreprise->ville),
            ],
            'settings' => [
                'layout' => 'centered',
            ],
            'animation' => 'slideUp',
        ];
    }

    /**
     * Appliquer un template à une entreprise
     */
    public function applyTemplate(Entreprise $entreprise, string $style = 'default'): void
    {
        $content = $this->generateInitialTemplate($entreprise, $style);
        
        $entreprise->update([
            'contenu_site_web' => $content,
        ]);
    }
}
