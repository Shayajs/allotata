<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KanbanColumn extends Model
{
    protected $fillable = [
        'board_id',
        'nom',
        'ordre',
        'couleur',
    ];

    protected $casts = [
        'ordre' => 'integer',
    ];

    /**
     * Board parent
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(KanbanBoard::class, 'board_id');
    }

    /**
     * Cartes de la colonne
     */
    public function cards(): HasMany
    {
        return $this->hasMany(KanbanCard::class, 'column_id')->orderBy('ordre');
    }
}
