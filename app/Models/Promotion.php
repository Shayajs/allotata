<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'produit_id',
        'prix_promotion',
        'date_debut',
        'date_fin',
        'est_active',
    ];

    protected function casts(): array
    {
        return [
            'prix_promotion' => 'decimal:2',
            'date_debut' => 'datetime',
            'date_fin' => 'datetime',
            'est_active' => 'boolean',
        ];
    }

    /**
     * Relation : Une promotion appartient à un produit
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }

    /**
     * Vérifier si la promotion est actuellement active
     */
    public function estActiveMaintenant(): bool
    {
        if (!$this->est_active) {
            return false;
        }

        $now = now();
        return $now >= $this->date_debut && $now <= $this->date_fin;
    }

    /**
     * Scope pour les promotions actives
     */
    public function scopeActive($query)
    {
        $now = now();
        return $query->where('est_active', true)
            ->where('date_debut', '<=', $now)
            ->where('date_fin', '>=', $now);
    }
}
