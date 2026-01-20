<?php

namespace App\Services;

use App\Models\Entreprise;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class FidelisationService
{
    /**
     * Calcule la norme de régularité pour le quartier de l'entreprise
     * 
     * @param Entreprise $entreprise
     * @return float Norme en visites/mois
     */
    public function calculateQuartierNorm(Entreprise $entreprise): float
    {
        // Cache de 1 heure pour éviter les calculs répétés
        return Cache::remember(
            "fidelisation_norme_quartier_{$entreprise->id}",
            3600,
            function () use ($entreprise) {
                return $this->computeQuartierNorm($entreprise);
            }
        );
    }

    /**
     * Calcule effectivement la norme quartier
     */
    private function computeQuartierNorm(Entreprise $entreprise): float
    {
        // Trouver les entreprises du même quartier (code postal ou ville)
        $entreprisesQuartier = $this->getEntreprisesQuartier($entreprise);
        
        // Si moins de 3 entreprises dans le quartier, utiliser une norme par défaut
        if ($entreprisesQuartier->count() < 3) {
            return $this->getDefaultNorm($entreprise->type_activite);
        }

        $percentiles = [];
        
        // Pour chaque entreprise du quartier
        foreach ($entreprisesQuartier as $entrepriseQuartier) {
            $frequences = $this->getFrequencesClients($entrepriseQuartier);
            
            if ($frequences->count() >= 2) {
                // Calculer le 75e percentile (médiane supérieure)
                $percentile = $this->calculatePercentile($frequences, 75);
                if ($percentile > 0) {
                    $percentiles[] = $percentile;
                }
            }
        }

        // Si pas assez de données, utiliser la norme par défaut
        if (count($percentiles) < 2) {
            return $this->getDefaultNorm($entreprise->type_activite);
        }

        // Moyenne des percentiles
        return round(array_sum($percentiles) / count($percentiles), 2);
    }

    /**
     * Récupère les entreprises du même quartier
     */
    private function getEntreprisesQuartier(Entreprise $entreprise): Collection
    {
        $query = Entreprise::where('id', '!=', $entreprise->id)
            ->whereNotNull('code_postal')
            ->whereNotNull('ville');

        // Priorité au code postal
        if ($entreprise->code_postal) {
            $query->where('code_postal', $entreprise->code_postal);
        } elseif ($entreprise->ville) {
            $query->where('ville', 'like', '%' . $entreprise->ville . '%');
        } else {
            // Pas de données de localisation, retourner collection vide
            return collect([]);
        }

        return $query->get();
    }

    /**
     * Récupère les fréquences de visite de tous les clients d'une entreprise
     * 
     * @param Entreprise $entreprise
     * @return Collection Collection de fréquences (visites/mois)
     */
    private function getFrequencesClients(Entreprise $entreprise): Collection
    {
        // Récupérer toutes les réservations non annulées
        $reservations = Reservation::where('entreprise_id', $entreprise->id)
            ->whereIn('statut', ['confirmee', 'terminee'])
            ->whereNotNull('date_reservation')
            ->whereNotNull('user_id')
            ->with('user')
            ->get();

        // Grouper par client
        $clientsReservations = $reservations->groupBy('user_id');

        $frequences = collect([]);

        foreach ($clientsReservations as $userId => $clientReservations) {
            if ($clientReservations->count() < 2) {
                continue; // Ignorer les clients avec moins de 2 réservations
            }

            // Calculer la période d'activité (première à dernière réservation)
            $premiereReservation = $clientReservations->min('date_reservation');
            $derniereReservation = $clientReservations->max('date_reservation');

            if (!$premiereReservation || !$derniereReservation) {
                continue;
            }

            $nbMois = $premiereReservation->diffInMonths($derniereReservation);
            
            // Si moins d'un mois, considérer comme 1 mois minimum
            if ($nbMois < 1) {
                $nbMois = 1;
            }

            // Fréquence = nombre de réservations / nombre de mois
            $frequence = $clientReservations->count() / $nbMois;
            $frequences->push($frequence);
        }

        return $frequences;
    }

    /**
     * Calcule un percentile d'une collection
     */
    private function calculatePercentile(Collection $values, int $percentile): float
    {
        $sorted = $values->sort()->values();
        $count = $sorted->count();

        if ($count === 0) {
            return 0;
        }

        $index = ceil(($percentile / 100) * $count) - 1;
        $index = max(0, min($index, $count - 1));

        return $sorted->get($index);
    }

    /**
     * Retourne une norme par défaut selon le type d'activité
     */
    private function getDefaultNorm(?string $typeActivite): float
    {
        // Normes par défaut selon le type d'activité
        $norms = [
            'coiffure' => 0.33, // Tous les 3 mois = 0.33 visites/mois
            'esthétique' => 0.33,
            'beauté' => 0.33,
            'restaurant' => 4.0, // Toutes les semaines = 4 visites/mois
            'café' => 8.0, // Plusieurs fois par semaine
            'boulangerie' => 12.0, // Presque quotidien
            'nourriture' => 4.0,
        ];

        // Chercher une correspondance partielle
        if ($typeActivite) {
            $typeLower = strtolower($typeActivite);
            foreach ($norms as $key => $norm) {
                if (str_contains($typeLower, $key)) {
                    return $norm;
                }
            }
        }

        // Norme par défaut : 1 visite/mois
        return 1.0;
    }

    /**
     * Calcule les statistiques de fidélité d'un client pour une entreprise
     * 
     * @param Entreprise $entreprise
     * @param User $client
     * @return array
     */
    public function calculateClientFidelite(Entreprise $entreprise, User $client): array
    {
        // Récupérer toutes les réservations non annulées du client
        $reservations = Reservation::where('entreprise_id', $entreprise->id)
            ->where('user_id', $client->id)
            ->whereIn('statut', ['confirmee', 'terminee'])
            ->whereNotNull('date_reservation')
            ->orderBy('date_reservation', 'asc')
            ->get();

        $nbReservations = $reservations->count();

        if ($nbReservations === 0) {
            return [
                'nb_reservations' => 0,
                'frequence_moyenne' => 0,
                'derniere_visite' => null,
                'premiere_visite' => null,
                'statut' => 'nouveau',
            ];
        }

        $premiereVisite = $reservations->first()->date_reservation;
        $derniereVisite = $reservations->last()->date_reservation;

        // Calculer la fréquence
        $nbMois = $premiereVisite->diffInMonths($derniereVisite);
        if ($nbMois < 1) {
            $nbMois = 1;
        }
        $frequenceMoyenne = $nbReservations / $nbMois;

        // Déterminer le statut
        $normeQuartier = $this->calculateQuartierNorm($entreprise);
        $statut = $this->determineStatut($frequenceMoyenne, $normeQuartier, $nbReservations, $premiereVisite);

        return [
            'nb_reservations' => $nbReservations,
            'frequence_moyenne' => round($frequenceMoyenne, 2),
            'derniere_visite' => $derniereVisite,
            'premiere_visite' => $premiereVisite,
            'statut' => $statut,
        ];
    }

    /**
     * Détermine le statut d'un client (régulier, occasionnel, nouveau)
     */
    private function determineStatut(float $frequence, float $normeQuartier, int $nbReservations, Carbon $premiereVisite): string
    {
        // Nouveau : moins de 3 réservations OU client récent (< 3 mois)
        if ($nbReservations < 3 || $premiereVisite->diffInMonths(now()) < 3) {
            return 'nouveau';
        }

        // Régulier : fréquence >= norme quartier
        if ($frequence >= $normeQuartier) {
            return 'regulier';
        }

        // Occasionnel : fréquence entre 50% et 100% de la norme
        if ($frequence >= ($normeQuartier * 0.5)) {
            return 'occasionnel';
        }

        // Sinon, nouveau (trop peu fréquent)
        return 'nouveau';
    }

    /**
     * Récupère la liste des clients réguliers avec leurs statistiques
     * 
     * @param Entreprise $entreprise
     * @param array $filters ['search' => string, 'statut' => string, 'sort' => string]
     * @return array
     */
    public function getClientsReguliers(Entreprise $entreprise, array $filters = []): array
    {
        $normeQuartier = $this->calculateQuartierNorm($entreprise);

        // Récupérer tous les clients ayant au moins une réservation
        $query = Reservation::where('entreprise_id', $entreprise->id)
            ->whereIn('statut', ['confirmee', 'terminee'])
            ->whereNotNull('user_id')
            ->whereNotNull('date_reservation')
            ->with('user')
            ->get()
            ->groupBy('user_id');

        $clientsData = collect([]);

        foreach ($query as $userId => $reservations) {
            $client = $reservations->first()->user;
            if (!$client) {
                continue;
            }

            $stats = $this->calculateClientFidelite($entreprise, $client);

            $clientsData->push([
                'client' => $client,
                'nb_reservations' => $stats['nb_reservations'],
                'frequence_moyenne' => $stats['frequence_moyenne'],
                'derniere_visite' => $stats['derniere_visite'],
                'premiere_visite' => $stats['premiere_visite'],
                'statut' => $stats['statut'],
            ]);
        }

        // Appliquer les filtres
        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $clientsData = $clientsData->filter(function ($item) use ($search) {
                $client = $item['client'];
                return str_contains(strtolower($client->name ?? ''), $search) ||
                       str_contains(strtolower($client->email ?? ''), $search);
            });
        }

        if (!empty($filters['statut'])) {
            $clientsData = $clientsData->filter(function ($item) use ($filters) {
                return $item['statut'] === $filters['statut'];
            });
        }

        // Trier
        $sort = $filters['sort'] ?? 'plus_present';
        if ($sort === 'plus_present') {
            $clientsData = $clientsData->sortByDesc('nb_reservations');
        } elseif ($sort === 'moins_present') {
            $clientsData = $clientsData->sortBy('nb_reservations');
        } elseif ($sort === 'frequence_desc') {
            $clientsData = $clientsData->sortByDesc('frequence_moyenne');
        } elseif ($sort === 'frequence_asc') {
            $clientsData = $clientsData->sortBy('frequence_moyenne');
        } elseif ($sort === 'derniere_visite_desc') {
            $clientsData = $clientsData->sortByDesc(function ($item) {
                return $item['derniere_visite'] ? $item['derniere_visite']->timestamp : 0;
            });
        }

        return [
            'norme_quartier' => $normeQuartier,
            'clients' => $clientsData->values()->all(),
        ];
    }

    /**
     * Récupère les clients à risque (qui n'ont pas réservé depuis longtemps)
     * 
     * @param Entreprise $entreprise
     * @param int $joursSansReservation Nombre de jours sans réservation pour être considéré à risque
     * @return array
     */
    public function getClientsARisque(Entreprise $entreprise, int $joursSansReservation = 90): array
    {
        $normeQuartier = $this->calculateQuartierNorm($entreprise);
        $dateLimite = now()->subDays($joursSansReservation);

        // Récupérer tous les clients ayant au moins une réservation
        $query = Reservation::where('entreprise_id', $entreprise->id)
            ->whereIn('statut', ['confirmee', 'terminee'])
            ->whereNotNull('user_id')
            ->whereNotNull('date_reservation')
            ->with('user')
            ->get()
            ->groupBy('user_id');

        $clientsARisque = collect([]);

        foreach ($query as $userId => $reservations) {
            $client = $reservations->first()->user;
            if (!$client) {
                continue;
            }

            $stats = $this->calculateClientFidelite($entreprise, $client);
            
            // Client à risque si :
            // - Pas de réservation depuis X jours ET avait au moins 2 réservations avant
            // - OU était régulier et ne l'est plus
            $estARisque = false;
            $raison = '';

            if ($stats['derniere_visite'] && $stats['derniere_visite'] < $dateLimite) {
                if ($stats['nb_reservations'] >= 2) {
                    $estARisque = true;
                    $joursDepuisDerniereVisite = $stats['derniere_visite']->diffInDays(now());
                    $raison = "Pas de réservation depuis {$joursDepuisDerniereVisite} jours";
                }
            }

            // Client qui était régulier mais ne l'est plus
            if ($stats['nb_reservations'] >= 3) {
                $frequenceAttendue = $normeQuartier; // visites/mois
                $joursAttendus = ($frequenceAttendue > 0) ? (30 / $frequenceAttendue) : 90;
                
                if ($stats['derniere_visite'] && $stats['derniere_visite'] < now()->subDays($joursAttendus * 2)) {
                    if ($stats['statut'] === 'regulier' || ($stats['frequence_moyenne'] >= $normeQuartier * 0.8)) {
                        $estARisque = true;
                        $raison = "Fréquence en baisse (était régulier)";
                    }
                }
            }

            if ($estARisque) {
                $clientsARisque->push([
                    'client' => $client,
                    'nb_reservations' => $stats['nb_reservations'],
                    'frequence_moyenne' => $stats['frequence_moyenne'],
                    'derniere_visite' => $stats['derniere_visite'],
                    'premiere_visite' => $stats['premiere_visite'],
                    'statut' => $stats['statut'],
                    'raison' => $raison,
                    'jours_sans_visite' => $stats['derniere_visite'] ? $stats['derniere_visite']->diffInDays(now()) : 0,
                ]);
            }
        }

        // Trier par jours sans visite (décroissant)
        $clientsARisque = $clientsARisque->sortByDesc('jours_sans_visite');

        return [
            'norme_quartier' => $normeQuartier,
            'clients' => $clientsARisque->values()->all(),
        ];
    }

    /**
     * Calcule les statistiques de fidélisation
     * 
     * @param Entreprise $entreprise
     * @return array
     */
    public function getStatistiques(Entreprise $entreprise): array
    {
        $normeQuartier = $this->calculateQuartierNorm($entreprise);

        // Récupérer tous les clients
        $query = Reservation::where('entreprise_id', $entreprise->id)
            ->whereIn('statut', ['confirmee', 'terminee'])
            ->whereNotNull('user_id')
            ->whereNotNull('date_reservation')
            ->with('user')
            ->get()
            ->groupBy('user_id');

        $stats = [
            'total_clients' => 0,
            'clients_reguliers' => 0,
            'clients_occasionnels' => 0,
            'clients_nouveaux' => 0,
            'evolution_mois' => [],
        ];

        $clientsParMois = [];

        foreach ($query as $userId => $reservations) {
            $client = $reservations->first()->user;
            if (!$client) {
                continue;
            }

            $clientStats = $this->calculateClientFidelite($entreprise, $client);
            $stats['total_clients']++;

            if ($clientStats['statut'] === 'regulier') {
                $stats['clients_reguliers']++;
            } elseif ($clientStats['statut'] === 'occasionnel') {
                $stats['clients_occasionnels']++;
            } else {
                $stats['clients_nouveaux']++;
            }

            // Évolution par mois (première visite)
            if ($clientStats['premiere_visite']) {
                $mois = $clientStats['premiere_visite']->format('Y-m');
                if (!isset($clientsParMois[$mois])) {
                    $clientsParMois[$mois] = 0;
                }
                $clientsParMois[$mois]++;
            }
        }

        // Préparer l'évolution sur 12 mois
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $mois = $date->format('Y-m');
            $stats['evolution_mois'][] = [
                'mois' => $date->format('M Y'),
                'clients' => $clientsParMois[$mois] ?? 0,
            ];
        }

        // Taux de rétention (clients avec au moins 2 réservations)
        $clientsAvecPlusieursReservations = 0;
        foreach ($query as $reservations) {
            if ($reservations->count() >= 2) {
                $clientsAvecPlusieursReservations++;
            }
        }
        $stats['taux_retention'] = $stats['total_clients'] > 0 
            ? round(($clientsAvecPlusieursReservations / $stats['total_clients']) * 100, 1)
            : 0;

        return [
            'norme_quartier' => $normeQuartier,
            'stats' => $stats,
        ];
    }

    /**
     * Invalide le cache de la norme quartier pour une entreprise
     */
    public function invalidateQuartierNormCache(Entreprise $entreprise): void
    {
        Cache::forget("fidelisation_norme_quartier_{$entreprise->id}");
    }
}
