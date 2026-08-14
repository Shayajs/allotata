<?php

namespace App\Http\Middleware;

use App\Support\SubdomainHost;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RewriteSubdomainRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $result = SubdomainHost::inboundPath($request);

        if ($result === null) {
            return $this->withNoindex($request, $next($request));
        }

        if (isset($result['abort'])) {
            abort((int) $result['abort']);
        }

        if (isset($result['redirect'])) {
            return $this->withNoindex(
                $request,
                redirect($result['redirect'], $this->redirectStatus($request, $result['status'] ?? 302))
            );
        }

        if (isset($result['path']) && $result['path'] !== $request->getPathInfo()) {
            $request->attributes->set('subdomain.original_url', $request->fullUrl());
            $this->rewritePath($request, $result['path']);
            $request->attributes->set('subdomain.rewritten', $result['path']);
        }

        return $this->withNoindex($request, $next($request));
    }

    private function rewritePath(Request $request, string $path): void
    {
        $query = $request->getQueryString();
        $uri = $path.($query ? '?'.$query : '');

        $request->server->set('REQUEST_URI', $uri);
        $request->server->set('PATH_INFO', $path);

        $request->initialize(
            $request->query->all(),
            $request->request->all(),
            $request->attributes->all(),
            $request->cookies->all(),
            $request->files->all(),
            $request->server->all(),
            $request->getContent()
        );
    }

    /**
     * Les methodes non idempotentes doivent conserver leur corps de requete.
     */
    private function redirectStatus(Request $request, int $status): int
    {
        if ($request->isMethodSafe()) {
            return $status;
        }

        return $status === 301 ? 308 : 307;
    }

    private function withNoindex(Request $request, Response $response): Response
    {
        if (! SubdomainHost::enabled() || ! SubdomainHost::isNonApex($request->getHost())) {
            return $response;
        }

        // La vitrine d'un tenant et la page de garde de l'API sont faites pour etre
        // trouvees ; tout le reste des sous-domaines reste hors des moteurs.
        $served = (string) $request->attributes->get('subdomain.rewritten', $request->getPathInfo());
        if (str_starts_with($served, '/w/') || $served === '/api') {
            return $response;
        }

        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
