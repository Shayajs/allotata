<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminTypingIndicator extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Relation : Un indicateur appartient à une conversation
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AdminConversation::class, 'conversation_id');
    }

    /**
     * Relation : Un indicateur appartient à un utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mettre à jour ou créer l'indicateur de frappe
     */
    public static function updateTyping(int $conversationId, int $userId): void
    {
        static::updateOrCreate(
            [
                'conversation_id' => $conversationId,
                'user_id' => $userId,
            ],
            [
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Récupère les utilisateurs en train d'écrire (updated_at < 3 secondes)
     */
    public static function getTypingUsers(int $conversationId, int $excludeUserId = null)
    {
        $query = static::where('conversation_id', $conversationId)
            ->where('updated_at', '>', now()->subSeconds(3))
            ->with('user');

        if ($excludeUserId) {
            $query->where('user_id', '!=', $excludeUserId);
        }

        return $query->get();
    }

    /**
     * Nettoyer les indicateurs anciens (> 3 secondes)
     */
    public static function cleanup(): void
    {
        static::where('updated_at', '<', now()->subSeconds(3))->delete();
    }
}
