<?php

namespace Tests\Unit\Facturation;

use App\Models\Entreprise;
use App\Services\Facturation\BillingProfileService;
use Tests\TestCase;

class BillingProfileServiceTest extends TestCase
{
    public function test_siret_luhn_et_mention_293_b(): void
    {
        $billing = app(BillingProfileService::class);
        $this->assertTrue($billing->siretEstValide('73282932000074'));
        $this->assertFalse($billing->siretEstValide('12345678900000'));
        $this->assertFalse($billing->siretEstValide('123'));

        $entreprise = new Entreprise(['assujetti_tva' => false]);
        $this->assertStringContainsString('293 B', $billing->mentionTva($entreprise));

        $assujetti = new Entreprise(['assujetti_tva' => true, 'taux_tva_defaut' => 20]);
        $this->assertStringContainsString('20', $billing->mentionTva($assujetti));
    }

    public function test_checklist_profil_incomplet(): void
    {
        $billing = app(BillingProfileService::class);
        $entreprise = new Entreprise([
            'nom' => 'Test',
            'status_juridique' => 'en_cours',
            'email' => 'a@b.c',
        ]);

        $manquants = $billing->champsManquants($entreprise);
        $this->assertArrayHasKey('siret', $manquants);
        $this->assertArrayHasKey('status_juridique', $manquants);
        $this->assertArrayHasKey('adresse', $manquants);
    }
}
