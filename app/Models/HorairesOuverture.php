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
        'type_exception',
        'date_debut',
        'date_fin',
        'mois',
        'annee',
        'jours_exclus',
        'est_jours_feries',
        'annee_jours_feries',
        'zone_jours_feries',
    ];

    protected function casts(): array
    {
        return [
            'est_exceptionnel' => 'boolean',
            'date_exception' => 'date',
            'date_debut' => 'date',
            'date_fin' => 'date',
            'jours_exclus' => 'array',
            'est_jours_feries' => 'boolean',
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

    /**
     * Vérifie si le type d'exception est 'jour'
     */
    public function isTypeJour(): bool
    {
        return $this->type_exception === 'jour' || ($this->est_exceptionnel && $this->date_exception && !$this->type_exception);
    }

    /**
     * Vérifie si le type d'exception est 'mois'
     */
    public function isTypeMois(): bool
    {
        return $this->type_exception === 'mois';
    }

    /**
     * Vérifie si le type d'exception est 'plage'
     */
    public function isTypePlage(): bool
    {
        return $this->type_exception === 'plage';
    }

    /**
     * Vérifie si le type d'exception est 'jours_feries'
     */
    public function isTypeJoursFeries(): bool
    {
        return $this->type_exception === 'jours_feries' || $this->est_jours_feries;
    }

    /**
     * Retourne le tableau des jours exclus
     * 
     * @return array
     */
    public function getJoursExclus(): array
    {
        if (!$this->jours_exclus) {
            return [];
        }
        
        if (is_array($this->jours_exclus)) {
            return $this->jours_exclus;
        }
        
        // Si c'est une string JSON, la décoder
        $decoded = json_decode($this->jours_exclus, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Retourne toutes les dates affectées par cette exception
     * Utilise le service ExceptionDateService
     * 
     * @return array Tableau de dates (format Y-m-d)
     */
    public function getDatesAffectees(): array
    {
        $service = app(\App\Services\ExceptionDateService::class);
        return $service->getDatesFromException($this);
    }

    /**
     * Retourne une description lisible du type d'exception
     * 
     * @return string
     */
    public function getTypeDescription(): string
    {
        if ($this->isTypeJour()) {
            return 'Jour ponctuel';
        } elseif ($this->isTypeMois()) {
            $moisNoms = [
                1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
                5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
                9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
            ];
            $moisNom = $moisNoms[$this->mois] ?? '';
            $joursExclus = $this->getJoursExclus();
            $exclusions = [];
            if (!empty($joursExclus)) {
                $joursNoms = self::getJoursSemaine();
                foreach ($joursExclus as $jour) {
                    $exclusions[] = $joursNoms[$jour] ?? '';
                }
                return $moisNom . ' ' . $this->annee . (count($exclusions) > 0 ? ' (sauf ' . implode(', ', $exclusions) . ')' : '');
            }
            return $moisNom . ' ' . $this->annee;
        } elseif ($this->isTypePlage()) {
            if ($this->date_debut && $this->date_fin) {
                return \Carbon\Carbon::parse($this->date_debut)->locale('fr')->isoFormat('D MMM') . ' - ' . 
                       \Carbon\Carbon::parse($this->date_fin)->locale('fr')->isoFormat('D MMM YYYY');
            }
            return 'Plage de dates';
        } elseif ($this->isTypeJoursFeries()) {
            $zones = app(\App\Services\JoursFeriesService::class)->getZonesDisponibles();
            $zoneNom = $zones[$this->zone_jours_feries] ?? $this->zone_jours_feries;
            return 'Jours fériés ' . ($this->annee_jours_feries ?? $this->annee) . ' (' . $zoneNom . ')';
        }
        
        return 'Exception';
    }
}
