<?php

namespace Tests\Feature;

use App\Models\Entreprise;
use App\Models\EntrepriseSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EssaiGratuitUnlockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    public function test_essai_premium_debloque_l_abonnement(): void
    {
        $user = User::factory()->create(['est_gerant' => true]);

        $this->actingAs($user)
            ->post(route('essai-gratuit.utilisateur'))
            ->assertRedirect(route('dashboard'));

        $user->refresh();

        $this->assertTrue($user->aAccesViaEssai('premium'));
        $this->assertTrue($user->aAbonnementActif());
        $this->assertNotNull($user->trial_ends_at);
        $this->assertTrue($user->trial_ends_at->isFuture());
    }

    public function test_essai_site_web_debloque_l_option(): void
    {
        $user = User::factory()->create([
            'est_gerant' => true,
            'abonnement_manuel' => true,
            'abonnement_manuel_actif_jusqu' => now()->addMonth(),
        ]);
        $entreprise = Entreprise::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('essai-gratuit.entreprise', $entreprise->slug), ['type' => 'site_web'])
            ->assertRedirect(route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'abonnements']));

        $entreprise->refresh();

        $this->assertTrue($entreprise->aAccesViaEssai('site_web'));
        $this->assertTrue($entreprise->aSiteWebActif());
        $this->assertTrue($entreprise->abonnementSiteWeb()?->estEnEssai());
        $this->assertTrue($entreprise->abonnementSiteWeb()?->estActif());
    }

    public function test_essai_site_web_debloque_malgre_un_manuel_expire(): void
    {
        $user = User::factory()->create([
            'est_gerant' => true,
            'abonnement_manuel' => true,
            'abonnement_manuel_actif_jusqu' => now()->addMonth(),
        ]);
        $entreprise = Entreprise::factory()->create(['user_id' => $user->id]);

        EntrepriseSubscription::create([
            'entreprise_id' => $entreprise->id,
            'type' => 'site_web',
            'name' => 'manuel_site_web',
            'est_manuel' => true,
            'actif_jusqu' => now()->subDay(),
        ]);

        $this->assertFalse($entreprise->fresh()->aSiteWebActif());

        $this->actingAs($user)
            ->post(route('essai-gratuit.entreprise', $entreprise->slug), ['type' => 'site_web'])
            ->assertRedirect();

        $this->assertTrue($entreprise->fresh()->aSiteWebActif());
    }

    public function test_echeance_actif_jusqu_debloque_sans_stripe(): void
    {
        $user = User::factory()->create(['est_gerant' => true]);
        $entreprise = Entreprise::factory()->create(['user_id' => $user->id]);

        EntrepriseSubscription::create([
            'entreprise_id' => $entreprise->id,
            'type' => 'site_web',
            'name' => 'echeance_site_web',
            'est_manuel' => false,
            'stripe_id' => null,
            'stripe_status' => null,
            'actif_jusqu' => now()->addDays(20),
        ]);

        $this->assertTrue($entreprise->fresh()->aSiteWebActif());
    }

    public function test_un_second_essai_premium_est_refuse_meme_apres_un_an(): void
    {
        $user = User::factory()->create(['est_gerant' => true]);

        $user->essaisGratuits()->create([
            'type_abonnement' => 'premium',
            'date_debut' => now()->subYears(2),
            'date_fin' => now()->subYears(2)->addDays(7),
            'duree_jours' => 7,
            'statut' => 'expire',
        ]);

        $this->assertFalse($user->peutDemarrerEssai('premium'));

        $this->actingAs($user)
            ->from(route('settings.index', ['tab' => 'subscription']))
            ->post(route('essai-gratuit.utilisateur'))
            ->assertRedirect(route('settings.index', ['tab' => 'subscription']))
            ->assertSessionHas('error');

        $this->assertSame(1, $user->essaisGratuits()->count());
        $this->assertFalse($user->fresh()->aAccesViaEssai('premium'));
    }

    public function test_admin_peut_accorder_plusieurs_essais_sans_abonnement_payant(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['est_gerant' => true]);

        $user->essaisGratuits()->create([
            'type_abonnement' => 'premium',
            'date_debut' => now()->subMonth(),
            'date_fin' => now()->subWeeks(3),
            'duree_jours' => 7,
            'statut' => 'expire',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.essais-gratuits.index'))
            ->post(route('admin.essais-gratuits.accorder'), [
                'type_cible' => 'user',
                'cible_id' => $user->id,
                'type_abonnement' => 'premium',
                'duree_jours' => 120,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame(2, $user->essaisGratuits()->count());
        $this->assertTrue($user->aAccesViaEssai('premium'));
        $this->assertTrue($user->trial_ends_at->gt(now()->addDays(110)));
    }

    public function test_admin_ne_peut_pas_accorder_un_essai_sur_un_abonnement_payant(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create([
            'est_gerant' => true,
            'abonnement_manuel' => true,
            'abonnement_manuel_actif_jusqu' => now()->addMonth(),
        ]);
        $entreprise = Entreprise::factory()->create(['user_id' => $user->id]);

        EntrepriseSubscription::create([
            'entreprise_id' => $entreprise->id,
            'type' => 'site_web',
            'name' => 'site_web',
            'stripe_id' => 'sub_payant_test',
            'stripe_status' => 'active',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.essais-gratuits.index'))
            ->post(route('admin.essais-gratuits.accorder'), [
                'type_cible' => 'entreprise',
                'cible_id' => $entreprise->id,
                'type_abonnement' => 'site_web',
                'duree_jours' => 14,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, $entreprise->essaisGratuits()->count());
    }
}
