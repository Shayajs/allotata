<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'contenu',
        'image',
        'est_lu',
        'type_message', // 'texte', 'proposition_rdv'
        'proposition_rdv_id',
    ];

    protected function casts(): array
    {
        return [
            'est_lu' => 'boolean',
        ];
    }

    /**
     * Relation : Un message appartient à une conversation
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Relation : Un message appartient à un utilisateur (expéditeur)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vérifie si le message contient une image
     */
    public function aImage(): bool
    {
        return !empty($this->image);
    }

    /**
     * Relation : Un message peut avoir une proposition de rendez-vous (via message_id dans proposition_rendez_vouses)
     */
    public function propositionRendezVous()
    {
        return $this->hasOne(PropositionRendezVous::class, 'message_id');
    }

    /**
     * Relation : Un message peut référencer une proposition de rendez-vous (via proposition_rdv_id)
     */
    public function propositionRdv()
    {
        return $this->belongsTo(PropositionRendezVous::class, 'proposition_rdv_id');
    }

    /**
     * Vérifie si le message est une proposition de rendez-vous
     */
    public function estPropositionRendezVous(): bool
    {
        return $this->type_message === 'proposition_rdv' || !empty($this->proposition_rdv_id);
    }
}
