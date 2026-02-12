<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JoursFeriesService
{
    private const API_BASE_URL = 'https://calendrier.api.gouv.fr/jours-feries';
    private const CACHE_PREFIX = 'jours_feries_';
    private const CACHE_DURATION = 86400; // 24 heures

    /**
     * Récupère les jours fériés pour une année donnée
     * 
     * @param int $annee Année (ex: 2025)
     * @param string $zone Zone géographique (metropole, alsace-moselle, guadeloupe, etc.)
     * @return array Tableau associatif [date => nom] (ex: ['2025-01-01' => 'Jour de l\'an'])
     */
    public function getJoursFeries(int $annee, string $zone = 'metropole'): array
    {
        $cacheKey = self::CACHE_PREFIX . $annee . '_' . $zone;
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($annee, $zone) {
            try {
                $url = self::API_BASE_URL . '/' . $zone . '/' . $annee . '.json';
                
                $response = Http::timeout(5)->get($url);
                
                if ($response->successful()) {
                    $data = $response->json();
                    
                    // L'API retourne un format comme : {"2025-01-01": "Jour de l'an", ...}
                    return is_array($data) ? $data : [];
                }
                
                Log::warning('Erreur lors de la récupération des jours fériés', [
                    'annee' => $annee,
                    'zone' => $zone,
                    'status' => $response->status(),
                ]);
                
                return [];
            } catch (\Exception $e) {
                Log::error('Exception lors de la récupération des jours fériés', [
                    'annee' => $annee,
                    'zone' => $zone,
                    'error' => $e->getMessage(),
                ]);
                
                return [];
            }
        });
    }

    /**
     * Récupère les jours fériés sur une plage d'années
     * 
     * @param int $anneeDebut Année de début
     * @param int $anneeFin Année de fin
     * @param string $zone Zone géographique
     * @return array Tableau associatif [date => nom] pour toutes les années
     */
    public function getJoursFeriesRange(int $anneeDebut, int $anneeFin, string $zone = 'metropole'): array
    {
        $result = [];
        
        for ($annee = $anneeDebut; $annee <= $anneeFin; $annee++) {
            $joursFeries = $this->getJoursFeries($annee, $zone);
            $result = array_merge($result, $joursFeries);
        }
        
        return $result;
    }

    /**
     * Vérifie si une date donnée est un jour férié
     * 
     * @param string $date Date au format Y-m-d
     * @param int $annee Année
     * @param string $zone Zone géographique
     * @return bool
     */
    public function estJourFerie(string $date, int $annee, string $zone = 'metropole'): bool
    {
        $joursFeries = $this->getJoursFeries($annee, $zone);
        return isset($joursFeries[$date]);
    }

    /**
     * Récupère le nom d'un jour férié pour une date donnée
     * 
     * @param string $date Date au format Y-m-d
     * @param int $annee Année
     * @param string $zone Zone géographique
     * @return string|null Nom du jour férié ou null
     */
    public function getNomJourFerie(string $date, int $annee, string $zone = 'metropole'): ?string
    {
        $joursFeries = $this->getJoursFeries($annee, $zone);
        return $joursFeries[$date] ?? null;
    }

    /**
     * Liste des zones disponibles
     * 
     * @return array
     */
    public function getZonesDisponibles(): array
    {
        return [
            'metropole' => 'Métropole',
            'alsace-moselle' => 'Alsace-Moselle',
            'guadeloupe' => 'Guadeloupe',
            'guyane' => 'Guyane',
            'martinique' => 'Martinique',
            'mayotte' => 'Mayotte',
            'nouvelle-caledonie' => 'Nouvelle-Calédonie',
            'polynesie-francaise' => 'Polynésie Française',
            'reunion' => 'La Réunion',
            'saint-barthelemy' => 'Saint-Barthélemy',
            'saint-martin' => 'Saint-Martin',
            'saint-pierre-et-miquelon' => 'Saint-Pierre-et-Miquelon',
            'wallis-et-futuna' => 'Wallis-et-Futuna',
        ];
    }
}
