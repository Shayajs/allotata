<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Support\SubdomainHost;
use Illuminate\Support\Str;

class ManifestController extends Controller
{
    public function forCurrentHost()
    {
        $parsed = SubdomainHost::current();

        if ($parsed['mode'] === SubdomainHost::MODE_ADMIN) {
            return response()->json($this->adminManifest());
        }

        if ($parsed['mode'] === SubdomainHost::MODE_DASH) {
            return response()->json($this->dashManifest());
        }

        if ($parsed['mode'] === SubdomainHost::MODE_SUPPORT) {
            return response()->json($this->supportManifest());
        }

        if ($parsed['mode'] === SubdomainHost::MODE_LEARN) {
            return response()->json($this->learnManifest());
        }

        if (in_array($parsed['mode'], [SubdomainHost::MODE_SIGN, SubdomainHost::MODE_API], true)) {
            return response()->json($this->apexManifest());
        }

        if ($parsed['mode'] === SubdomainHost::MODE_TENANT) {
            $entreprise = Entreprise::where('slug', $parsed['subdomain'])->firstOrFail();

            return response()->json($this->tenantManifest($entreprise));
        }

        return response()->json($this->apexManifest());
    }

    public function show($slug)
    {
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        $parsed = SubdomainHost::current();
        if ($parsed['mode'] === SubdomainHost::MODE_TENANT && $parsed['subdomain'] === $entreprise->slug) {
            return response()->json($this->tenantManifest($entreprise));
        }

        return response()->json([
            'name' => $entreprise->nom,
            'short_name' => Str::limit($entreprise->nom, 12, ''),
            'description' => $entreprise->description ?? "Application pour {$entreprise->nom}",
            'start_url' => route('entreprise.dashboard', ['slug' => $entreprise->slug]),
            'scope' => route('entreprise.dashboard', ['slug' => $entreprise->slug], false),
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#ffffff',
            'icons' => $this->entrepriseIcons($entreprise),
        ]);
    }

    public function icon($slug, $size)
    {
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        $size = (int) $size;

        if ($entreprise->logo) {
            $logoPath = \App\Helpers\SiteHelper::publicStorageAbsolutePath($entreprise->logo);
            if ($logoPath && $this->isPwaIconFile($logoPath)) {
                return response()->file($logoPath, [
                    'Content-Type' => \App\Helpers\SiteHelper::faviconMimeType($entreprise->logo),
                ]);
            }
        }

        $allotataFavicon = \App\Helpers\SiteHelper::getDefaultFaviconAbsolutePath();
        if ($allotataFavicon && $this->isPwaIconFile($allotataFavicon)) {
            return response()->file($allotataFavicon, [
                'Content-Type' => \App\Helpers\SiteHelper::faviconMimeType($allotataFavicon),
            ]);
        }

        return $this->fallbackPngIcon($size);
    }

