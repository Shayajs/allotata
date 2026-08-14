<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Entreprise;
use App\Models\EntrepriseFinance;
use App\Models\Reservation;
use App\Models\TypeService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * API de gestion (v1 authentifiee) : le jeton, le perimetre qu'il ouvre et ce
 * qu'il refuse.
 */
class ApiGestionTest extends TestCase
{
    use RefreshDatabase;

    private User $gerante;

    private Entreprise $entreprise;

    private string $jeton;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'subdomains.enabled' => true,
            'subdomains.base_domain' => 'allotata.test',
            'app.url' => 'https://allotata.test',
        ]);

        $this->gerante = User::factory()->create(['est_gerant' => true]);
        $this->entreprise = Entreprise::factory()->create([
            'user_id' => $this->gerante->id,
            'slug' => 'salon-lumiere',
        ]);

        $this->jeton = ApiToken::creerPour($this->gerante, 'Tests')['jeton'];
    }

    private function avecJeton(string $chemin, ?string $jeton = null): TestResponse
    {
        return $this->getJson('https://api.allotata.test/v1'.$chemin, [
            'Authorization' => 'Bearer '.($jeton ?? $this->jeton),
        ]);
    }

    public function test_sans_jeton_la_gestion_refuse_et_le_dit(): void
    {
        $this->getJson('https://api.allotata.test/v1/moi')
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertJsonPath('code', 'jeton_absent');
    }

    public function test_un_jeton_inconnu_revoque_ou_expire_est_refuse(): void
    {
        $this->avecJeton('/moi', 'alto_'.str_repeat('x', 48))
            ->assertUnauthorized()
            ->assertJsonPath('code', 'jeton_invalide');

        $expire = ApiToken::creerPour($this->gerante, 'Perime', now()->subDay());
        $this->avecJeton('/moi', $expire['jeton'])
            ->assertUnauthorized()
            ->assertJsonPath('code', 'jeton_invalide');

        $revoque = ApiToken::creerPour($this->gerante, 'Revoque');
        $revoque['modele']->delete();
        $this->avecJeton('/moi', $revoque['jeton'])
            ->assertUnauthorized()
            ->assertJsonPath('code', 'jeton_invalide');
    }

    public function test_seule_l_empreinte_du_jeton_est_stockee(): void
    {
        $this->assertDatabaseMissing('api_tokens', ['token_hash' => $this->jeton]);
        $this->assertDatabaseHas('api_tokens', ['token_hash' => hash('sha256', $this->jeton)]);
    }

    public function test_moi_decrit_le_compte_et_son_perimetre(): void
    {
        $reponse = $this->avecJeton('/moi')->assertOk();

        $reponse->assertJsonPath('compte.email', $this->gerante->email)
            ->assertJsonPath('compte.est_gerant', true)
            ->assertJsonPath('entreprises.0.slug', 'salon-lumiere')
            ->assertJsonPath('entreprises.0.role', 'proprietaire')
            ->assertJsonPath('jeton.nom', 'Tests');

        // L'appel horodate le jeton : c'est ce qui permet de reperer les oublies.
        $this->assertNotNull(ApiToken::first()->fresh()->derniere_utilisation_at);
    }

    public function test_l_usage_du_jeton_ouvre_la_fiche_et_le_catalogue(): void
    {
        TypeService::factory()->create([
            'entreprise_id' => $this->entreprise->id,
            'nom' => 'Coupe femme',
            'est_actif' => true,
        ]);

        $this->avecJeton('/entreprises')
            ->assertOk()
            ->assertJsonPath('donnees.0.slug', 'salon-lumiere')
            ->assertJsonPath('pagination.total', 1);

        $this->avecJeton('/entreprises/salon-lumiere')
            ->assertOk()
            ->assertJsonPath('slug', 'salon-lumiere')
            ->assertJsonPath('compteurs.services_actifs', 1);

        $this->avecJeton('/entreprises/salon-lumiere/services?actifs=1')
            ->assertOk()
            ->assertJsonPath('donnees.0.nom', 'Coupe femme');
    }

    public function test_les_reservations_se_filtrent_par_statut_et_par_date(): void
    {
        Reservation::factory()->create([
            'entreprise_id' => $this->entreprise->id,
            'statut' => 'confirmee',
            'date_reservation' => now()->subDays(2)->setTime(10, 0),
        ]);
        Reservation::factory()->create([
            'entreprise_id' => $this->entreprise->id,
            'statut' => 'annulee',
            'date_reservation' => now()->subDays(2)->setTime(14, 0),
        ]);

        $this->avecJeton('/entreprises/salon-lumiere/reservations')
            ->assertOk()
            ->assertJsonPath('pagination.total', 2);

        $confirmees = $this->avecJeton('/entreprises/salon-lumiere/reservations?statut=confirmee')->assertOk();
        $confirmees->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('donnees.0.statut', 'confirmee');

        // Hors periode : la liste est vide, pas une erreur.
        $this->avecJeton('/entreprises/salon-lumiere/reservations?du='.now()->addDay()->toDateString())
            ->assertOk()
            ->assertJsonPath('pagination.total', 0);
    }

    public function test_un_parametre_illisible_est_refuse_avec_son_code(): void
    {
        $this->avecJeton('/entreprises/salon-lumiere/reservations?statut=peut_etre')
            ->assertStatus(422)
            ->assertJsonPath('code', 'statut_invalide');

        $this->avecJeton('/entreprises/salon-lumiere/reservations?du=hier')
            ->assertStatus(422)
            ->assertJsonPath('code', 'date_invalide');

        $this->avecJeton('/entreprises/salon-lumiere/finances?type=cadeau')
            ->assertStatus(422)
            ->assertJsonPath('code', 'type_invalide');
    }

    public function test_le_jeton_ne_sort_pas_de_son_perimetre(): void
    {
        $autre = Entreprise::factory()->create(['slug' => 'chez-fatou']);

        $this->avecJeton('/entreprises/chez-fatou')
            ->assertForbidden()
            ->assertJsonPath('code', 'entreprise_hors_perimetre');

        $this->avecJeton('/entreprises/inexistante')
            ->assertNotFound()
            ->assertJsonPath('code', 'entreprise_inconnue');

        // Une reservation voisine n'est pas visible depuis ma propre entreprise.
        $ailleurs = Reservation::factory()->create(['entreprise_id' => $autre->id]);

        $this->avecJeton('/entreprises/salon-lumiere/reservations/'.$ailleurs->id)
            ->assertNotFound()
            ->assertJsonPath('code', 'reservation_inconnue');
    }

    public function test_les_statistiques_et_les_finances_couvrent_une_periode(): void
    {
        Reservation::factory()->create([
            'entreprise_id' => $this->entreprise->id,
            'statut' => 'terminee',
            'est_paye' => true,
            'prix' => 45,
            'date_reservation' => now()->subDays(3),
        ]);
        Reservation::factory()->create([
            'entreprise_id' => $this->entreprise->id,
            'statut' => 'confirmee',
            'est_paye' => false,
            'prix' => 30,
            'date_reservation' => now()->subDays(3),
        ]);

        $statistiques = $this->avecJeton('/entreprises/salon-lumiere/statistiques')
            ->assertOk()
            ->assertJsonPath('reservations.total', 2)
            ->assertJsonPath('reservations.terminees', 1);

        $this->assertEquals(45, $statistiques->json('chiffre_affaires.encaisse'));
        $this->assertEquals(30, $statistiques->json('chiffre_affaires.a_encaisser'));

        EntrepriseFinance::create([
            'entreprise_id' => $this->entreprise->id,
            'type' => 'expense',
            'category' => 'Fournitures',
            'amount' => 20,
            'date_record' => now()->subDay(),
        ]);

        $finances = $this->avecJeton('/entreprises/salon-lumiere/finances')->assertOk();

        $this->assertEquals(20, $finances->json('totaux.depenses'));
        $this->assertEquals(-20, $finances->json('totaux.solde'));
    }

    public function test_la_clientele_se_reconstitue_depuis_les_reservations(): void
    {
        $cliente = User::factory()->create(['name' => 'Awa', 'est_client' => true]);

        Reservation::factory()->count(2)->create([
            'entreprise_id' => $this->entreprise->id,
            'user_id' => $cliente->id,
            'est_paye' => true,
            'prix' => 25,
        ]);
        Reservation::factory()->create([
            'entreprise_id' => $this->entreprise->id,
            'user_id' => null,
            'nom_client' => 'Invitee',
            'email_client' => 'invitee@example.fr',
        ]);

        $reponse = $this->avecJeton('/entreprises/salon-lumiere/clients')->assertOk();

        $this->assertSame(2, $reponse->json('total_clients'));

        $awa = collect($reponse->json('donnees'))->firstWhere('nom', 'Awa');
        $this->assertSame(2, $awa['reservations']);
        $this->assertEquals(50, $awa['total_encaisse']);
        $this->assertTrue($awa['inscrit']);

        // Le filtre porte sur le nom comme sur l'email.
        $this->avecJeton('/entreprises/salon-lumiere/clients?q=invitee')
            ->assertOk()
            ->assertJsonPath('total_clients', 1);
    }

    public function test_les_disponibilites_repondent_meme_sans_horaires(): void
    {
        $this->avecJeton('/entreprises/salon-lumiere/disponibilites?date='.now()->addWeek()->toDateString())
            ->assertOk()
            ->assertJsonPath('ferme', true)
            ->assertJsonPath('creneaux', []);

        $this->avecJeton('/entreprises/salon-lumiere/disponibilites?service_id=999999')
            ->assertNotFound()
            ->assertJsonPath('code', 'service_inconnu');
    }

    public function test_la_gestion_reste_cantonnee_au_host_api(): void
    {
        // Ailleurs, /v1 n'existe pas : le jeton ne rattrape pas un mauvais host.
        $this->getJson('https://dash.allotata.test/v1/moi', ['Authorization' => 'Bearer '.$this->jeton])
            ->assertNotFound();

        // Mais /api reste partage : les appels internes gardent leur origine.
        $this->getJson('https://allotata.test/api/v1/moi', ['Authorization' => 'Bearer '.$this->jeton])
            ->assertOk()
            ->assertJsonPath('compte.email', $this->gerante->email);
    }

    public function test_l_index_public_annonce_la_gestion_et_ses_jetons(): void
    {
        $this->getJson('https://api.allotata.test/v1')
            ->assertOk()
            ->assertJsonPath('gestion.authentification', 'Authorization: Bearer <jeton>')
            ->assertJsonPath('gestion.jetons', 'https://dash.allotata.test/settings/api')
            ->assertJsonPath('gestion.endpoints.0.url', 'https://api.allotata.test/v1/moi');
    }
}
