<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropositionRendezVous extends Model
{
    protected $fillable = [
        'conversation_id',
        'message_id',
        'user_id',
        'entreprise_id',
        'date_rdv',
        'heure_debut',
        'heure_fin',
        'duree_minutes',
        'prix_propose',
        'prix_final',
        'statut',
        'notes',
        'lieu',
        'reservation_id',
    ];

    protected function casts(): array
    {
        return [
            'date_rdv' => 'date',
            'heure_debut' => 'datetime',
            'heure_fin' => 'datetime',
            'duree_minutes' => 'integer',
            'prix_propose' => 'decimal:2',
            'prix_final' => 'decimal:2',
        ];
    }

    /**
     * Relation : Une proposition appartient à une conversation
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Relation : Une proposition appartient à un message
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Relation : Une proposition appartient à un utilisateur (client)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : Une proposition appartient à une entreprise
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /**
     * Relation : Une proposition peut avoir une réservation
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Vérifie si la proposition peut être négociée
     */
    public function peutEtreNegociee(): bool
    {
        return $this->entreprise->prix_negociables && $this->statut === 'proposee';
    }

    /**
     * Vérifie si la proposition est acceptée
     */
    public function estAcceptee(): bool
    {
        return $this->statut === 'acceptee';
    }
}
