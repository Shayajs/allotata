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

    private function adminManifest(): array
    {
        return [
            'name' => 'Allo Tata Admin',
            'short_name' => 'Admin',
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
        return [
            [
                'src' => route('manifest.icon', ['slug' => $entreprise->slug, 'size' => 192]),
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any maskable',
            ],
            [
                'src' => route('manifest.icon', ['slug' => $entreprise->slug, 'size' => 512]),
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any maskable',
            ],
        ];
    }

    private function defaultIcons(): array
    {
        return [
            [
                'src' => '/icons/icon-192x192.png',
                'sizes' => '192x192',
                'type' => 'image/png',
            ],
            [
                'src' => '/icons/icon-512x512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
            ],
            [
                'src' => '/icons/icon-512x512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'maskable',
            ],
        ];
    }
}
