<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Entreprise;
use App\Models\Facture;
use App\Models\FcmToken;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\EmailVerificationNotification;
use App\Services\Facturation\PdfDocumentRenderer;
use App\Services\ReservationStatusService;
use App\Support\CapacitorClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PocketOfflineTest extends TestCase
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
        Mail::fake();
    }

    public function test_device_token_refuse_hors_apk(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('https://dash.allotata.test/native/device-token')
            ->assertForbidden()
            ->assertJsonPath('code', 'hors_application');
    }

    public function test_device_token_et_handoff_capacitor(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withHeaders(['X-Capacitor' => '1'])
            ->postJson('https://dash.allotata.test/native/device-token')
            ->assertOk()
            ->assertJsonStructure(['jeton']);

        $html = $this->actingAs($user)
            ->withHeaders(['X-Capacitor' => '1'])
            ->get('https://dash.allotata.test/native/handoff')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('allotata://handoff#token=', $html);
    }

    public function test_handoff_web_renvoie_au_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('https://dash.allotata.test/native/handoff')
            ->assertRedirect(route('dashboard'));
    }

    public function test_sync_et_mes_reservations(): void
    {
        $gerant = User::factory()->create(['est_gerant' => true]);
        $client = User::factory()->create(['est_client' => true]);
        $entreprise = Entreprise::factory()->create(['user_id' => $gerant->id, 'slug' => 'atelier']);
        Reservation::factory()->create([
            'entreprise_id' => $entreprise->id,
            'user_id' => $client->id,
            'statut' => 'en_attente',
            'date_reservation' => now()->addDay()->setTime(10, 0),
        ]);

        $jetonGerant = ApiToken::creerPour($gerant, 'Pocket')['jeton'];
        $this->getJson('https://api.allotata.test/v1/sync', [
            'Authorization' => 'Bearer '.$jetonGerant,
        ])->assertOk()
            ->assertJsonPath('compte.est_gerant', true)
            ->assertJsonPath('entreprises.0.slug', 'atelier');

        $jetonClient = ApiToken::creerPour($client, 'Pocket')['jeton'];
        $this->getJson('https://api.allotata.test/v1/mes-reservations', [
            'Authorization' => 'Bearer '.$jetonClient,
        ])->assertOk()
            ->assertJsonPath('pagination.total', 1);
    }

    public function test_accepter_est_idempotent(): void
    {
        $gerant = User::factory()->create(['est_gerant' => true]);
        $entreprise = Entreprise::factory()->create(['user_id' => $gerant->id, 'slug' => 'atelier']);
        $reservation = Reservation::factory()->create([
            'entreprise_id' => $entreprise->id,
            'user_id' => null,
            'email_client' => 'client@example.test',
            'statut' => 'en_attente',
            'date_reservation' => now()->addDay()->setTime(11, 0),
        ]);
        $jeton = ApiToken::creerPour($gerant, 'Pocket')['jeton'];

        $payload = ['idempotency_key' => 'abc-1', 'notes' => 'ok'];
        $this->postJson(
            'https://api.allotata.test/v1/entreprises/atelier/reservations/'.$reservation->id.'/accepter',
            $payload,
            ['Authorization' => 'Bearer '.$jeton]
        )->assertOk()->assertJsonPath('statut', 'confirmee');

        $this->postJson(
            'https://api.allotata.test/v1/entreprises/atelier/reservations/'.$reservation->id.'/accepter',
            $payload,
            ['Authorization' => 'Bearer '.$jeton]
        )->assertOk()->assertJsonPath('statut', 'confirmee');

        $this->assertSame('confirmee', $reservation->fresh()->statut);
        $this->assertDatabaseCount('api_idempotency', 1);
    }

    public function test_service_refuse_si_plus_en_attente(): void
    {
        $gerant = User::factory()->create(['est_gerant' => true]);
        $entreprise = Entreprise::factory()->create(['user_id' => $gerant->id]);
        $reservation = Reservation::factory()->create([
            'entreprise_id' => $entreprise->id,
            'statut' => 'confirmee',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        app(ReservationStatusService::class)->accepter($reservation, $gerant);
    }

    public function test_pdf_facture_avec_bearer(): void
    {
        $gerant = User::factory()->create(['est_gerant' => true]);
        $entreprise = Entreprise::factory()->create(['user_id' => $gerant->id]);
        $reservation = Reservation::factory()->create([
            'entreprise_id' => $entreprise->id,
            'user_id' => $gerant->id,
        ]);
        $facture = Facture::create([
            'reservation_id' => $reservation->id,
            'entreprise_id' => $entreprise->id,
            'user_id' => $gerant->id,
            'type_facture' => 'reservation',
            'numero_facture' => 'F-TEST-1',
            'date_facture' => now(),
            'montant_ht' => 10,
            'taux_tva' => 0,
            'montant_tva' => 0,
            'montant_ttc' => 10,
            'statut' => 'payee',
        ]);
        $jeton = ApiToken::creerPour($gerant, 'Pocket')['jeton'];

        $this->mock(PdfDocumentRenderer::class, function ($mock) {
            $mock->shouldReceive('facturePdf')->andReturn(new class
            {
                public function download(string $name)
                {
                    return response('pdf', 200, ['Content-Type' => 'application/pdf']);
                }
            });
        });

        $this->get('https://api.allotata.test/v1/factures/'.$facture->id.'/pdf', [
            'Authorization' => 'Bearer '.$jeton,
        ])->assertOk();
    }

    public function test_refuser_est_idempotent(): void
    {
        $gerant = User::factory()->create(['est_gerant' => true]);
        $entreprise = Entreprise::factory()->create(['user_id' => $gerant->id, 'slug' => 'atelier']);
        $reservation = Reservation::factory()->create([
            'entreprise_id' => $entreprise->id,
            'user_id' => null,
            'email_client' => 'client@example.test',
            'statut' => 'en_attente',
            'date_reservation' => now()->addDay()->setTime(11, 0),
        ]);
        $jeton = ApiToken::creerPour($gerant, 'Pocket')['jeton'];

        $payload = ['idempotency_key' => 'ref-1', 'notes' => 'complet'];
        $this->postJson(
            'https://api.allotata.test/v1/entreprises/atelier/reservations/'.$reservation->id.'/refuser',
            $payload,
            ['Authorization' => 'Bearer '.$jeton]
        )->assertOk()->assertJsonPath('statut', 'annulee');

        $this->postJson(
            'https://api.allotata.test/v1/entreprises/atelier/reservations/'.$reservation->id.'/refuser',
            $payload,
            ['Authorization' => 'Bearer '.$jeton]
        )->assertOk()->assertJsonPath('statut', 'annulee');

        $this->assertSame('annulee', $reservation->fresh()->statut);
        $this->assertDatabaseCount('api_idempotency', 1);
    }

    public function test_service_accepte_une_reservation_en_attente(): void
    {
        $gerant = User::factory()->create(['est_gerant' => true]);
        $entreprise = Entreprise::factory()->create(['user_id' => $gerant->id]);
        $reservation = Reservation::factory()->create([
            'entreprise_id' => $entreprise->id,
            'user_id' => null,
            'email_client' => 'client@example.test',
            'statut' => 'en_attente',
        ]);

        $miseAJour = app(ReservationStatusService::class)->accepter($reservation, $gerant, 'ok');

        $this->assertSame('confirmee', $miseAJour->statut);
        $this->assertStringContainsString('[Note de la tata] ok', (string) $miseAJour->notes);
    }

    public function test_fcm_enregistre_le_token(): void
    {
        $user = User::factory()->create();
        $jeton = ApiToken::creerPour($user, 'Pocket')['jeton'];

        $this->postJson('https://api.allotata.test/v1/device/fcm', [
            'token' => 'fcm-test-token',
            'device' => 'android',
        ], ['Authorization' => 'Bearer '.$jeton])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $user->id,
            'token' => 'fcm-test-token',
            'device' => 'android',
        ]);
        $this->assertSame(1, FcmToken::where('user_id', $user->id)->count());
    }

    public function test_login_capacitor_redirige_vers_handoff(): void
    {
        $request = Request::create('https://sign.allotata.test/signin', 'POST');
        $request->headers->set('X-Capacitor', '1');

        $redirect = CapacitorClient::afterLoginRedirect($request);

        $this->assertNotNull($redirect);
        $this->assertSame(route('native.handoff'), $redirect->getTargetUrl());
    }

    public function test_auth_login_refuse_hors_apk(): void
    {
        $user = User::factory()->create();

        $this->postJson('https://api.allotata.test/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertForbidden()->assertJsonPath('code', 'hors_application');
    }

    public function test_auth_login_pocket_rend_un_jeton(): void
    {
        $user = User::factory()->create();

        $this->withHeaders(['X-Capacitor' => '1'])
            ->postJson('https://api.allotata.test/v1/auth/login', [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure(['jeton', 'compte']);
    }

    public function test_auth_login_mauvais_mot_de_passe(): void
    {
        $user = User::factory()->create();

        $this->withHeaders(['X-Capacitor' => '1'])
            ->postJson('https://api.allotata.test/v1/auth/login', [
                'email' => $user->email,
                'password' => 'mauvais',
            ])
            ->assertUnauthorized()
            ->assertJsonPath('code', 'identifiants');
    }

    public function test_auth_register_refuse_hors_apk(): void
    {
        $this->postJson('https://api.allotata.test/v1/auth/register', $this->payloadInscription())
            ->assertForbidden()
            ->assertJsonPath('code', 'hors_application');
    }

    public function test_auth_register_cree_un_membre_sans_jeton(): void
    {
        Notification::fake();

        $payload = $this->payloadInscription();

        $this->withHeaders(['X-Capacitor' => '1'])
            ->postJson('https://api.allotata.test/v1/auth/register', $payload)
            ->assertCreated()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('email', $payload['email'])
            ->assertJsonMissing(['jeton']);

        $user = User::where('email', $payload['email'])->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->est_client);
        $this->assertFalse($user->est_gerant);
        $this->assertNull($user->email_verified_at);
        $this->assertDatabaseCount('api_tokens', 0);

        Notification::assertSentTo($user, EmailVerificationNotification::class);

        $this->withHeaders(['X-Capacitor' => '1'])
            ->postJson('https://api.allotata.test/v1/auth/login', [
                'email' => $payload['email'],
                'password' => $payload['password'],
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'email_non_verifie');
    }

    public function test_auth_register_valide_comme_le_wizard(): void
    {
        $this->withHeaders(['X-Capacitor' => '1'])
            ->postJson('https://api.allotata.test/v1/auth/register', [
                'email' => 'incomplet@example.test',
            ])
            ->assertUnprocessable();

        User::factory()->create(['email' => 'pris@example.test']);

        $this->withHeaders(['X-Capacitor' => '1'])
            ->postJson('https://api.allotata.test/v1/auth/register', $this->payloadInscription([
                'email' => 'pris@example.test',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->withHeaders(['X-Capacitor' => '1'])
            ->postJson('https://api.allotata.test/v1/auth/register', $this->payloadInscription([
                'cgu_accepted' => false,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cgu_accepted']);
    }

    /**
     * @param  array<string, mixed>  $remplace
     * @return array<string, mixed>
     */
    private function payloadInscription(array $remplace = []): array
    {
        return array_merge([
            'name' => 'Marie',
            'surname' => 'Dupont',
            'email' => 'marie.dupont@example.test',
            'password' => 'password1',
            'password_confirmation' => 'password1',
            'date_naissance' => '1990-05-12',
            'telephone' => '0612345678',
            'adresse' => '10 rue de la Paix',
            'ville' => 'Paris',
            'code_postal' => '75002',
            'cgu_accepted' => true,
            'cgv_accepted' => true,
            'confidentialite_accepted' => true,
        ], $remplace);
    }
}
