<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'entreprise_id',
        'dernier_message_at',
        'est_archivee',
    ];

    protected function casts(): array
    {
        return [
            'dernier_message_at' => 'datetime',
            'est_archivee' => 'boolean',
        ];
    }

    /**
     * Relation : Une conversation appartient à un utilisateur (client)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : Une conversation appartient à une entreprise
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /**
     * Relation : Une conversation a plusieurs messages
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    /**
     * Dernier message de la conversation
     */
    public function dernierMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Compter les messages non lus pour un utilisateur
     */
    public function messagesNonLus($userId)
    {
        return $this->messages()
            ->where('user_id', '!=', $userId)
            ->where('est_lu', false)
            ->count();
    }

    /**
     * Relation : Une conversation peut avoir plusieurs propositions de rendez-vous
     */
    public function propositionsRendezVous()
    {
        return $this->hasMany(PropositionRendezVous::class)->orderBy('created_at', 'desc');
    }

    /**
     * Récupère la proposition de rendez-vous active (non acceptée, non refusée, non expirée)
     */
    public function propositionRendezVousActive()
    {
        return $this->propositionsRendezVous()
            ->whereIn('statut', ['proposee', 'negociee'])
            ->where('date_rdv', '>=', now()->toDateString())
            ->first();
    }
}
