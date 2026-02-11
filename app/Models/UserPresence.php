<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UserPresence extends Model
{
    use HasFactory;

    protected $table = 'user_presence';

    protected $fillable = [
        'user_id',
        'status',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
        ];
    }

    /**
     * Relation : Une présence appartient à un utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Détermine automatiquement le statut basé sur la dernière activité
     */
    public static function determineStatus(?Carbon $lastActivityAt): string
    {
        if (!$lastActivityAt) {
            return 'offline';
        }

        $now = now();
        $minutesSinceActivity = $now->diffInMinutes($lastActivityAt);

        if ($minutesSinceActivity < 2) {
            return 'online';
        } elseif ($minutesSinceActivity < 5) {
            return 'idle';
        } else {
            return 'offline';
        }
    }

    /**
     * Met à jour ou crée une entrée de présence pour un utilisateur
     */
    public static function updateOrCreateForUser(int $userId, ?Carbon $lastActivityAt = null): self
    {
        $lastActivityAt = $lastActivityAt ?? now();
        $status = self::determineStatus($lastActivityAt);

        return self::updateOrCreate(
            ['user_id' => $userId],
            [
                'status' => $status,
                'last_activity_at' => $lastActivityAt,
            ]
        );
    }

    /**
     * Marque un utilisateur comme en ligne
     */
    public static function markAsOnline(int $userId): self
    {
        return self::updateOrCreate(
            ['user_id' => $userId],
            [
                'status' => 'online',
                'last_activity_at' => now(),
            ]
        );
    }

    /**
     * Marque un utilisateur comme inactif
     */
    public static function markAsIdle(int $userId): self
    {
        $presence = self::where('user_id', $userId)->first();
        
        if ($presence) {
            $presence->update([
                'status' => 'idle',
            ]);
            return $presence;
        }

        return self::create([
            'user_id' => $userId,
            'status' => 'idle',
            'last_activity_at' => now()->subMinutes(3), // Simule une activité il y a 3 minutes
        ]);
    }

    /**
     * Marque un utilisateur comme déconnecté
     */
    public static function markAsOffline(int $userId): self
    {
        return self::updateOrCreate(
            ['user_id' => $userId],
            [
                'status' => 'offline',
                'last_activity_at' => now()->subMinutes(10), // Simule une activité il y a 10 minutes
            ]
        );
    }

    /**
     * Vérifie si l'utilisateur est en ligne
     */
    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    /**
     * Vérifie si l'utilisateur est inactif
     */
    public function isIdle(): bool
    {
        return $this->status === 'idle';
    }

    /**
     * Vérifie si l'utilisateur est déconnecté
     */
    public function isOffline(): bool
    {
        return $this->status === 'offline';
    }
}
