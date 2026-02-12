<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoteCursor extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'note_id',
        'user_id',
        'position',
        'selection_start',
        'selection_end',
        'updated_at',
    ];

    protected $casts = [
        'position' => 'integer',
        'selection_start' => 'integer',
        'selection_end' => 'integer',
        'updated_at' => 'datetime',
    ];

    /**
     * Note associée
     */
    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class, 'note_id');
    }

    /**
     * Utilisateur propriétaire du curseur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
