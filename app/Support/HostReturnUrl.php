<?php

namespace App\Support;

/**
 * Valide une URL de retour post-login vers l'apex ou un sous-domaine mappé.
 */
class HostReturnUrl
{
    public static function normalize(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $url = trim($url);
        if (strlen($url) > 2048) {
            return null;
        }

        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['host']) || empty($parts['path'])) {
            return null;
        }

        $host = strtolower((string) $parts['host']);
        $host = preg_replace('/:\d+$/', '', $host) ?: $host;
        $parsed = SubdomainHost::parse($host);

        if ($parsed['mode'] === SubdomainHost::MODE_UNKNOWN) {
            return null;
        }

        if (! empty($parts['scheme']) && ! in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)) {
            return null;
        }

        $scheme = ! empty($parts['scheme'])
            ? strtolower((string) $parts['scheme'])
            : SubdomainHost::scheme();

        $path = $parts['path'];
        if ($path === '' || $path[0] !== '/') {
            $path = '/'.$path;
        }

        $query = isset($parts['query']) && $parts['query'] !== '' ? '?'.$parts['query'] : '';

        return $scheme.'://'.$host.$path.$query;
    }
}
