<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EmailVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'hash',
        'expires_at',
        'is_used',
        'used_at',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_used' => 'boolean',
            'used_at' => 'datetime',
        ];
    }

    /**
     * Relation : Une vérification appartient à un utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vérifie si le hash est valide
     */
    public function isValid(): bool
    {
        return !$this->is_used && $this->expires_at->isFuture();
    }

    /**
     * Marque la vérification comme utilisée
     */
    public function markAsUsed(string $ipAddress = null): void
    {
        $this->update([
            'is_used' => true,
            'used_at' => now(),
            'ip_address' => $ipAddress,
        ]);
    }

    /**
     * Génère un nouveau hash de vérification pour un utilisateur
     */
    public static function generateHashForUser(int $userId): self
    {
        // Marquer toutes les anciennes vérifications comme expirées/invalides
        self::where('user_id', $userId)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->update(['is_used' => true]);

        // Créer un nouveau hash
        $hash = Str::random(64);
        
        return self::create([
            'user_id' => $userId,
            'hash' => $hash,
            'expires_at' => now()->addDays(7), // Valide 7 jours
        ]);
    }

    /**
     * Nettoie les hash expirés et utilisés (peut être appelé par une commande schedule)
     */
    public static function cleanup(): void
    {
        self::where(function($query) {
            $query->where('is_used', true)
                  ->orWhere('expires_at', '<', now());
        })
        ->where('created_at', '<', now()->subDays(30)) // Garder 30 jours pour audit
        ->delete();
    }
}
