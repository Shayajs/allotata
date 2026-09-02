<?php

namespace Tests\Feature;

use App\Models\Entreprise;
use App\Models\EntrepriseModification;
use App\Models\User;
use App\Services\EntrepriseModificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntrepriseVisibilityAndModerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    public function test_page_publique_exige_premium_et_validation(): void
    {
        $gerant = $this->gerantPremium();
        $entreprise = Entreprise::factory()->create([
            'user_id' => $gerant->id,
            'est_verifiee' => false,
        ]);

        $this->get(route('public.entreprise', $entreprise->slug))->assertNotFound();

        $entreprise->update(['est_verifiee' => true]);
        \App\Services\CacheService::clearEntrepriseCache($entreprise->id, $entreprise->slug);

        $this->get(route('public.entreprise', $entreprise->slug))->assertOk();
    }

    public function test_page_publique_masquee_sans_premium_meme_si_validee(): void
    {
        $gerant = User::factory()->create(['est_gerant' => true]);
        $entreprise = Entreprise::factory()->verified()->create(['user_id' => $gerant->id]);

        $this->get(route('public.entreprise', $entreprise->slug))->assertNotFound();
    }

    public function test_proprietaire_et_admin_voient_une_fiche_non_publique(): void
    {
        $gerant = User::factory()->create(['est_gerant' => true]);
        $admin = User::factory()->create(['is_admin' => true]);
        $entreprise = Entreprise::factory()->create([
            'user_id' => $gerant->id,
            'est_verifiee' => false,
        ]);

        $this->actingAs($gerant)
            ->get(route('public.entreprise', $entreprise->slug))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('public.entreprise', $entreprise->slug))
            ->assertOk();
    }

    public function test_recherche_et_autocomplete_ignorent_les_non_validees(): void
    {
        $gerant = $this->gerantPremium();
        $hidden = Entreprise::factory()->create([
            'user_id' => $gerant->id,
            'nom' => 'Salon HiddenTest',
            'est_verifiee' => false,
        ]);
        $visible = Entreprise::factory()->verified()->create([
            'user_id' => $gerant->id,
            'nom' => 'Salon VisibleTest',
        ]);

        $this->get(route('search', ['q' => 'Salon']))
            ->assertOk()
            ->assertSee('Salon VisibleTest')
            ->assertDontSee('Salon HiddenTest');

        $this->getJson(route('search.autocomplete', ['q' => 'Salon']))
            ->assertOk()
            ->assertJsonFragment(['nom' => 'Salon VisibleTest'])
            ->assertJsonMissing(['nom' => 'Salon HiddenTest']);

        $this->assertFalse($hidden->estVisiblePubliquement());
        $this->assertTrue($visible->fresh()->estVisiblePubliquement());
    }

    public function test_nom_adresse_et_description_s_appliquent_tout_de_suite(): void
    {
        $gerant = $this->gerantPremium();
        $entreprise = Entreprise::factory()->verified()->create([
            'user_id' => $gerant->id,
            'nom' => 'Ancien Nom',
            'description' => 'Desc live',
            'ville' => 'Paris',
            'adresse_rue' => '1 rue de la Paix',
            'code_postal' => '75001',
        ]);

        $this->actingAs($gerant)
            ->post(route('settings.entreprise.update', $entreprise->slug), $this->profilePayload($entreprise, [
                'nom' => 'Nouveau Nom Public',
                'description' => 'Desc mise a jour',
                'ville' => 'Lyon',
                'adresse_rue' => '12 rue des Fleurs',
                'code_postal' => '69001',
            ]))
            ->assertRedirect();

        $entreprise->refresh();
        $this->assertSame('Nouveau Nom Public', $entreprise->nom);
        $this->assertSame('Desc mise a jour', $entreprise->description);
        $this->assertSame('Lyon', $entreprise->ville);
        $this->assertSame('12 rue des Fleurs', $entreprise->adresse_rue);
        $this->assertSame('69001', $entreprise->code_postal);
        $this->assertTrue($entreprise->est_verifiee);
        $this->assertNull($entreprise->modificationEnAttente);
    }

    public function test_siren_et_video_restent_en_file_d_attente(): void
    {
        $gerant = $this->gerantPremium();
        $entreprise = Entreprise::factory()->verified()->create([
            'user_id' => $gerant->id,
            'nom' => 'Ancien Nom',
            'siren' => '732829320',
            'video_url' => null,
        ]);

        $this->actingAs($gerant)
            ->post(route('settings.entreprise.update', $entreprise->slug), $this->profilePayload($entreprise, [
                'nom' => 'Nouveau Nom Public',
                'siren' => '123456789',
                'siret' => '',
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ]))
            ->assertRedirect();

        $entreprise->refresh();
        $this->assertSame('Nouveau Nom Public', $entreprise->nom);
        $this->assertSame('732829320', $entreprise->siren);
        $this->assertNull($entreprise->video_url);
        $this->assertTrue($entreprise->est_verifiee);

        $pending = $entreprise->modificationEnAttente;
        $this->assertNotNull($pending);
        $this->assertSame('123456789', $pending->fields()['siren']);
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $pending->fields()['video_url']);
        $this->assertArrayNotHasKey('nom', $pending->fields());
    }

    public function test_champs_exploitation_s_appliquent_tout_de_suite(): void
    {
        $gerant = $this->gerantPremium();
        $entreprise = Entreprise::factory()->verified()->create([
            'user_id' => $gerant->id,
            'nom' => 'Stable Nom',
            'intervalle_creneaux_minutes' => 30,
        ]);

        $this->actingAs($gerant)
            ->post(route('settings.entreprise.update', $entreprise->slug), $this->profilePayload($entreprise, [
                'intervalle_creneaux_minutes' => 45,
            ]))
            ->assertRedirect();

        $entreprise->refresh();
        $this->assertSame('Stable Nom', $entreprise->nom);
        $this->assertSame(45, $entreprise->intervalle_creneaux_minutes);
        $this->assertNull($entreprise->modificationEnAttente);
    }

    public function test_admin_confirme_la_modification_sans_retirer_la_validation(): void
    {
        $gerant = $this->gerantPremium();
        $admin = User::factory()->create(['is_admin' => true]);
        $entreprise = Entreprise::factory()->verified()->create([
            'user_id' => $gerant->id,
            'siren' => '732829320',
        ]);

        $this->actingAs($gerant)
            ->post(route('settings.entreprise.update', $entreprise->slug), $this->profilePayload($entreprise, [
                'siren' => '123456789',
                'siret' => '',
            ]));

        $modification = EntrepriseModification::pending()->first();
        $this->assertNotNull($modification);

        $this->actingAs($admin)
            ->post(route('admin.entreprises.modifications.approve', $modification))
            ->assertRedirect();

        $entreprise->refresh();
        $this->assertSame('123456789', $entreprise->siren);
        $this->assertTrue($entreprise->est_verifiee);
        $this->assertTrue($entreprise->siren_valide);
        $this->assertSame(EntrepriseModification::STATUT_APPROVED, $modification->fresh()->statut);
    }

    public function test_refus_conserve_la_fiche_live(): void
    {
        $gerant = $this->gerantPremium();
        $admin = User::factory()->create(['is_admin' => true]);
        $entreprise = Entreprise::factory()->verified()->create([
            'user_id' => $gerant->id,
            'siren' => '732829320',
        ]);

        $this->actingAs($gerant)
            ->post(route('settings.entreprise.update', $entreprise->slug), $this->profilePayload($entreprise, [
                'siren' => '111111111',
                'siret' => '',
            ]));

        $modification = EntrepriseModification::pending()->first();

        $this->actingAs($admin)
            ->post(route('admin.entreprises.modifications.reject', $modification), [
                'motif_refus' => 'SIREN incorrect',
            ])
            ->assertRedirect();

        $entreprise->refresh();
        $this->assertSame('732829320', $entreprise->siren);
        $this->assertTrue($entreprise->est_verifiee);
        $this->assertSame(EntrepriseModification::STATUT_REJECTED, $modification->fresh()->statut);
    }

    public function test_edition_non_validee_s_applique_immediatement(): void
    {
        $gerant = $this->gerantPremium();
        $entreprise = Entreprise::factory()->create([
            'user_id' => $gerant->id,
            'est_verifiee' => false,
            'nom' => 'Brouillon',
        ]);

        $this->actingAs($gerant)
            ->post(route('settings.entreprise.update', $entreprise->slug), $this->profilePayload($entreprise, [
                'nom' => 'Brouillon Modifie',
            ]))
            ->assertRedirect();

        $this->assertSame('Brouillon Modifie', $entreprise->fresh()->nom);
        $this->assertNull($entreprise->fresh()->modificationEnAttente);
    }

    public function test_edition_admin_s_applique_immediatement(): void
    {
        $gerant = $this->gerantPremium();
        $admin = User::factory()->create(['is_admin' => true]);
        $entreprise = Entreprise::factory()->verified()->create([
            'user_id' => $gerant->id,
            'nom' => 'Chez Admin',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.entreprises.update', $entreprise), $this->profilePayload($entreprise, [
                'nom' => 'Chez Admin Corrige',
            ]))
            ->assertRedirect();

        $this->assertSame('Chez Admin Corrige', $entreprise->fresh()->nom);
        $this->assertNull($entreprise->fresh()->modificationEnAttente);
        $this->assertTrue($entreprise->fresh()->est_verifiee);
    }

    public function test_inbox_admin_liste_les_notifications(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $gerant = $this->gerantPremium();
        Entreprise::factory()->create(['user_id' => $gerant->id, 'est_verifiee' => false]);

        $this->actingAs($admin)
            ->get(route('admin.inbox.index'))
            ->assertOk()
            ->assertSee('Entreprise à valider');
    }

    public function test_service_should_queue_uniquement_si_deja_validee(): void
    {
        $service = app(EntrepriseModificationService::class);
        $live = Entreprise::factory()->verified()->create();
        $draft = Entreprise::factory()->create(['est_verifiee' => false]);

        $this->assertTrue($service->shouldQueue($live));
        $this->assertFalse($service->shouldQueue($live, true));
        $this->assertFalse($service->shouldQueue($draft));
        $this->assertNotContains('nom', EntrepriseModificationService::MODERATED_FIELDS);
        $this->assertNotContains('adresse_rue', EntrepriseModificationService::MODERATED_FIELDS);
        $this->assertContains('siren', EntrepriseModificationService::MODERATED_FIELDS);
        $this->assertContains('video_url', EntrepriseModificationService::MODERATED_FIELDS);
    }

    private function gerantPremium(): User
    {
        return User::factory()->create([
            'est_gerant' => true,
            'abonnement_manuel' => true,
            'abonnement_manuel_actif_jusqu' => now()->addMonth(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function profilePayload(Entreprise $entreprise, array $overrides = []): array
    {
        return array_merge([
            'nom' => $entreprise->nom,
            'type_activite' => $entreprise->type_activite,
            'email' => $entreprise->email,
            'telephone' => $entreprise->telephone,
            'description' => $entreprise->description,
            'type_localisation' => $entreprise->type_localisation ?: Entreprise::LOCALISATION_PHYSIQUE,
            'ville' => $entreprise->ville,
            'adresse_rue' => $entreprise->adresse_rue,
            'code_postal' => $entreprise->code_postal,
            'siren' => $entreprise->siren,
            'siret' => $entreprise->siret,
            'intervalle_creneaux_minutes' => $entreprise->intervalle_creneaux_minutes ?? 30,
        ], $overrides);
    }
}
