<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\CourseLesson;

class EnsureLessonAccessible
{
    /**
     * Vérifier que l'utilisateur peut accéder à la leçon demandée
     * Protection IDOR : vérification côté serveur
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Récupérer la leçon depuis la route
        $lesson = $request->route('lesson');
        
        if (!$lesson instanceof CourseLesson) {
            abort(404, 'Leçon non trouvée.');
        }

        $user = $request->user();

        // Si admin, toujours autorisé
        if ($user && $user->is_admin) {
            return $next($request);
        }

        // Vérifier l'accès via la Policy
        if (!auth()->check()) {
            // Utilisateur non connecté : peut voir en mode aperçu seulement
            if ($request->routeIs('courses.lesson')) {
                // Autoriser la vue mais avec limitations
                return $next($request);
            }
        } else {
            // Vérifier via la Policy
            if (!$request->user()->can('view', $lesson)) {
                abort(403, 'Vous n\'avez pas accès à cette leçon. Veuillez compléter les leçons précédentes.');
            }
        }

        return $next($request);
    }
}
