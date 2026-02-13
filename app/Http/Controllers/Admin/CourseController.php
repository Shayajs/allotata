<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseModule;
use App\Models\CourseLesson;
use App\Models\QuizQuestion;
use App\Services\CoursesBulkService;
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
    // REMPLISSAGE IA (BULK FILL) — Délégué au CoursesBulkService
    // =====================================================================

    public function bulkFillValidate(Request $request)
    {
        $request->validate([
            'json_data' => 'required|string',
            'mode' => 'required|in:global,module,lesson',
            'target_id' => 'nullable|integer',
        ]);

        $data = json_decode($request->input('json_data'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['success' => false, 'error' => 'JSON invalide : ' . json_last_error_msg(), 'errors' => [], 'summary' => null]);
        }

        $service = app(CoursesBulkService::class);
        $result = $service->validateFill($data, $request->input('mode'), $request->input('target_id'));

        return response()->json($result);
    }

    public function bulkFill(Request $request)
    {
        $request->validate([
            'json_data' => 'required|string',
            'mode' => 'required|in:global,module,lesson',
            'target_id' => 'nullable|integer',
        ]);

        $data = json_decode($request->input('json_data'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['success' => false, 'error' => 'JSON invalide : ' . json_last_error_msg()], 422);
        }

        $service = app(CoursesBulkService::class);
        $validation = $service->validateFill($data, $request->input('mode'), $request->input('target_id'));

        if (!$validation['success']) {
            return response()->json(['success' => false, 'error' => 'Erreurs de validation.', 'errors' => $validation['errors']], 422);
        }

        try {
            $result = $service->executeFill($data, $request->input('mode'), $request->input('target_id'));
            return response()->json(['success' => true, 'message' => 'Remplissage terminé avec succès !', 'created' => $result]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors du remplissage IA', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'error' => 'Erreur lors de l\'insertion : ' . $e->getMessage()], 500);
        }
    }

    // =====================================================================
    // COMMANDES IA BULK — Délégué au CoursesBulkService
    // =====================================================================

    public function bulkActionValidate(Request $request)
    {
        $request->validate([
            'json_data' => 'required|string',
            'action' => 'required|in:update,delete,toggle,reorder',
        ]);

        $data = json_decode($request->input('json_data'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['success' => false, 'error' => 'JSON invalide : ' . json_last_error_msg(), 'errors' => [], 'summary' => null]);
        }

        $service = app(CoursesBulkService::class);
        $result = $service->validateAction($data, $request->input('action'));

        return response()->json($result);
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'json_data' => 'required|string',
            'action' => 'required|in:update,delete,toggle,reorder',
        ]);

        $data = json_decode($request->input('json_data'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['success' => false, 'error' => 'JSON invalide : ' . json_last_error_msg()], 422);
        }

        $service = app(CoursesBulkService::class);
        $validation = $service->validateAction($data, $request->input('action'));

        if (!$validation['success']) {
            return response()->json(['success' => false, 'error' => 'Erreurs de validation.', 'errors' => $validation['errors']], 422);
        }

        try {
            $result = $service->executeAction($data, $request->input('action'));
            return response()->json(['success' => true, 'message' => 'Commande exécutée avec succès !', 'affected' => $result]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la commande bulk', ['action' => $request->input('action'), 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Erreur lors de l\'exécution : ' . $e->getMessage()], 500);
        }
    }
}

