<?php

namespace Tests\Unit;

use App\Models\Echeance;
use App\Models\User;
use App\Services\PaymentVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

/**
 * Tests de robustesse pour PaymentVerificationService
 * Sans utiliser Stripe réel - tests de logique uniquement
 */
class PaymentVerificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        // Désactiver les appels Stripe réels
        $this->withoutStripe();
    }

    /**
     * Test : Vérification que le montant débité correspond au montant attendu
     */
    public function test_verification_montant_correspond_au_montant_attendu(): void
    {
        $user = User::factory()->create();
        $echeance = Echeance::factory()->create([
            'user_id' => $user->id,
            'statut' => Echeance::STATUT_A_PAYER,
            'montant_du' => 15.00,
            'montant_final' => 15.00,
        ]);

        // Mock d'un PaymentIntent avec le bon montant
        $pi = (object) [
            'id' => 'pi_test_123',
            'status' => 'succeeded',
            'amount' => 1500, // 15.00€ en centimes
            'currency' => 'eur',
            'metadata' => [
            'user_id' => (string) $user->id,
            'echeance_id' => (string) $echeance->id,
            ],
        ];

        // Mock de PaymentIntent::retrieve
        $this->mockStripePaymentIntent($pi);

        $result = PaymentVerificationService::markEcheancePaidFromPaymentIntent('pi_test_123');

        $this->assertTrue($result['ok']);
        $echeance->refresh();
        $this->assertEquals(Echeance::STATUT_PAYE, $echeance->statut);
    }

    /**
     * Test : Rejet si le montant débité ne correspond pas
     */
    public function test_rejet_si_montant_debite_ne_correspond_pas(): void
    {
        $user = User::factory()->create();
        $echeance = Echeance::factory()->create([
            'user_id' => $user->id,
            'statut' => Echeance::STATUT_A_PAYER,
            'montant_du' => 15.00,
            'montant_final' => 15.00,
        ]);

        // Mock d'un PaymentIntent avec un montant incorrect
        $pi = (object) [
            'id' => 'pi_test_123',
            'status' => 'succeeded',
            'amount' => 2000, // 20.00€ au lieu de 15.00€
            'currency' => 'eur',
            'metadata' => [
            'user_id' => (string) $user->id,
            'echeance_id' => (string) $echeance->id,
            ],
        ];

        $this->mockStripePaymentIntent($pi);

        $result = PaymentVerificationService::markEcheancePaidFromPaymentIntent('pi_test_123');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('ne correspond pas', $result['message']);
        $echeance->refresh();
        $this->assertNotEquals(Echeance::STATUT_PAYE, $echeance->statut);
    }

    /**
     * Test : Rejet si l'échéance est annulée
     */
    public function test_rejet_si_echeance_annulee(): void
    {
        $user = User::factory()->create();
        $echeance = Echeance::factory()->create([
            'user_id' => $user->id,
            'statut' => Echeance::STATUT_ANNULE,
            'montant_du' => 15.00,
            'montant_final' => 15.00,
        ]);

        $pi = (object) [
            'id' => 'pi_test_123',
            'status' => 'succeeded',
            'amount' => 1500,
            'currency' => 'eur',
            'metadata' => [
            'user_id' => (string) $user->id,
            'echeance_id' => (string) $echeance->id,
            ],
        ];

        $this->mockStripePaymentIntent($pi);

        $result = PaymentVerificationService::markEcheancePaidFromPaymentIntent('pi_test_123');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('annulée', $result['message']);
    }

    /**
     * Test : Idempotence - ne pas payer deux fois
     */
    public function test_idempotence_ne_pas_payer_deux_fois(): void
    {
        $user = User::factory()->create();
        $echeance = Echeance::factory()->create([
            'user_id' => $user->id,
            'statut' => Echeance::STATUT_PAYE,
            'montant_du' => 15.00,
            'montant_final' => 15.00,
            'paye_at' => now(),
        ]);

        $pi = (object) [
            'id' => 'pi_test_123',
            'status' => 'succeeded',
            'amount' => 1500,
            'currency' => 'eur',
            'metadata' => [
            'user_id' => (string) $user->id,
            'echeance_id' => (string) $echeance->id,
            ],
        ];

        $this->mockStripePaymentIntent($pi);

        $result = PaymentVerificationService::markEcheancePaidFromPaymentIntent('pi_test_123');

        $this->assertTrue($result['ok']);
        $this->assertTrue($result['already']);
        $this->assertStringContainsString('Déjà', $result['message']);
    }

    /**
     * Test : Tolérance d'arrondi de 0.01€
     */
    public function test_tolerance_arrondi_001(): void
    {
        $user = User::factory()->create();
        $echeance = Echeance::factory()->create([
            'user_id' => $user->id,
            'statut' => Echeance::STATUT_A_PAYER,
            'montant_du' => 15.00,
            'montant_final' => 15.00,
        ]);

        // Montant débité : 15.01€ (différence de 0.01€ - toléré)
        $pi = (object) [
            'id' => 'pi_test_123',
            'status' => 'succeeded',
            'amount' => 1501, // 15.01€
            'currency' => 'eur',
            'metadata' => [
            'user_id' => (string) $user->id,
            'echeance_id' => (string) $echeance->id,
            ],
        ];

        $this->mockStripePaymentIntent($pi);

        $result = PaymentVerificationService::markEcheancePaidFromPaymentIntent('pi_test_123');

        $this->assertTrue($result['ok'], 'La tolérance de 0.01€ devrait être acceptée');
    }

    /**
     * Test : Rejet si différence > 0.01€
     */
    public function test_rejet_si_difference_superieure_001(): void
    {
        $user = User::factory()->create();
        $echeance = Echeance::factory()->create([
            'user_id' => $user->id,
            'statut' => Echeance::STATUT_A_PAYER,
            'montant_du' => 15.00,
            'montant_final' => 15.00,
        ]);

        // Montant débité : 15.02€ (différence de 0.02€ - non toléré)
        $pi = (object) [
            'id' => 'pi_test_123',
            'status' => 'succeeded',
            'amount' => 1502, // 15.02€
            'currency' => 'eur',
            'metadata' => [
            'user_id' => (string) $user->id,
            'echeance_id' => (string) $echeance->id,
            ],
        ];

        $this->mockStripePaymentIntent($pi);

        $result = PaymentVerificationService::markEcheancePaidFromPaymentIntent('pi_test_123');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('ne correspond pas', $result['message']);
    }

    /**
     * Helper : Mock PaymentIntent::retrieve
     */
    private function mockStripePaymentIntent($pi): void
    {
        $mock = Mockery::mock('alias:Stripe\PaymentIntent');
        $mock->shouldReceive('retrieve')
            ->with('pi_test_123')
            ->andReturn($pi);
    }

    /**
     * Désactiver les appels Stripe
     */
    private function withoutStripe(): void
    {
        // Les appels Stripe sont mockés via mockStripePaymentIntent().
    }
}
