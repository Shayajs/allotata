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
        'numero_devis',
        'snapshot',
        'date_validite',
        'verrouille_at',
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
            'snapshot' => 'array',
            'date_validite' => 'date',
            'verrouille_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Devis $devis) {
            if (! $devis->verrouille_at) {
                return;
            }

            $interdit = [
                'numero_devis', 'montant_propose', 'type_structure_propose',
                'date_proposee', 'duree_proposee_minutes', 'notes_prestataire',
                'entreprise_id', 'user_id', 'type_service_id', 'date_validite',
            ];
            foreach ($interdit as $champ) {
                if ($devis->isDirty($champ)) {
                    throw new \App\Exceptions\ImmutableDocumentException;
                }
            }

            if ($devis->isDirty('statut')) {
                $from = $devis->getOriginal('statut');
                $to = $devis->statut;
                $autorise = ($from === 'propose' && in_array($to, ['accepte', 'refuse'], true));
                if (! $autorise) {
                    throw new \App\Exceptions\ImmutableDocumentException;
                }
            }

            if ($devis->isDirty('snapshot')) {
                throw new \App\Exceptions\ImmutableDocumentException;
            }
        });
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

    public function estVerrouille(): bool
    {
        return $this->verrouille_at !== null || filled($this->numero_devis);
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
