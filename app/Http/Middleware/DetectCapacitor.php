<?php

namespace App\Http\Middleware;

use App\Support\CapacitorClient;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DetectCapacitor
{
    public function handle(Request $request, Closure $next): Response
    {
        $isCapacitor = CapacitorClient::detect($request);
        $request->attributes->set('is_capacitor', $isCapacitor);

        View::share('isCapacitor', $isCapacitor);
        View::share('brandUrl', CapacitorClient::brandUrl($request));

        if ($isCapacitor) {
            cookie()->queue(cookie(
                CapacitorClient::COOKIE,
                '1',
                60 * 24 * 365,
                '/',
                config('session.domain'),
                (bool) config('session.secure'),
                false,
                false,
                'lax'
            ));
        }

        if ($isCapacitor && $request->isMethod('GET') && $request->routeIs('home')) {
            return $request->user()
                ? redirect()->route('dashboard')
                : redirect()->route('login');
        }

        $response = $next($request);

        if ($isCapacitor) {
            $this->markHtmlDocument($response);
        }

        return $response;
    }

    private function markHtmlDocument(Response $response): void
    {
        if ($response instanceof StreamedResponse || ! method_exists($response, 'getContent')) {
            return;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        if ($contentType !== '' && ! str_contains($contentType, 'html')) {
            return;
        }

        $content = $response->getContent();
        if (! is_string($content) || $content === '' || ! str_contains($content, '<html')) {
            return;
        }

        if (str_contains($content, 'is-capacitor')) {
            return;
        }

        if (preg_match('/<html\b[^>]*\bclass="/i', $content)) {
            $content = preg_replace('/(<html\b[^>]*\bclass=")/i', '$1is-capacitor ', $content, 1);
        } else {
            $content = preg_replace('/<html\b/i', '<html class="is-capacitor"', $content, 1);
        }

        $response->setContent($content);
    }
}
