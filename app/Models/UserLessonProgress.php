<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLessonProgress extends Model
{
    protected $table = 'user_lesson_progress';

    protected $fillable = [
        'user_id',
        'lesson_id',
        'completed_at',
        'score',
        'points_earned',
        'quiz_answers_json',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'score' => 'integer',
        'points_earned' => 'integer',
        'quiz_answers_json' => 'array',
    ];

    /**
     * Relation : Une progression appartient à un utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relation : Une progression appartient à une leçon
     */
    public function lesson()
    {
        return $this->belongsTo(CourseLesson::class, 'lesson_id');
    }

    /**
     * Marquer la leçon comme complétée
     */
    public function markAsCompleted(?int $score = null, ?int $pointsEarned = null, ?array $quizAnswers = null): void
    {
        $this->completed_at = now();
        
        if ($score !== null) {
            $this->score = $score;
        }
        
        if ($pointsEarned !== null) {
            $this->points_earned = $pointsEarned;
        }
        
        if ($quizAnswers !== null) {
            $this->quiz_answers_json = $quizAnswers;
        }
        
        $this->save();

        // Mettre à jour la progression du module
        $this->updateModuleProgress();
    }

    /**
     * Mettre à jour la progression du module parent
     */
    protected function updateModuleProgress(): void
    {
        $module = $this->lesson->module;
        $user = $this->user;

        // Calculer le nombre de leçons complétées
        $lessonsCompleted = $module->lessons()
            ->whereHas('userProgress', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->whereNotNull('completed_at');
            })
            ->count();

        $totalLessons = $module->activeLessons->count();
        $progressPercentage = $totalLessons > 0 
            ? round(($lessonsCompleted / $totalLessons) * 100, 2) 
            : 0;

        // Calculer les points totaux
        $pointsTotal = $module->lessons()
            ->whereHas('userProgress', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->whereNotNull('completed_at');
            })
            ->join('user_lesson_progress', function ($join) use ($user) {
                $join->on('course_lessons.id', '=', 'user_lesson_progress.lesson_id')
                    ->where('user_lesson_progress.user_id', '=', $user->id);
            })
            ->sum('user_lesson_progress.points_earned');

        // Créer ou mettre à jour la progression du module
        UserModuleProgress::updateOrCreate(
            [
                'user_id' => $user->id,
                'module_id' => $module->id,
            ],
            [
                'progress_percentage' => $progressPercentage,
                'lessons_completed' => $lessonsCompleted,
                'total_lessons' => $totalLessons,
                'points_total' => $pointsTotal,
                'last_accessed_at' => now(),
            ]
        );
    }
}
