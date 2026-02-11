<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KanbanCard extends Model
{
    protected $fillable = [
        'column_id',
        'board_id',
        'titre',
        'description',
        'type',
        'reference_id',
        'assignee_id',
        'priorite',
        'ordre',
        'couleur',
        'due_date',
        'created_by',
    ];

    protected $casts = [
        'ordre' => 'integer',
        'reference_id' => 'integer',
        'due_date' => 'date',
    ];

    /**
     * Colonne parente
     */
    public function column(): BelongsTo
    {
        return $this->belongsTo(KanbanColumn::class, 'column_id');
    }

    /**
     * Board parent
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(KanbanBoard::class, 'board_id');
    }

    /**
     * Utilisateur assigné
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * Créateur de la carte
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
