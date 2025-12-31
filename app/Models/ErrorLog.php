<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErrorLog extends Model
{
    protected $fillable = [
        'level',
        'message',
        'context',
        'file',
        'line',
        'trace',
        'url',
        'method',
        'ip',
        'user_agent',
        'user_id',
        'est_vue',
        'vu_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'est_vue' => 'boolean',
            'vu_at' => 'datetime',
        ];
    }

    /**
     * Relation : Une erreur peut être associée à un utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Marquer l'erreur comme vue
     */
    public function marquerCommeVue(): void
    {
        $this->update([
            'est_vue' => true,
            'vu_at' => now(),
        ]);
    }
}
