<?php

namespace Tests\Feature;

use App\Models\PlayPurchase;
use App\Models\User;
use App\Services\PlayBilling\PlayBillingVerifierContract;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_refuse_les_invites(): void
    {
        $this->postJson('/play-billing/verify', [
            'product_id' => 'fr.allotata.premium',
            'purchase_token' => 'token-test',
        ])->assertUnauthorized();
    }

    public function test_verify_active_le_premium_apres_validation_google(): void
    {
        $user = User::factory()->create(['est_gerant' => true]);

        $this->mock(PlayBillingVerifierContract::class, function ($mock) {
            $mock->shouldReceive('verify')->once()->andReturn([
                'valid' => true,
                'order_id' => 'GPA.1234',
                'expires_at' => Carbon::now()->addMonth(),
                'acknowledged' => true,
                'payload' => ['subscriptionState' => 'SUBSCRIPTION_STATE_ACTIVE'],
            ]);
            $mock->shouldReceive('acknowledge')->never();
        });

        $this->actingAs($user)
            ->postJson('/play-billing/verify', [
                'product_id' => 'fr.allotata.premium',
                'purchase_token' => 'play-token-premium',
                'order_id' => 'GPA.1234',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('purchase.grants', 'premium');

        $this->assertTrue($user->fresh()->aAbonnementActif());
        $this->assertDatabaseHas('play_purchases', [
            'user_id' => $user->id,
            'purchase_token' => 'play-token-premium',
            'status' => 'active',
        ]);
    }

    public function test_verify_rejette_un_achat_invalide(): void
    {
        $user = User::factory()->create(['est_gerant' => true]);

        $this->mock(PlayBillingVerifierContract::class, function ($mock) {
            $mock->shouldReceive('verify')->once()->andReturn([
                'valid' => false,
                'order_id' => null,
                'expires_at' => null,
                'acknowledged' => false,
                'payload' => [],
            ]);
        });

        $this->actingAs($user)
            ->postJson('/play-billing/verify', [
                'product_id' => 'fr.allotata.premium',
                'purchase_token' => 'bad-token',
            ])
            ->assertStatus(422)
            ->assertJsonPath('ok', false);

        $this->assertDatabaseCount('play_purchases', 0);
    }

    public function test_un_achat_ne_peut_pas_etre_lie_a_un_autre_compte(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        PlayPurchase::query()->create([
            'user_id' => $owner->id,
            'product_id' => 'fr.allotata.premium',
            'grants' => 'premium',
            'purchase_token' => 'shared-token',
            'status' => 'active',
            'expires_at' => now()->addMonth(),
        ]);

        $this->mock(PlayBillingVerifierContract::class, function ($mock) {
            $mock->shouldReceive('verify')->never();
        });

        $this->actingAs($other)
            ->postJson('/play-billing/verify', [
                'product_id' => 'fr.allotata.premium',
                'purchase_token' => 'shared-token',
            ])
            ->assertStatus(422);
    }
}
