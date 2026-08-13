<?php

namespace App\Services\Facturation;

use App\Exceptions\BillingProfileIncompleteException;
use App\Exceptions\ImmutableDocumentException;
use App\Mail\InvoiceEmail;
use App\Models\Facture;
use App\Models\Reservation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class FactureEmissionService
{
    public function __construct(
        private BillingProfileService $billing,
        private DocumentSequenceService $sequences,
        private DocumentSnapshotService $snapshots,
        private PdfDocumentRenderer $pdf,
    ) {}

    public function emettrePourReservation(Reservation $reservation): ?Facture
    {
        $reservation->loadMissing(['entreprise', 'user', 'typeService', 'facture']);

        if ($reservation->facture) {
            return $reservation->facture;
        }

        if ($reservation->aDejaFacture()) {
            return $reservation->facturesGroupes()->first();
        }

        if (! $reservation->prix || $reservation->prix <= 0) {
            Log::warning("Impossible d'émettre une facture pour la réservation #{$reservation->id} : prix invalide.");

            return null;
        }

        $entreprise = $reservation->entreprise;
        $this->assertProfilComplet($entreprise);

        $montantHT = (float) $reservation->prix;
        $assujetti = (bool) $entreprise->assujetti_tva;
        $tauxTVA = $assujetti ? (float) ($entreprise->taux_tva_defaut ?? 20) : 0.0;
        $montantTVA = round($montantHT * ($tauxTVA / 100), 2);
        $montantTTC = round($montantHT + $montantTVA, 2);

        $dejaPayee = (bool) $reservation->est_paye;
        $dateFacture = now();

        $facture = Facture::create([
            'reservation_id' => $reservation->id,
            'entreprise_id' => $reservation->entreprise_id,
            'user_id' => $reservation->user_id,
            'type_facture' => 'reservation',
            'numero_facture' => $this->sequences->next($entreprise->id, DocumentSequenceService::TYPE_FACTURE),
            'date_facture' => $dateFacture,
            'date_echeance' => $dateFacture->copy()->addDays(30),
            'date_paiement' => $dejaPayee ? ($reservation->date_paiement ?? $dateFacture) : null,
            'montant_ht' => $montantHT,
            'taux_tva' => $tauxTVA,
            'montant_tva' => $montantTVA,
            'montant_ttc' => $montantTTC,
            'statut' => $dejaPayee ? 'payee' : 'emise',
            'verrouillee_at' => now(),
        ]);

        $snapshot = $this->snapshots->pourFacture(
            $facture,
            $entreprise,
            [$reservation],
            $reservation->user,
            $reservation,
        );
        $facture->forceFill(['snapshot' => $snapshot])->saveQuietly();

        if ($dejaPayee) {
            $this->envoyerAuClient($facture->fresh(['entreprise', 'user']));
        }

        return $facture->fresh();
    }

    public function acquitter(Facture $facture, $datePaiement = null): Facture
    {
        if ($facture->statut === 'payee' && $facture->date_paiement) {
            return $facture;
        }

        if ($facture->statut === 'annulee') {
            throw new ImmutableDocumentException('Une facture annulée ne peut pas être acquittée.');
        }

        $date = $datePaiement ? \Carbon\Carbon::parse($datePaiement) : now();

        $snapshot = is_array($facture->snapshot) ? $facture->snapshot : $this->snapshots->ensureFacture($facture);
        $snapshot['paiement'] = [
            'acquittee' => true,
            'date_paiement' => $date->format('d/m/Y'),
        ];

        $facture->forceFill([
            'statut' => 'payee',
            'date_paiement' => $date,
            'snapshot' => $snapshot,
            'verrouillee_at' => $facture->verrouillee_at ?? now(),
        ])->saveQuietly();

        $this->envoyerAuClient($facture->fresh(['entreprise', 'user']));

        return $facture->fresh();
    }

    public function assertProfilComplet($entreprise): void
    {
        $manquants = $this->billing->champsManquants($entreprise);
        if ($manquants !== []) {
            throw new BillingProfileIncompleteException($manquants);
        }
    }

    public function envoyerAuClient(Facture $facture): void
    {
        $email = $facture->user?->email
            ?? $facture->reservation?->email_client
            ?? $facture->snapshot['client']['email'] ?? null;

        if (! $email) {
            return;
        }

        try {
            $pdf = $this->pdf->outputFacture($facture);
            Mail::to($email)->send(new InvoiceEmail($facture, true, $pdf));
        } catch (\Throwable $e) {
            Log::error('Envoi de la facture PDF impossible : '.$e->getMessage(), [
                'facture_id' => $facture->id,
            ]);
        }
    }
}
