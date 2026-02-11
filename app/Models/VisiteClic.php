<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisiteClic extends Model
{
    use HasFactory;

    protected $table = 'visite_clics';

    protected $fillable = [
        'visite_id',
        'type',
        'item_id',
        'item_nom',
        'clicked_at',
    ];

    protected function casts(): array
    {
        return [
            'clicked_at' => 'datetime',
        ];
    }

    /**
     * Relation : Un clic appartient à une visite
     */
    public function visite(): BelongsTo
    {
        return $this->belongsTo(EntrepriseVisite::class, 'visite_id');
    }
}
