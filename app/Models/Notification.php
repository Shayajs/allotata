<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'titre',
        'message',
        'lien',
        'est_lue',
        'lue_at',
        'donnees',
    ];

    protected $casts = [
        'est_lue' => 'boolean',
        'lue_at' => 'datetime',
        'donnees' => 'array',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Marquer comme lue
     */
    public function marquerCommeLue(): void
    {
        if (!$this->est_lue) {
            $this->update([
                'est_lue' => true,
                'lue_at' => now(),
            ]);
        }
    }

    /**
     * Créer une notification
     */
    public static function creer(
        int $userId,
        string $type,
        string $titre,
        string $message,
        ?string $lien = null,
        ?array $donnees = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'type' => $type,
            'titre' => $titre,
            'message' => $message,
            'lien' => $lien,
            'donnees' => $donnees,
        ]);
    }
}
