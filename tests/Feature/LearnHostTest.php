<?php

namespace Tests\Feature;

use App\Models\CourseModule;
use App\Models\User;
use App\Support\SubdomainHost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnHostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'subdomains.enabled' => true,
            'subdomains.base_domain' => 'allotata.test',
            'app.url' => 'https://allotata.test',
        ]);
    }

    public function test_la_racine_du_host_learn_sert_le_catalogue(): void
    {
        $this->get('https://learn.allotata.test/')
            ->assertOk()
            ->assertHeaderMissing('X-Robots-Tag')
            ->assertSee('<link rel="canonical" href="https://learn.allotata.test/">', false);
    }

    public function test_le_prefixe_apprendre_disparait_des_adresses(): void
    {
        $module = $this->module();

        $this->get('https://learn.allotata.test/module/'.$module->id)
            ->assertOk();

        // L'ancienne adresse reste servie sur place : les liens en circulation
        // ne cassent pas.
        $this->get('https://learn.allotata.test/apprendre/module/'.$module->id)
            ->assertOk();

        $this->assertSame(
            'https://learn.allotata.test/module/'.$module->id,
            SubdomainHost::ownerUrl('/apprendre/module/'.$module->id)
        );
    }

    public function test_les_anciens_liens_de_l_apex_renvoient_vers_learn(): void
    {
        config(['subdomains.legacy_redirect' => true]);

        $module = $this->module();

        $this->get('https://allotata.test/apprendre')
            ->assertRedirect('https://learn.allotata.test/');

        $this->get('https://allotata.test/apprendre/module/'.$module->id)
            ->assertRedirect('https://learn.allotata.test/module/'.$module->id);
    }

    public function test_les_cours_ont_quitte_dash_pour_learn(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('https://dash.allotata.test/apprendre')
            ->assertRedirect('https://learn.allotata.test/');

        $this->assertSame('https://learn.allotata.test/', SubdomainHost::ownerUrl('/apprendre'));
    }

    public function test_la_progression_reste_sur_l_origine_de_la_page(): void
    {
        // Les lecons suivent la progression par appel AJAX : le chemin est partage,
        // donc servi par le host courant. Sans ca, la requete serait inter-origines.
        $this->assertSame(
            'serve',
            SubdomainHost::decide(
                \Illuminate\Http\Request::create('https://learn.allotata.test/api/courses/complete-lesson', 'POST')
            )['action']
        );

        $this->assertTrue(SubdomainHost::isSharedPath('/api/courses/complete-lesson'));
    }

    public function test_le_host_learn_renvoie_les_espaces_de_travail_a_leur_proprietaire(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('https://learn.allotata.test/dashboard')
            ->assertRedirect('https://dash.allotata.test/');

        $this->get('https://learn.allotata.test/support/faq')
            ->assertRedirect('https://support.allotata.test/');

        $this->get('https://learn.allotata.test/nawak')
            ->assertNotFound();
    }

    public function test_le_manifeste_du_host_learn_reste_dans_son_perimetre(): void
    {
        $this->get('https://learn.allotata.test/manifest.json')
            ->assertOk()
            ->assertJsonPath('short_name', 'Apprendre')
            ->assertJsonPath('start_url', '/')
            ->assertJsonPath('scope', '/');
    }

    public function test_le_slug_learn_est_reserve(): void
    {
        $this->assertTrue(SubdomainHost::isReservedSlug('learn'));
        $this->assertSame('learn-1', SubdomainHost::nextAvailableSlug('learn'));
    }

    private function module(): CourseModule
    {
        return CourseModule::create([
            'titre' => 'Gérer sa TVA',
            'description' => 'Les bases de la TVA en micro entreprise.',
            'ordre' => 1,
            'est_actif' => true,
        ]);
    }
}
