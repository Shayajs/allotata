<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Note extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'titre',
        'contenu_markdown',
        'created_by',
        'updated_by',
    ];

    /**
     * Créateur de la note
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Dernier utilisateur à avoir modifié la note
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Collaborateurs de la note
     */
    public function collaborators(): HasMany
    {
        return $this->hasMany(NoteCollaborator::class, 'note_id');
    }

    /**
     * Curseurs des utilisateurs
     */
    public function cursors(): HasMany
    {
        return $this->hasMany(NoteCursor::class, 'note_id');
    }
}
