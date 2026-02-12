<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoteCollaborator extends Model
{
    protected $fillable = [
        'note_id',
        'user_id',
        'derniere_activite',
    ];

    protected $casts = [
        'derniere_activite' => 'datetime',
    ];

    /**
     * Note associée
     */
    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class, 'note_id');
    }

    /**
     * Utilisateur collaborateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
