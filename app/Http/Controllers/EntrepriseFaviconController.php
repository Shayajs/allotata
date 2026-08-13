<?php

namespace App\Http\Controllers;

use App\Helpers\SiteHelper;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EntrepriseFaviconController extends Controller
{
    public function show(string $slug): BinaryFileResponse
    {
        $entreprise = SiteHelper::findEntrepriseByPublicSlug($slug);

        if (! $entreprise) {
            abort(404);
        }

        $logoPath = SiteHelper::publicStorageAbsolutePath($entreprise->logo);
        if ($logoPath) {
            return response()->file($logoPath, [
                'Content-Type' => SiteHelper::faviconMimeType($entreprise->logo),
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        $fallback = SiteHelper::getDefaultFaviconAbsolutePath();
        if ($fallback) {
            return response()->file($fallback, [
                'Content-Type' => SiteHelper::faviconMimeType($fallback),
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        abort(404);
    }
}
