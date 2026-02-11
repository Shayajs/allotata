<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AdminConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'est_groupe',
        'dernier_message_at',
    ];

    protected function casts(): array
    {
        return [
            'est_groupe' => 'boolean',
            'dernier_message_at' => 'datetime',
        ];
    }

    /**
     * Relation : Une conversation a plusieurs messages
     */
    public function messages(): HasMany
    {
        return $this->hasMany(AdminMessage::class, 'conversation_id')->orderBy('created_at', 'asc');
    }

    /**
     * Relation : Une conversation a plusieurs membres
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'admin_conversation_members', 'conversation_id', 'user_id')
            ->withPivot('dernier_vu_at')
            ->withTimestamps();
    }

    /**
     * Relation : Une conversation a plusieurs indicateurs de frappe
     */
    public function typingIndicators(): HasMany
    {
        return $this->hasMany(AdminTypingIndicator::class, 'conversation_id');
    }

    /**
     * Dernier message de la conversation
     */
    public function dernierMessage()
    {
        return $this->hasOne(AdminMessage::class, 'conversation_id')->latestOfMany();
    }

    /**
     * Vérifie si un utilisateur est membre de cette conversation
     */
    public function isMember(int $userId): bool
    {
        return $this->members()->where('users.id', $userId)->exists();
    }

    /**
     * Ajouter un membre à la conversation
     */
    public function addMember(int $userId): void
    {
        if (!$this->isMember($userId)) {
            $this->members()->attach($userId, [
                'dernier_vu_at' => now(),
            ]);
        }
    }

    /**
     * Retirer un membre de la conversation
     */
    public function removeMember(int $userId): void
    {
        $this->members()->detach($userId);
    }
}
