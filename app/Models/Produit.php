<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Produit extends Model
{
    use HasFactory;

    protected $fillable = [
        'entreprise_id',
        'nom',
        'description',
        'prix',
        'est_actif',
        'gestion_stock',
    ];

    protected function casts(): array
    {
        return [
            'prix' => 'decimal:2',
            'est_actif' => 'boolean',
        ];
    }

    /**
     * Relation : Un produit appartient à une entreprise
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /**
     * Relation : Un produit peut avoir un stock
     */
    public function stock(): HasOne
    {
        return $this->hasOne(Stock::class);
    }

    /**
     * Relation : Un produit peut avoir plusieurs images
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProduitImage::class)->orderBy('ordre');
    }

    /**
     * Relation : Récupérer l'image de couverture
     */
    public function imageCouverture(): HasOne
    {
        return $this->hasOne(ProduitImage::class)->where('est_couverture', true);
    }

    /**
     * Relation : Un produit peut avoir plusieurs promotions
     */
    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    /**
     * Récupérer la promotion active actuelle
     */
    public function promotionActive(): HasOne
    {
        $now = now();
        return $this->hasOne(Promotion::class)
            ->where('est_active', true)
            ->where('date_debut', '<=', $now)
            ->where('date_fin', '>=', $now);
    }

    /**
     * Vérifier si le produit a une promotion active
     */
    public function aPromotionActive(): bool
    {
        return $this->promotionActive()->exists();
    }

    /**
     * Obtenir le prix actuel (avec promotion si applicable)
     */
    public function getPrixActuelAttribute(): float
    {
        $promotion = $this->promotionActive()->first();
        return $promotion ? $promotion->prix_promotion : $this->prix;
    }

    /**
     * Vérifier si le produit est disponible
     */
    public function estDisponible(): bool
    {
        if (!$this->est_actif) {
            return false;
        }

        // Si gestion immédiate, vérifier le stock
        if ($this->gestion_stock === 'disponible_immediatement') {
            $stock = $this->stock;
            return $stock && $stock->quantite_disponible > 0;
        }

        // Si en attente de commandes, toujours disponible
        return true;
    }
}
