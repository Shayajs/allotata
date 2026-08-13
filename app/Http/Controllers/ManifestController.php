<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ManifestController extends Controller
{
    public function show($slug)
    {
        $entreprise = \App\Models\Entreprise::where('slug', $slug)->firstOrFail();

        $manifest = [
            'name' => $entreprise->nom,
            'short_name' => \Illuminate\Support\Str::limit($entreprise->nom, 12, ''),
            'description' => $entreprise->description ?? "Application pour {$entreprise->nom}",
            'start_url' => route('entreprise.dashboard', ['slug' => $entreprise->slug]),
            'scope' => route('entreprise.dashboard', ['slug' => $entreprise->slug], false), // Scope restreint à l'entreprise
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#ffffff',
            'icons' => [
                [
                    'src' => route('manifest.icon', ['slug' => $entreprise->slug, 'size' => 192]),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ],
                [
                    'src' => route('manifest.icon', ['slug' => $entreprise->slug, 'size' => 512]),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ]
            ]
        ];

        return response()->json($manifest);
    }

    public function icon($slug, $size)
    {
        $entreprise = \App\Models\Entreprise::where('slug', $slug)->firstOrFail();
        
        if ($entreprise->logo) {
            $logoPath = \App\Helpers\SiteHelper::publicStorageAbsolutePath($entreprise->logo);
            if ($logoPath) {
                return response()->file($logoPath);
            }
        }

        $allotataFavicon = \App\Helpers\SiteHelper::getDefaultFaviconAbsolutePath();
        if ($allotataFavicon) {
            return response()->file($allotataFavicon);
        }

        // Dernier recours : initiale de l'entreprise
        $color = '0f172a';
        $bg = 'ffffff';
        $name = urlencode($entreprise->nom);
        
        $url = "https://ui-avatars.com/api/?name={$name}&size={$size}&background={$color}&color={$bg}&length=1&font-size=0.5&bold=true";
        
        return redirect($url);
    }
    public function brightshell()
    {
        $manifest = [
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
                    'purpose' => 'any maskable'
                ],
                [
                    'src' => asset('/media/brightshell/favicon.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ]
            ]
        ];

        return response()->json($manifest);
    }
}
