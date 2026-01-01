<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class MembreIndisponibilite extends Model
{
    use HasFactory;

    protected $table = 'membre_indisponibilites';

    protected $fillable = [
        'membre_id',
        'date_debut',
        'date_fin',
        'heure_debut',
        'heure_fin',
        'raison',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
        ];
    }

    /**
     * Relation : Une indisponibilité appartient à un membre
     */
    public function membre(): BelongsTo
    {
        return $this->belongsTo(EntrepriseMembre::class, 'membre_id');
    }

    /**
     * Vérifie si l'indisponibilité est en cours
     */
    public function estEnCours(): bool
    {
        $now = now();
        $dateDebut = Carbon::parse($this->date_debut);
        $dateFin = $this->date_fin ? Carbon::parse($this->date_fin) : $dateDebut;

        return $now->between($dateDebut->startOfDay(), $dateFin->endOfDay());
    }

    /**
     * Vérifie si l'indisponibilité chevauche avec un créneau donné
     */
    public function chevaucheAvec(Carbon $date, ?Carbon $heureDebut = null, ?Carbon $heureFin = null): bool
    {
        $dateDebut = Carbon::parse($this->date_debut);
        $dateFin = $this->date_fin ? Carbon::parse($this->date_fin) : $dateDebut;

        // Vérifier si la date est dans la plage
        if (!$date->between($dateDebut->startOfDay(), $dateFin->endOfDay())) {
            return false;
        }

        // Si pas d'heures spécifiques, l'indisponibilité couvre toute la journée
        if (!$this->heure_debut || !$this->heure_fin) {
            return true;
        }

        // Si pas d'heures pour le créneau, vérifier seulement la date
        if (!$heureDebut || !$heureFin) {
            return true;
        }

        // Vérifier le chevauchement des heures
        $indispoDebut = $date->copy()->setTimeFromTimeString($this->heure_debut);
        $indispoFin = $date->copy()->setTimeFromTimeString($this->heure_fin);

        return $heureDebut->lt($indispoFin) && $heureFin->gt($indispoDebut);
    }

    /**
     * Vérifie si l'indisponibilité est passée
     */
    public function estPassee(): bool
    {
        $dateFin = $this->date_fin ? Carbon::parse($this->date_fin) : Carbon::parse($this->date_debut);
        return $dateFin->endOfDay()->isPast();
    }

    /**
     * Vérifie si l'indisponibilité est future
     */
    public function estFuture(): bool
    {
        $dateDebut = Carbon::parse($this->date_debut);
        return $dateDebut->startOfDay()->isFuture();
    }
}
