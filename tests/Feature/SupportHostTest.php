<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\User;
use App\Support\SubdomainHost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportHostTest extends TestCase
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

    public function test_la_racine_du_host_support_sert_la_faq(): void
    {
        $this->get('https://support.allotata.test/')
            ->assertOk()
            ->assertSee('Questions fréquentes', false)
            ->assertSee('https://support.allotata.test/', false);
    }

    public function test_les_canaux_de_demande_vivent_sur_le_host_support(): void
    {
        $this->get('https://support.allotata.test/contact')->assertOk();
        $this->get('https://support.allotata.test/tickets/create')->assertOk();
        $this->get('https://support.allotata.test/feedback')->assertOk();
    }

    public function test_l_aide_publique_reste_indexable_mais_pas_les_tickets(): void
    {
        $this->get('https://support.allotata.test/')->assertOk()->assertHeaderMissing('X-Robots-Tag');
        $this->get('https://support.allotata.test/contact')->assertOk()->assertHeaderMissing('X-Robots-Tag');
        $this->get('https://support.allotata.test/feedback')->assertOk()->assertHeaderMissing('X-Robots-Tag');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('https://support.allotata.test/tickets')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_la_faq_et_le_contact_designent_une_seule_url_canonique(): void
    {
        $this->get('https://support.allotata.test/')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://support.allotata.test/">', false);

        $this->get('https://support.allotata.test/contact')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://support.allotata.test/contact">', false);
    }

    public function test_les_anciens_liens_de_l_apex_renvoient_vers_support(): void
    {
        config(['subdomains.legacy_redirect' => true]);

        $this->get('https://allotata.test/support/faq')
            ->assertRedirect('https://support.allotata.test/');

        $this->get('https://allotata.test/tickets/create')
            ->assertRedirect('https://support.allotata.test/tickets/create');

        $this->get('https://allotata.test/feedback')
            ->assertRedirect('https://support.allotata.test/feedback');
    }

    public function test_les_tickets_ont_quitte_dash_pour_support(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('https://dash.allotata.test/tickets')
            ->assertRedirect('https://support.allotata.test/tickets');

        $this->assertSame(
            'https://support.allotata.test/tickets',
            SubdomainHost::ownerUrl('/tickets')
        );
    }

    public function test_le_host_support_renvoie_les_espaces_de_travail_a_leur_proprietaire(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('https://support.allotata.test/dashboard')
            ->assertRedirect('https://dash.allotata.test/');

        $this->get('https://support.allotata.test/signin')
            ->assertRedirect('https://sign.allotata.test/');

        $this->get('https://support.allotata.test/un-chemin-qui-n-existe-pas')
            ->assertNotFound();
    }

    public function test_le_formulaire_du_pied_de_page_ecrit_sur_le_host_qui_recoit(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $message = [
            'nom' => 'Camille',
            'email' => 'camille@example.test',
            'sujet' => 'Une question',
            'message' => 'Bonjour, comment activer la livraison ?',
        ];

        // L'ecriture est servie par le host qui la recoit : jamais de rebond, donc
        // jamais de requete inter-origines.
        $this->post('https://allotata.test/contact', $message)
            ->assertRedirect('https://allotata.test/contact');

        $this->post('https://support.allotata.test/contact', $message)
            ->assertRedirect('https://support.allotata.test/contact');

        $this->assertSame(2, Contact::where('email', 'camille@example.test')->count());

        $this->followingRedirects()
            ->post('https://support.allotata.test/contact', $message)
            ->assertOk()
            ->assertSee('envoyé avec succès', false);

        // Avec les redirections implicites, la confirmation part vers le
        // proprietaire du chemin : l'apex ne garde pas la page de contact.
        config(['subdomains.legacy_redirect' => true]);

        $this->post('https://allotata.test/contact', $message)
            ->assertRedirect('https://support.allotata.test/contact');
    }

    public function test_le_manifeste_du_host_support_reste_dans_son_perimetre(): void
    {
        $this->get('https://support.allotata.test/manifest.json')
            ->assertOk()
            ->assertJsonPath('short_name', 'Aide')
            ->assertJsonPath('start_url', '/')
            ->assertJsonPath('scope', '/');
    }

    public function test_le_slug_support_est_reserve(): void
    {
        $this->assertTrue(SubdomainHost::isReservedSlug('support'));
    }
}
