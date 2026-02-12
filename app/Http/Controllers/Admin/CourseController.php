<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseModule;
use App\Models\CourseLesson;
use App\Models\QuizQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    /**
     * Liste des modules (mode édition)
     */
    public function index()
    {
        $modules = CourseModule::orderBy('ordre')->with('lessons')->get();
        return view('admin.courses.index', compact('modules'));
    }

    /**
     * Créer un nouveau module
     */
    public function storeModule(Request $request)
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_url' => 'nullable|url|max:500',
            'ordre' => 'nullable|integer|min:0',
            'est_actif' => 'nullable|boolean',
            'image' => 'nullable|image|max:5120', // 5MB max
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('courses/modules', 'public');
        }

        CourseModule::create([
            'titre' => $validated['titre'],
            'description' => $validated['description'] ?? null,
            'image_path' => $validated['image_path'] ?? null,
            'video_url' => $validated['video_url'] ?? null,
            'ordre' => $validated['ordre'] ?? 0,
            'est_actif' => $validated['est_actif'] ?? true,
        ]);

        return redirect()->route('admin.courses.index')
            ->with('success', 'Module créé avec succès.');
    }

    /**
     * Mettre à jour un module
     */
    public function updateModule(Request $request, CourseModule $module)
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_url' => 'nullable|url|max:500',
            'ordre' => 'nullable|integer|min:0',
            'est_actif' => 'nullable|boolean',
            'image' => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image
            if ($module->image_path) {
                Storage::disk('public')->delete($module->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('courses/modules', 'public');
        }

        $module->update([
            'titre' => $validated['titre'],
            'description' => $validated['description'] ?? null,
            'image_path' => $validated['image_path'] ?? $module->image_path,
            'video_url' => array_key_exists('video_url', $validated) ? $validated['video_url'] : $module->video_url,
            'ordre' => $validated['ordre'] ?? $module->ordre,
            'est_actif' => $validated['est_actif'] ?? $module->est_actif,
        ]);

        return redirect()->route('admin.courses.index')
            ->with('success', 'Module mis à jour avec succès.');
    }

    /**
     * Supprimer un module
     */
    public function destroyModule(CourseModule $module)
    {
        // Supprimer l'image
        if ($module->image_path) {
            Storage::disk('public')->delete($module->image_path);
        }

        $module->delete();

        return redirect()->route('admin.courses.index')
            ->with('success', 'Module supprimé avec succès.');
    }

    /**
     * Mettre à jour l'ordre des modules (drag & drop)
     */
    public function updateModuleOrder(Request $request)
    {
        $order = $request->input('order', []);

        foreach ($order as $index => $moduleId) {
            CourseModule::where('id', $moduleId)->update(['ordre' => $index]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Éditer un module avec ses leçons
     */
    public function editModule(CourseModule $module)
    {
        $module->load('lessons.quizQuestions');
        return view('admin.courses.module-edit', compact('module'));
    }

    /**
     * Créer une nouvelle leçon
     */
    public function storeLesson(Request $request, CourseModule $module)
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'contenu_rich_html' => 'nullable|string',
            'type' => 'required|in:course,quiz',
            'ordre' => 'nullable|integer|min:0',
            'points_quiz' => 'nullable|integer|min:0',
            'est_actif' => 'nullable|boolean',
            'image' => 'nullable|image|max:5120',
        ]);

        // Nettoyer le HTML avec HTML Purifier (protection XSS)
        if (!empty($validated['contenu_rich_html'])) {
            $validated['contenu_rich_html'] = \App\Services\HtmlPurifierService::purify($validated['contenu_rich_html']);
        }

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('courses/lessons', 'public');
        }

        $lesson = CourseLesson::create([
            'module_id' => $module->id,
            'titre' => $validated['titre'],
            'description' => $validated['description'] ?? null,
            'contenu_rich_html' => $validated['contenu_rich_html'] ?? null,
            'contenu_blocks_json' => CourseLesson::getDefaultBlocks(), // Initialiser avec des blocs par défaut
            'image_path' => $validated['image_path'] ?? null,
            'type' => $validated['type'],
            'ordre' => $validated['ordre'] ?? 0,
            'points_quiz' => $validated['points_quiz'] ?? 0,
            'est_actif' => $validated['est_actif'] ?? true,
            'is_draft' => true, // Nouveau = brouillon par défaut
        ]);

        // Rediriger vers la page d'édition dédiée
        return redirect()->route('admin.courses.lessons.edit', $lesson)
            ->with('success', 'Leçon créée avec succès. Commencez à éditer votre contenu !');
    }

    /**
     * Mettre à jour une leçon
     */
    public function updateLesson(Request $request, CourseModule $module, CourseLesson $lesson)
    {
        // Vérifier que la leçon appartient au module
        if ($lesson->module_id !== $module->id) {
            abort(404);
        }

        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'contenu_rich_html' => 'nullable|string',
            'type' => 'required|in:course,quiz',
            'ordre' => 'nullable|integer|min:0',
            'points_quiz' => 'nullable|integer|min:0',
            'est_actif' => 'nullable|boolean',
            'image' => 'nullable|image|max:5120',
        ]);

        // Nettoyer le HTML avec HTML Purifier (protection XSS)
        if (!empty($validated['contenu_rich_html'])) {
            $validated['contenu_rich_html'] = \App\Services\HtmlPurifierService::purify($validated['contenu_rich_html']);
        }

        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image
            if ($lesson->image_path) {
                Storage::disk('public')->delete($lesson->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('courses/lessons', 'public');
        }

        $lesson->update([
            'titre' => $validated['titre'],
            'description' => $validated['description'] ?? null,
            'contenu_rich_html' => $validated['contenu_rich_html'] ?? $lesson->contenu_rich_html,
            'image_path' => $validated['image_path'] ?? $lesson->image_path,
            'type' => $validated['type'],
            'ordre' => $validated['ordre'] ?? $lesson->ordre,
            'points_quiz' => $validated['points_quiz'] ?? $lesson->points_quiz,
            'est_actif' => $validated['est_actif'] ?? $lesson->est_actif,
        ]);

        return redirect()->route('admin.courses.module.edit', $module)
            ->with('success', 'Leçon mise à jour avec succès.');
    }

    /**
     * Supprimer une leçon
     */
    public function destroyLesson(CourseModule $module, CourseLesson $lesson)
    {
        // Vérifier que la leçon appartient au module
        if ($lesson->module_id !== $module->id) {
            abort(404);
        }

        // Supprimer l'image
        if ($lesson->image_path) {
            Storage::disk('public')->delete($lesson->image_path);
        }

        $lesson->delete();

        return redirect()->route('admin.courses.module.edit', $module)
            ->with('success', 'Leçon supprimée avec succès.');
    }

    /**
     * Mettre à jour l'ordre des leçons (drag & drop)
     */
    public function updateLessonOrder(Request $request, CourseModule $module)
    {
        $order = $request->input('order', []);

        foreach ($order as $index => $lessonId) {
            CourseLesson::where('id', $lessonId)
                ->where('module_id', $module->id)
                ->update(['ordre' => $index]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Créer une question de quiz
     */
    public function storeQuizQuestion(Request $request, CourseModule $module, CourseLesson $lesson)
    {
        // Vérifier que c'est bien un quiz
        if (!$lesson->isQuiz()) {
            abort(400, 'Cette leçon n\'est pas un quiz.');
        }

        $validated = $request->validate([
            'question' => 'required|string',
            'type' => 'required|in:multiple_choice,true_false,text',
            'options_json' => 'nullable|array',
            'bonne_reponse' => 'required|string',
            'points' => 'nullable|integer|min:1',
            'ordre' => 'nullable|integer|min:0',
        ]);

        QuizQuestion::create([
            'lesson_id' => $lesson->id,
            'question' => $validated['question'],
            'type' => $validated['type'],
            'options_json' => $validated['options_json'] ?? [],
            'bonne_reponse' => $validated['bonne_reponse'],
            'points' => $validated['points'] ?? 1,
            'ordre' => $validated['ordre'] ?? 0,
        ]);

        return redirect()->route('admin.courses.module.edit', $module)
            ->with('success', 'Question ajoutée avec succès.');
    }

    /**
     * Mettre à jour une question de quiz
     */
    public function updateQuizQuestion(Request $request, CourseModule $module, CourseLesson $lesson, QuizQuestion $question)
    {
        // Vérifier que la question appartient à la leçon
        if ($question->lesson_id !== $lesson->id) {
            abort(404);
        }

        $validated = $request->validate([
            'question' => 'required|string',
            'type' => 'required|in:multiple_choice,true_false,text',
            'options_json' => 'nullable|array',
            'bonne_reponse' => 'required|string',
            'points' => 'nullable|integer|min:1',
            'ordre' => 'nullable|integer|min:0',
        ]);

        $question->update($validated);

        return redirect()->route('admin.courses.module.edit', $module)
            ->with('success', 'Question mise à jour avec succès.');
    }

    /**
     * Supprimer une question de quiz
     */
    public function destroyQuizQuestion(CourseModule $module, CourseLesson $lesson, QuizQuestion $question)
    {
        // Vérifier que la question appartient à la leçon
        if ($question->lesson_id !== $lesson->id) {
            abort(404);
        }

        $question->delete();

        return redirect()->route('admin.courses.module.edit', $module)
            ->with('success', 'Question supprimée avec succès.');
    }

    /**
     * Afficher la page d'édition complète d'une leçon
     */
    public function editLesson(CourseLesson $lesson)
    {
        $lesson->load('module', 'quizQuestions');
        
        // Initialiser les blocs si vides
        if (empty($lesson->contenu_blocks_json)) {
            $blocks = $lesson->getBlocks();
            if (empty($blocks)) {
                $blocks = CourseLesson::getDefaultBlocks();
                $lesson->update(['contenu_blocks_json' => $blocks]);
                $lesson->refresh();
            }
        }

        return view('admin.courses.lesson-edit', compact('lesson'));
    }

    /**
     * Sauvegarder comme brouillon (AJAX)
     */
    public function saveDraft(Request $request, CourseLesson $lesson)
    {
        $validated = $request->validate([
            'blocks' => 'required|array',
            'titre' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|in:course,quiz',
            'ordre' => 'nullable|integer|min:0',
            'points_quiz' => 'nullable|integer|min:0',
            'est_actif' => 'nullable|boolean',
            'is_auto_save' => 'nullable|boolean',
        ]);

        // Mettre à jour les autres champs si fournis
        $updateData = [
            'is_draft' => true, // Toujours brouillon avec cette méthode
        ];

        // Mettre à jour les blocs - IMPORTANT: les inclure dans updateData
        if (isset($validated['blocks'])) {
            $updateData['contenu_blocks_json'] = $validated['blocks'];
        }

        if (isset($validated['titre'])) {
            $updateData['titre'] = $validated['titre'];
        }
        if (isset($validated['description'])) {
            $updateData['description'] = $validated['description'];
        }
        if (isset($validated['type'])) {
            $updateData['type'] = $validated['type'];
        }
        if (isset($validated['ordre'])) {
            $updateData['ordre'] = $validated['ordre'];
        }
        if (isset($validated['points_quiz'])) {
            $updateData['points_quiz'] = $validated['points_quiz'];
        }
        if (isset($validated['est_actif'])) {
            $updateData['est_actif'] = $validated['est_actif'];
        }

        $lesson->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Brouillon sauvegardé',
            'lastSaved' => now()->toIso8601String(),
            'is_draft' => $lesson->is_draft,
        ]);
    }

    /**
     * Publier la leçon (AJAX)
     */
    public function publish(Request $request, CourseLesson $lesson)
    {
        try {
            // Validation avec gestion d'erreur JSON
            try {
                $validated = $request->validate([
                    'blocks' => 'nullable|array', // Peut être vide au début
                    'titre' => 'nullable|string|max:255',
                    'description' => 'nullable|string',
                    'type' => 'nullable|in:course,quiz',
                    'ordre' => 'nullable|integer|min:0',
                    'points_quiz' => 'nullable|integer|min:0',
                    'est_actif' => 'nullable|boolean',
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                \Log::error('Erreur de validation lors de la publication', [
                    'lesson_id' => $lesson->id,
                    'errors' => $e->errors()
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Erreur de validation',
                    'errors' => $e->errors(),
                ], 422);
            }

            // Mettre à jour les blocs (garder les anciens si non fournis)
            if (isset($validated['blocks'])) {
                // S'assurer que blocks est bien un array
                if (is_array($validated['blocks'])) {
                    $lesson->contenu_blocks_json = $validated['blocks'];
                } else {
                    \Log::warning('blocks n\'est pas un array dans publish', [
                        'lesson_id' => $lesson->id,
                        'blocks_type' => gettype($validated['blocks'])
                    ]);
                    // Ne pas mettre à jour si ce n'est pas un array
                }
            }
            // Générer le HTML à partir des blocs
            if (!empty($lesson->contenu_blocks_json)) {
                try {
                    // S'assurer que contenu_blocks_json est bien un array
                    if (!is_array($lesson->contenu_blocks_json)) {
                        \Log::warning('contenu_blocks_json n\'est pas un array', [
                            'lesson_id' => $lesson->id,
                            'type' => gettype($lesson->contenu_blocks_json)
                        ]);
                        $lesson->contenu_blocks_json = [];
                    }
                    
                    $generatedHtml = $lesson->generateHtmlFromBlocks();
                    $lesson->contenu_rich_html = $generatedHtml;
                } catch (\Exception $e) {
                    \Log::error('Erreur lors de la génération du HTML depuis les blocs', [
                        'lesson_id' => $lesson->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'blocks_json' => is_array($lesson->contenu_blocks_json) ? 'array of ' . count($lesson->contenu_blocks_json) . ' blocks' : gettype($lesson->contenu_blocks_json)
                    ]);
                    
                    // Ne pas bloquer la publication si le HTML ne peut pas être généré
                    // On met une chaîne vide ou le HTML existant
                    if (empty($lesson->contenu_rich_html)) {
                        $lesson->contenu_rich_html = '';
                    }
                }
            } else {
                // Si pas de blocs, garder le HTML existant ou mettre une chaîne vide
                if (empty($lesson->contenu_rich_html)) {
                    $lesson->contenu_rich_html = '';
                }
            }

            // Mettre à jour les autres champs
            $updateData = [
                'is_draft' => false,
                'contenu_blocks_json' => $lesson->contenu_blocks_json, // IMPORTANT: sauvegarder les blocs
                'contenu_rich_html' => $lesson->contenu_rich_html,     // IMPORTANT: sauvegarder le HTML généré
            ];

            // Définir published_at seulement si c'est la première publication
            if ($lesson->published_at === null) {
                $updateData['published_at'] = now();
            }

            if (isset($validated['titre'])) {
                $updateData['titre'] = $validated['titre'];
            }
            if (isset($validated['description'])) {
                $updateData['description'] = $validated['description'];
            }
            if (isset($validated['type'])) {
                $updateData['type'] = $validated['type'];
            }
            if (isset($validated['ordre'])) {
                $updateData['ordre'] = $validated['ordre'];
            }
            if (isset($validated['points_quiz'])) {
                $updateData['points_quiz'] = $validated['points_quiz'];
            }
            if (isset($validated['est_actif'])) {
                $updateData['est_actif'] = $validated['est_actif'];
            }

            // Sauvegarder les modifications
            $lesson->update($updateData);
            
            // Log pour debug
            \Log::info('Leçon publiée', [
                'lesson_id' => $lesson->id,
                'html_length' => strlen($lesson->contenu_rich_html ?? ''),
                'blocks_count' => is_array($lesson->contenu_blocks_json) ? count($lesson->contenu_blocks_json) : 0,
            ]);
            
            // Rafraîchir le modèle pour obtenir les valeurs à jour
            $lesson->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Leçon publiée avec succès',
                'published_at' => $lesson->published_at ? $lesson->published_at->toIso8601String() : null,
                'is_draft' => false,
                'is_published' => true,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Double check pour les erreurs de validation
            \Log::error('Erreur de validation lors de la publication (catch externe)', [
                'lesson_id' => $lesson->id,
                'errors' => $e->errors()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\QueryException $e) {
            // Erreur de base de données
            \Log::error('Erreur de base de données lors de la publication', [
                'lesson_id' => $lesson->id,
                'error' => $e->getMessage(),
                'sql' => $e->getSql() ?? null
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Erreur de base de données lors de la publication',
                'message' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue lors de la publication',
            ], 500);
        } catch (\Exception $e) {
            // Toute autre exception
            \Log::error('Erreur lors de la publication de la leçon', [
                'lesson_id' => $lesson->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la publication',
                'message' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue lors de la publication',
            ], 500);
        }
    }

    /**
     * Rendre un bloc en HTML (AJAX)
     */
    public function renderBlock(Request $request, CourseLesson $lesson)
    {
        $validated = $request->validate([
            'block' => 'required|array',
            'block.id' => 'required|string',
            'block.type' => 'required|string',
            'block.content' => 'required|array',
            'block.settings' => 'nullable|array',
        ]);

        $block = $validated['block'];
        
        try {
            // Utiliser la méthode du modèle pour rendre le bloc
            $html = $lesson->renderBlockForEdit($block);

            return response()->json([
                'success' => true,
                'html' => $html,
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors du rendu du bloc dans CourseController::renderBlock', [
                'lesson_id' => $lesson->id,
                'block' => $block,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du rendu du bloc: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload d'image pour un bloc de leçon (via API)
     */
    public function uploadImageForLesson(Request $request, CourseLesson $lesson)
    {
        $request->validate([
            'image' => 'required|image|max:5120',
            'block_id' => 'nullable|string',
            'field' => 'nullable|string',
        ]);

        try {
            $path = $request->file('image')->store('courses/lessons/' . $lesson->id, 'public');

            return response()->json([
                'success' => true,
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de l\'upload: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload de vidéo pour un bloc de leçon (via API)
     */
    public function uploadVideoForLesson(Request $request, CourseLesson $lesson)
    {
        $request->validate([
            'video' => 'required|mimes:mp4,webm,ogg,mov,avi|max:102400', // 100MB max
            'block_id' => 'nullable|string',
        ]);

        try {
            $path = $request->file('video')->store('courses/lessons/' . $lesson->id . '/videos', 'public');

            return response()->json([
                'success' => true,
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'upload de vidéo', [
                'lesson_id' => $lesson->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de l\'upload: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload d'image pour module ou leçon (via API) - Legacy
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120',
            'type' => 'required|in:module,lesson',
        ]);

        $path = $request->file('image')->store('courses/' . $request->type . 's', 'public');

        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    // =====================================================================
    // REMPLISSAGE IA (BULK FILL)
    // =====================================================================

    /**
     * Types de blocs supportés par le système
     */
    private const SUPPORTED_BLOCK_TYPES = [
        'text', 'heading', 'image', 'video', 'iframe', 'gallery',
        'columns', 'divider', 'code', 'callout', 'steps',
        'checklist', 'exercise', 'quiz_block', 'embed',
    ];

    /**
     * Valider le JSON du remplissage IA (dry-run)
     */
    public function bulkFillValidate(Request $request)
    {
        $request->validate([
            'json_data' => 'required|string',
            'mode' => 'required|in:global,module,lesson',
            'target_id' => 'nullable|integer',
        ]);

        $data = json_decode($request->input('json_data'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'error' => 'JSON invalide : ' . json_last_error_msg(),
                'errors' => [],
                'summary' => null,
            ]);
        }

        $mode = $request->input('mode');
        $errors = [];
        $summary = ['modules' => 0, 'lessons' => 0, 'questions' => 0, 'blocks' => 0];

        switch ($mode) {
            case 'global':
                $this->validateGlobalData($data, $errors, $summary);
                break;
            case 'module':
                $targetId = $request->input('target_id');
                if (!$targetId || !CourseModule::find($targetId)) {
                    $errors[] = 'Module cible introuvable (ID: ' . $targetId . ')';
                } else {
                    $this->validateModuleData($data, $errors, $summary);
                }
                break;
            case 'lesson':
                $targetId = $request->input('target_id');
                if (!$targetId || !CourseLesson::find($targetId)) {
                    $errors[] = 'Leçon cible introuvable (ID: ' . $targetId . ')';
                } else {
                    $this->validateLessonData($data, $errors, $summary);
                }
                break;
        }

        return response()->json([
            'success' => empty($errors),
            'errors' => $errors,
            'summary' => $summary,
        ]);
    }

    /**
     * Exécuter le remplissage IA
     */
    public function bulkFill(Request $request)
    {
        $request->validate([
            'json_data' => 'required|string',
            'mode' => 'required|in:global,module,lesson',
            'target_id' => 'nullable|integer',
        ]);

        $data = json_decode($request->input('json_data'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'error' => 'JSON invalide : ' . json_last_error_msg(),
            ], 422);
        }

        $mode = $request->input('mode');
        $targetId = $request->input('target_id');

        // Pré-validation
        $errors = [];
        $summary = ['modules' => 0, 'lessons' => 0, 'questions' => 0, 'blocks' => 0];
        
        switch ($mode) {
            case 'global':
                $this->validateGlobalData($data, $errors, $summary);
                break;
            case 'module':
                if (!$targetId || !CourseModule::find($targetId)) {
                    return response()->json(['success' => false, 'error' => 'Module cible introuvable.'], 422);
                }
                $this->validateModuleData($data, $errors, $summary);
                break;
            case 'lesson':
                if (!$targetId || !CourseLesson::find($targetId)) {
                    return response()->json(['success' => false, 'error' => 'Leçon cible introuvable.'], 422);
                }
                $this->validateLessonData($data, $errors, $summary);
                break;
        }

        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'error' => 'Erreurs de validation détectées.',
                'errors' => $errors,
            ], 422);
        }

        // Insertion dans une transaction
        try {
            $result = DB::transaction(function () use ($data, $mode, $targetId) {
                $created = ['modules' => 0, 'lessons' => 0, 'questions' => 0, 'blocks' => 0];

                switch ($mode) {
                    case 'global':
                        $this->insertGlobalData($data, $created);
                        break;
                    case 'module':
                        $module = CourseModule::findOrFail($targetId);
                        $this->insertModuleData($data, $module, $created);
                        break;
                    case 'lesson':
                        $lesson = CourseLesson::findOrFail($targetId);
                        $this->insertLessonData($data, $lesson, $created);
                        break;
                }

                return $created;
            });

            return response()->json([
                'success' => true,
                'message' => 'Remplissage terminé avec succès !',
                'created' => $result,
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur lors du remplissage IA', [
                'mode' => $mode,
                'target_id' => $targetId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de l\'insertion : ' . $e->getMessage(),
            ], 500);
        }
    }

    // ----- Méthodes de validation -----

    private function validateGlobalData(array $data, array &$errors, array &$summary): void
    {
        if (!isset($data['modules']) || !is_array($data['modules'])) {
            $errors[] = 'Clé "modules" manquante ou invalide. Le JSON doit contenir un tableau "modules".';
            return;
        }

        foreach ($data['modules'] as $mi => $module) {
            $prefix = "Module #" . ($mi + 1);
            
            if (empty($module['titre'])) {
                $errors[] = "{$prefix} : champ \"titre\" requis.";
            }

            $summary['modules']++;

            if (isset($module['lessons']) && is_array($module['lessons'])) {
                foreach ($module['lessons'] as $li => $lesson) {
                    $this->validateLessonEntry($lesson, $li, "{$prefix} > Leçon #" . ($li + 1), $errors, $summary);
                }
            }
        }
    }

    private function validateModuleData(array $data, array &$errors, array &$summary): void
    {
        if (!isset($data['lessons']) || !is_array($data['lessons'])) {
            $errors[] = 'Clé "lessons" manquante ou invalide. Le JSON doit contenir un tableau "lessons".';
            return;
        }

        foreach ($data['lessons'] as $li => $lesson) {
            $this->validateLessonEntry($lesson, $li, "Leçon #" . ($li + 1), $errors, $summary);
        }
    }

    private function validateLessonData(array $data, array &$errors, array &$summary): void
    {
        if (!isset($data['blocks']) || !is_array($data['blocks'])) {
            $errors[] = 'Clé "blocks" manquante ou invalide. Le JSON doit contenir un tableau "blocks".';
            return;
        }

        foreach ($data['blocks'] as $bi => $block) {
            $this->validateBlockEntry($block, $bi, "Bloc #" . ($bi + 1), $errors);
            $summary['blocks']++;
        }
    }

    private function validateLessonEntry(array $lesson, int $index, string $prefix, array &$errors, array &$summary): void
    {
        if (empty($lesson['titre'])) {
            $errors[] = "{$prefix} : champ \"titre\" requis.";
        }

        $type = $lesson['type'] ?? 'course';
        if (!in_array($type, ['course', 'quiz'])) {
            $errors[] = "{$prefix} : type \"{$type}\" invalide (course ou quiz).";
        }

        $summary['lessons']++;

        // Valider les blocs
        if (isset($lesson['blocks']) && is_array($lesson['blocks'])) {
            foreach ($lesson['blocks'] as $bi => $block) {
                $this->validateBlockEntry($block, $bi, "{$prefix} > Bloc #" . ($bi + 1), $errors);
                $summary['blocks']++;
            }
        }

        // Valider les questions quiz
        if ($type === 'quiz' && isset($lesson['questions']) && is_array($lesson['questions'])) {
            foreach ($lesson['questions'] as $qi => $question) {
                $qPrefix = "{$prefix} > Question #" . ($qi + 1);
                
                if (empty($question['question'])) {
                    $errors[] = "{$qPrefix} : champ \"question\" requis.";
                }

                $qType = $question['type'] ?? 'multiple_choice';
                if (!in_array($qType, ['multiple_choice', 'true_false', 'text'])) {
                    $errors[] = "{$qPrefix} : type \"{$qType}\" invalide (multiple_choice, true_false ou text).";
                }

                if ($qType === 'multiple_choice') {
                    if (empty($question['options']) || !is_array($question['options']) || count($question['options']) < 2) {
                        $errors[] = "{$qPrefix} : au moins 2 options requises pour un QCM.";
                    }
                    if (!empty($question['options']) && !empty($question['bonne_reponse'])) {
                        if (!in_array($question['bonne_reponse'], $question['options'])) {
                            $errors[] = "{$qPrefix} : la bonne réponse \"{$question['bonne_reponse']}\" n'est pas dans les options.";
                        }
                    }
                }

                if (empty($question['bonne_reponse']) && $question['bonne_reponse'] !== '0' && $question['bonne_reponse'] !== false) {
                    $errors[] = "{$qPrefix} : champ \"bonne_reponse\" requis.";
                }

                $summary['questions']++;
            }
        }
    }

    private function validateBlockEntry(array $block, int $index, string $prefix, array &$errors): void
    {
        if (empty($block['type'])) {
            $errors[] = "{$prefix} : champ \"type\" requis.";
            return;
        }

        if (!in_array($block['type'], self::SUPPORTED_BLOCK_TYPES)) {
            $errors[] = "{$prefix} : type de bloc \"{$block['type']}\" non reconnu. Types supportés : " . implode(', ', self::SUPPORTED_BLOCK_TYPES);
        }

        if (!isset($block['content']) || !is_array($block['content'])) {
            $errors[] = "{$prefix} : champ \"content\" requis (objet).";
        }

        // Validations spécifiques par type
        $content = $block['content'] ?? [];
        switch ($block['type'] ?? '') {
            case 'text':
                if (empty($content['html'])) {
                    $errors[] = "{$prefix} (text) : champ \"content.html\" requis.";
                }
                break;
            case 'heading':
                if (empty($content['text'])) {
                    $errors[] = "{$prefix} (heading) : champ \"content.text\" requis.";
                }
                break;
            case 'code':
                if (!isset($content['code'])) {
                    $errors[] = "{$prefix} (code) : champ \"content.code\" requis.";
                }
                break;
            case 'callout':
                if (empty($content['html'])) {
                    $errors[] = "{$prefix} (callout) : champ \"content.html\" requis.";
                }
                break;
            case 'steps':
                if (!isset($content['steps']) || !is_array($content['steps'])) {
                    $errors[] = "{$prefix} (steps) : champ \"content.steps\" requis (tableau).";
                }
                break;
            case 'checklist':
                if (!isset($content['items']) || !is_array($content['items'])) {
                    $errors[] = "{$prefix} (checklist) : champ \"content.items\" requis (tableau).";
                }
                break;
        }
    }

    // ----- Méthodes d'insertion -----

    private function insertGlobalData(array $data, array &$created): void
    {
        $maxOrdre = CourseModule::max('ordre') ?? -1;

        foreach ($data['modules'] as $mi => $moduleData) {
            $maxOrdre++;

            $module = CourseModule::create([
                'titre' => $moduleData['titre'],
                'description' => $moduleData['description'] ?? null,
                'video_url' => $moduleData['video_url'] ?? null,
                'ordre' => $moduleData['ordre'] ?? $maxOrdre,
                'est_actif' => $moduleData['est_actif'] ?? true,
            ]);

            $created['modules']++;

            if (isset($moduleData['lessons']) && is_array($moduleData['lessons'])) {
                $this->insertModuleData($moduleData, $module, $created);
            }
        }
    }

    private function insertModuleData(array $data, CourseModule $module, array &$created): void
    {
        $lessons = $data['lessons'] ?? [];
        $maxOrdre = $module->lessons()->max('ordre') ?? -1;

        foreach ($lessons as $li => $lessonData) {
            $maxOrdre++;
            $type = $lessonData['type'] ?? 'course';

            // Préparer les blocs avec des IDs uniques
            $blocks = [];
            if (isset($lessonData['blocks']) && is_array($lessonData['blocks'])) {
                foreach ($lessonData['blocks'] as $block) {
                    $blocks[] = [
                        'id' => 'block-' . uniqid(),
                        'type' => $block['type'],
                        'content' => $block['content'] ?? [],
                        'settings' => $block['settings'] ?? [],
                    ];
                    $created['blocks']++;
                }
            }

            // Si pas de blocs fournis, utiliser les blocs par défaut
            if (empty($blocks)) {
                $blocks = CourseLesson::getDefaultBlocks();
            }

            $lesson = CourseLesson::create([
                'module_id' => $module->id,
                'titre' => $lessonData['titre'],
                'description' => $lessonData['description'] ?? null,
                'contenu_blocks_json' => $blocks,
                'type' => $type,
                'ordre' => $lessonData['ordre'] ?? $maxOrdre,
                'points_quiz' => $lessonData['points_quiz'] ?? 0,
                'est_actif' => $lessonData['est_actif'] ?? true,
                'is_draft' => true,
            ]);

            // Générer le HTML depuis les blocs
            try {
                $html = $lesson->generateHtmlFromBlocks();
                $lesson->update(['contenu_rich_html' => $html]);
            } catch (\Exception $e) {
                \Log::warning('Bulk fill: impossible de générer le HTML pour la leçon ' . $lesson->id, [
                    'error' => $e->getMessage(),
                ]);
            }

            $created['lessons']++;

            // Insérer les questions de quiz
            if ($type === 'quiz' && isset($lessonData['questions']) && is_array($lessonData['questions'])) {
                $qOrdre = 0;
                foreach ($lessonData['questions'] as $questionData) {
                    QuizQuestion::create([
                        'lesson_id' => $lesson->id,
                        'question' => $questionData['question'],
                        'type' => $questionData['type'] ?? 'multiple_choice',
                        'options_json' => $questionData['options'] ?? [],
                        'bonne_reponse' => (string) $questionData['bonne_reponse'],
                        'points' => $questionData['points'] ?? 1,
                        'ordre' => $questionData['ordre'] ?? $qOrdre,
                    ]);
                    $qOrdre++;
                    $created['questions']++;
                }
            }
        }
    }

    private function insertLessonData(array $data, CourseLesson $lesson, array &$created): void
    {
        $blocks = [];
        foreach ($data['blocks'] as $block) {
            $blocks[] = [
                'id' => 'block-' . uniqid(),
                'type' => $block['type'],
                'content' => $block['content'] ?? [],
                'settings' => $block['settings'] ?? [],
            ];
            $created['blocks']++;
        }

        $lesson->update([
            'contenu_blocks_json' => $blocks,
            'is_draft' => true,
        ]);

        // Générer le HTML depuis les blocs
        try {
            $lesson->refresh();
            $html = $lesson->generateHtmlFromBlocks();
            $lesson->update(['contenu_rich_html' => $html]);
        } catch (\Exception $e) {
            \Log::warning('Bulk fill: impossible de générer le HTML pour la leçon ' . $lesson->id, [
                'error' => $e->getMessage(),
            ]);
        }
    }
}