    public function brightshell()
    {
        return response()->json([
            'name' => 'BrightShell ERP',
            'short_name' => 'BrightShell',
            'description' => 'ERP de gestion Allo Tata',
            'start_url' => route('brightshell.index'),
            'scope' => '/brightshell/',
            'display' => 'standalone',
            'background_color' => '#0f172a',
            'theme_color' => '#0f172a',
            'icons' => [
                [
                    'src' => asset('/media/brightshell/favicon.png'),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
                [
                    'src' => asset('/media/brightshell/favicon.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
            ],
        ]);
    }

    private function apexManifest(): array
    {
        return [
            'name' => 'Allo Tata',
            'short_name' => 'AlloTata',
            'description' => 'Votre service pour gérer votre micro entreprise l\'esprit tranquille.',
            'start_url' => '/dashboard',
            'display' => 'standalone',
            'background_color' => '#0f172a',
            'theme_color' => '#0f172a',
            'orientation' => 'portrait',
            'icons' => $this->defaultIcons(),
        ];
    }

    private function dashManifest(): array
    {
        return [
            'name' => 'Allo Tata',
            'short_name' => 'AlloTata',
            'description' => 'Votre service pour gérer votre micro entreprise l\'esprit tranquille.',
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#0f172a',
            'theme_color' => '#0f172a',
            'orientation' => 'portrait',
            'icons' => $this->defaultIcons(),
        ];
    }

    private function supportManifest(): array
    {
        return [
            'name' => 'Aide Allo Tata',
            'short_name' => 'Aide',
            'description' => 'FAQ, contact et suivi de vos demandes de support Allo Tata.',
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#0f172a',
            'theme_color' => '#0f172a',
            'orientation' => 'portrait',
            'icons' => $this->defaultIcons(),
        ];
    }

    private function learnManifest(): array
    {
        return [
            'name' => 'Apprendre avec Allo Tata',
            'short_name' => 'Apprendre',
            'description' => 'Les cours Allo Tata : gérer sa micro entreprise, étape par étape.',
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#0f172a',
            'theme_color' => '#0f172a',
            'orientation' => 'portrait',
            'icons' => $this->defaultIcons(),
        ];
    }

    private function adminManifest(): array
    {
        return [
            'name' => 'alloadmin',
            'short_name' => 'alloadmin',
            'description' => 'Administration Allo Tata',
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#0f172a',
            'theme_color' => '#0f172a',
            'icons' => $this->defaultIcons(),
        ];
    }

    private function tenantManifest(Entreprise $entreprise): array
    {
        return [
            'name' => $entreprise->nom,
            'short_name' => Str::limit($entreprise->nom, 12, ''),
            'description' => $entreprise->description ?? "Application pour {$entreprise->nom}",
            'start_url' => '/manage',
            'scope' => '/manage',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#ffffff',
            'icons' => $this->entrepriseIcons($entreprise),
        ];
    }

    private function entrepriseIcons(Entreprise $entreprise): array
    {
        $type = $this->iconMimeType($entreprise);

        return [
            [
                'src' => $this->iconSrc($entreprise, 192),
                'sizes' => '192x192',
                'type' => $type,
                'purpose' => 'any',
            ],
            [
                'src' => $this->iconSrc($entreprise, 512),
                'sizes' => '512x512',
                'type' => $type,
                'purpose' => 'any',
            ],
            [
                'src' => $this->iconSrc($entreprise, 512),
                'sizes' => '512x512',
                'type' => $type,
                'purpose' => 'maskable',
            ],
        ];
    }

    private function iconSrc(Entreprise $entreprise, int $size): string
    {
        $parsed = SubdomainHost::current();
        if ($parsed['mode'] === SubdomainHost::MODE_TENANT && $parsed['subdomain'] === $entreprise->slug) {
            return '/manage/icon/'.$size.'.png';
        }

        return route('manifest.icon', ['slug' => $entreprise->slug, 'size' => $size]);
    }

    private function iconMimeType(Entreprise $entreprise): string
    {
        if ($entreprise->logo) {
            $logoPath = \App\Helpers\SiteHelper::publicStorageAbsolutePath($entreprise->logo);
            if ($logoPath && $this->isPwaIconFile($logoPath)) {
                return \App\Helpers\SiteHelper::faviconMimeType($entreprise->logo);
            }
        }

        return 'image/png';
    }

    private function isPwaIconFile(string $path): bool
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, ['png', 'jpg', 'jpeg', 'webp'], true);
    }

    private function fallbackPngIcon(int $size)
    {
        $sized = public_path('icons/icon-'.$size.'x'.$size.'.png');
        if (is_file($sized)) {
            return response()->file($sized, ['Content-Type' => 'image/png']);
        }

        $default = public_path('icons/icon-192x192.png');
        if (is_file($default)) {
            return response()->file($default, ['Content-Type' => 'image/png']);
        }

        abort(404);
    }

    private function defaultIcons(): array
    {
        $v192 = $this->iconVersion('icon-192x192.png');
        $v512 = $this->iconVersion('icon-512x512.png');

        return [
            [
                'src' => '/icons/icon-192x192.png'.$v192,
                'sizes' => '192x192',
                'type' => 'image/png',
            ],
            [
                'src' => '/icons/icon-512x512.png'.$v512,
                'sizes' => '512x512',
                'type' => 'image/png',
            ],
            [
                'src' => '/icons/icon-512x512.png'.$v512,
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'maskable',
            ],
        ];
    }

    private function iconVersion(string $filename): string
    {
        $path = public_path('icons/'.$filename);

        return is_file($path) ? '?v='.filemtime($path) : '';
    }
}
