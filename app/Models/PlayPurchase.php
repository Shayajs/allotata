<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayPurchase extends Model
{
    protected $fillable = [
        'user_id',
        'entreprise_id',
        'product_id',
        'grants',
        'purchase_token',
        'order_id',
        'package_name',
        'kind',
        'status',
        'expires_at',
        'acknowledged_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where(function (Builder $inner) {
                $inner->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        return $this->expires_at === null || $this->expires_at->isFuture();
    }
}
