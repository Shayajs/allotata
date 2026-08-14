<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSequence extends Model
{
    protected $fillable = [
        'entreprise_id',
        'type',
        'annee',
        'cle',
        'dernier_numero',
    ];

    protected function casts(): array
    {
        return [
            'annee' => 'integer',
            'dernier_numero' => 'integer',
        ];
    }

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }
}
