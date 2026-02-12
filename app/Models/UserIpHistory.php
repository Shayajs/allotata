<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserIpHistory extends Model
{
    use HasFactory;

    protected $table = 'user_ip_history';

    protected $fillable = [
        'user_id',
        'ip_address',
        'location',
        'country_code',
        'first_seen_at',
        'last_seen_at',
        'login_count',
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'login_count' => 'integer',
        ];
    }

    /**
     * Relation : Un historique d'IP appartient à un utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Enregistre ou met à jour une IP pour un utilisateur
     */
    public static function recordIp(int $userId, string $ipAddress, ?string $location = null, ?string $countryCode = null): self
    {
        $history = self::where('user_id', $userId)
            ->where('ip_address', $ipAddress)
            ->first();

        if ($history) {
            // Mettre à jour
            $history->update([
                'last_seen_at' => now(),
                'login_count' => $history->login_count + 1,
                'location' => $location ?? $history->location,
                'country_code' => $countryCode ?? $history->country_code,
            ]);
            return $history;
        }

        // Créer nouveau
        return self::create([
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'location' => $location,
            'country_code' => $countryCode,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'login_count' => 1,
        ]);
    }
}
