<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseModule extends Model
{
    protected $fillable = [
        'titre',
        'description',
        'image_path',
        'ordre',
        'est_actif',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
        'ordre' => 'integer',
    ];

    /**
     * Relation : Un module a plusieurs leçons
     */
    public function lessons()
    {
        return $this->hasMany(CourseLesson::class, 'module_id')->orderBy('ordre');
    }

    /**
     * Relation : Leçons actives uniquement
     */
    public function activeLessons()
    {
        return $this->hasMany(CourseLesson::class, 'module_id')
            ->where('est_actif', true)
            ->orderBy('ordre');
    }

    /**
     * Relation : Un module a plusieurs progressions utilisateur
     */
    public function userProgress()
    {
        return $this->hasMany(UserModuleProgress::class, 'module_id');
    }

    /**
     * Obtenir la progression d'un utilisateur pour ce module
     */
    public function getUserProgress(?User $user): ?UserModuleProgress
    {
        if (!$user) {
            return null;
        }

        return $this->userProgress()
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Vérifier si un utilisateur a accès à ce module (toujours true, mais peut être grisé)
     */
    public function isAccessible(?User $user): bool
    {
        // Tous les modules sont visibles, mais peuvent être grisés si pas de progression
        return true;
    }
}
