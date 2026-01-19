<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_type',
        'ip_address',
        'user_agent',
        'location',
        'metadata',
        'severity',
        'is_suspicious',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'is_suspicious' => 'boolean',
        ];
    }

    /**
     * Relation : Un log appartient à un utilisateur (optionnel)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Crée un log de sécurité
     */
    public static function log(
        ?int $userId,
        string $eventType,
        string $ipAddress,
        ?string $userAgent = null,
        ?string $location = null,
        array $metadata = [],
        string $severity = 'low',
        bool $isSuspicious = false,
        ?string $description = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'event_type' => $eventType,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'location' => $location,
            'metadata' => $metadata,
            'severity' => $severity,
            'is_suspicious' => $isSuspicious,
            'description' => $description,
        ]);
    }
}
