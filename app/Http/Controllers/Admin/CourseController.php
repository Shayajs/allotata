<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseModule;
use App\Models\CourseLesson;
use App\Models\QuizQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        // Mettre à jour les blocs
        if (isset($validated['blocks'])) {
            $lesson->contenu_blocks_json = $validated['blocks'];
        }

        // Mettre à jour les autres champs si fournis
        $updateData = [
            'is_draft' => true, // Toujours brouillon avec cette méthode
        ];

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
        $validated = $request->validate([
            'blocks' => 'nullable|array', // Peut être vide au début
            'titre' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|in:course,quiz',
            'ordre' => 'nullable|integer|min:0',
            'points_quiz' => 'nullable|integer|min:0',
            'est_actif' => 'nullable|boolean',
        ]);

        // Mettre à jour les blocs (garder les anciens si non fournis)
        if (isset($validated['blocks'])) {
            $lesson->contenu_blocks_json = $validated['blocks'];
        }

        // Générer le HTML à partir des blocs
        if (!empty($lesson->contenu_blocks_json)) {
            $lesson->contenu_rich_html = $lesson->generateHtmlFromBlocks();
        }

        // Mettre à jour les autres champs
        $updateData = [
            'is_draft' => false,
            'published_at' => $lesson->published_at ?? now(), // Ne change que si première publication
        ];

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
            'message' => 'Leçon publiée avec succès',
            'published_at' => $lesson->published_at->toIso8601String(),
            'is_draft' => false,
            'is_published' => true,
        ]);
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
}

