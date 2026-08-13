<?php

namespace App\Services\Facturation;

use App\Models\DocumentSequence;
use Illuminate\Support\Facades\DB;

class DocumentSequenceService
{
    public const TYPE_FACTURE = 'facture';

    public const TYPE_DEVIS = 'devis';

    /**
     * Attribue le prochain numéro chronologique (sans trou) pour l'entreprise et l'année.
     */
    public function next(int $entrepriseId, string $type, ?int $annee = null): string
    {
        $annee = $annee ?? (int) date('Y');
        $prefix = $type === self::TYPE_DEVIS ? 'DEV' : 'FAC';

        $numero = DB::transaction(function () use ($entrepriseId, $type, $annee) {
            DocumentSequence::query()->firstOrCreate(
                [
                    'entreprise_id' => $entrepriseId,
                    'type' => $type,
                    'annee' => $annee,
                ],
                ['dernier_numero' => 0]
            );

            $sequence = DocumentSequence::query()
                ->where('entreprise_id', $entrepriseId)
                ->where('type', $type)
                ->where('annee', $annee)
                ->lockForUpdate()
                ->firstOrFail();

            $sequence->dernier_numero++;
            $sequence->save();

            return $sequence->dernier_numero;
        });

        return $prefix.'-'.$annee.'-'.str_pad((string) $numero, 4, '0', STR_PAD_LEFT);
    }
}
