<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'admin_id',
        'action',
        'model_type',
        'model_id',
        'description',
        'changes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Créer un log d'activité
     */
    public static function log(string $action, string $description, ?Model $model = null, ?array $changes = null): self
    {
        return self::create([
            'admin_id' => auth()->id(),
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'description' => $description,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Obtenir le nom du modèle formaté
     */
    public function getModelNameAttribute(): string
    {
        if (!$this->model_type) {
            return '-';
        }
        
        $parts = explode('\\', $this->model_type);
        return end($parts);
    }

    /**
     * Couleurs par action
     */
    public function getActionColorAttribute(): string
    {
        return match($this->action) {
            'create' => 'green',
            'update' => 'blue',
            'delete' => 'red',
            'verify', 'validate' => 'green',
            'reject', 'unverify' => 'orange',
            'login' => 'purple',
            default => 'slate',
        };
    }

    /**
     * Icône par action
     */
    public function getActionIconAttribute(): string
    {
        return match($this->action) {
            'create' => '➕',
            'update' => '✏️',
            'delete' => '🗑️',
            'verify', 'validate' => '✅',
            'reject' => '❌',
            'unverify' => '↩️',
            'login' => '🔐',
            'export' => '📤',
            default => '📝',
        };
    }
}
