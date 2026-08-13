<?php

namespace Tests\Unit\Helpers;

use App\Helpers\SiteHelper;
use App\Models\Entreprise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteHelperFaviconTest extends TestCase
{
    public function test_sans_entreprise_le_favicon_est_nul(): void
    {
        $this->assertNull(SiteHelper::getEntrepriseFavicon(null));
    }

    public function test_sans_logo_le_favicon_est_nul(): void
    {
        $entreprise = new Entreprise(['logo' => null]);

        $this->assertNull(SiteHelper::getEntrepriseFavicon($entreprise));
    }

    public function test_fichier_manquant_le_favicon_est_nul(): void
    {
        Storage::fake('public');
        $entreprise = new Entreprise(['logo' => 'logos/absent.png']);

        $this->assertNull(SiteHelper::getEntrepriseFavicon($entreprise));
    }

    public function test_avec_logo_le_favicon_pointe_vers_le_media(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('logos/acme.png', 'fake-png');
        $entreprise = new Entreprise(['logo' => 'logos/acme.png']);

        $url = SiteHelper::getEntrepriseFavicon($entreprise);

        $this->assertNotNull($url);
        $this->assertStringContainsString('logos/acme.png', $url);
    }

    public function test_favicon_mime_selon_extension(): void
    {
        $this->assertSame('image/jpeg', SiteHelper::faviconMimeType('logos/photo.jpg'));
        $this->assertSame('image/png', SiteHelper::faviconMimeType('logos/logo.png'));
        $this->assertSame('image/webp', SiteHelper::faviconMimeType('logos/logo.webp'));
        $this->assertSame('image/png', SiteHelper::faviconMimeType(null));
    }

    public function test_url_favicon_sur_p(): void
    {
        $entreprise = new Entreprise(['slug' => 'acme', 'slug_web' => 'acme-web']);
        $request = Request::create('/p/acme', 'GET');

        $this->assertStringContainsString('/p/acme/favicon.png', SiteHelper::entrepriseContextFaviconUrl($entreprise, $request));
    }

    public function test_url_favicon_sur_w(): void
    {
        $entreprise = new Entreprise(['slug' => 'acme', 'slug_web' => 'acme-web']);
        $request = Request::create('/w/acme-web', 'GET');

        $this->assertStringContainsString('/w/acme-web/favicon.png', SiteHelper::entrepriseContextFaviconUrl($entreprise, $request));
    }

    public function test_url_favicon_sur_m(): void
    {
        $entreprise = new Entreprise(['slug' => 'acme', 'slug_web' => 'acme-web']);
        $request = Request::create('/m/acme/reservations', 'GET');

        $this->assertStringContainsString('/m/acme/favicon.png', SiteHelper::entrepriseContextFaviconUrl($entreprise, $request));
    }

    public function test_pas_de_favicon_entreprise_hors_p_w_m(): void
    {
        $entreprise = new Entreprise(['slug' => 'acme', 'slug_web' => 'acme-web']);
        $request = Request::create('/settings', 'GET');

        $this->assertNull(SiteHelper::entrepriseContextFaviconUrl($entreprise, $request));
    }
}
