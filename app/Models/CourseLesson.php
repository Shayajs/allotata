<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseLesson extends Model
{
    protected $fillable = [
        'module_id',
        'titre',
        'description',
        'contenu_rich_html',
        'contenu_blocks_json',
        'image_path',
        'type',
        'ordre',
        'points_quiz',
        'est_actif',
        'is_draft',
        'published_at',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
        'is_draft' => 'boolean',
        'ordre' => 'integer',
        'points_quiz' => 'integer',
        'contenu_blocks_json' => 'array',
        'published_at' => 'datetime',
    ];

    /**
     * Relation : Une leçon appartient à un module
     */
    public function module()
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    /**
     * Relation : Une leçon (quiz) a plusieurs questions
     */
    public function quizQuestions()
    {
        return $this->hasMany(QuizQuestion::class, 'lesson_id')->orderBy('ordre');
    }

    /**
     * Relation : Une leçon a plusieurs progressions utilisateur
     */
    public function userProgress()
    {
        return $this->hasMany(UserLessonProgress::class, 'lesson_id');
    }

    /**
     * Obtenir la progression d'un utilisateur pour cette leçon
     */
    public function getUserProgress(?User $user): ?UserLessonProgress
    {
        if (!$user) {
            return null;
        }

        return $this->userProgress()
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Vérifier si une leçon est complétée par un utilisateur
     */
    public function isCompletedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $progress = $this->getUserProgress($user);
        return $progress && $progress->completed_at !== null;
    }

    /**
     * Vérifier si un utilisateur peut accéder à cette leçon
     */
    public function isAccessibleBy(?User $user): bool
    {
        // Si admin, toujours accessible
        if ($user && $user->is_admin) {
            return true;
        }

        // Récupérer toutes les leçons du module
        $lessons = $this->module->activeLessons;
        $currentIndex = $lessons->search(function ($lesson) {
            return $lesson->id === $this->id;
        });

        // Si première leçon, accessible
        if ($currentIndex === 0) {
            return true;
        }

        // Si pas connecté, pas accessible (sauf première)
        if (!$user) {
            return false;
        }

        // Vérifier si la leçon précédente est complétée
        if ($currentIndex > 0) {
            $previousLesson = $lessons[$currentIndex - 1];
            return $previousLesson->isCompletedBy($user);
        }

        return false;
    }

    /**
     * Obtenir la leçon précédente dans le module
     */
    public function previousLesson(): ?self
    {
        $lessons = $this->module->activeLessons;
        $currentIndex = $lessons->search(function ($lesson) {
            return $lesson->id === $this->id;
        });

        if ($currentIndex > 0) {
            return $lessons[$currentIndex - 1];
        }

        return null;
    }

    /**
     * Obtenir la leçon suivante dans le module
     */
    public function nextLesson(): ?self
    {
        $lessons = $this->module->activeLessons;
        $currentIndex = $lessons->search(function ($lesson) {
            return $lesson->id === $this->id;
        });

        if ($currentIndex !== false && $currentIndex < $lessons->count() - 1) {
            return $lessons[$currentIndex + 1];
        }

        return null;
    }

    /**
     * Vérifier si c'est un quiz
     */
    public function isQuiz(): bool
    {
        return $this->type === 'quiz';
    }

    /**
     * Vérifier si c'est un cours
     */
    public function isCourse(): bool
    {
        return $this->type === 'course';
    }

    /**
     * Vérifier si la leçon est publiée
     */
    public function isPublished(): bool
    {
        return !$this->is_draft && $this->published_at !== null;
    }

    /**
     * Générer le HTML à partir des blocs JSON
     * Utilisé lors de la publication pour créer le contenu_rich_html
     */
    public function generateHtmlFromBlocks(): string
    {
        if (empty($this->contenu_blocks_json) || !is_array($this->contenu_blocks_json)) {
            return $this->contenu_rich_html ?? '';
        }

        $html = '';
        
        foreach ($this->contenu_blocks_json as $block) {
            try {
                $blockType = $block['type'] ?? null;
                if (!$blockType) {
                    continue;
                }

                // Rendre le bloc selon son type
                $html .= $this->renderBlock($block);
            } catch (\Exception $e) {
                \Log::error('Erreur lors du rendu du bloc dans CourseLesson::generateHtmlFromBlocks', [
                    'lesson_id' => $this->id,
                    'block' => $block,
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        // Nettoyer le HTML avec HTML Purifier
        return \App\Services\HtmlPurifierService::purify($html);
    }

    /**
     * Rendre un bloc individuel en HTML (public pour utilisation depuis le contrôleur)
     */
    public function renderBlock(array $block): string
    {
        $blockType = $block['type'] ?? null;
        $blockContent = $block['content'] ?? [];
        $blockSettings = $block['settings'] ?? [];

        // Vue pour ce type de bloc
        $viewPath = "components.course-blocks.{$blockType}";
        
        // Si le composant course-blocks n'existe pas, essayer site-web (blocs génériques)
        if (!view()->exists($viewPath)) {
            $viewPath = "components.site-web.blocks.{$blockType}";
        }

        // Si toujours pas trouvé, utiliser un rendu de base
        if (!view()->exists($viewPath)) {
            return $this->renderBlockFallback($block);
        }

        try {
            return view($viewPath, [
                'block' => $block,
                'lesson' => $this,
                'entreprise' => null, // Pas utilisé pour les cours
                'content' => $blockContent,
                'settings' => $blockSettings,
                'editMode' => false, // Mode rendu final (pas édition)
            ])->render();
        } catch (\Exception $e) {
            \Log::error('Erreur lors du rendu du bloc', [
                'block_type' => $blockType,
                'error' => $e->getMessage()
            ]);
            return $this->renderBlockFallback($block);
        }
    }

    /**
     * Rendre un bloc pour l'édition (mode édition = true)
     */
    public function renderBlockForEdit(array $block): string
    {
        $blockType = $block['type'] ?? null;
        $blockContent = $block['content'] ?? [];
        $blockSettings = $block['settings'] ?? [];

        // Vue pour ce type de bloc
        $viewPath = "components.course-blocks.{$blockType}";
        
        // Si le composant course-blocks n'existe pas, essayer site-web (blocs génériques)
        if (!view()->exists($viewPath)) {
            $viewPath = "components.site-web.blocks.{$blockType}";
        }

        // Si toujours pas trouvé, utiliser un rendu de base
        if (!view()->exists($viewPath)) {
            return $this->renderBlockFallback($block);
        }

        try {
            return view($viewPath, [
                'block' => $block,
                'lesson' => $this,
                'entreprise' => null, // Pas utilisé pour les cours
                'content' => $blockContent,
                'settings' => $blockSettings,
                'editMode' => true, // Mode édition
            ])->render();
        } catch (\Exception $e) {
            \Log::error('Erreur lors du rendu du bloc pour édition', [
                'block_type' => $blockType,
                'error' => $e->getMessage()
            ]);
            return $this->renderBlockFallback($block);
        }
    }

    /**
     * Rendu de secours si le bloc n'a pas de composant
     */
    protected function renderBlockFallback(array $block): string
    {
        $blockType = $block['type'] ?? 'unknown';
        $blockContent = $block['content'] ?? [];
        
        // Rendu minimal pour les types courants
        if ($blockType === 'text' && isset($blockContent['html'])) {
            return '<div class="course-block course-block-text">' . $blockContent['html'] . '</div>';
        }
        
        if ($blockType === 'heading' && isset($blockContent['text'])) {
            $level = $blockContent['level'] ?? 2;
            return "<h{$level} class=\"course-block course-block-heading\">{$blockContent['text']}</h{$level}>";
        }
        
        if ($blockType === 'image' && isset($blockContent['src'])) {
            $alt = $blockContent['alt'] ?? '';
            $caption = isset($blockContent['caption']) ? "<figcaption>{$blockContent['caption']}</figcaption>" : '';
            return "<figure class=\"course-block course-block-image\"><img src=\"{$blockContent['src']}\" alt=\"{$alt}\">{$caption}</figure>";
        }

        // Rendu générique
        return '<div class="course-block course-block-unknown">[Bloc: ' . htmlspecialchars($blockType) . ']</div>';
    }

    /**
     * Obtenir les blocs ou initialiser avec un bloc par défaut
     */
    public function getBlocks(): array
    {
        $blocks = $this->contenu_blocks_json ?? [];
        
        // Si pas de blocs et qu'on a du HTML, créer un bloc texte à partir du HTML
        if (empty($blocks) && !empty($this->contenu_rich_html)) {
            return [
                [
                    'id' => 'block-' . uniqid(),
                    'type' => 'text',
                    'content' => [
                        'html' => $this->contenu_rich_html,
                    ],
                    'settings' => [],
                ]
            ];
        }
        
        return $blocks;
    }

    /**
     * Obtenir le contenu par défaut (blocs initiaux)
     */
    public static function getDefaultBlocks(): array
    {
        return [
            [
                'id' => 'block-' . uniqid(),
                'type' => 'heading',
                'content' => [
                    'text' => 'Titre de la leçon',
                    'level' => 1,
                ],
                'settings' => [
                    'alignment' => 'left',
                ],
            ],
            [
                'id' => 'block-' . uniqid(),
                'type' => 'text',
                'content' => [
                    'html' => '<p>Commencez à éditer votre cours en ajoutant des blocs dans la sidebar.</p>',
                ],
                'settings' => [],
            ],
        ];
    }
}

