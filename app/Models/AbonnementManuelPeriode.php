<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AbonnementManuelPeriode extends Model
{
    public const PENDING = 'pending';

    public const CONFIRMED = 'confirmed';

    public const NOT_PAID = 'not_paid';

    public const MEMBER_DECLARED = 'member_declared_paid';

    protected $table = 'abonnement_manuel_periodes';

    protected $fillable = [
        'user_id',
        'echeance_at',
        'periode_debut',
        'periode_fin',
        'statut',
        'confirmed_at',
        'confirmed_by',
        'refused_at',
        'refused_by',
        'declared_at',
        'notified_due_at',
        'notified_overdue_at',
    ];

    protected function casts(): array
    {
        return [
            'echeance_at' => 'date',
            'periode_debut' => 'date',
            'periode_fin' => 'date',
            'confirmed_at' => 'datetime',
            'refused_at' => 'datetime',
            'declared_at' => 'datetime',
            'notified_due_at' => 'datetime',
            'notified_overdue_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function refusedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refused_by');
    }

    public function evenements(): HasMany
    {
        return $this->hasMany(AbonnementManuelEvenement::class, 'abonnement_manuel_periode_id');
    }

    public function scopeNeedingReview(Builder $query): Builder
    {
        return $query
            ->whereIn('statut', [self::PENDING, self::MEMBER_DECLARED])
            ->whereDate('echeance_at', '<=', now()->toDateString())
            ->whereHas('user', fn (Builder $user) => $user->withActiveManualPremium());
    }

    public function estOuverte(): bool
    {
        return in_array($this->statut, [self::PENDING, self::MEMBER_DECLARED], true);
    }

    public function libelleMois(): string
    {
        return $this->echeance_at->copy()->locale('fr')->translatedFormat('F Y');
    }

    public function libelleStatut(): string
    {
        return match ($this->statut) {
            self::PENDING => 'Paiement attendu',
            self::CONFIRMED => 'Confirmé',
            self::NOT_PAID => 'Paiement non réalisé',
            self::MEMBER_DECLARED => 'Déclaré par le membre',
            default => $this->statut,
        };
    }
}
