<?php

namespace Tests\Feature;

use App\Models\Entreprise;
use App\Models\User;
use App\Services\VisitorLocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SearchGeolocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_accueil_propose_d_utiliser_la_position(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Utiliser ma position')
            ->assertSee('data-search-geo-form', false);
    }

    public function test_position_navigateur_remplace_paris_en_session(): void
    {
        $this->session([
            'visitor_location_v2' => [
                'latitude' => 48.8566,
                'longitude' => 2.3522,
                'city' => 'Paris',
                'source' => 'default',
            ],
        ]);

        $request = Request::create('/search', 'GET', [
            'user_lat' => '43.6047',
            'user_lng' => '1.4442',
        ]);

        $location = app(VisitorLocationService::class)->resolve($request);

        $this->assertSame('browser', $location['source']);
        $this->assertEqualsWithDelta(43.6047, $location['latitude'], 0.0001);
        $this->assertEqualsWithDelta(1.4442, $location['longitude'], 0.0001);
        $this->assertNull($location['city']);
    }

    public function test_forget_geo_ignore_le_gps_et_revient_a_paris(): void
    {
        $this->session([
            'visitor_location_v2' => [
                'latitude' => 43.6047,
                'longitude' => 1.4442,
                'city' => null,
                'source' => 'browser',
            ],
        ]);

        $request = Request::create('/search', 'GET', [
            'forget_geo' => '1',
            'user_lat' => '43.6047',
            'user_lng' => '1.4442',
        ]);

        $location = app(VisitorLocationService::class)->resolve($request);

        $this->assertSame('default', $location['source']);
        $this->assertSame('Paris', $location['city']);
    }

    public function test_coords_invalides_sont_ignorees(): void
    {
        $request = Request::create('/search', 'GET', [
            'user_lat' => '999',
            'user_lng' => '1',
        ]);

        $location = app(VisitorLocationService::class)->resolve($request);

        $this->assertSame('default', $location['source']);
    }

    public function test_recherche_vide_avec_gps_affiche_pres_de_vous_et_trie_par_distance(): void
    {
        $gerant = User::factory()->create([
            'est_gerant' => true,
            'abonnement_manuel' => true,
            'abonnement_manuel_actif_jusqu' => now()->addMonth(),
        ]);

        Entreprise::factory()->verified()->create([
            'user_id' => $gerant->id,
            'nom' => 'Salon Toulouse GPS',
            'ville' => 'Toulouse',
            'latitude' => 43.6047,
            'longitude' => 1.4442,
            'type_localisation' => Entreprise::LOCALISATION_PHYSIQUE,
        ]);

        Entreprise::factory()->verified()->create([
            'user_id' => $gerant->id,
            'nom' => 'Salon Lille GPS',
            'ville' => 'Lille',
            'latitude' => 50.6292,
            'longitude' => 3.0573,
            'type_localisation' => Entreprise::LOCALISATION_PHYSIQUE,
        ]);

        $html = $this->get(route('search', [
            'user_lat' => '43.6045',
            'user_lng' => '1.4440',
        ]))
            ->assertOk()
            ->assertSee('près de vous')
            ->assertSee('Position activée')
            ->assertSee('Salon Toulouse GPS')
            ->assertSee('Salon Lille GPS')
            ->getContent();

        $this->assertLessThan(
            strpos($html, 'Salon Lille GPS'),
            strpos($html, 'Salon Toulouse GPS'),
        );
    }
}
