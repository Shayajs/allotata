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
        
        foreach ($this->contenu_blocks_json as $index => $block) {
            $shouldContinue = false;
            
            try {
                // Vérifier que c'est bien un tableau
                if (!is_array($block)) {
                    \Log::warning('Bloc non-tableau dans CourseLesson::generateHtmlFromBlocks', [
                        'lesson_id' => $this->id,
                        'block_index' => $index,
                        'block' => $block
                    ]);
                    $shouldContinue = true;
                }

                if (!$shouldContinue) {
                    $blockType = $block['type'] ?? null;
                    if (!$blockType || !is_string($blockType)) {
                        \Log::warning('Type de bloc invalide dans CourseLesson::generateHtmlFromBlocks', [
                            'lesson_id' => $this->id,
                            'block_index' => $index,
                            'block' => $block
                        ]);
                        $shouldContinue = true;
                    }
                }

                if (!$shouldContinue) {
                    // Rendre le bloc selon son type
                    $blockHtml = $this->renderBlock($block);
                    if (!empty($blockHtml)) {
                        $html .= $blockHtml;
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Erreur lors du rendu du bloc dans CourseLesson::generateHtmlFromBlocks', [
                    'lesson_id' => $this->id,
                    'block_index' => $index,
                    'block' => $block,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // On continue avec le prochain bloc en cas d'erreur
            }
        }

        // Si pas de HTML généré, retourner une chaîne vide au lieu d'une erreur
        if (empty($html) || !is_string($html)) {
            \Log::debug('generateHtmlFromBlocks: HTML vide ou non-string', [
                'lesson_id' => $this->id,
                'html_type' => gettype($html),
                'html_length' => is_string($html) ? strlen($html) : 0
            ]);
            return '';
        }

        // Nettoyer le HTML avec HTML Purifier
        try {
            $purified = \App\Services\HtmlPurifierService::purify($html);
            
            // Vérifier que le HTML n'a pas été complètement vidé ou échappé
            // Si le HTML purifié est vide ou beaucoup plus court, il y a peut-être un problème
            if (empty($purified) || (strlen($purified) < strlen($html) * 0.3 && strpos($html, '<section') !== false && strpos($purified, '<section') === false)) {
                \Log::warning('HTML Purifier a peut-être supprimé trop de contenu', [
                    'lesson_id' => $this->id,
                    'original_length' => strlen($html),
                    'purified_length' => strlen($purified),
                    'original_preview' => substr($html, 0, 500),
                    'purified_preview' => substr($purified, 0, 500)
                ]);
                // En mode debug, retourner le HTML original pour diagnostic
                if (config('app.debug')) {
                    return $html;
                }
            }
            
            return is_string($purified) ? $purified : $html;
        } catch (\Exception $e) {
            \Log::error('Erreur lors du nettoyage HTML avec HTML Purifier dans generateHtmlFromBlocks', [
                'lesson_id' => $this->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            // En cas d'erreur, retourner le HTML non nettoyé plutôt que de faire échouer la publication
            return $html;
        }
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
            $rendered = view($viewPath, [
                'block' => $block,
                'lesson' => $this,
                'entreprise' => null, // Pas utilisé pour les cours
                'content' => $blockContent,
                'settings' => $blockSettings,
                'editMode' => false, // Mode rendu final (pas édition)
            ])->render();
            
            return $rendered ?: '';
        } catch (\Exception $e) {
            \Log::error('Erreur lors du rendu du bloc', [
                'block_type' => $blockType,
                'lesson_id' => $this->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
            $html = is_string($blockContent['html']) ? $blockContent['html'] : '';
            return '<div class="course-block course-block-text">' . $html . '</div>';
        }
        
        if ($blockType === 'heading' && isset($blockContent['text'])) {
            $level = (int)($blockContent['level'] ?? 2);
            $text = htmlspecialchars($blockContent['text'] ?? '', ENT_QUOTES, 'UTF-8');
            if ($level < 1 || $level > 6) $level = 2;
            return "<h{$level} class=\"course-block course-block-heading\">{$text}</h{$level}>";
        }
        
        if ($blockType === 'image' && isset($blockContent['src'])) {
            $src = htmlspecialchars($blockContent['src'] ?? '', ENT_QUOTES, 'UTF-8');
            $alt = htmlspecialchars($blockContent['alt'] ?? '', ENT_QUOTES, 'UTF-8');
            $caption = '';
            if (isset($blockContent['caption']) && !empty($blockContent['caption'])) {
                $captionText = htmlspecialchars($blockContent['caption'], ENT_QUOTES, 'UTF-8');
                $caption = "<figcaption>{$captionText}</figcaption>";
            }
            return "<figure class=\"course-block course-block-image\"><img src=\"{$src}\" alt=\"{$alt}\">{$caption}</figure>";
        }

        // Rendu générique
        $blockTypeEscaped = htmlspecialchars((string)$blockType, ENT_QUOTES, 'UTF-8');
        return '<div class="course-block course-block-unknown">[Bloc: ' . $blockTypeEscaped . ']</div>';
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

