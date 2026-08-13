<?php

namespace Tests\Unit\Facturation;

use Tests\TestCase;

class PdfTemplateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        set_error_handler(function (int $severity, string $message): bool {
            if (str_contains($message, 'GMP or BCMath')) {
                return true;
            }

            return false;
        });
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        parent::tearDown();
    }

    public function test_pdf_franchise_contient_mentions_cgi(): void
    {
        $html = html_entity_decode(
            view('factures.pdf', ['doc' => $this->doc(assujetti: false)])->render(),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        $this->assertStringContainsString('FACTURE', $html);
        $this->assertStringContainsString('FAC-2026-0001', $html);
        $this->assertStringContainsString('SIRET 73282932000074', $html);
        $this->assertStringContainsString('TVA non applicable, article 293 B du CGI', $html);
        $this->assertStringContainsString('Pas d\'escompte', $html);
        $this->assertStringContainsString('Entrepreneur individuel', $html);
        $this->assertStringNotContainsString('générée le', mb_strtolower($html));
    }

    public function test_pdf_assujetti_detaille_la_tva(): void
    {
        $html = view('factures.pdf', ['doc' => $this->doc(assujetti: true)])->render();

        $this->assertStringContainsString('TVA (20', $html);
        $this->assertStringContainsString('20,00 €', $html);
        $this->assertStringContainsString('120,00 €', $html);
    }

    public function test_pdf_acquittee_affiche_le_tampon(): void
    {
        $doc = $this->doc(assujetti: false);
        $doc['paiement'] = ['acquittee' => true, 'date_paiement' => '13/08/2026'];

        $html = view('factures.pdf', ['doc' => $doc])->render();

        $this->assertStringContainsString('ACQUITTÉE', $html);
        $this->assertStringContainsString('13/08/2026', $html);
    }

    public function test_pdf_devis_titre_et_validite(): void
    {
        $doc = $this->doc(assujetti: false);
        $doc['type'] = 'devis';
        $doc['numero'] = 'DEV-2026-0001';
        $doc['date_validite'] = '12/09/2026';
        $doc['mentions'] = [
            'validite' => 'Ce devis est valable jusqu\'au 12/09/2026.',
            'acceptation' => 'Bon pour accord',
            'escompte' => 'Pas d\'escompte pour paiement anticipé.',
        ];

        $html = view('devis.pdf', ['doc' => $doc])->render();

        $this->assertStringContainsString('DEVIS', $html);
        $this->assertStringContainsString('DEV-2026-0001', $html);
        $this->assertStringContainsString('12/09/2026', $html);
    }

    /**
     * @return array<string, mixed>
     */
    private function doc(bool $assujetti): array
    {
        $ht = 100.0;
        $tva = $assujetti ? 20.0 : 0.0;

        return [
            'type' => 'facture',
            'numero' => 'FAC-2026-0001',
            'date_emission' => '13/08/2026',
            'date_prestation' => '13/08/2026',
            'date_echeance' => '12/09/2026',
            'emetteur' => [
                'nom' => 'Salon Test',
                'forme_juridique' => 'Entrepreneur individuel',
                'siret' => '73282932000074',
                'adresse' => "1 rue de la Paix\n75001 Paris",
                'email' => 'salon@example.com',
                'telephone' => '0601020304',
            ],
            'client' => [
                'nom' => 'Marie Client',
                'adresse' => "2 avenue Victor Hugo\n69002 Lyon",
                'email' => 'marie@example.com',
            ],
            'lignes' => [[
                'description' => 'Coupe',
                'details' => '60 min',
                'date' => '13/08/2026',
                'quantite' => 1,
                'montant_ht' => $ht,
                'taux_tva' => $assujetti ? 20 : 0,
                'montant_ttc' => $ht + $tva,
            ]],
            'totaux' => [
                'montant_ht' => $ht,
                'taux_tva' => $assujetti ? 20 : 0,
                'montant_tva' => $tva,
                'montant_ttc' => $ht + $tva,
                'assujetti_tva' => $assujetti,
                'mention_tva' => $assujetti
                    ? 'TVA au taux de 20,00 %.'
                    : 'TVA non applicable, article 293 B du CGI',
            ],
            'mentions' => [
                'escompte' => 'Pas d\'escompte pour paiement anticipé.',
                'penalites' => 'En cas de retard de paiement, des pénalités calculées sur la base du taux d\'intérêt légal sont exigibles.',
                'tva' => $assujetti
                    ? 'TVA applicable selon le taux indiqué.'
                    : 'TVA non applicable, article 293 B du CGI',
            ],
            'couleurs' => [
                'primary' => '#059669',
                'secondary' => '#1F2937',
                'text' => '#1a1a1a',
                'muted' => '#6b7280',
                'background' => '#f9fafb',
                'border' => '#e5e7eb',
                'success' => '#10b981',
            ],
            'paiement' => ['acquittee' => false, 'date_paiement' => null],
        ];
    }
}
