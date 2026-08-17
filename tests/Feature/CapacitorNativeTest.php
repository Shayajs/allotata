<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CapacitorNativeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['subdomains.enabled' => false]);
    }

    public function test_homepage_publique_reste_accessible_hors_app(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_invite_capacitor_est_renvoye_vers_la_connexion(): void
    {
        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 Capacitor AlloTataApp/1.0',
        ])->get('/')->assertRedirect(route('login'));
    }

    public function test_utilisateur_connecte_capacitor_va_au_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withHeaders(['X-Capacitor' => '1'])
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }

    public function test_login_capacitor_utilise_le_skin_immersif(): void
    {
        $html = $this->withHeaders(['X-Capacitor' => '1'])
            ->get(route('login'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('android-auth-page', $html);
        $this->assertStringContainsString('android-auth-brand', $html);
        $this->assertStringContainsString('is-capacitor', $html);
    }

    public function test_onglet_installer_est_masque_en_capacitor(): void
    {
        $request = \Illuminate\Http\Request::create('/dashboard', 'GET');
        $request->headers->set('X-Capacitor', '1');
        $request->attributes->set('is_capacitor', true);
        $this->app->instance('request', $request);

        $items = \App\Services\NavigationService::getDashboardItems(User::factory()->create());
        $installer = collect($items)->firstWhere('key', 'installer');

        $this->assertNotNull($installer);
        $this->assertFalse($installer['visible']);
    }

    public function test_assetlinks_est_servi(): void
    {
        config(['play.sha256_fingerprints' => ['AA:BB']]);

        $this->get('/.well-known/assetlinks.json')
            ->assertOk()
            ->assertJsonFragment([
                'package_name' => 'fr.allotata.app',
                'sha256_cert_fingerprints' => ['AA:BB'],
            ]);
    }
}
