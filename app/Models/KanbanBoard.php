<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KanbanBoard extends Model
{
    protected $fillable = [
        'nom',
        'description',
    ];

    /**
     * Colonnes du board
     */
    public function columns(): HasMany
    {
        return $this->hasMany(KanbanColumn::class, 'board_id')->orderBy('ordre');
    }

    /**
     * Cartes du board
     */
    public function cards(): HasMany
    {
        return $this->hasMany(KanbanCard::class, 'board_id');
    }
}
