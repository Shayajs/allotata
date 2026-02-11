<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NoAdminExists
{
    /**
     * Autorise l'accès uniquement s'il n'existe aucun administrateur.
     * Utilisé pour le bootstrap initial (création du premier admin).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (User::where('is_admin', true)->exists()) {
            abort(404);
        }

        return $next($request);
    }
}
