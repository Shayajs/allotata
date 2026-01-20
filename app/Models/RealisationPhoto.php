<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealisationPhoto extends Model
{
    protected $fillable = [
        'entreprise_id',
        'avis_id',
        'service_avis_id',
        'produit_avis_id',
        'photo_path',
        'titre',
        'description',
        'ordre',
    ];

    protected $casts = [
        'ordre' => 'integer',
    ];

    /**
     * Relation avec l'entreprise
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /**
     * Relation avec l'avis
     */
    public function avis(): BelongsTo
    {
        return $this->belongsTo(Avis::class);
    }

    /**
     * Relation avec l'avis de service
     */
    public function serviceAvis(): BelongsTo
    {
        return $this->belongsTo(ServiceAvis::class);
    }

    /**
     * Relation avec l'avis de produit
     */
    public function produitAvis(): BelongsTo
    {
        return $this->belongsTo(ProduitAvis::class);
    }
}
