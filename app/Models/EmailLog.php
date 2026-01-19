<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'recipient_email',
        'subject',
        'type',
        'status',
        'error_message',
        'content_preview',
        'ip_address',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    /**
     * Relation : Un log email peut être associé à un utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vérifie si l'email a été envoyé avec succès
     */
    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    /**
     * Vérifie si l'envoi a échoué
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }
}
