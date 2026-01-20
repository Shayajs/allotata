<?php

namespace App\Http\Controllers;

use App\Models\CourseModule;
use App\Models\CourseLesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    /**
     * Afficher la liste des modules (index)
     */
    public function index()
    {
        $user = Auth::user();
        
        // Les admins peuvent voir la page publique pour prévisualiser
        // Ils ont un bouton flottant pour aller vers l'édition
        
        $modules = CourseModule::where('est_actif', true)
            ->orderBy('ordre')
            ->with(['activeLessons'])
            ->get();

        $userProgress = null;

        if ($user) {
            $userProgress = \App\Models\UserModuleProgress::where('user_id', $user->id)
                ->whereIn('module_id', $modules->pluck('id'))
                ->get()
                ->keyBy('module_id');
        }

        return view('courses.index', compact('modules', 'user', 'userProgress'));
    }

    /**
     * Afficher un module avec sa sidebar de navigation
     */
    public function showModule(CourseModule $module)
    {
        $user = Auth::user();
        
        // Les admins peuvent voir la page publique pour prévisualiser
        // Ils ont un bouton flottant pour aller vers l'édition

        // Vérifier que le module est actif
        if (!$module->est_actif && (!Auth::check() || !Auth::user()->is_admin)) {
            abort(404);
        }

        // Charger toutes les leçons actives du module
        $lessons = $module->activeLessons;

        // Récupérer la progression de l'utilisateur pour ce module
        $moduleProgress = null;
        $lessonProgress = [];

        if ($user) {
            $moduleProgress = $module->getUserProgress($user);
            
            // Charger toutes les progressions des leçons de ce module
            $lessonProgressRecords = \App\Models\UserLessonProgress::where('user_id', $user->id)
                ->whereIn('lesson_id', $lessons->pluck('id'))
                ->get()
                ->keyBy('lesson_id');

            foreach ($lessons as $lesson) {
                $lessonProgress[$lesson->id] = $lessonProgressRecords->get($lesson->id);
            }

            // Mettre à jour la dernière date d'accès
            if ($moduleProgress) {
                $moduleProgress->touchLastAccessed();
            } else {
                // Créer une entrée de progression si elle n'existe pas
                $moduleProgress = \App\Models\UserModuleProgress::create([
                    'user_id' => $user->id,
                    'module_id' => $module->id,
                    'progress_percentage' => 0,
                    'lessons_completed' => 0,
                    'total_lessons' => $lessons->count(),
                    'points_total' => 0,
                    'last_accessed_at' => now(),
                ]);
            }
        }

        // Charger tous les modules pour la navigation
        $allModules = CourseModule::where('est_actif', true)
            ->orderBy('ordre')
            ->get();

        // Si pas de leçon spécifique, afficher la première accessible
        $currentLesson = null;
        if ($lessons->count() > 0) {
            foreach ($lessons as $lesson) {
                if ($lesson->isAccessibleBy($user)) {
                    $currentLesson = $lesson;
                    break;
                }
            }
            // Si aucune accessible et pas connecté, montrer la première (en mode bloqué)
            if (!$currentLesson) {
                $currentLesson = $lessons->first();
            }
        }

        return view('courses.module', compact(
            'module',
            'lessons',
            'allModules',
            'currentLesson',
            'user',
            'moduleProgress',
            'lessonProgress'
        ));
    }

    /**
     * Afficher une leçon spécifique
     */
    public function showLesson(CourseModule $module, CourseLesson $lesson)
    {
        // Vérifier que la leçon appartient au module
        if ($lesson->module_id !== $module->id) {
            abort(404);
        }

        $user = Auth::user();
        
        // Les admins peuvent voir la page publique pour prévisualiser
        // Ils ont un bouton flottant pour aller vers l'édition

        // Vérifier que le module est actif
        if (!$module->est_actif && (!Auth::check() || !Auth::user()->is_admin)) {
            abort(404);
        }

        // Vérifier l'accès via Policy (déjà fait par le middleware, mais double vérification)
        if ($user && !$user->can('view', $lesson)) {
            abort(403, 'Vous n\'avez pas accès à cette leçon.');
        }

        // Charger toutes les leçons du module pour la navigation
        $lessons = $module->activeLessons;

        // Charger tous les modules pour la navigation
        $allModules = CourseModule::where('est_actif', true)
            ->orderBy('ordre')
            ->get();

        // Récupérer la progression
        $moduleProgress = $user ? $module->getUserProgress($user) : null;
        $lessonProgress = $user ? $lesson->getUserProgress($user) : null;

        // Charger toutes les progressions des leçons du module
        $lessonProgressMap = [];
        if ($user) {
            $lessonProgressRecords = \App\Models\UserLessonProgress::where('user_id', $user->id)
                ->whereIn('lesson_id', $lessons->pluck('id'))
                ->get()
                ->keyBy('lesson_id');

            foreach ($lessons as $l) {
                $lessonProgressMap[$l->id] = $lessonProgressRecords->get($l->id);
            }
        }

        // Si c'est un quiz, charger les questions
        $quizQuestions = [];
        if ($lesson->isQuiz()) {
            $quizQuestions = $lesson->quizQuestions;
        }

        // Leçon précédente et suivante
        $previousLesson = $lesson->previousLesson();
        $nextLesson = $lesson->nextLesson();

        return view('courses.lesson', compact(
            'module',
            'lesson',
            'lessons',
            'allModules',
            'user',
            'moduleProgress',
            'lessonProgress',
            'lessonProgressMap',
            'quizQuestions',
            'previousLesson',
            'nextLesson'
        ));
    }

    /**
     * Marquer une leçon comme complétée (API)
     */
    public function completeLesson(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        $validated = $request->validate([
            'lesson_id' => 'required|exists:course_lessons,id',
        ]);

        $lesson = CourseLesson::findOrFail($validated['lesson_id']);

        // Vérifier l'accès via Policy
        if (!$user->can('complete', $lesson)) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        // Vérifier si déjà complétée
        $progress = $lesson->getUserProgress($user);
        if ($progress && $progress->completed_at) {
            return response()->json(['success' => true, 'already_completed' => true]);
        }

        // Créer ou mettre à jour la progression
        if (!$progress) {
            $progress = \App\Models\UserLessonProgress::create([
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
            ]);
        }

        // Marquer comme complétée (cours = 100 points par défaut, points_earned = 0 si cours)
        $pointsEarned = $lesson->isQuiz() ? 0 : 0; // Les points viennent du quiz
        $progress->markAsCompleted(null, $pointsEarned);

        return response()->json([
            'success' => true,
            'message' => 'Leçon complétée avec succès',
            'progress' => $lesson->module->getUserProgress($user),
        ]);
    }

    /**
     * Soumettre un quiz (API)
     */
    public function submitQuiz(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        $validated = $request->validate([
            'lesson_id' => 'required|exists:course_lessons,id',
            'answers' => 'required|array',
        ]);

        $lesson = CourseLesson::findOrFail($validated['lesson_id']);

        // Vérifier que c'est un quiz
        if (!$lesson->isQuiz()) {
            return response()->json(['error' => 'Cette leçon n\'est pas un quiz'], 400);
        }

        // Vérifier l'accès via Policy
        if (!$user->can('complete', $lesson)) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        // Charger les questions
        $questions = $lesson->quizQuestions;
        $answers = $validated['answers'];

        // Calculer le score
        $totalPoints = 0;
        $earnedPoints = 0;
        $correctAnswers = 0;
        $totalQuestions = $questions->count();

        foreach ($questions as $question) {
            $totalPoints += $question->points;
            $answer = $answers[$question->id] ?? null;

            if ($answer !== null && $question->checkAnswer($answer)) {
                $earnedPoints += $question->points;
                $correctAnswers++;
            }
        }

        $score = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100) : 0;
        $pointsEarned = $lesson->points_quiz; // Les points du quiz sont attribués même si pas réussi

        // Créer ou mettre à jour la progression
        $progress = $lesson->getUserProgress($user);
        if (!$progress) {
            $progress = \App\Models\UserLessonProgress::create([
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
            ]);
        }

        // Marquer comme complétée avec le score
        $progress->markAsCompleted($score, $pointsEarned, $answers);

        return response()->json([
            'success' => true,
            'message' => 'Quiz soumis avec succès',
            'score' => $score,
            'correct_answers' => $correctAnswers,
            'total_questions' => $totalQuestions,
            'points_earned' => $pointsEarned,
            'progress' => $lesson->module->getUserProgress($user),
        ]);
    }
}
