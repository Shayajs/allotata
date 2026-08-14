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
        $host = preg_replace('/:\d+$/', '', $host) ?: $host;
        $expectedHostBare = preg_replace('/:\d+$/', '', $expectedHost) ?: $expectedHost;

        $tenantSlug = self::tenantSlugForHost($host, $expectedHostBare);

        if ($host !== $expectedHostBare && $host !== 'www.'.$expectedHostBare && $tenantSlug === null) {
            return null;
        }

        if (! empty($parts['scheme']) && strtolower($parts['scheme']) !== $expectedScheme) {
            return null;
        }

        $path = $parts['path'];
        if ($path === '' || $path[0] !== '/') {
            $path = '/'.$path;
        }

        $isClassicAgenda = (bool) preg_match('#^/p/[^/]+/agenda$#', $path);
        $isTenantAgenda = $tenantSlug !== null && preg_match('#^/public/agenda$#', $path);

        if (! $isClassicAgenda && ! $isTenantAgenda) {
            return null;
        }

        $query = isset($parts['query']) && $parts['query'] !== '' ? '?'.$parts['query'] : '';

        if ($tenantSlug !== null) {
            return $expectedScheme.'://'.$host.$path.$query;
        }

        return $appUrl.$path.$query;
    }

    private static function tenantSlugForHost(string $host, string $expectedHost): ?string
    {
        $base = SubdomainHost::baseDomain();
        if ($base === '' || $host === $expectedHost || $host === 'www.'.$expectedHost) {
            return null;
        }

        $suffix = '.'.$base;
        if (! str_ends_with($host, $suffix)) {
            return null;
        }

        $sub = substr($host, 0, -strlen($suffix));
        if ($sub === '' || str_contains($sub, '.') || SubdomainHost::isReservedSlug($sub)) {
            return null;
        }

        return $sub;
    }
}
