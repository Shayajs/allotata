<?php

namespace Tests\Feature;

use App\Mail\EssaiGratuitExpireMail;
use App\Models\Entreprise;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EssaiGratuitExpirationCronTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_cron_expire_l_essai_et_notifie_que_c_est_arrete(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'est_gerant' => true,
            'trial_ends_at' => now()->addDay(),
        ]);

        $essai = $user->essaisGratuits()->create([
            'type_abonnement' => 'premium',
            'date_debut' => now()->subDays(7),
            'date_fin' => now()->subMinute(),
            'duree_jours' => 7,
            'statut' => 'actif',
        ]);

        $this->artisan('essais:check-expiration')->assertSuccessful();

        $essai->refresh();
        $this->assertSame('expire', $essai->statut);
        $this->assertNotNull($essai->notification_expiration_envoye_le);
        $this->assertFalse($user->fresh()->peutDemarrerEssai('premium'));
        $this->assertTrue($user->fresh()->trial_ends_at->isPast());
        $this->assertFalse($user->fresh()->aAbonnementActif());

        $notif = Notification::query()
            ->where('user_id', $user->id)
            ->where('type', 'expiration_essai')
            ->first();

        $this->assertNotNull($notif);
        $this->assertSame('Votre essai gratuit est arrêté', $notif->titre);
        $this->assertStringContainsString('n\'est plus possible', $notif->message);
        $this->assertTrue($notif->donnees['nouvel_essai_interdit'] ?? false);

        Mail::assertSent(EssaiGratuitExpireMail::class, function (EssaiGratuitExpireMail $mail) use ($user) {
            return $mail->hasTo($user->email)
                && $mail->typeLabel === 'Allo Tata Premium';
        });
    }

    public function test_le_cron_rattrape_un_essai_expire_sans_notification(): void
    {
        Mail::fake();

        $user = User::factory()->create(['est_gerant' => true]);

        $essai = $user->essaisGratuits()->create([
            'type_abonnement' => 'premium',
            'date_debut' => now()->subDays(10),
            'date_fin' => now()->subDays(5),
            'duree_jours' => 7,
            'statut' => 'expire',
        ]);

        $this->artisan('essais:check-expiration')->assertSuccessful();

        $this->assertNotNull($essai->fresh()->notification_expiration_envoye_le);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'expiration_essai',
        ]);
        Mail::assertSent(EssaiGratuitExpireMail::class);
    }

    public function test_le_cron_notifie_le_proprietaire_pour_un_essai_entreprise(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'est_gerant' => true,
            'abonnement_manuel' => true,
            'abonnement_manuel_actif_jusqu' => now()->addMonth(),
        ]);
        $entreprise = Entreprise::factory()->create(['user_id' => $user->id]);

        $entreprise->essaisGratuits()->create([
            'type_abonnement' => 'site_web',
            'date_debut' => now()->subDays(7),
            'date_fin' => now()->subMinute(),
            'duree_jours' => 7,
            'statut' => 'actif',
        ]);

        $this->artisan('essais:check-expiration')->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'expiration_essai',
        ]);
        Mail::assertSent(EssaiGratuitExpireMail::class, function (EssaiGratuitExpireMail $mail) {
            return $mail->typeLabel === 'Site Web Vitrine';
        });
    }

    public function test_le_cron_n_envoie_pas_deux_fois_la_notification_d_arret(): void
    {
        Mail::fake();

        $user = User::factory()->create(['est_gerant' => true]);

        $user->essaisGratuits()->create([
            'type_abonnement' => 'premium',
            'date_debut' => now()->subDays(8),
            'date_fin' => now()->subDay(),
            'duree_jours' => 7,
            'statut' => 'expire',
            'notification_expiration_envoye_le' => now()->subDay(),
        ]);

        $this->artisan('essais:check-expiration')->assertSuccessful();

        $this->assertSame(0, Notification::where('type', 'expiration_essai')->count());
        Mail::assertNothingSent();
    }

    public function test_le_cron_resynchronise_un_essai_actif_sans_prolonger(): void
    {
        Mail::fake();

        $dateFin = now()->addDays(4)->seconds(0);
        $user = User::factory()->create([
            'est_gerant' => true,
            'trial_ends_at' => null,
        ]);

        $user->essaisGratuits()->create([
            'type_abonnement' => 'premium',
            'date_debut' => now()->subDays(3),
            'date_fin' => $dateFin,
            'duree_jours' => 7,
            'statut' => 'actif',
        ]);

        $this->artisan('essais:check-expiration')->assertSuccessful();

        $user->refresh();
        $this->assertNotNull($user->trial_ends_at);
        $this->assertTrue($user->trial_ends_at->between(now()->addDays(3), now()->addDays(5)));
        $this->assertTrue($user->aAbonnementActif());
        $this->assertTrue($user->aAccesViaEssai('premium'));
    }

    public function test_le_cron_retire_un_acces_premium_oublie_apres_expiration(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'est_gerant' => true,
            'trial_ends_at' => now()->addDays(3),
        ]);

        $user->essaisGratuits()->create([
            'type_abonnement' => 'premium',
            'date_debut' => now()->subDays(10),
            'date_fin' => now()->subDays(5),
            'duree_jours' => 7,
            'statut' => 'expire',
            'notification_expiration_envoye_le' => now()->subDays(5),
        ]);

        $this->artisan('essais:check-expiration')->assertSuccessful();

        $this->assertTrue($user->fresh()->trial_ends_at->isPast());
        $this->assertFalse($user->fresh()->aAbonnementActif());
    }

    public function test_le_cron_retire_une_option_entreprise_oubliee_sans_trial_ends_at(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'est_gerant' => true,
            'abonnement_manuel' => true,
            'abonnement_manuel_actif_jusqu' => now()->addMonth(),
        ]);
        $entreprise = Entreprise::factory()->create(['user_id' => $user->id]);

        $entreprise->essaisGratuits()->create([
            'type_abonnement' => 'site_web',
            'date_debut' => now()->subDays(10),
            'date_fin' => now()->subDays(5),
            'duree_jours' => 7,
            'statut' => 'expire',
            'notification_expiration_envoye_le' => now()->subDays(5),
        ]);

        \App\Models\EntrepriseSubscription::create([
            'entreprise_id' => $entreprise->id,
            'type' => 'site_web',
            'name' => 'essai_site_web',
            'est_manuel' => false,
            'actif_jusqu' => now()->addDays(2)->toDateString(),
        ]);

        $this->assertTrue($entreprise->fresh()->aSiteWebActif());

        $this->artisan('essais:check-expiration')->assertSuccessful();

        $this->assertFalse($entreprise->fresh()->aSiteWebActif());
    }
}
