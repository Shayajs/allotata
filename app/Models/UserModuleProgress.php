<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserModuleProgress extends Model
{
    protected $table = 'user_module_progress';

    protected $fillable = [
        'user_id',
        'module_id',
        'progress_percentage',
        'lessons_completed',
        'total_lessons',
        'points_total',
        'video_watched_at',
        'video_points_earned',
        'last_accessed_at',
    ];

    protected $casts = [
        'progress_percentage' => 'decimal:2',
        'lessons_completed' => 'integer',
        'total_lessons' => 'integer',
        'points_total' => 'integer',
        'video_watched_at' => 'datetime',
        'video_points_earned' => 'integer',
        'last_accessed_at' => 'datetime',
    ];

    /**
     * Relation : Une progression appartient à un utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relation : Une progression appartient à un module
     */
    public function module()
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    /**
     * Vérifier si le module est complété
     */
    public function isCompleted(): bool
    {
        return $this->progress_percentage >= 100;
    }

    /**
     * Mettre à jour la dernière date d'accès
     */
    public function touchLastAccessed(): void
    {
        $this->last_accessed_at = now();
        $this->save();
    }
}
