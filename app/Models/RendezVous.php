<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RendezVous extends Model
{
    use HasFactory;

    protected $table = 'rendez_vous';

    protected $fillable = [
        'reservation_id',
        'date_heure',
        'duree_minutes',
        'titre',
        'notes',
        'statut',
        'membre_id',
        'lieu',
        'google_event_id',
    ];

    protected function casts(): array
    {
        return [
            'date_heure' => 'datetime',
            'duree_minutes' => 'integer',
            'statut' => 'string',
        ];
    }

    /**
     * Relation : Un rendez-vous appartient à une réservation
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Relation : Un rendez-vous peut être assigné à un membre
     */
    public function membre(): BelongsTo
    {
        return $this->belongsTo(EntrepriseMembre::class);
    }

    /**
     * Vérifie si le rendez-vous est confirmé
     */
    public function estConfirme(): bool
    {
        return $this->statut === 'confirmee';
    }

    /**
     * Vérifie si le rendez-vous est terminé
     */
    public function estTermine(): bool
    {
        return $this->statut === 'terminee';
    }

    /**
     * Vérifie si le rendez-vous est annulé
     */
    public function estAnnule(): bool
    {
        return $this->statut === 'annulee';
    }

    /**
     * Vérifie si le rendez-vous est en attente
     */
    public function estEnAttente(): bool
    {
        return $this->statut === 'en_attente';
    }
}
