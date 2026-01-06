<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HorairesOuverture extends Model
{
    use HasFactory;

    protected $table = 'horaires_ouverture';

    protected $fillable = [
        'entreprise_id',
        'jour_semaine',
        'ordre_plage',
        'heure_ouverture',
        'heure_fermeture',
        'est_exceptionnel',
        'date_exception',
    ];

    protected function casts(): array
    {
        return [
            'est_exceptionnel' => 'boolean',
            'date_exception' => 'date',
        ];
    }

    /**
     * Relation : Les horaires appartiennent à une entreprise
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
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
     * Vérifie si l'entreprise est fermée ce jour
     */
    public function estFerme(): bool
    {
        return $this->heure_ouverture === null || $this->heure_fermeture === null;
    }
}
