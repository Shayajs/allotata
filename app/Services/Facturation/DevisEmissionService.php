<?php

namespace App\Services\Facturation;

use App\Exceptions\BillingProfileIncompleteException;
use App\Models\Devis;

class DevisEmissionService
{
    public function __construct(
        private BillingProfileService $billing,
        private DocumentSequenceService $sequences,
        private DocumentSnapshotService $snapshots,
    ) {}

    /**
     * Fige le devis à l'envoi de la proposition (numéro + snapshot).
     */
    public function figerProposition(Devis $devis): Devis
    {
        $devis->loadMissing(['entreprise', 'user', 'typeService']);
        $entreprise = $devis->entreprise;

        $manquants = $this->billing->champsManquants($entreprise);
        if ($manquants !== []) {
            throw new BillingProfileIncompleteException($manquants);
        }

        if (! $devis->numero_devis) {
            $devis->numero_devis = $this->sequences->next(
                $entreprise->id,
                DocumentSequenceService::TYPE_DEVIS
            );
        }

        if (! $devis->date_validite) {
            $devis->date_validite = now()->addDays(30)->toDateString();
        }

        $devis->verrouille_at = $devis->verrouille_at ?? now();
        $devis->save();

        $snapshot = $this->snapshots->pourDevis($devis->fresh(['entreprise', 'user', 'typeService']), $entreprise);
        $devis->forceFill(['snapshot' => $snapshot])->saveQuietly();

        return $devis->fresh();
    }
}
