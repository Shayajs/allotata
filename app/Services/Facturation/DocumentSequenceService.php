<?php

namespace App\Services\Facturation;

use App\Models\DocumentSequence;
use Illuminate\Support\Facades\DB;

class DocumentSequenceService
{
    public const TYPE_FACTURE = 'facture';

    public const TYPE_DEVIS = 'devis';

    public const TYPE_FACTURE_PLATEFORME = 'facture_plateforme';

    /**
     * Attribue le prochain numéro chronologique (sans trou) pour l'entreprise et l'année.
     */
    public function next(int $entrepriseId, string $type, ?int $annee = null): string
    {
        $annee = $annee ?? (int) date('Y');
        $prefix = $type === self::TYPE_DEVIS ? 'DEV' : 'FAC';
        $numero = $this->incrementer($entrepriseId, $type, $annee);

        return $prefix.'-'.$annee.'-'.str_pad((string) $numero, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Séquence Allotata (Lucas Espinar EI) : ALO-YYYY-NNNN.
     */
    public function nextPlateforme(?int $annee = null): string
    {
        $annee = $annee ?? (int) date('Y');
        $numero = $this->incrementer(null, self::TYPE_FACTURE_PLATEFORME, $annee);

        return 'ALO-'.$annee.'-'.str_pad((string) $numero, 4, '0', STR_PAD_LEFT);
    }

    private function incrementer(?int $entrepriseId, string $type, int $annee): int
    {
        $cle = ($entrepriseId === null ? 'p' : (string) $entrepriseId).'|'.$type.'|'.$annee;

        return (int) DB::transaction(function () use ($entrepriseId, $type, $annee, $cle) {
            DocumentSequence::query()->firstOrCreate(
                ['cle' => $cle],
                [
                    'entreprise_id' => $entrepriseId,
                    'type' => $type,
                    'annee' => $annee,
                    'dernier_numero' => 0,
                ]
            );

            $sequence = DocumentSequence::query()
                ->where('cle', $cle)
                ->lockForUpdate()
                ->firstOrFail();

            $sequence->dernier_numero++;
            $sequence->save();

            return $sequence->dernier_numero;
        });
    }
}
