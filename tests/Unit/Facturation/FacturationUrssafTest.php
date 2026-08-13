<?php

namespace Tests\Unit\Facturation;

use App\Exceptions\BillingProfileIncompleteException;
use App\Exceptions\ImmutableDocumentException;
use App\Models\Entreprise;
use App\Models\Facture;
use App\Models\Reservation;
use App\Services\Facturation\DocumentSequenceService;
use App\Services\Facturation\FactureEmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FacturationUrssafTest extends TestCase
{
    use RefreshDatabase;

    public function test_sequence_par_entreprise_sans_trou(): void
    {
        $a = Entreprise::factory()->create();
        $b = Entreprise::factory()->create();
        $seq = app(DocumentSequenceService::class);

        $this->assertSame('FAC-'.date('Y').'-0001', $seq->next($a->id, 'facture'));
        $this->assertSame('FAC-'.date('Y').'-0002', $seq->next($a->id, 'facture'));
        $this->assertSame('FAC-'.date('Y').'-0001', $seq->next($b->id, 'facture'));
        $this->assertSame('DEV-'.date('Y').'-0001', $seq->next($a->id, 'devis'));
    }

    public function test_emission_refusee_si_profil_incomplet(): void
    {
        $entreprise = Entreprise::factory()->incomplet()->create();
        $reservation = Reservation::factory()->create([
            'entreprise_id' => $entreprise->id,
            'statut' => 'confirmee',
        ]);

        $this->expectException(BillingProfileIncompleteException::class);
        app(FactureEmissionService::class)->emettrePourReservation($reservation);
    }

    public function test_emission_fige_le_snapshot_et_le_siret(): void
    {
        Mail::fake();
        $entreprise = Entreprise::factory()->create(['siret' => '73282932000074']);
        $reservation = Reservation::factory()->create([
            'entreprise_id' => $entreprise->id,
            'statut' => 'confirmee',
            'prix' => 80,
        ]);

        $facture = app(FactureEmissionService::class)->emettrePourReservation($reservation);

        $this->assertNotNull($facture);
        $this->assertNotNull($facture->verrouillee_at);
        $this->assertSame('emise', $facture->statut);
        $this->assertSame('73282932000074', $facture->snapshot['emetteur']['siret']);
        $this->assertStringContainsString('293 B', $facture->snapshot['totaux']['mention_tva']);

        $entreprise->update(['siret' => '55210055400013']);
        $facture->refresh();
        $this->assertSame('73282932000074', $facture->snapshot['emetteur']['siret']);

        $this->expectException(ImmutableDocumentException::class);
        $facture->update(['montant_ttc' => 1]);
    }

    public function test_pdf_contient_les_mentions_legales(): void
    {
        Mail::fake();
        $entreprise = Entreprise::factory()->create();
        $reservation = Reservation::factory()->create([
            'entreprise_id' => $entreprise->id,
            'statut' => 'confirmee',
        ]);
        $facture = app(FactureEmissionService::class)->emettrePourReservation($reservation);

        $html = html_entity_decode(
            view('factures.pdf', ['doc' => $facture->snapshot])->render(),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        $this->assertStringContainsString('FACTURE', $html);
        $this->assertStringContainsString($facture->numero_facture, $html);
        $this->assertStringContainsString('SIRET', $html);
        $this->assertStringContainsString('TVA non applicable, article 293 B du CGI', $html);
        $this->assertStringContainsString('Pas d\'escompte', $html);
    }

    public function test_pdf_tva_si_assujetti(): void
    {
        Mail::fake();
        $entreprise = Entreprise::factory()->assujettiTva()->create();
        $reservation = Reservation::factory()->create([
            'entreprise_id' => $entreprise->id,
            'statut' => 'confirmee',
            'prix' => 100,
        ]);
        $facture = app(FactureEmissionService::class)->emettrePourReservation($reservation);

        $this->assertEquals(20, (float) $facture->taux_tva);
        $this->assertEquals(20, (float) $facture->montant_tva);
        $this->assertEquals(120, (float) $facture->montant_ttc);

        $html = view('factures.pdf', ['doc' => $facture->snapshot])->render();
        $this->assertStringContainsString('TVA (20', $html);
    }
}
