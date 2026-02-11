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
        
        // Si l'entreprise a un logo
        if ($entreprise->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($entreprise->logo)) {
            // Ici, idéalement, on redimensionnerait l'image avec un package comme Intervention Image
            // Pour l'instant, on renvoie l'image d'origine (le navigateur s'adaptera, mais ce n'est pas opti en perf)
            // TODO: Utiliser ImageService pour resize
            $path = \Illuminate\Support\Facades\Storage::disk('public')->path($entreprise->logo);
            return response()->file($path);
        }

        // Sinon, on génère une image placeholder avec la première lettre
        // Note: Pour faire simple sans librairie GD complexe ici, on renvoie une image par défaut ou une redirection
        // Vers une API de placeholder (ex: ui-avatars.com)
        $color = '0f172a'; // Bleu nuit par défaut
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
