<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class TrustedDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ip_address',
        'device_hash',
        'user_agent',
        'country_code',
        'location',
        'first_used_at',
        'last_used_at',
        'usage_count',
    ];

    protected function casts(): array
    {
        return [
            'first_used_at' => 'datetime',
            'last_used_at' => 'datetime',
            'usage_count' => 'integer',
        ];
    }

    /**
     * Relation : Un périphérique approuvé appartient à un utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Génère un hash pour identifier le périphérique basé sur le user agent
     */
    public static function generateDeviceHash(string $userAgent): string
    {
        // Normaliser le user agent pour éviter les variations mineures
        $normalized = self::normalizeUserAgent($userAgent);
        return hash('sha256', $normalized);
    }

    /**
     * Normalise le user agent pour extraire les caractéristiques principales du périphérique
     */
    private static function normalizeUserAgent(string $userAgent): string
    {
        // Extraire les informations principales : navigateur, OS, appareil
        $parts = [];
        
        // Navigateur
        if (preg_match('/(Chrome|Firefox|Safari|Edge|Opera|Brave)[\/\s]/i', $userAgent, $matches)) {
            $parts[] = strtolower($matches[1]);
        }
        
        // OS
        if (preg_match('/(Windows|Mac OS X|Linux|Android|iOS|iPad|iPhone)/i', $userAgent, $matches)) {
            $os = strtolower($matches[1]);
            // Normaliser les variantes
            if (str_contains($os, 'mac')) $os = 'macos';
            if (str_contains($os, 'iphone') || str_contains($os, 'ipad')) $os = 'ios';
            $parts[] = $os;
        }
        
        // Type d'appareil (mobile/desktop)
        if (preg_match('/(Mobile|Tablet|iPad|iPhone|Android)/i', $userAgent)) {
            $parts[] = 'mobile';
        } else {
            $parts[] = 'desktop';
        }
        
        return implode('|', $parts);
    }

    /**
     * Marque un périphérique comme approuvé (utilisé après vérification A2F)
     */
    public static function markAsTrusted(
        int $userId,
        string $ipAddress,
        string $userAgent,
        ?string $countryCode = null,
        ?string $location = null
    ): self {
        $deviceHash = self::generateDeviceHash($userAgent);
        
        // Chercher si ce périphérique/IP existe déjà
        $trusted = self::where('user_id', $userId)
            ->where('ip_address', $ipAddress)
            ->where('device_hash', $deviceHash)
            ->first();

        if ($trusted) {
            // Mettre à jour
            $trusted->update([
                'last_used_at' => now(),
                'usage_count' => $trusted->usage_count + 1,
                'user_agent' => $userAgent, // Mettre à jour si le user agent a changé
                'country_code' => $countryCode ?? $trusted->country_code,
                'location' => $location ?? $trusted->location,
            ]);
            return $trusted;
        }

        // Créer nouveau
        return self::create([
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'device_hash' => $deviceHash,
            'user_agent' => $userAgent,
            'country_code' => $countryCode,
            'location' => $location,
            'first_used_at' => now(),
            'last_used_at' => now(),
            'usage_count' => 1,
        ]);
    }

    /**
     * Vérifie si un périphérique/IP est approuvé
     */
    public static function isTrusted(int $userId, string $ipAddress, string $userAgent): bool
    {
        $deviceHash = self::generateDeviceHash($userAgent);
        
        return self::where('user_id', $userId)
            ->where(function($query) use ($ipAddress, $deviceHash) {
                // Vérifier si l'IP est connue OU le périphérique est connu
                $query->where('ip_address', $ipAddress)
                      ->orWhere('device_hash', $deviceHash);
            })
            ->exists();
    }

    /**
     * Vérifie si l'IP est connue (utilisée auparavant)
     */
    public static function isIpKnown(int $userId, string $ipAddress): bool
    {
        return self::where('user_id', $userId)
            ->where('ip_address', $ipAddress)
            ->exists();
    }

    /**
     * Vérifie si le périphérique est connu (utilisé auparavant)
     */
    public static function isDeviceKnown(int $userId, string $userAgent): bool
    {
        $deviceHash = self::generateDeviceHash($userAgent);
        
        return self::where('user_id', $userId)
            ->where('device_hash', $deviceHash)
            ->exists();
    }
}
