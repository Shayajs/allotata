<?php

namespace Tests\Unit;

use App\Models\CustomPrice;
use App\Models\Echeance;
use App\Models\PromoCode;
use App\Models\Tarif;
use App\Models\User;
use App\Services\CalculMontantDuService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de robustesse pour CalculMontantDuService
 * Tests de logique de calcul sans Stripe
 */
class CalculMontantDuServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test : Calcul correct pour une échéance simple
     */
    public function test_calcul_correct_echeance_simple(): void
    {
        $user = User::factory()->create();
        
        // Créer un tarif par défaut
        Tarif::updateOrCreate(
            ['type' => 'default'],
            ['amount' => 15.00, 'currency' => 'eur', 'label' => 'Abonnement Premium']
        );

        $echeance = Echeance::factory()->create([
            'user_id' => $user->id,
            'subscription_type' => Echeance::TYPE_DEFAULT,
            'montant_du' => 15.00,
        ]);

        $result = CalculMontantDuService::calculerPourEcheance($echeance);

        $this->assertEquals(15.00, $result['montant_du']);
        $this->assertEquals(15.00, $result['montant_final']);
        $this->assertEquals(0, $result['reduction_promo']);
    }

    /**
     * Test : Application d'un code promo pourcentage
     */
    public function test_application_code_promo_pourcentage(): void
    {
        $user = User::factory()->create();
        
        Tarif::updateOrCreate(
            ['type' => 'default'],
            ['amount' => 20.00, 'currency' => 'eur', 'label' => 'Abonnement Premium']
        );

        $promo = PromoCode::create([
            'code' => 'TEST10',
            'type' => 'pourcentage',
            'valeur' => 10,
            'est_actif' => true,
            'date_debut' => now()->subDay(),
            'date_fin' => now()->addMonth(),
        ]);

        $echeance = Echeance::factory()->create([
            'user_id' => $user->id,
            'subscription_type' => Echeance::TYPE_DEFAULT,
            'montant_du' => 20.00,
        ]);

        $result = CalculMontantDuService::calculerPourEcheance($echeance, 'TEST10');

        $this->assertEquals(20.00, $result['montant_du']);
        $this->assertEquals(18.00, $result['montant_final']); // 20 - 10% = 18
        $this->assertEquals(2.00, $result['reduction_promo']);
    }

    /**
     * Test : Application d'un code promo montant fixe
     */
    public function test_application_code_promo_montant_fixe(): void
    {
        $user = User::factory()->create();
        
        Tarif::updateOrCreate(
            ['type' => 'default'],
            ['amount' => 20.00, 'currency' => 'eur', 'label' => 'Abonnement Premium']
        );

        $promo = PromoCode::create([
            'code' => 'REDUC5',
            'type' => 'montant_fixe',
            'valeur' => 5.00,
            'est_actif' => true,
            'date_debut' => now()->subDay(),
            'date_fin' => now()->addMonth(),
        ]);

        $echeance = Echeance::factory()->create([
            'user_id' => $user->id,
            'subscription_type' => Echeance::TYPE_DEFAULT,
            'montant_du' => 20.00,
        ]);

        $result = CalculMontantDuService::calculerPourEcheance($echeance, 'REDUC5');

        $this->assertEquals(20.00, $result['montant_du']);
        $this->assertEquals(15.00, $result['montant_final']); // 20 - 5 = 15
        $this->assertEquals(5.00, $result['reduction_promo']);
    }

    /**
     * Test : Réduction manuelle appliquée
     */
    public function test_reduction_manuelle_appliquee(): void
    {
        $user = User::factory()->create();
        
        Tarif::updateOrCreate(
            ['type' => 'default'],
            ['amount' => 20.00, 'currency' => 'eur', 'label' => 'Abonnement Premium']
        );

        $echeance = Echeance::factory()->create([
            'user_id' => $user->id,
            'subscription_type' => Echeance::TYPE_DEFAULT,
            'montant_du' => 20.00,
            'reduction_manuel' => 3.00,
        ]);

        $result = CalculMontantDuService::calculerPourEcheance($echeance);

        $this->assertEquals(20.00, $result['montant_du']);
        $this->assertEquals(17.00, $result['montant_final']); // 20 - 3 = 17
    }

    /**
     * Test : Montant final ne peut pas être négatif
     */
    public function test_montant_final_ne_peut_pas_etre_negatif(): void
    {
        $user = User::factory()->create();
        
        Tarif::updateOrCreate(
            ['type' => 'default'],
            ['amount' => 10.00, 'currency' => 'eur', 'label' => 'Abonnement Premium']
        );

        $echeance = Echeance::factory()->create([
            'user_id' => $user->id,
            'subscription_type' => Echeance::TYPE_DEFAULT,
            'montant_du' => 10.00,
            'reduction_manuel' => 15.00, // Réduction supérieure au montant
        ]);

        $result = CalculMontantDuService::calculerPourEcheance($echeance);

        $this->assertEquals(10.00, $result['montant_du']);
        $this->assertEquals(0.00, $result['montant_final']); // max(0, 10 - 15) = 0
        $this->assertGreaterThanOrEqual(0, $result['montant_final']);
    }

    /**
     * Test : Code promo expiré n'est pas appliqué
     */
    public function test_code_promo_expire_non_applique(): void
    {
        $user = User::factory()->create();
        
        Tarif::updateOrCreate(
            ['type' => 'default'],
            ['amount' => 20.00, 'currency' => 'eur', 'label' => 'Abonnement Premium']
        );

        $promo = PromoCode::create([
            'code' => 'EXPIRE',
            'type' => 'pourcentage',
            'valeur' => 10,
            'est_actif' => true,
            'date_debut' => now()->subMonth(),
            'date_fin' => now()->subDay(), // Expiré hier
        ]);

        $echeance = Echeance::factory()->create([
            'user_id' => $user->id,
            'subscription_type' => Echeance::TYPE_DEFAULT,
            'montant_du' => 20.00,
        ]);

        $result = CalculMontantDuService::calculerPourEcheance($echeance, 'EXPIRE');

        $this->assertEquals(20.00, $result['montant_du']);
        $this->assertEquals(20.00, $result['montant_final']); // Pas de réduction
        $this->assertEquals(0, $result['reduction_promo']);
        $this->assertNull($result['promo_code_id']);
    }

    /**
     * Test : Code promo inactif n'est pas appliqué
     */
    public function test_code_promo_inactif_non_applique(): void
    {
        $user = User::factory()->create();
        
        Tarif::updateOrCreate(
            ['type' => 'default'],
            ['amount' => 20.00, 'currency' => 'eur', 'label' => 'Abonnement Premium']
        );

        $promo = PromoCode::create([
            'code' => 'INACTIF',
            'type' => 'pourcentage',
            'valeur' => 10,
            'est_actif' => false, // Inactif
            'date_debut' => now()->subDay(),
            'date_fin' => now()->addMonth(),
        ]);

        $echeance = Echeance::factory()->create([
            'user_id' => $user->id,
            'subscription_type' => Echeance::TYPE_DEFAULT,
            'montant_du' => 20.00,
        ]);

        $result = CalculMontantDuService::calculerPourEcheance($echeance, 'INACTIF');

        $this->assertEquals(20.00, $result['montant_final']);
        $this->assertEquals(0, $result['reduction_promo']);
    }

    /**
     * Test : Arrondi correct à 2 décimales
     */
    public function test_arrondi_correct_2_decimales(): void
    {
        $user = User::factory()->create();
        
        Tarif::updateOrCreate(
            ['type' => 'default'],
            ['amount' => 19.99, 'currency' => 'eur', 'label' => 'Abonnement Premium']
        );

        $promo = PromoCode::create([
            'code' => 'TEST33',
            'type' => 'pourcentage',
            'valeur' => 33.333, // Réduction qui donne un nombre avec beaucoup de décimales
            'est_actif' => true,
            'date_debut' => now()->subDay(),
            'date_fin' => now()->addMonth(),
        ]);

        $echeance = Echeance::factory()->create([
            'user_id' => $user->id,
            'subscription_type' => Echeance::TYPE_DEFAULT,
            'montant_du' => 19.99,
        ]);

        $result = CalculMontantDuService::calculerPourEcheance($echeance, 'TEST33');

        // Vérifier que le montant final est arrondi à 2 décimales
        $this->assertEquals(2, strlen(substr(strrchr((string)$result['montant_final'], '.'), 1)));
    }
}
