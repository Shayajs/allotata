<?php

namespace App\Http\Middleware;

use App\Services\SiteTrafficService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackSiteTraffic
{
    public function __construct(
        private SiteTrafficService $siteTrafficService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if ($response->getStatusCode() >= 400) {
            return;
        }

        $this->siteTrafficService->record($request);
    }
}
