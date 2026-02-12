<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class AccountLockout extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'failed_attempts',
        'locked_until',
        'last_failed_attempt',
        'locking_ip_address',
        'is_locked',
        'unlocked_at',
    ];

    protected function casts(): array
    {
        return [
            'failed_attempts' => 'integer',
            'locked_until' => 'datetime',
            'last_failed_attempt' => 'datetime',
            'is_locked' => 'boolean',
            'unlocked_at' => 'datetime',
        ];
    }

    /**
     * Relation : Un blocage appartient à un utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vérifie si le compte est actuellement verrouillé
     */
    public function isCurrentlyLocked(): bool
    {
        if (!$this->is_locked) {
            return false;
        }

        if ($this->locked_until && $this->locked_until->isPast()) {
            // Le verrouillage a expiré
            $this->unlock();
            return false;
        }

        return true;
    }

    /**
     * Verrouille le compte pour 5 minutes
     */
    public function lock(string $ipAddress): void
    {
        $this->update([
            'is_locked' => true,
            'locked_until' => now()->addMinutes(5),
            'locking_ip_address' => $ipAddress,
        ]);
    }

    /**
     * Déverrouille le compte
     */
    public function unlock(): void
    {
        $this->update([
            'is_locked' => false,
            'failed_attempts' => 0,
            'locked_until' => null,
            'unlocked_at' => now(),
        ]);
    }

    /**
     * Incrémente le nombre de tentatives échouées
     */
    public function incrementFailedAttempts(string $ipAddress): void
    {
        $this->increment('failed_attempts');
        $this->update([
            'last_failed_attempt' => now(),
            'locking_ip_address' => $ipAddress,
        ]);

        // Si 5 tentatives ou plus, verrouiller
        if ($this->failed_attempts >= 5) {
            $this->lock($ipAddress);
        }
    }
}
