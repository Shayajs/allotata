<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'contenu',
        'type',
        'fichier',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'string',
        ];
    }

    /**
     * Relation : Un message appartient à une conversation
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AdminConversation::class, 'conversation_id');
    }

    /**
     * Relation : Un message appartient à un utilisateur (expéditeur)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : Un message a plusieurs réactions
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(AdminMessageReaction::class, 'message_id');
    }

    /**
     * Vérifie si le message contient un fichier
     */
    public function aFichier(): bool
    {
        return !empty($this->fichier);
    }

    /**
     * Vérifie si c'est une image
     */
    public function estImage(): bool
    {
        return $this->type === 'image';
    }

    /**
     * Vérifie si c'est une vidéo
     */
    public function estVideo(): bool
    {
        return $this->type === 'video';
    }

    /**
     * Récupère les réactions groupées par emoji
     */
    public function getReactionsGrouped()
    {
        return $this->reactions()
            ->selectRaw('emoji, COUNT(*) as count')
            ->groupBy('emoji')
            ->get()
            ->map(function ($reaction) {
                return [
                    'emoji' => $reaction->emoji,
                    'count' => $reaction->count,
                    'users' => $this->reactions()
                        ->where('emoji', $reaction->emoji)
                        ->with('user')
                        ->get()
                        ->pluck('user'),
                ];
            });
    }
}
