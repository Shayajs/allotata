<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProduitImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'produit_id',
        'image_path',
        'est_couverture',
        'ordre',
    ];

    protected function casts(): array
    {
        return [
            'est_couverture' => 'boolean',
            'ordre' => 'integer',
        ];
    }

    /**
     * Relation : Une image appartient à un produit
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }
}
