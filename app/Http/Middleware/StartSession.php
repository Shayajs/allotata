<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession as BaseStartSession;

class StartSession extends BaseStartSession
{
    /**
     * Memorise l'URL publique (avant reecriture de sous-domaine) pour que back() ne
     * renvoie jamais un chemin interne du type /m/{slug}.
     */
    protected function storeCurrentUrl(Request $request, $session)
    {
        parent::storeCurrentUrl($request, $session);

        $original = $request->attributes->get('subdomain.original_url');

        if ($original && $session->previousUrl() === $request->fullUrl()) {
            $session->setPreviousUrl($original);
        }
    }
}
