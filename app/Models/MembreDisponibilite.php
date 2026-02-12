<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembreDisponibilite extends Model
{
    use HasFactory;

    protected $table = 'membre_disponibilites';

    protected $fillable = [
        'membre_id',
        'jour_semaine',
        'heure_debut',
        'heure_fin',
        'est_exceptionnel',
        'date_exception',
        'est_disponible',
        'raison_indisponibilite',
    ];

    protected function casts(): array
    {
        return [
            'jour_semaine' => 'integer',
            'est_exceptionnel' => 'boolean',
            'est_disponible' => 'boolean',
            'date_exception' => 'date',
        ];
    }

    /**
     * Relation : Une disponibilité appartient à un membre
     */
    public function membre(): BelongsTo
    {
        return $this->belongsTo(EntrepriseMembre::class, 'membre_id');
    }

    /**
     * Vérifie si le membre est disponible à ce créneau
     */
    public function estDisponible(): bool
    {
        return $this->est_disponible && $this->heure_debut && $this->heure_fin;
    }

    /**
     * Vérifie si l'entreprise est fermée ce jour
     */
    public function estFerme(): bool
    {
        return !$this->heure_debut || !$this->heure_fin || !$this->est_disponible;
    }

    /**
     * Noms des jours de la semaine
     */
    public static function getJoursSemaine(): array
    {
        return [
            0 => 'Dimanche',
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
        ];
    }

    /**
     * Récupère le nom du jour
     */
    public function getNomJourAttribute(): string
    {
        $jours = self::getJoursSemaine();
        return $jours[$this->jour_semaine] ?? '';
    }
}
