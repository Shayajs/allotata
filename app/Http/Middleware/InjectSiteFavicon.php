<?php

namespace App\Http\Middleware;

use App\Helpers\SiteHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InjectSiteFavicon
{
    public const CACHE_KEY = 'site_favicon_html';

    private const MARKER = 'allotata-site-favicon';

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldInject($request, $response)) {
            return $response;
        }

        $content = $response->getContent();
        if ($content === false || ! preg_match('/<\/head>/i', $content)) {
            return $response;
        }

        $faviconHtml = $this->renderFaviconHtml($request);
        $content = preg_replace('/<\/head>/i', $faviconHtml."\n</head>", $content, 1);
        $response->setContent($content);

        return $response;
    }

    private function shouldInject(Request $request, Response $response): bool
    {
        if ($request->attributes->get('skip_site_favicon')) {
            return false;
        }

        if ($request->routeIs('brightshell.*', 'public.favicon', 'site-web.favicon', 'entreprise.favicon')
            || $request->is('brightshell', 'brightshell/*')) {
            return false;
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return false;
        }

        if ($response instanceof StreamedResponse) {
            return false;
        }

        if (! method_exists($response, 'getContent')) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        if ($contentType !== '' && ! str_contains($contentType, 'html')) {
            return false;
        }

        $content = $response->getContent();
        if ($content === false || $content === '') {
            return false;
        }

        if (str_contains($content, self::MARKER) || str_contains($content, 'id="site-favicon"')) {
            return false;
        }

        return true;
    }

    private function renderFaviconHtml(Request $request): string
    {
        $entreprise = SiteHelper::resolveEntrepriseFromRequest($request);

        if ($entreprise) {
            return trim(view('partials.favicon', ['entreprise' => $entreprise])->render());
        }

        return Cache::remember(self::CACHE_KEY, 300, function () {
            return trim(view('partials.favicon')->render());
        });
    }
}
