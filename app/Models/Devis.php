<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Devis extends Model
{
    use HasFactory;

    protected $fillable = [
        'entreprise_id',
        'user_id',
        'type_service_id',
        'description_besoin',
        'statut',
        'montant_propose',
        'type_structure_propose',
        'date_proposee',
        'duree_proposee_minutes',
        'notes_prestataire',
        'reservation_id',
        'nom_client',
        'email_client',
        'telephone_client',
    ];

    protected function casts(): array
    {
        return [
            'montant_propose' => 'decimal:2',
            'date_proposee' => 'datetime',
            'duree_proposee_minutes' => 'integer',
        ];
    }

    // ─── Relations ───

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeService(): BelongsTo
    {
        return $this->belongsTo(TypeService::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    // ─── Helpers de statut ───

    public function estEnAttente(): bool
    {
        return $this->statut === 'en_attente';
    }

    public function estPropose(): bool
    {
        return $this->statut === 'propose';
    }

    public function estAccepte(): bool
    {
        return $this->statut === 'accepte';
    }

    public function estRefuse(): bool
    {
        return $this->statut === 'refuse';
    }

    /**
     * Retourne le nom du client (inscrit ou non)
     */
    public function getNomClientCompletAttribute(): ?string
    {
        if ($this->nom_client) {
            return $this->nom_client;
        }
        return $this->user?->name;
    }

    /**
     * Retourne le libellé du statut
     */
    public function getStatutLibelleAttribute(): string
    {
        return match ($this->statut) {
            'en_attente' => 'En attente',
            'propose' => 'Proposé',
            'accepte' => 'Accepté',
            'refuse' => 'Refusé',
            default => $this->statut,
        };
    }

    /**
     * Retourne la couleur CSS du statut
     */
    public function getStatutCouleurAttribute(): string
    {
        return match ($this->statut) {
            'en_attente' => 'yellow',
            'propose' => 'blue',
            'accepte' => 'green',
            'refuse' => 'red',
            default => 'gray',
        };
    }
}
