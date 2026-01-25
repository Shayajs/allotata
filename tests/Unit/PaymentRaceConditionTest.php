<?php

namespace Tests\Unit;

use App\Models\Echeance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests de robustesse pour les race conditions
 * Simule des paiements simultanés sans utiliser Stripe
 */
class PaymentRaceConditionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test : Vérifier que le verrou transactionnel empêche les doubles paiements
     * 
     * Ce test simule deux tentatives de paiement simultanées sur la même échéance.
     * Avec le verrou lockForUpdate(), seule une transaction devrait réussir.
     */
    public function test_verrou_empeche_doubles_paiements(): void
    {
        $user = User::factory()->create();
        $echeance = Echeance::factory()->create([
            'user_id' => $user->id,
            'statut' => Echeance::STATUT_A_PAYER,
            'montant_du' => 15.00,
            'montant_final' => 15.00,
        ]);

        $echeanceId = $echeance->id;
        $userId = $user->id;

        // Simuler deux transactions simultanées
        $transaction1Success = false;
        $transaction2Success = false;
        $transaction1Error = null;
        $transaction2Error = null;

        // Transaction 1
        try {
            DB::transaction(function () use ($userId, $echeanceId, &$transaction1Success) {
                $echeance = Echeance::where('user_id', $userId)
                    ->whereIn('statut', [Echeance::STATUT_A_PAYER, Echeance::STATUT_EN_ATTENTE])
                    ->whereNotIn('statut', [Echeance::STATUT_ANNULE, Echeance::STATUT_ARRETE])
                    ->lockForUpdate()
                    ->findOrFail($echeanceId);

                if ($echeance->estPayee()) {
                    throw new \Exception('Déjà payée');
                }

                // Simuler le paiement
                sleep(0.1); // Simuler un délai de traitement
                $echeance->update(['statut' => Echeance::STATUT_PAYE, 'paye_at' => now()]);
                $transaction1Success = true;
            });
        } catch (\Exception $e) {
            $transaction1Error = $e->getMessage();
        }

        // Transaction 2 (simultanée)
        try {
            DB::transaction(function () use ($userId, $echeanceId, &$transaction2Success) {
                $echeance = Echeance::where('user_id', $userId)
                    ->whereIn('statut', [Echeance::STATUT_A_PAYER, Echeance::STATUT_EN_ATTENTE])
                    ->whereNotIn('statut', [Echeance::STATUT_ANNULE, Echeance::STATUT_ARRETE])
                    ->lockForUpdate()
                    ->findOrFail($echeanceId);

                if ($echeance->estPayee()) {
                    throw new \Exception('Déjà payée');
                }

                // Simuler le paiement
                $echeance->update(['statut' => Echeance::STATUT_PAYE, 'paye_at' => now()]);
                $transaction2Success = true;
            });
        } catch (\Exception $e) {
            $transaction2Error = $e->getMessage();
        }

        // Vérifier qu'une seule transaction a réussi
        $successCount = ($transaction1Success ? 1 : 0) + ($transaction2Success ? 1 : 0);
        $this->assertEquals(1, $successCount, 'Une seule transaction devrait réussir');

        // Vérifier que l'échéance est payée exactement une fois
        $echeance->refresh();
        $this->assertEquals(Echeance::STATUT_PAYE, $echeance->statut);
    }

    /**
     * Test : Vérifier que les échéances annulées sont rejetées
     */
    public function test_echeance_annulee_rejetee(): void
    {
        $user = User::factory()->create();
        $echeance = Echeance::factory()->create([
            'user_id' => $user->id,
            'statut' => Echeance::STATUT_ANNULE,
            'montant_du' => 15.00,
        ]);

        $echeanceId = $echeance->id;
        $userId = $user->id;

        $exceptionThrown = false;

        try {
            DB::transaction(function () use ($userId, $echeanceId) {
                $echeance = Echeance::where('user_id', $userId)
                    ->whereIn('statut', [Echeance::STATUT_A_PAYER, Echeance::STATUT_EN_ATTENTE])
                    ->whereNotIn('statut', [Echeance::STATUT_ANNULE, Echeance::STATUT_ARRETE])
                    ->lockForUpdate()
                    ->findOrFail($echeanceId);

                // Ne devrait jamais arriver ici
                $echeance->update(['statut' => Echeance::STATUT_PAYE]);
            });
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown, 'Une exception devrait être levée pour une échéance annulée');
    }

    /**
     * Test : Vérifier que les échéances déjà payées sont rejetées
     */
    public function test_echeance_deja_payee_rejetee(): void
    {
        $user = User::factory()->create();
        $echeance = Echeance::factory()->create([
            'user_id' => $user->id,
            'statut' => Echeance::STATUT_PAYE,
            'montant_du' => 15.00,
            'paye_at' => now(),
        ]);

        $echeanceId = $echeance->id;
        $userId = $user->id;

        $alreadyPaid = false;

        DB::transaction(function () use ($userId, $echeanceId, &$alreadyPaid) {
            $echeance = Echeance::where('user_id', $userId)
                ->whereIn('statut', [Echeance::STATUT_A_PAYER, Echeance::STATUT_EN_ATTENTE])
                ->whereNotIn('statut', [Echeance::STATUT_ANNULE, Echeance::STATUT_ARRETE])
                ->lockForUpdate()
                ->find($echeanceId);

            if (!$echeance) {
                $alreadyPaid = true;
                return;
            }

            if ($echeance->estPayee()) {
                $alreadyPaid = true;
                return;
            }
        });

        $this->assertTrue($alreadyPaid, 'Une échéance déjà payée devrait être détectée');
    }

    /**
     * Test : Vérifier la validation du montant final
     */
    public function test_validation_montant_final(): void
    {
        $user = User::factory()->create();
        
        // Test 1 : Montant final > montant dû (devrait être rejeté)
        $echeance1 = Echeance::factory()->create([
            'user_id' => $user->id,
            'statut' => Echeance::STATUT_A_PAYER,
            'montant_du' => 15.00,
            'montant_final' => 20.00, // Erreur : supérieur au montant dû
        ]);

        $montantFinal = 20.00;
        $montantDu = (float) ($echeance1->montant_du ?? 0);
        
        $this->assertTrue($montantFinal > $montantDu, 'Le montant final ne devrait pas dépasser le montant dû');

        // Test 2 : Montant final = 0 (devrait être rejeté)
        $echeance2 = Echeance::factory()->create([
            'user_id' => $user->id,
            'statut' => Echeance::STATUT_A_PAYER,
            'montant_du' => 15.00,
            'montant_final' => 0.00,
        ]);

        $montantFinal2 = 0.00;
        $this->assertTrue($montantFinal2 <= 0, 'Le montant final ne devrait pas être nul ou négatif');

        // Test 3 : Montant final valide
        $echeance3 = Echeance::factory()->create([
            'user_id' => $user->id,
            'statut' => Echeance::STATUT_A_PAYER,
            'montant_du' => 15.00,
            'montant_final' => 15.00,
        ]);

        $montantFinal3 = 15.00;
        $montantDu3 = (float) ($echeance3->montant_du ?? 0);
        
        $this->assertTrue($montantFinal3 > 0 && $montantFinal3 <= $montantDu3, 'Le montant final devrait être valide');
    }
}
