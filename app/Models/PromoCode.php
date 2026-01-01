<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoCode extends Model
{
    protected $fillable = [
        'code',
        'description',
        'type',
        'valeur',
        'usages_max',
        'usages_actuels',
        'duree_mois',
        'date_debut',
        'date_fin',
        'est_actif',
        'premier_abonnement_uniquement',
        'created_by',
    ];

    protected $casts = [
        'valeur' => 'decimal:2',
        'usages_max' => 'integer',
        'usages_actuels' => 'integer',
        'duree_mois' => 'integer',
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
        'est_actif' => 'boolean',
        'premier_abonnement_uniquement' => 'boolean',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Vérifier si le code est valide
     */
    public function isValid(?User $user = null): bool
    {
        // Vérifie si actif
        if (!$this->est_actif) {
            return false;
        }

        // Vérifie les dates
        if ($this->date_debut && $this->date_debut->isFuture()) {
            return false;
        }

        if ($this->date_fin && $this->date_fin->isPast()) {
            return false;
        }

        // Vérifie le nombre d'usages
        if ($this->usages_max !== null && $this->usages_actuels >= $this->usages_max) {
            return false;
        }

        // Vérifie si premier abonnement uniquement
        if ($user && $this->premier_abonnement_uniquement) {
            // TODO: Vérifier si l'utilisateur a déjà eu un abonnement
        }

        return true;
    }

    /**
     * Valider un code promo par son code
     */
    public static function validateCode(string $code, ?User $user = null): ?self
    {
        $promoCode = self::where('code', strtoupper($code))->first();

        if (!$promoCode || !$promoCode->isValid($user)) {
            return null;
        }

        return $promoCode;
    }

    /**
     * Utiliser le code (incrémenter le compteur)
     */
    public function use(): void
    {
        $this->increment('usages_actuels');
    }

    /**
     * Calculer la réduction
     */
    public function calculateDiscount(float $originalPrice): float
    {
        if ($this->type === 'pourcentage') {
            return $originalPrice * ($this->valeur / 100);
        }

        return min($this->valeur, $originalPrice);
    }

    /**
     * Obtenir le label formaté
     */
    public function getFormattedValueAttribute(): string
    {
        if ($this->type === 'pourcentage') {
            return $this->valeur . '%';
        }

        return number_format($this->valeur, 2) . '€';
    }

    /**
     * Usages restants
     */
    public function getUsagesRestantsAttribute(): ?int
    {
        if ($this->usages_max === null) {
            return null; // Illimité
        }

        return max(0, $this->usages_max - $this->usages_actuels);
    }
}
