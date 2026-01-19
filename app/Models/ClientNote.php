<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'entreprise_id',
        'user_id', // Client concerné
        'created_by_user_id', // Utilisateur qui a créé la note
        'note',
        'tags',
        'is_important',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'is_important' => 'boolean',
        ];
    }

    /**
     * Relation : Une note appartient à une entreprise
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /**
     * Relation : Une note concerne un client (User)
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relation : Une note est créée par un utilisateur
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
