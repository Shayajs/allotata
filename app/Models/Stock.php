<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'produit_id',
        'quantite_disponible',
        'quantite_minimum',
        'alerte_stock',
    ];

    protected function casts(): array
    {
        return [
            'quantite_disponible' => 'integer',
            'quantite_minimum' => 'integer',
            'alerte_stock' => 'boolean',
        ];
    }

    /**
     * Relation : Un stock appartient à un produit
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }

    /**
     * Vérifier si une alerte doit être déclenchée
     */
    public function doitAlerter(): bool
    {
        return $this->quantite_disponible <= $this->quantite_minimum;
    }

    /**
     * Réduire le stock
     */
    public function reduire(int $quantite): bool
    {
        if ($this->quantite_disponible >= $quantite) {
            $this->quantite_disponible -= $quantite;
            
            // Vérifier si on doit déclencher une alerte
            if ($this->doitAlerter()) {
                $this->alerte_stock = true;
            }
            
            return $this->save();
        }
        
        return false;
    }

    /**
     * Augmenter le stock
     */
    public function augmenter(int $quantite): bool
    {
        $this->quantite_disponible += $quantite;
        
        // Si on dépasse le minimum, désactiver l'alerte
        if ($this->quantite_disponible > $this->quantite_minimum) {
            $this->alerte_stock = false;
        }
        
        return $this->save();
    }
}
