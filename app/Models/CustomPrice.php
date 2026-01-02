<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'entreprise_id',
        'subscription_type',
        'stripe_price_id',
        'amount',
        'currency',
        'notes',
        'created_by',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relation : Un prix personnalisé appartient à un utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : Un prix personnalisé appartient à une entreprise
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /**
     * Relation : Créé par un administrateur
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Vérifie si le prix personnalisé est encore valide
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Récupère le prix personnalisé pour un utilisateur et un type d'abonnement
     */
    public static function getForUser(User $user, string $subscriptionType): ?self
    {
        return self::where('user_id', $user->id)
            ->where('subscription_type', $subscriptionType)
            ->where('is_active', true)
            ->where(function($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    /**
     * Récupère le prix personnalisé pour une entreprise et un type d'abonnement
     */
    public static function getForEntreprise(Entreprise $entreprise, string $subscriptionType): ?self
    {
        return self::where('entreprise_id', $entreprise->id)
            ->where('subscription_type', $subscriptionType)
            ->where('is_active', true)
            ->where(function($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->first();
    }
}
