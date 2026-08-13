<?php

namespace App\Helpers;

use App\Models\Entreprise;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class SiteHelper
{
    /**
     * Récupérer le logo du site selon le contexte
     * 
     * @param string $type 'light', 'dark', 'transparent', ou 'auto' (détecte selon le thème)
     * @return string|null URL du logo ou null si non défini
     */
    public static function getLogo(?string $type = 'auto'): ?string
    {
        // Si auto, détecter selon le thème (pour le favicon et le site web)
        if ($type === 'auto') {
            // Pour les emails, on utilise toujours transparent
            // Pour le web, on pourrait détecter le thème mais on va utiliser un système différent
            $type = 'transparent';
        }
        
        $logoPath = Setting::get("site_logo_{$type}", null);
        
        if (!$logoPath) {
            return null;
        }
        
        // Retourner l'URL complète
        return route('storage.serve', ['path' => $logoPath]);
    }
    
    /**
     * Récupérer le logo pour les emails (toujours transparent)
     */
    public static function getEmailLogo(): ?string
    {
        return self::getLogo('transparent');
    }
    
    /**
     * Récupérer le favicon selon le mode (clair/sombre)
     */
    public static function getFavicon(?string $theme = null): ?string
    {
        // Si le thème n'est pas spécifié, on retourne les deux versions
        if ($theme === null) {
            return null;
        }
        
        $logoPath = Setting::get("site_logo_{$theme}", null);
        
        if (!$logoPath) {
            return null;
        }
        
        return route('storage.serve', ['path' => $logoPath]);
    }

    /**
     * Chemin disque d'un fichier storage/app/public (même logique que StorageController).
     */
    public static function publicStorageAbsolutePath(?string $relative): ?string
    {
        if (! $relative) {
            return null;
        }

        $relative = ltrim(str_replace('..', '', $relative), '/');

        $candidates = array_unique([
            base_path('storage/app/public/'.$relative),
            storage_path('app/public/'.$relative),
        ]);

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        if (Storage::disk('public')->exists($relative)) {
            return Storage::disk('public')->path($relative);
        }

        return null;
    }

    /**
     * Entreprise publique par slug (/p, /m) ou slug_web (/w).
     */
    public static function findEntrepriseByPublicSlug(string $slug): ?Entreprise
    {
        return Entreprise::query()
            ->where(function ($query) use ($slug) {
                $query->where('slug', $slug)->orWhere('slug_web', $slug);
            })
            ->first(['id', 'logo', 'slug', 'slug_web']);
    }

    /**
     * Entreprise de la requête courante (/p, /w, /m, messagerie).
     */
    public static function resolveEntrepriseFromRequest(?\Illuminate\Http\Request $request = null): ?Entreprise
    {
        $request ??= request();
        if (! $request || ! preg_match('#^(p|w|m|messagerie)/([^/]+)#', $request->path(), $matches)) {
            return null;
        }

        return self::findEntrepriseByPublicSlug($matches[2]);
    }

    /**
     * URL du favicon de la page entreprise (/p/{slug}/favicon.png, /w/..., /m/...).
     */
    public static function entrepriseContextFaviconUrl(mixed $entreprise = null, ?\Illuminate\Http\Request $request = null): ?string
    {
        $request ??= request();
        $path = $request?->path() ?? '';

        if (! preg_match('#^(p|w|m|messagerie)/([^/]+)#', $path)) {
            return null;
        }

        $entreprise = $entreprise instanceof Entreprise
            ? $entreprise
            : self::resolveEntrepriseFromRequest($request);

        if (! $entreprise || ! $entreprise->slug) {
            return null;
        }

        if (str_starts_with($path, 'w/')) {
            return route('site-web.favicon', $entreprise->slug_web ?: $entreprise->slug);
        }

        if (str_starts_with($path, 'm/')) {
            return route('entreprise.favicon', $entreprise->slug);
        }

        return route('public.favicon', $entreprise->slug);
    }

    /**
     * Favicon d'une entreprise (logo) ou null si absent.
     */
    public static function getEntrepriseFavicon(mixed $entreprise): ?string
    {
        if (! $entreprise instanceof Entreprise || empty($entreprise->logo)) {
            return null;
        }

        if (! self::publicStorageAbsolutePath($entreprise->logo)) {
            return null;
        }

        return route('storage.serve', ['path' => $entreprise->logo]);
    }

    /**
     * Type MIME d'un favicon à partir de son chemin (logo ou URL).
     */
    public static function faviconMimeType(?string $path): string
    {
        if (! $path) {
            return 'image/png';
        }

        $file = parse_url($path, PHP_URL_PATH) ?: $path;
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            default => 'image/png',
        };
    }

    /**
     * Fichier logo Allotata à servir en fallback (PWA / icône).
     */
    public static function getDefaultFaviconAbsolutePath(): ?string
    {
        foreach (['site_logo_pwa', 'site_logo_light', 'site_logo_transparent', 'site_logo_dark'] as $key) {
            $path = Setting::get($key, null);
            $absolute = self::publicStorageAbsolutePath($path);
            if ($absolute) {
                return $absolute;
            }
        }

        $ico = public_path('favicon.ico');

        return file_exists($ico) ? $ico : null;
    }
    
    /**
     * Récupérer le nom du site
     */
    public static function getSiteName(): string
    {
        return Setting::get('site_name', 'Allo Tata');
    }
}
