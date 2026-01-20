<?php

namespace App\Policies;

use App\Models\User;
use App\Models\CourseLesson;

class CourseLessonPolicy
{
    /**
     * Détermine si l'utilisateur peut voir n'importe quelle leçon
     * Tous les membres peuvent voir la liste des leçons
     */
    public function viewAny(?User $user): bool
    {
        // Tout le monde peut voir la liste des leçons
        return true;
    }

    /**
     * Détermine si l'utilisateur peut voir une leçon spécifique
     * Vérifie si la leçon est accessible (non bloquée)
     */
    public function view(?User $user, CourseLesson $lesson): bool
    {
        // Si admin, toujours accessible
        if ($user && $user->is_admin) {
            return true;
        }

        // Si pas connecté, peut voir mais pas accéder (mode aperçu)
        if (!$user) {
            return true; // Peut voir mais accès limité
        }

        // Vérifier si la leçon est accessible selon la logique de progression
        return $lesson->isAccessibleBy($user);
    }

    /**
     * Détermine si l'utilisateur peut créer une leçon
     * Admin uniquement
     */
    public function create(?User $user): bool
    {
        return $user && $user->is_admin;
    }

    /**
     * Détermine si l'utilisateur peut mettre à jour une leçon
     * Admin uniquement
     */
    public function update(?User $user, CourseLesson $lesson): bool
    {
        return $user && $user->is_admin;
    }

    /**
     * Détermine si l'utilisateur peut supprimer une leçon
     * Admin uniquement
     */
    public function delete(?User $user, CourseLesson $lesson): bool
    {
        return $user && $user->is_admin;
    }

    /**
     * Détermine si l'utilisateur peut compléter une leçon
     * Utilisateurs connectés uniquement
     */
    public function complete(?User $user, CourseLesson $lesson): bool
    {
        if (!$user) {
            return false;
        }

        // Doit être accessible pour être complétée
        return $lesson->isAccessibleBy($user);
    }
}
