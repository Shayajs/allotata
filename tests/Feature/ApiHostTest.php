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
        // Apex : toujours servi (legacy off en test), pour Google et les vieux clients.
        $this->getJson('https://allotata.test/api/search/autocomplete?q=a')->assertOk();

        // Sur un autre host, la v1 publique appartient a api.* : on y redirige.
        $this->get('https://dash.allotata.test/api/v1/search/autocomplete?q=a')
            ->assertRedirect('https://api.allotata.test/v1/search/autocomplete?q=a');

        // Sur api.*, les deux formes (courte et legacy /api/...) repondent.
        $this->getJson('https://api.allotata.test/search/autocomplete?q=a')->assertOk();
        $this->getJson('https://api.allotata.test/v1/search/autocomplete?q=a')
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_la_v1_reste_cantonnee_au_host_api(): void
    {
        // Sur un autre host, /v1 n'existe pas : ni page de garde, ni endpoint.
        $this->get('https://dash.allotata.test/v1')->assertNotFound();
        $this->get('https://admin.allotata.test/v1/search/autocomplete')->assertNotFound();

        // Avec les redirections implicites, l'apex renvoie vers le proprietaire.
        config(['subdomains.legacy_redirect' => true]);
        $this->get('https://allotata.test/api/v1')
            ->assertRedirect('https://api.allotata.test/v1');
    }

    public function test_les_apps_pointent_vers_la_base_api_propre(): void
    {
        // Catalogue public : pas de garde d'auth, le partial api-base y est injecte.
        $this->get('https://learn.allotata.test/')
            ->assertOk()
            ->assertSee('window.ALLOTATA_API = '.json_encode('https://api.allotata.test/v1'), false);

        $this->assertSame('https://api.allotata.test/v1', \App\Support\SubdomainHost::apiBaseUrl());
        $this->assertSame('https://api.allotata.test/v1', url('/api/v1'));
    }
}
