<?php

namespace App\Support;

use App\Models\Entreprise;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SubdomainHost
{
    public const MODE_APEX = 'apex';

    public const MODE_ADMIN = 'admin';

    public const MODE_DASH = 'dash';

    public const MODE_SIGN = 'sign';

    public const MODE_API = 'api';

    public const MODE_TENANT = 'tenant';

    public const MODE_UNKNOWN = 'unknown';

    /** Racine memorisee par URL::formatHostUsing() avant chaque formatage de chemin. */
    private static ?string $urlRoot = null;

    /** @var array<string, string> */
    private static array $webSlugCache = [];

    /** @var array<string, bool> */
    private static array $writeOnlyCache = [];

    public static function enabled(): bool
    {
        return (bool) config('subdomains.enabled');
    }

    public static function legacyRedirect(): bool
    {
        return self::enabled() && (bool) config('subdomains.legacy_redirect');
    }

    public static function redirectStatus(): int
    {
        $status = (int) config('subdomains.redirect_status', 302);

        return in_array($status, [301, 302, 303, 307, 308], true) ? $status : 302;
    }

    public static function baseDomain(): string
    {
        $configured = strtolower(trim((string) config('subdomains.base_domain')));
        if ($configured !== '') {
            return preg_replace('/:\d+$/', '', $configured) ?: $configured;
        }

        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: '';
        $host = preg_replace('/^www\./i', '', strtolower($host)) ?: '';

        return preg_replace('/:\d+$/', '', $host) ?: $host;
    }

    public static function scheme(): string
    {
        return parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';
    }

    /**
     * @return list<string>
     */
    public static function reservedSlugs(): array
    {
        return array_map('strtolower', config('subdomains.reserved', []));
    }

    public static function isReservedSlug(string $slug): bool
    {
        return in_array(strtolower($slug), self::reservedSlugs(), true);
    }

    /**
     * @return list<string>
     */
    public static function mappedHosts(): array
    {
        return array_keys(config('subdomains.hosts', []));
    }

    public static function nextAvailableSlug(string $baseSlug, ?int $exceptId = null): string
    {
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'entreprise';
        $slug = $baseSlug;
        $counter = 1;

        while (self::isReservedSlug($slug) || self::slugExists($slug, $exceptId)) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public static function tenantUrl(string $slug, string $suffix = '/manage'): string
    {
        return self::absoluteUrl($slug, $suffix);
    }

    public static function signUrl(string $path = '/signin'): string
    {
        return self::absoluteUrl('sign', $path);
    }

    public static function guestLoginUrl(Request $request): string
    {
        if (! self::enabled()) {
            return route('login');
        }

        $mode = self::parse($request->getHost())['mode'];
        if (in_array($mode, [self::MODE_APEX, self::MODE_SIGN], true)) {
            return route('login');
        }

        if (! self::isIsolated($mode)) {
            return route('login');
        }

        $target = (string) ($request->attributes->get('subdomain.original_url') ?: $request->fullUrl());

        return self::signUrl('/signin').'?return='.rawurlencode($target);
    }

    /**
     * @return array{mode: string, subdomain: ?string, host: string}
     */
    public static function current(?Request $request = null): array
    {
        $host = null;
        try {
            $host = ($request ?? request())->getHost();
        } catch (\Throwable) {
            $host = self::baseDomain();
        }

        return self::parse($host);
    }

    /**
     * @return array{mode: string, subdomain: ?string, host: string}
     */
    public static function parse(?string $host = null): array
    {
        if ($host === null) {
            try {
                $host = request()->getHost();
            } catch (\Throwable) {
                $host = self::baseDomain();
            }
        }

        $host = strtolower((string) $host);
        $host = preg_replace('/:\d+$/', '', $host) ?: $host;
        $base = self::baseDomain();

        if ($base === '' || $host === $base || $host === 'www.'.$base) {
            return ['mode' => self::MODE_APEX, 'subdomain' => null, 'host' => $host];
        }

        $suffix = '.'.$base;
        if (! str_ends_with($host, $suffix)) {
            return ['mode' => self::MODE_UNKNOWN, 'subdomain' => null, 'host' => $host];
        }

        $sub = substr($host, 0, -strlen($suffix));
        if ($sub === '' || str_contains($sub, '.')) {
            return ['mode' => self::MODE_UNKNOWN, 'subdomain' => $sub !== '' ? $sub : null, 'host' => $host];
        }

        if ($sub === 'www') {
            return ['mode' => self::MODE_APEX, 'subdomain' => null, 'host' => $host];
        }

        if (in_array($sub, self::mappedHosts(), true)) {
            return ['mode' => $sub, 'subdomain' => $sub, 'host' => $host];
        }

        if (self::isReservedSlug($sub)) {
            return ['mode' => self::MODE_UNKNOWN, 'subdomain' => $sub, 'host' => $host];
        }

        return ['mode' => self::MODE_TENANT, 'subdomain' => $sub, 'host' => $host];
    }

    public static function isIsolated(string $mode): bool
    {
        return in_array($mode, [
            self::MODE_ADMIN,
            self::MODE_DASH,
            self::MODE_SIGN,
            self::MODE_API,
            self::MODE_TENANT,
        ], true);
    }

    public static function isNonApex(?string $host = null): bool
    {
        return self::isIsolated(self::parse($host)['mode']);
    }

    public static function isSharedPath(string $path): bool
    {
        return self::matchesPrefixes($path, array_merge(
            config('subdomains.static', []),
            config('subdomains.shared', [])
        ));
    }

    /**
     * Fichier servi par le serveur web sur tous les hosts, jamais par le routeur.
     */
    public static function isStaticPath(string $path): bool
    {
        return self::matchesPrefixes($path, config('subdomains.static', []));
    }

    /**
     * @param  list<string>  $prefixes
     */
    private static function matchesPrefixes(string $path, array $prefixes): bool
    {
        $path = self::normalizePath($path);
        if ($path === '/') {
            return false;
        }

        foreach ($prefixes as $prefix) {
            $prefix = '/'.ltrim((string) $prefix, '/');
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @deprecated Utiliser isSharedPath()
     */
    public static function isPassthroughPath(string $path): bool
    {
        return self::isSharedPath($path);
    }

    /**
     * @return array{path?: string, redirect?: string, status?: int, abort?: int}|null
     */
    public static function inboundPath(Request $request): ?array
    {
        $decision = self::decide($request);

        return match ($decision['action']) {
            'rewrite' => ['path' => $decision['path']],
            'redirect' => ['redirect' => $decision['url'], 'status' => $decision['code'] ?? 302],
            'abort' => ['abort' => $decision['code'] ?? 404],
            default => null,
        };
    }

    /**
     * @return array{action: string, path?: string, url?: string, code?: int}
     */
    public static function decide(Request $request): array
    {
        if (! self::enabled()) {
            return ['action' => 'serve'];
        }

        $parsed = self::parse($request->getHost());
        $path = self::normalizePath($request->getPathInfo());
        $method = strtoupper($request->getMethod());
        $query = $request->getQueryString();

        if ($parsed['mode'] === self::MODE_APEX) {
            return self::keepWritesLocal(self::decideApex($path, $query), $path, $method);
        }

        if ($parsed['mode'] === self::MODE_UNKNOWN) {
            return ['action' => 'abort', 'code' => 404];
        }

        $decision = $parsed['mode'] === self::MODE_TENANT
            ? self::decideTenant($parsed, $path, $method, $query)
            : self::decideMapped($parsed['mode'], $path, $method, $query);

        return self::keepWritesLocal($decision, $path, $method);
    }

    /**
     * Une ecriture est servie sur le host ou elle arrive : la renvoyer ailleurs
     * casserait les appels fetch (cross-origin) et les soumissions de formulaire.
     *
     * @param  array{action: string, path?: string, url?: string, code?: int}  $decision
     * @return array{action: string, path?: string, url?: string, code?: int}
     */
    private static function keepWritesLocal(array $decision, string $path, string $method): array
    {
        if (! in_array($decision['action'], ['redirect', 'abort'], true)) {
            return $decision;
        }

        if (in_array($method, ['GET', 'HEAD'], true) || ! self::routeAllows($path, $method)) {
            return $decision;
        }

        return ['action' => 'serve'];
    }

    /**
     * Sous-domaine et chemin public qui possedent ce chemin interne, null si c'est l'apex.
     *
     * @return array{subdomain: string, path: string}|null
     */
    public static function ownerFor(string $path): ?array
    {
        $path = self::normalizePath($path);

        if ($path === '/admin' || str_starts_with($path, '/admin/')) {
            return ['subdomain' => 'admin', 'path' => $path === '/admin' ? '/' : self::stripPrefix($path, '/admin')];
        }

        if ($path === '/api' || str_starts_with($path, '/api/')) {
            return ['subdomain' => 'api', 'path' => $path === '/api' ? '/' : self::stripPrefix($path, '/api')];
        }

        if (self::pathMatchesSegments($path, config('subdomains.hosts.sign.segments', []))) {
            return ['subdomain' => 'sign', 'path' => $path === '/signin' ? '/' : $path];
        }

        if (self::pathMatchesSegments($path, config('subdomains.hosts.dash.segments', []))) {
            return ['subdomain' => 'dash', 'path' => $path === '/dashboard' ? '/' : $path];
        }

        if (preg_match('#^/m/([^/]+)(/.*)?$#', $path, $matches)) {
            return ['subdomain' => $matches[1], 'path' => '/manage'.($matches[2] ?? '')];
        }

        if (preg_match('#^/p/([^/]+)(/.*)?$#', $path, $matches)) {
            return ['subdomain' => $matches[1], 'path' => '/public'.($matches[2] ?? '')];
        }

        if (preg_match('#^/w/([^/]+)(/.*)?$#', $path, $matches)) {
            return ['subdomain' => self::tenantSlugForWebSlug($matches[1]), 'path' => $matches[2] ?? '/'];
        }

        return null;
    }

    /**
     * Le chemin interne sert-il une page publique d'entreprise ?
     *
     * Vitrine (/w/) et profil public (/p/) sont ouverts a tous et donc indexables.
     * L'espace de gestion (/m/) n'en fait pas partie.
     */
    public static function isEntreprisePublicPath(string $path): bool
    {
        $path = self::normalizePath($path);

        return preg_match('#^/(p|w)(/|$)#', $path) === 1;
    }

    /**
     * URL unique a faire indexer pour la requete courante.
     *
     * La meme page repond depuis l'apex et depuis le sous-domaine de l'entreprise :
     * la canonique designe le sous-domaine, pour ne pas faire indexer deux fois le
     * meme contenu. La chaine de requete en est exclue, elle ne change pas la page.
     */
    public static function canonicalUrl(?Request $request = null): ?string
    {
        try {
            $request ??= request();
        } catch (\Throwable) {
            return null;
        }

        if (! $request) {
            return null;
        }

        $interne = (string) $request->attributes->get('subdomain.rewritten', $request->getPathInfo());

        if (! self::enabled()) {
            return rtrim((string) config('app.url'), '/').self::normalizePath($interne);
        }

        return self::ownerUrl($interne);
    }

    public static function ownerUrl(string $path, ?string $query = null): string
    {
        $path = self::normalizePath($path);
        $suffix = $query ? '?'.$query : '';
        $owner = self::ownerFor($path);

        if ($owner === null) {
            $apex = rtrim((string) config('app.url'), '/');

            return $apex.($path === '/' ? '/' : $path).$suffix;
        }

        return self::scheme().'://'.$owner['subdomain'].'.'.self::baseDomain().$owner['path'].$suffix;
    }

    public static function outboundPath(string $path, ?Request $request = null): string
    {
        if (! self::enabled()) {
            return $path === '' ? '/' : $path;
        }

        try {
            $request ??= request();
        } catch (\Throwable) {
            return $path === '' ? '/' : $path;
        }

        if (! $request) {
            return $path === '' ? '/' : $path;
        }

        $parsed = self::parse($request->getHost());
        $path = self::normalizePath($path);

        if ($parsed['mode'] === self::MODE_ADMIN) {
            if ($path === '/admin') {
                return '/';
            }
            if (str_starts_with($path, '/admin/')) {
                return self::stripPrefix($path, '/admin');
            }
        }

        if ($parsed['mode'] === self::MODE_API) {
            if ($path === '/api') {
                return '/';
            }
            if (str_starts_with($path, '/api/')) {
                return self::stripPrefix($path, '/api');
            }
        }

        if ($parsed['mode'] === self::MODE_DASH && $path === '/dashboard') {
            return '/';
        }

        if ($parsed['mode'] === self::MODE_SIGN && $path === '/signin') {
            return '/';
        }

        if ($parsed['mode'] === self::MODE_TENANT) {
            $slug = preg_quote((string) $parsed['subdomain'], '#');
            if (preg_match('#^/m/'.$slug.'(/.*)?$#', $path, $matches)) {
                return '/manage'.($matches[1] ?? '');
            }
            if (preg_match('#^/p/'.$slug.'(/.*)?$#', $path, $matches)) {
                return '/public'.($matches[1] ?? '');
            }
            if (preg_match('#^/w/[^/]+(/.*)?$#', $path, $matches)) {
                return $matches[1] ?? '/';
            }
        }

        return $path;
    }

    public static function rememberUrlRoot(string $root): void
    {
        self::$urlRoot = rtrim($root, '/');
    }

    /**
     * Genere l'URL sortante complete : chemin local si le host courant le possede,
     * URL absolue vers le host proprietaire sinon (evite un 302 par lien).
     */
    public static function outboundUrl(string $path): string
    {
        $root = (string) self::$urlRoot;
        $path = self::normalizePath($path);

        if (! self::enabled()) {
            return $root.$path;
        }

        try {
            $request = request();
        } catch (\Throwable) {
            return $root.$path;
        }

        if (! $request) {
            return $root.$path;
        }

        $parsed = self::parse($request->getHost());

        if (self::isSharedPath($path) || $parsed['mode'] === self::MODE_UNKNOWN) {
            return $root.$path;
        }

        // Sur l'apex, on pointe directement le proprietaire pour eviter un rebond.
        if ($parsed['mode'] === self::MODE_APEX) {
            return self::legacyRedirect() && ! self::isWriteOnlyPath($path) && self::ownerFor($path) !== null
                ? self::ownerUrl($path)
                : $root.$path;
        }

        if (self::belongsToHost($parsed, $path)) {
            return $root.self::outboundPath($path, $request);
        }

        if (self::isWriteOnlyPath($path)) {
            return $root.$path;
        }

        return self::ownerUrl($path);
    }

    /**
     * @param  array{mode: string, subdomain: ?string, host: string}  $parsed
     */
    public static function belongsToHost(array $parsed, string $path): bool
    {
        $path = self::normalizePath($path);

        if ($parsed['mode'] === self::MODE_TENANT) {
            $slug = preg_quote((string) $parsed['subdomain'], '#');

            if (preg_match('#^/(m|p)/'.$slug.'(/.*)?$#', $path)) {
                return true;
            }

            // Chemins deja publics (/manage, /public) : ils vivent sur ce host.
            if (preg_match('#^/(manage|public)(/|$)#', $path)) {
                return true;
            }

            if (preg_match('#^/w/([^/]+)(/.*)?$#', $path, $matches)) {
                return self::tenantSlugForWebSlug($matches[1]) === $parsed['subdomain'];
            }

            return false;
        }

        $config = config('subdomains.hosts.'.$parsed['mode']);
        if (! is_array($config)) {
            return false;
        }

        $root = self::normalizePath((string) ($config['root'] ?? '/'));

        if (($config['type'] ?? 'space') === 'prefix') {
            return $path === $root || str_starts_with($path, $root.'/');
        }

        return self::pathMatchesSegments($path, $config['segments'] ?? []);
    }

    public static function normalizePath(string $path): string
    {
        if ($path === '' || $path === '/') {
            return '/';
        }

        $path = '/'.ltrim($path, '/');

        return rtrim($path, '/') ?: '/';
    }

    public static function routeExists(string $path, string $method = 'GET'): bool
    {
        $path = self::normalizePath($path);

        try {
            app('router')->getRoutes()->match(Request::create($path, $method));

            return true;
        } catch (MethodNotAllowedHttpException) {
            return true;
        } catch (NotFoundHttpException) {
            return false;
        }
    }

    /**
     * Contrairement a routeExists(), exige que la methode soit reellement acceptee.
     */
    public static function routeAllows(string $path, string $method): bool
    {
        $path = self::normalizePath($path);

        try {
            app('router')->getRoutes()->match(Request::create($path, $method));

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Chemin d'ecriture seule : appele en fetch ou en formulaire, jamais navigue.
     * Il doit donc rester sur le host courant, sinon l'appel devient cross-origin
     * (pas d'en-tetes CORS, pas de cookies) et echoue silencieusement.
     */
    public static function isWriteOnlyPath(string $path): bool
    {
        $path = self::normalizePath($path);

        if (array_key_exists($path, self::$writeOnlyCache)) {
            return self::$writeOnlyCache[$path];
        }

        if (self::routeAllows($path, 'GET')) {
            return self::$writeOnlyCache[$path] = false;
        }

        foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            if (self::routeAllows($path, $method)) {
                return self::$writeOnlyCache[$path] = true;
            }
        }

        return self::$writeOnlyCache[$path] = false;
    }

    /**
     * L'apex garde ses anciens liens fonctionnels mais renvoie vers le proprietaire.
     *
     * @return array{action: string, path?: string, url?: string, code?: int}
     */
    private static function decideApex(string $path, ?string $query): array
    {
        if (! self::legacyRedirect() || self::isSharedPath($path)) {
            return ['action' => 'serve'];
        }

        $owner = self::ownerFor($path);
        if ($owner === null) {
            return ['action' => 'serve'];
        }

        return [
            'action' => 'redirect',
            'url' => self::ownerUrl($path, $query),
            'code' => self::redirectStatus(),
        ];
    }

    /**
     * @param  array{mode: string, subdomain: ?string, host: string}  $parsed
     * @return array{action: string, path?: string, url?: string, code?: int}
     */
    private static function decideTenant(array $parsed, string $path, string $method, ?string $query): array
    {
        $slug = (string) $parsed['subdomain'];
        $entreprise = Entreprise::query()->where('slug', $slug)->first();
        if (! $entreprise) {
            return ['action' => 'abort', 'code' => 404];
        }

        if ($path === '/manage' || str_starts_with($path, '/manage/')) {
            return ['action' => 'rewrite', 'path' => '/m/'.$slug.substr($path, strlen('/manage'))];
        }

        if ($path === '/public' || str_starts_with($path, '/public/')) {
            return ['action' => 'rewrite', 'path' => '/p/'.$slug.substr($path, strlen('/public'))];
        }

        $webSlug = $entreprise->slug_web ?: $entreprise->slug;

        // La racine du tenant : la vitrine, ou l'espace de gestion sans abonnement.
        // Seule la navigation est redirigee, une ecriture reste sur sa route.
        if ($path === '/') {
            $navigation = in_array($method, ['GET', 'HEAD'], true);

            return $entreprise->aSiteWebActif() || ! $navigation
                ? ['action' => 'rewrite', 'path' => '/w/'.$webSlug]
                : ['action' => 'redirect', 'url' => self::tenantUrl($slug, '/manage')];
        }

        // Le reste de la vitrine est reecrit tel quel : c'est l'application qui
        // gere l'abonnement et les droits, exactement comme sur l'apex.
        $vitrinePath = '/w/'.$webSlug.$path;
        if (self::routeAllows($vitrinePath, $method)) {
            return ['action' => 'rewrite', 'path' => $vitrinePath];
        }

        if (self::isSharedPath($path)) {
            return ['action' => 'serve'];
        }

        if (preg_match('#^/(m|p|w)(/|$)#', $path) || self::routeAllows($path, $method)) {
            return ['action' => 'redirect', 'url' => self::ownerUrl($path, $query)];
        }

        if (self::routeExists($vitrinePath, $method)) {
            return ['action' => 'rewrite', 'path' => $vitrinePath];
        }

        if (self::routeExists($path, $method)) {
            return ['action' => 'redirect', 'url' => self::ownerUrl($path, $query)];
        }

        return ['action' => 'abort', 'code' => 404];
    }

    /**
     * @return array{action: string, path?: string, url?: string, code?: int}
     */
    private static function decideMapped(string $mode, string $path, string $method, ?string $query): array
    {
        $config = config('subdomains.hosts.'.$mode);
        if (! is_array($config)) {
            return ['action' => 'abort', 'code' => 404];
        }

        $root = self::normalizePath((string) ($config['root'] ?? '/'));
        $type = $config['type'] ?? 'space';

        if ($type === 'prefix') {
            if ($path === $root || str_starts_with($path, $root.'/')) {
                return ['action' => 'serve'];
            }

            if ($path === '/') {
                return ['action' => 'rewrite', 'path' => $root];
            }

            // Le perimetre du host prime sur les chemins partages : sans ca,
            // admin/api/* et admin/media seraient avales par /api et /media.
            // La methode doit etre reellement acceptee, sinon un POST serait
            // detourne vers une route GET homonyme du perimetre.
            $candidate = $root.$path;
            if (self::routeAllows($candidate, $method)) {
                return ['action' => 'rewrite', 'path' => $candidate];
            }

            if (self::isSharedPath($path)) {
                return ['action' => 'serve'];
            }

            if (self::routeAllows($path, $method)) {
                return ['action' => 'redirect', 'url' => self::ownerUrl($path, $query)];
            }

            if (self::routeExists($candidate, $method)) {
                return ['action' => 'rewrite', 'path' => $candidate];
            }

            if (self::routeExists($path, $method)) {
                return ['action' => 'redirect', 'url' => self::ownerUrl($path, $query)];
            }

            return ['action' => 'abort', 'code' => 404];
        }

        if ($path === '/') {
            return ['action' => 'rewrite', 'path' => $root];
        }

        $segments = $config['segments'] ?? [];
        if (self::pathMatchesSegments($path, $segments)) {
            return ['action' => 'serve'];
        }

        if (self::isSharedPath($path)) {
            return ['action' => 'serve'];
        }

        if (self::routeExists($path, $method)) {
            return ['action' => 'redirect', 'url' => self::ownerUrl($path, $query)];
        }

        return ['action' => 'abort', 'code' => 404];
    }

    /**
     * @param  list<string>  $segments
     */
    public static function pathMatchesSegments(string $path, array $segments): bool
    {
        $trimmed = ltrim(self::normalizePath($path), '/');
        foreach ($segments as $segment) {
            $segment = trim((string) $segment, '/');
            if ($segment === '') {
                continue;
            }
            if ($trimmed === $segment || str_starts_with($trimmed, $segment.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retire le prefixe du host, sauf si le chemin obtenu serait capte par le
     * serveur web (/admin/media ne doit pas devenir le dossier public/media).
     */
    private static function stripPrefix(string $path, string $prefix): string
    {
        $stripped = substr($path, strlen($prefix)) ?: '/';

        return self::isStaticPath($stripped) ? $path : $stripped;
    }

    private static function absoluteUrl(string $subdomain, string $path): string
    {
        $path = self::normalizePath($path);

        return self::scheme().'://'.$subdomain.'.'.self::baseDomain().($path === '/' ? '/' : $path);
    }

    private static function tenantSlugForWebSlug(string $webSlug): string
    {
        if (isset(self::$webSlugCache[$webSlug])) {
            return self::$webSlugCache[$webSlug];
        }

        $entreprise = Entreprise::query()
            ->where('slug_web', $webSlug)
            ->orWhere('slug', $webSlug)
            ->first(['slug']);

        return self::$webSlugCache[$webSlug] = $entreprise?->slug ?: $webSlug;
    }

    private static function slugExists(string $slug, ?int $exceptId): bool
    {
        return Entreprise::query()
            ->where('slug', $slug)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists();
    }
}
