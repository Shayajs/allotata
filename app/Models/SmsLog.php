<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'destinataire',
        'message',
        'statut',
        'provider',
        'provider_message_id',
        'error_message',
        'ip_address',
        'user_id',
        'reservation_id',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    /**
     * Relation : Un log SMS peut être associé à un utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : Un log SMS peut être associé à une réservation
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Vérifie si le SMS a été envoyé avec succès
     */
    public function estEnvoye(): bool
    {
        return $this->statut === 'envoye';
    }

    /**
     * Vérifie si l'envoi a échoué
     */
    public function aEchoue(): bool
    {
        return $this->statut === 'echec';
    }
}
