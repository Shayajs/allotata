<?php

namespace App\Support;

/**
 * Valide une URL de retour vers l'agenda public (/p/{slug}/agenda), anti open-redirect.
 */
class PublicAgendaReturnUrl
{
    public const POST_VERIFY_COOKIE = 'post_verify_return';

    /** Durée de vie du cookie (minutes). */
    public const POST_VERIFY_COOKIE_MINUTES = 60 * 24 * 7;

    /**
     * Retourne l'URL absolue sûre ou null.
     */
    public static function normalize(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $url = trim($url);
        if (strlen($url) > 2048) {
            return null;
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        $appParts = parse_url($appUrl);
        if (! is_array($appParts) || empty($appParts['host'])) {
            return null;
        }

        $expectedHost = strtolower($appParts['host']);
        $expectedScheme = strtolower($appParts['scheme'] ?? 'https');

        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['path'])) {
            return null;
        }

        $host = isset($parts['host']) ? strtolower($parts['host']) : $expectedHost;
        if ($host !== $expectedHost) {
            return null;
        }

        if (! empty($parts['scheme']) && strtolower($parts['scheme']) !== $expectedScheme) {
            return null;
        }

        $path = $parts['path'];
        if ($path === '' || $path[0] !== '/') {
            $path = '/'.$path;
        }

        // Un seul segment dynamique : slug ; termine par /agenda (sans sous-chemin supplémentaire)
        if (! preg_match('#^/p/[^/]+/agenda$#', $path)) {
            return null;
        }

        $query = isset($parts['query']) && $parts['query'] !== '' ? '?'.$parts['query'] : '';

        return $appUrl.$path.$query;
    }
}
