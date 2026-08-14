<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ecran Reglages -> API & jetons : creation, affichage unique, revocation.
 */
class ApiTokenSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $gerante;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'subdomains.enabled' => true,
            'subdomains.base_domain' => 'allotata.test',
            'app.url' => 'https://allotata.test',
        ]);

        $this->gerante = User::factory()->create([
            'est_gerant' => true,
            'email_verified_at' => now(),
        ]);

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckTrustedDevice::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);
    }

    public function test_l_ecran_liste_les_jetons_du_compte_seulement(): void
    {
        ApiToken::creerPour($this->gerante, 'Mon tableau de bord');
        ApiToken::creerPour(User::factory()->create(), 'Jeton d\'un autre');

        $this->actingAs($this->gerante)
            ->get('https://dash.allotata.test/settings/api')
            ->assertOk()
            ->assertSee('Mon tableau de bord')
            ->assertDontSee('Jeton d\'un autre');
    }

    public function test_la_creation_affiche_le_jeton_une_seule_fois(): void
    {
        $reponse = $this->actingAs($this->gerante)
            ->post('https://dash.allotata.test/settings/api', [
                'nom' => 'Intégration compta',
                'expiration_jours' => 30,
            ]);

        $reponse->assertRedirect()->assertSessionHas('jeton_cree');

        $clair = session('jeton_cree');
        $this->assertStringStartsWith(ApiToken::PREFIXE, $clair);

        $jeton = ApiToken::first();
        $this->assertSame('Intégration compta', $jeton->nom);
        $this->assertSame(hash('sha256', $clair), $jeton->token_hash);
        $this->assertTrue($jeton->expire_at->isFuture());

        // Au retour de la redirection, le jeton s'affiche : c'est sa seule occasion.
        $this->actingAs($this->gerante)
            ->get('https://dash.allotata.test/settings/api')
            ->assertOk()
            ->assertSee($clair, false);

        // A la visite suivante, il a disparu : la base n'en a que l'empreinte.
        $this->actingAs($this->gerante)
            ->get('https://dash.allotata.test/settings/api')
            ->assertOk()
            ->assertDontSee($clair);
    }

    public function test_un_nom_est_exige(): void
    {
        $this->actingAs($this->gerante)
            ->post('https://dash.allotata.test/settings/api', ['nom' => ''])
            ->assertSessionHasErrors('nom');

        $this->assertSame(0, ApiToken::count());
    }

    public function test_la_revocation_coupe_l_acces_et_respecte_le_proprietaire(): void
    {
        $mien = ApiToken::creerPour($this->gerante, 'A révoquer');
        $autre = ApiToken::creerPour(User::factory()->create(), 'Pas le mien');

        $this->actingAs($this->gerante)
            ->delete('https://dash.allotata.test/settings/api/'.$mien['modele']->id)
            ->assertRedirect();

        $this->assertSame(0, ApiToken::where('id', $mien['modele']->id)->count());

        // Le jeton d'un autre compte reste hors de portee.
        $this->actingAs($this->gerante)
            ->delete('https://dash.allotata.test/settings/api/'.$autre['modele']->id)
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(1, ApiToken::where('id', $autre['modele']->id)->count());

        // Et l'API refuse desormais le jeton revoque.
        $this->getJson('https://api.allotata.test/v1/moi', ['Authorization' => 'Bearer '.$mien['jeton']])
            ->assertUnauthorized();
    }

    public function test_les_reglages_donnent_acces_a_l_ecran(): void
    {
        $this->actingAs($this->gerante)
            ->get('https://dash.allotata.test/settings')
            ->assertOk()
            ->assertSee('API &amp; jetons', false)
            ->assertSee('https://dash.allotata.test/settings/api', false);
    }
}
