<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiHostTest extends TestCase
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

    public function test_la_racine_du_host_api_sert_la_page_de_garde(): void
    {
        $this->get('https://api.allotata.test/')
            ->assertOk()
            ->assertSee('API Allo Tata')
            ->assertSee('https://api.allotata.test/v1/', false)
            ->assertSee('aucune v2 n\'est prévue', false);
    }

    public function test_la_page_de_garde_est_indexable_et_canonique(): void
    {
        // Contrairement au reste des sous-domaines : une doc publique doit se trouver.
        $this->get('https://api.allotata.test/')
            ->assertHeaderMissing('X-Robots-Tag')
            ->assertSee('<link rel="canonical" href="https://api.allotata.test/">', false);

        // Le reste du host api reste hors des moteurs.
        $this->get('https://api.allotata.test/v1')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_l_index_v1_decrit_les_endpoints_publies(): void
    {
        $response = $this->getJson('https://api.allotata.test/v1');

        $response->assertOk()
            ->assertJsonPath('version', 'v1')
            ->assertJsonPath('base_url', 'https://api.allotata.test/v1')
            ->assertJsonPath('authentification', 'aucune');

        $urls = array_column($response->json('endpoints'), 'url');

        $this->assertContains('https://api.allotata.test/v1/search/autocomplete', $urls);
        $this->assertContains('https://api.allotata.test/v1/address/search', $urls);
        $this->assertContains('https://api.allotata.test/v1/address/cities', $urls);
        $this->assertContains('https://api.allotata.test/v1/address/geocode', $urls);
    }

    public function test_un_endpoint_v1_repond_sur_le_host_api(): void
    {
        // Requete trop courte : la reponse est une liste vide, sans toucher a la base.
        $this->getJson('https://api.allotata.test/v1/search/autocomplete?q=a')
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_les_chemins_historiques_restent_valides(): void
    {
        // /api est partage : l'application continue de l'appeler depuis tous les hosts.
        foreach ([
            'https://allotata.test/api/search/autocomplete?q=a',
            'https://dash.allotata.test/api/search/autocomplete?q=a',
            'https://api.allotata.test/search/autocomplete?q=a',
        ] as $url) {
            $this->getJson($url)->assertOk();
        }
    }

    public function test_la_v1_reste_cantonnee_au_host_api(): void
    {
        // Sur un autre host, /v1 n'existe pas : ni page de garde, ni endpoint.
        $this->get('https://dash.allotata.test/v1')->assertNotFound();
        $this->get('https://admin.allotata.test/v1/search/autocomplete')->assertNotFound();

        // /api reste un chemin partage : l'apex le sert sans rediriger, pour que les
        // appels internes de l'application restent same-origin. Seule l'URL annoncee
        // est canonique.
        config(['subdomains.legacy_redirect' => true]);
        $this->getJson('https://allotata.test/api/v1')
            ->assertOk()
            ->assertJsonPath('base_url', 'https://api.allotata.test/v1');
    }
}
