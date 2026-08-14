<?php

namespace Tests\Unit\Facturation;

use App\Models\Entreprise;
use App\Models\EntrepriseSubscription;
use App\Models\Facture;
use App\Models\User;
use App\Services\Facturation\DocumentSequenceService;
use App\Services\Facturation\DocumentSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactureAbonnementPlateformeTest extends TestCase
{
    use RefreshDatabase;

    public function test_sequence_plateforme_alo_sans_trou(): void
    {
        $seq = app(DocumentSequenceService::class);
        $annee = (int) date('Y');

        $this->assertSame('ALO-'.$annee.'-0001', $seq->nextPlateforme());
        $this->assertSame('ALO-'.$annee.'-0002', $seq->nextPlateforme());

        $entreprise = Entreprise::factory()->create();
        $this->assertSame('FAC-'.$annee.'-0001', $seq->next($entreprise->id, DocumentSequenceService::TYPE_FACTURE));
        $this->assertSame('ALO-'.$annee.'-0003', $seq->nextPlateforme());
    }

    public function test_emetteur_siret_plateforme_distinct_du_prestataire(): void
    {
        $facture = $this->emettreAbonnementEntreprise(
            nomPrestataire: 'Salon Prestataire Unique',
            siretPrestataire: '73282932000074',
        );

        $this->assertSame('99453590400019', $facture->snapshot['emetteur']['siret']);
        $this->assertSame('Lucas Espinar', $facture->snapshot['emetteur']['nom']);
        $this->assertSame('Allotata', $facture->snapshot['emetteur']['marque']);
        $this->assertSame('plateforme', $facture->snapshot['emetteur_kind']);
        $this->assertSame('allotata', $facture->snapshot['logo']);
        $this->assertStringContainsString('293 B', $facture->snapshot['totaux']['mention_tva']);
        $this->assertNotSame('Salon Prestataire Unique', $facture->snapshot['emetteur']['nom']);
        $this->assertNotSame('73282932000074', $facture->snapshot['emetteur']['siret']);
        $this->assertMatchesRegularExpression('/^ALO-'.date('Y').'-\d{4}$/', $facture->numero_facture);
        $this->assertNotNull($facture->verrouillee_at);
    }

    public function test_html_pdf_plateforme_mentions_et_pas_le_siret_prestataire(): void
    {
        $facture = $this->emettreAbonnementEntreprise(
            nomPrestataire: 'Salon Prestataire Unique',
            siretPrestataire: '73282932000074',
        );

        $doc = $facture->snapshot;
        $doc['logo_base64'] = 'data:image/png;base64,AAAA';

        $html = html_entity_decode(
            view('factures.pdf', ['doc' => $doc])->render(),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        $this->assertStringContainsString('Lucas Espinar', $html);
        $this->assertStringContainsString('Allotata', $html);
        $this->assertStringContainsString('alt="Allotata"', $html);
        $this->assertStringContainsString('293 B', $html);
        $this->assertStringContainsString('SIRET 994 535 904 00019', $html);
        $this->assertStringContainsString('Facture Allotata — abonnement plateforme', $html);
        $this->assertStringNotContainsString('73282932000074', $html);
        $this->assertStringNotContainsString('SIRET 73282932000074', $html);
    }

    public function test_backfill_ne_renomme_pas_un_numero_deja_emis(): void
    {
        $user = User::factory()->create();
        $entreprise = Entreprise::factory()->create([
            'user_id' => $user->id,
            'nom' => 'Prestataire Ancien',
            'siret' => '73282932000074',
        ]);

        $facture = Facture::create([
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'type_facture' => 'abonnement_entreprise',
            'numero_facture' => 'FAC-2026-000042',
            'date_facture' => now(),
            'date_echeance' => now()->addDays(30),
            'montant_ht' => 29.9,
            'taux_tva' => 0,
            'montant_tva' => 0,
            'montant_ttc' => 29.9,
            'statut' => 'emise',
        ]);

        $snapshot = app(DocumentSnapshotService::class)->ensureFacture($facture);

        $this->assertSame('FAC-2026-000042', $facture->fresh()->numero_facture);
        $this->assertSame('99453590400019', $snapshot['emetteur']['siret']);
        $this->assertSame('Lucas Espinar', $snapshot['emetteur']['nom']);
    }

    public function test_idempotence_une_facture_par_periode(): void
    {
        $user = User::factory()->create();
        $entreprise = Entreprise::factory()->create(['user_id' => $user->id]);
        $subscription = EntrepriseSubscription::query()->create([
            'entreprise_id' => $entreprise->id,
            'type' => 'site_web',
            'name' => 'site_web',
            'est_manuel' => true,
            'montant' => 49,
            'type_renouvellement' => 'mensuel',
            'actif_jusqu' => now()->addMonth(),
        ]);

        $a = Facture::generateFromManualEntrepriseSubscription($subscription);
        $b = Facture::generateFromManualEntrepriseSubscription($subscription);

        $this->assertNotNull($a);
        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, Facture::query()->where('entreprise_subscription_id', $subscription->id)->count());
    }

    public function test_abonnement_manuel_utilisateur_emetteur_plateforme(): void
    {
        $user = User::factory()->create([
            'abonnement_manuel' => true,
            'abonnement_manuel_montant' => 19.9,
            'abonnement_manuel_type_renouvellement' => 'mensuel',
        ]);
        Entreprise::factory()->create([
            'user_id' => $user->id,
            'nom' => 'Mon Salon',
            'siret' => '73282932000074',
        ]);

        $facture = Facture::generateFromManualSubscription($user);

        $this->assertNotNull($facture);
        $this->assertSame('abonnement_manuel', $facture->type_facture);
        $this->assertSame('99453590400019', $facture->snapshot['emetteur']['siret']);
        $this->assertSame('Lucas Espinar', $facture->snapshot['emetteur']['nom']);
        $this->assertSame($user->name, $facture->snapshot['client']['nom']);
    }

    private function emettreAbonnementEntreprise(string $nomPrestataire, string $siretPrestataire): Facture
    {
        $user = User::factory()->create();
        $entreprise = Entreprise::factory()->create([
            'user_id' => $user->id,
            'nom' => $nomPrestataire,
            'siret' => $siretPrestataire,
        ]);
        $subscription = EntrepriseSubscription::query()->create([
            'entreprise_id' => $entreprise->id,
            'type' => 'site_web',
            'name' => 'site_web',
            'est_manuel' => true,
            'montant' => 29.9,
            'type_renouvellement' => 'mensuel',
            'actif_jusqu' => now()->addMonth(),
        ]);

        $facture = Facture::generateFromManualEntrepriseSubscription($subscription);
        $this->assertNotNull($facture);

        return $facture->fresh();
    }
}
