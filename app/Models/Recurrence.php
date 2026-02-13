<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recurrence extends Model
{
    use HasFactory;

    protected $fillable = [
        'entreprise_id',
        'user_id',
        'type_service_id',
        'membre_id',
        'frequence',
        'intervalle_jours',
        'date_debut',
        'date_fin',
        'heure',
        'lieu',
        'notes',
        'prix_par_occurrence',
        'est_active',
        'nom_client',
        'email_client',
        'telephone_client',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'prix_par_occurrence' => 'decimal:2',
            'est_active' => 'boolean',
            'intervalle_jours' => 'integer',
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

    public function membre(): BelongsTo
    {
        return $this->belongsTo(EntrepriseMembre::class, 'membre_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class)->orderBy('date_reservation');
    }

    // ─── Helpers ───

    public function estActive(): bool
    {
        return $this->est_active;
    }

    /**
     * Nombre total d'occurrences prévues
     */
    public function getNombreOccurrencesAttribute(): int
    {
        return $this->reservations()->count();
    }

    /**
     * Nombre d'occurrences futures non annulées
     */
    public function getOccurrencesFuturesAttribute(): int
    {
        return $this->reservations()
            ->where('date_reservation', '>=', now())
            ->whereNotIn('statut', ['annulee'])
            ->count();
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
     * Retourne le libellé de la fréquence
     */
    public function getFrequenceLibelleAttribute(): string
    {
        return match ($this->frequence) {
            'hebdomadaire' => 'Chaque semaine',
            'bimensuel' => 'Toutes les 2 semaines',
            'mensuel' => 'Chaque mois',
            'personnalise' => 'Tous les ' . $this->intervalle_jours . ' jours',
            default => $this->frequence,
        };
    }
}
