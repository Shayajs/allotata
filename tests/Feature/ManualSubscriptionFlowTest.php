<?php

namespace Tests\Feature;

use App\Models\AbonnementManuelEvenement;
use App\Models\AbonnementManuelPeriode;
use App\Models\Notification;
use App\Models\User;
use App\Services\ManualSubscriptionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualSubscriptionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_passing_the_anniversary_never_cancels_the_member(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-20 10:00:00', 'Europe/Paris'));
        $user = $this->manualMember('2026-01-12');

        app(ManualSubscriptionService::class)->syncUser($user);

        $user->refresh();
        $this->assertTrue($user->hasActiveManualPremium());
        $this->assertSame(User::MANUAL_STATUS_ACTIVE, $user->abonnement_manuel_statut);
        $this->assertSame(
            [AbonnementManuelPeriode::PENDING],
            $user->abonnementManuelPeriodes()->pluck('statut')->unique()->values()->all()
        );
        $this->assertTrue($user->aAbonnementActif());
    }

    public function test_overdue_reminder_does_not_remove_access(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-15 08:00:00', 'Europe/Paris'));
        $user = $this->manualMember('2026-08-12');
        $service = app(ManualSubscriptionService::class);

        $service->syncUser($user);

        $user->refresh();
        $attention = $service->attention($user);

        $this->assertTrue($user->hasActiveManualPremium());
        $this->assertSame('red', $attention['level']);
        $this->assertSame(3, $attention['days']);
        $this->assertSame(1, Notification::where('user_id', $user->id)->where('type', 'manuel_echeance_retard')->count());

        $service->syncUser($user);
        $this->assertSame(1, Notification::where('user_id', $user->id)->where('type', 'manuel_echeance_retard')->count());
    }

    public function test_admin_refusal_removes_only_manual_benefits_and_keeps_history(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-16 11:00:00', 'Europe/Paris'));
        $user = $this->manualMember('2026-08-12');
        $user->forceFill(['premium_actif_jusqu' => '2026-12-01'])->save();
        $admin = $this->admin();
        $service = app(ManualSubscriptionService::class);
        $service->syncUser($user, false);

        $period = $user->abonnementManuelPeriodes()->first();
        $this->actingAs($admin)->post(route('admin.manual-subscriptions.refuse', $period))->assertRedirect();

        $user->refresh();
        $period->refresh();

        $this->assertFalse($user->hasActiveManualPremium());
        $this->assertSame(User::MANUAL_STATUS_CANCELLED, $user->abonnement_manuel_statut);
        $this->assertTrue((bool) $user->abonnement_manuel);
        $this->assertSame('2026-08-12', $user->abonnement_manuel_date_debut->toDateString());
        $this->assertSame('2026-12-01', $user->premium_actif_jusqu->toDateString());
        $this->assertSame(AbonnementManuelPeriode::NOT_PAID, $period->statut);
        $this->assertSame($admin->id, $period->refused_by);
        $this->assertNotNull($user->fresh());
        $this->assertSame(1, Notification::where('user_id', $user->id)->where('type', 'manuel_abonnement_annule')->count());
    }

    public function test_member_declaration_reactivates_immediately_and_is_idempotent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-18 09:00:00', 'Europe/Paris'));
        $user = $this->manualMember('2026-08-12');
        $admin = $this->admin();
        $service = app(ManualSubscriptionService::class);
        $service->syncUser($user, false);
        $period = $user->abonnementManuelPeriodes()->first();
        $service->refuse($period, $admin);

        $this->actingAs($user)->post(route('subscription.manual.declare'))->assertRedirect();
        $this->actingAs($user)->post(route('subscription.manual.declare'))->assertRedirect();

        $user->refresh();
        $period->refresh();

        $this->assertTrue($user->hasActiveManualPremium());
        $this->assertSame(AbonnementManuelPeriode::MEMBER_DECLARED, $period->statut);
        $this->assertSame(1, Notification::where('type', 'admin_manuel_declare')->count());
        $this->assertSame(1, AbonnementManuelEvenement::where('type', AbonnementManuelEvenement::REACTIVATED)->count());
    }

    public function test_confirmation_keeps_previous_periods_and_is_idempotent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-20 12:00:00', 'Europe/Paris'));
        $user = $this->manualMember('2026-01-12');
        $adminA = $this->admin();
        $adminB = $this->admin();
        $service = app(ManualSubscriptionService::class);
        $service->syncUser($user, false);

        $periods = $user->abonnementManuelPeriodes()->orderBy('echeance_at')->get();
        $this->assertCount(3, $periods);

        $service->confirm($periods[0], $adminA);
        $service->confirm($periods[0]->fresh(), $adminB);

        $this->assertSame(AbonnementManuelPeriode::CONFIRMED, $periods[0]->fresh()->statut);
        $this->assertSame(AbonnementManuelPeriode::PENDING, $periods[1]->fresh()->statut);
        $this->assertSame(AbonnementManuelPeriode::PENDING, $periods[2]->fresh()->statut);
        $this->assertSame(1, AbonnementManuelEvenement::where('type', AbonnementManuelEvenement::CONFIRMED)->count());
        $this->assertTrue($user->fresh()->hasActiveManualPremium());
    }

    public function test_member_cannot_confirm_a_payment(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-13 09:00:00', 'Europe/Paris'));
        $user = $this->manualMember('2026-08-12');
        app(ManualSubscriptionService::class)->syncUser($user, false);
        $period = $user->abonnementManuelPeriodes()->first();

        $this->actingAs($user)->post(route('admin.manual-subscriptions.confirm', $period))->assertForbidden();
        $this->assertSame(AbonnementManuelPeriode::PENDING, $period->fresh()->statut);
    }

    public function test_yellow_banner_before_the_third_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-13 09:00:00', 'Europe/Paris'));
        $user = $this->manualMember('2026-08-12');
        app(ManualSubscriptionService::class)->syncUser($user, false);

        $attention = app(ManualSubscriptionService::class)->attention($user->fresh());

        $this->assertSame('yellow', $attention['level']);
        $this->assertSame(1, $attention['days']);
        $this->assertTrue($user->fresh()->hasActiveManualPremium());
    }

    public function test_doing_nothing_after_cancellation_stays_cancelled(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 09:00:00', 'Europe/Paris'));
        $user = $this->manualMember('2026-08-12');
        $service = app(ManualSubscriptionService::class);
        $service->syncUser($user, false);
        $service->refuse($user->abonnementManuelPeriodes()->orderBy('echeance_at')->first(), $this->admin());

        $service->syncAllActive();

        $this->assertFalse($user->fresh()->hasActiveManualPremium());
        $this->assertSame(User::MANUAL_STATUS_CANCELLED, $user->fresh()->abonnement_manuel_statut);
    }

    private function manualMember(string $start): User
    {
        return User::factory()->create([
            'name' => 'Lucas',
            'surname' => 'Espinar',
            'abonnement_manuel' => true,
            'abonnement_manuel_statut' => User::MANUAL_STATUS_ACTIVE,
            'abonnement_manuel_date_debut' => $start,
            'abonnement_manuel_type_renouvellement' => 'mensuel',
            'abonnement_manuel_jour_renouvellement' => (int) substr($start, 8, 2),
            'abonnement_manuel_montant' => 29.90,
            'abonnement_manuel_actif_jusqu' => null,
            'est_gerant' => true,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'name' => 'Admin',
        ]);
    }
}
