<?php

namespace App\Services;

use App\Models\Entreprise;
use App\Models\HorairesOuverture;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ExceptionDateService
{
    /**
     * Récupère les horaires applicables pour une date donnée
     * Gère la priorité : Jour ponctuel > Mois > Plage > Jours fériés > Horaires réguliers
     * 
     * @param Entreprise $entreprise
     * @param Carbon $date
     * @return Collection Collection de HorairesOuverture (peut être vide)
     */
    public function getHorairesForDate(Entreprise $entreprise, Carbon $date): Collection
    {
        $dateString = $date->format('Y-m-d');
        $jourSemaine = $date->dayOfWeek;
        
        // Récupérer toutes les exceptions de l'entreprise
        $exceptions = $entreprise->horairesOuverture()
            ->where('est_exceptionnel', true)
            ->get();
        
        // 1. Vérifier les jours ponctuels (priorité la plus haute)
        $jourPonctuel = $exceptions->first(function ($exception) use ($dateString) {
            return $exception->type_exception === 'jour' 
                && $exception->date_exception 
                && $exception->date_exception->format('Y-m-d') === $dateString;
        });
        
        if ($jourPonctuel) {
            return collect([$jourPonctuel]);
        }
        
        // 2. Vérifier les mois
        $moisException = $exceptions->first(function ($exception) use ($date, $jourSemaine) {
            if ($exception->type_exception !== 'mois') {
                return false;
            }
            
            // Vérifier que la date est dans le bon mois et année
            if ($exception->mois && $exception->annee) {
                if ($date->month !== (int)$exception->mois || $date->year !== (int)$exception->annee) {
                    return false;
                }
            }
            
            // Vérifier que le jour de la semaine n'est pas exclu
            $joursExclus = $exception->getJoursExclus();
            if (in_array($jourSemaine, $joursExclus)) {
                return false;
            }
            
            return true;
        });
        
        if ($moisException) {
            return collect([$moisException]);
        }
        
        // 3. Vérifier les plages
        $plageException = $exceptions->first(function ($exception) use ($dateString) {
            if ($exception->type_exception !== 'plage') {
                return false;
            }
            
            if (!$exception->date_debut || !$exception->date_fin) {
                return false;
            }
            
            $dateDebut = Carbon::parse($exception->date_debut);
            $dateFin = Carbon::parse($exception->date_fin);
            
            return $date->between($dateDebut, $dateFin, true); // true = inclusif
        });
        
        if ($plageException) {
            return collect([$plageException]);
        }
        
        // 4. Vérifier les jours fériés
        $joursFeriesException = $exceptions->first(function ($exception) use ($dateString) {
            if ($exception->type_exception !== 'jours_feries' || !$exception->est_jours_feries) {
                return false;
            }
            
            // Vérifier si cette date correspond à un jour férié
            if ($exception->date_exception && $exception->date_exception->format('Y-m-d') === $dateString) {
                return true;
            }
            
            return false;
        });
        
        if ($joursFeriesException) {
            return collect([$joursFeriesException]);
        }
        
        // 5. Retourner les horaires réguliers pour ce jour de la semaine
        return $entreprise->horairesOuverture()
            ->where('jour_semaine', $jourSemaine)
            ->where('est_exceptionnel', false)
            ->orderBy('ordre_plage')
            ->get();
    }

    /**
     * Récupère toutes les dates affectées par une exception
     * Utile pour l'affichage dans la liste
     * 
     * @param HorairesOuverture $exception
     * @return array Tableau de dates (format Y-m-d)
     */
    public function getDatesFromException(HorairesOuverture $exception): array
    {
        $dates = [];
        
        switch ($exception->type_exception) {
            case 'jour':
                if ($exception->date_exception) {
                    $dates[] = $exception->date_exception->format('Y-m-d');
                }
                break;
                
            case 'mois':
                if ($exception->mois && $exception->annee) {
                    $debut = Carbon::create($exception->annee, $exception->mois, 1);
                    $fin = $debut->copy()->endOfMonth();
                    $joursExclus = $exception->getJoursExclus();
                    
                    $current = $debut->copy();
                    while ($current->lte($fin)) {
                        if (!in_array($current->dayOfWeek, $joursExclus)) {
                            $dates[] = $current->format('Y-m-d');
                        }
                        $current->addDay();
                    }
                }
                break;
                
            case 'plage':
                if ($exception->date_debut && $exception->date_fin) {
                    $debut = Carbon::parse($exception->date_debut);
                    $fin = Carbon::parse($exception->date_fin);
                    
                    $current = $debut->copy();
                    while ($current->lte($fin)) {
                        $dates[] = $current->format('Y-m-d');
                        $current->addDay();
                    }
                }
                break;
                
            case 'jours_feries':
                // Pour les jours fériés, on retourne juste la date_exception si elle existe
                // (car chaque jour férié est un enregistrement séparé)
                if ($exception->date_exception) {
                    $dates[] = $exception->date_exception->format('Y-m-d');
                }
                break;
        }
        
        return $dates;
    }

    /**
     * Vérifie si une date est affectée par une exception donnée
     * 
     * @param HorairesOuverture $exception
     * @param Carbon $date
     * @return bool
     */
    public function dateEstAffectee(HorairesOuverture $exception, Carbon $date): bool
    {
        $dates = $this->getDatesFromException($exception);
        return in_array($date->format('Y-m-d'), $dates);
    }

    /**
     * Récupère toutes les exceptions qui affectent une date donnée (sans priorité)
     * 
     * @param Entreprise $entreprise
     * @param Carbon $date
     * @return Collection
     */
    public function getAllExceptionsForDate(Entreprise $entreprise, Carbon $date): Collection
    {
        $dateString = $date->format('Y-m-d');
        $jourSemaine = $date->dayOfWeek;
        
        return $entreprise->horairesOuverture()
            ->where('est_exceptionnel', true)
            ->get()
            ->filter(function ($exception) use ($date, $dateString, $jourSemaine) {
                switch ($exception->type_exception) {
                    case 'jour':
                        return $exception->date_exception 
                            && $exception->date_exception->format('Y-m-d') === $dateString;
                            
                    case 'mois':
                        if (!$exception->mois || !$exception->annee) {
                            return false;
                        }
                        if ($date->month !== (int)$exception->mois || $date->year !== (int)$exception->annee) {
                            return false;
                        }
                        $joursExclus = $exception->getJoursExclus();
                        return !in_array($jourSemaine, $joursExclus);
                        
                    case 'plage':
                        if (!$exception->date_debut || !$exception->date_fin) {
                            return false;
                        }
                        $dateDebut = Carbon::parse($exception->date_debut);
                        $dateFin = Carbon::parse($exception->date_fin);
                        return $date->between($dateDebut, $dateFin, true);
                        
                    case 'jours_feries':
                        return $exception->date_exception 
                            && $exception->date_exception->format('Y-m-d') === $dateString;
                            
                    default:
                        return false;
                }
            });
    }
}
