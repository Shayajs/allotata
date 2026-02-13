<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GdprRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'requested_by',
        'type',
        'status',
        'reason',
        'export_path',
        'scheduled_at',
        'processed_at',
        'expires_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'processed_at' => 'datetime',
            'expires_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    // --- Constantes ---

    const TYPE_EXPORT = 'export';
    const TYPE_DELETION = 'deletion';

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_FAILED = 'failed';

    // --- Relations ---

    /**
     * L'utilisateur concerné par la demande
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * L'admin qui a initié la demande (null si self-service)
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    // --- Helpers ---

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isExport(): bool
    {
        return $this->type === self::TYPE_EXPORT;
    }

    public function isDeletion(): bool
    {
        return $this->type === self::TYPE_DELETION;
    }

    /**
     * Vérifie si la demande peut être annulée (en attente + pas encore traitée)
     */
    public function canBeCancelled(): bool
    {
        return $this->isPending() && (!$this->scheduled_at || $this->scheduled_at->isFuture());
    }

    /**
     * Vérifie si le lien de téléchargement est encore valide
     */
    public function isDownloadAvailable(): bool
    {
        return $this->isExport()
            && $this->isCompleted()
            && $this->export_path
            && $this->expires_at
            && $this->expires_at->isFuture();
    }

    /**
     * Nombre de jours restants avant exécution de la suppression
     */
    public function daysUntilExecution(): ?int
    {
        if (!$this->isDeletion() || !$this->isPending() || !$this->scheduled_at) {
            return null;
        }

        return max(0, (int) now()->diffInDays($this->scheduled_at, false));
    }

    /**
     * Vérifie si la demande de suppression est prête à être exécutée
     */
    public function isReadyForExecution(): bool
    {
        return $this->isDeletion()
            && $this->isPending()
            && $this->scheduled_at
            && $this->scheduled_at->isPast();
    }

    // --- Scopes ---

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeReadyForExecution($query)
    {
        return $query->where('type', self::TYPE_DELETION)
            ->where('status', self::STATUS_PENDING)
            ->where('scheduled_at', '<=', now());
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Récupère le libellé du type
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_EXPORT => 'Export des données',
            self::TYPE_DELETION => 'Suppression du compte',
            default => $this->type,
        };
    }

    /**
     * Récupère le libellé du statut
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_PROCESSING => 'En cours',
            self::STATUS_COMPLETED => 'Terminée',
            self::STATUS_CANCELLED => 'Annulée',
            self::STATUS_FAILED => 'Échouée',
            default => $this->status,
        };
    }
}
