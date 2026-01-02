<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AddressService
{
    /**
     * Base URL de l'API Adresse du gouvernement français
     * https://adresse.data.gouv.fr/api-doc/adresse
     */
    protected string $baseUrl = 'https://api-adresse.data.gouv.fr';

    /**
     * Recherche d'adresses avec autocomplétion
     * 
     * @param string $query La recherche (ex: "15 rue de la paix Paris")
     * @param int $limit Nombre maximum de résultats
     * @param string|null $type Type de résultat: housenumber, street, locality, municipality
     * @return array
     */
    public function search(string $query, int $limit = 5, ?string $type = null): array
    {
        if (strlen(trim($query)) < 3) {
            return [];
        }

        $cacheKey = 'address_search_' . md5($query . $limit . $type);
        
        return Cache::remember($cacheKey, 3600, function () use ($query, $limit, $type) {
            try {
                $params = [
                    'q' => $query,
                    'limit' => $limit,
                    'autocomplete' => 1,
                ];

                if ($type) {
                    $params['type'] = $type;
                }

                $response = Http::timeout(5)->get($this->baseUrl . '/search/', $params);

                if ($response->successful()) {
                    return $this->formatResults($response->json()['features'] ?? []);
                }
            } catch (\Exception $e) {
                \Log::error('AddressService search error: ' . $e->getMessage());
            }

            return [];
        });
    }

    /**
     * Recherche uniquement des communes (villes)
     */
    public function searchCities(string $query, int $limit = 5): array
    {
        return $this->search($query, $limit, 'municipality');
    }

    /**
     * Recherche d'adresses complètes (rue + numéro)
     */
    public function searchAddresses(string $query, int $limit = 5): array
    {
        if (strlen(trim($query)) < 3) {
            return [];
        }

        $cacheKey = 'address_full_' . md5($query . $limit);
        
        return Cache::remember($cacheKey, 3600, function () use ($query, $limit) {
            try {
                $response = Http::timeout(5)->get($this->baseUrl . '/search/', [
                    'q' => $query,
                    'limit' => $limit,
                    'autocomplete' => 1,
                ]);

                if ($response->successful()) {
                    return $this->formatResults($response->json()['features'] ?? []);
                }
            } catch (\Exception $e) {
                \Log::error('AddressService searchAddresses error: ' . $e->getMessage());
            }

            return [];
        });
    }

    /**
     * Géocodage : convertir une adresse en coordonnées GPS
     */
    public function geocode(string $address): ?array
    {
        if (strlen(trim($address)) < 3) {
            return null;
        }

        $cacheKey = 'geocode_' . md5($address);
        
        return Cache::remember($cacheKey, 86400, function () use ($address) {
            try {
                $response = Http::timeout(5)->get($this->baseUrl . '/search/', [
                    'q' => $address,
                    'limit' => 1,
                ]);

                if ($response->successful()) {
                    $features = $response->json()['features'] ?? [];
                    if (!empty($features)) {
                        $feature = $features[0];
                        return [
                            'latitude' => $feature['geometry']['coordinates'][1] ?? null,
                            'longitude' => $feature['geometry']['coordinates'][0] ?? null,
                            'label' => $feature['properties']['label'] ?? null,
                            'city' => $feature['properties']['city'] ?? null,
                            'postcode' => $feature['properties']['postcode'] ?? null,
                            'street' => $feature['properties']['street'] ?? $feature['properties']['name'] ?? null,
                            'housenumber' => $feature['properties']['housenumber'] ?? null,
                        ];
                    }
                }
            } catch (\Exception $e) {
                \Log::error('AddressService geocode error: ' . $e->getMessage());
            }

            return null;
        });
    }

    /**
     * Géocodage inverse : convertir des coordonnées en adresse
     */
    public function reverseGeocode(float $latitude, float $longitude): ?array
    {
        $cacheKey = 'reverse_geocode_' . md5($latitude . '_' . $longitude);
        
        return Cache::remember($cacheKey, 86400, function () use ($latitude, $longitude) {
            try {
                $response = Http::timeout(5)->get($this->baseUrl . '/reverse/', [
                    'lat' => $latitude,
                    'lon' => $longitude,
                ]);

                if ($response->successful()) {
                    $features = $response->json()['features'] ?? [];
                    if (!empty($features)) {
                        $feature = $features[0];
                        return [
                            'label' => $feature['properties']['label'] ?? null,
                            'city' => $feature['properties']['city'] ?? null,
                            'postcode' => $feature['properties']['postcode'] ?? null,
                            'street' => $feature['properties']['street'] ?? $feature['properties']['name'] ?? null,
                        ];
                    }
                }
            } catch (\Exception $e) {
                \Log::error('AddressService reverseGeocode error: ' . $e->getMessage());
            }

            return null;
        });
    }

    /**
     * Formater les résultats de l'API
     */
    protected function formatResults(array $features): array
    {
        return array_map(function ($feature) {
            $props = $feature['properties'] ?? [];
            $coords = $feature['geometry']['coordinates'] ?? [null, null];

            return [
                'label' => $props['label'] ?? '',
                'name' => $props['name'] ?? '',
                'city' => $props['city'] ?? '',
                'postcode' => $props['postcode'] ?? '',
                'street' => $props['street'] ?? $props['name'] ?? '',
                'housenumber' => $props['housenumber'] ?? '',
                'type' => $props['type'] ?? '',
                'latitude' => $coords[1] ?? null,
                'longitude' => $coords[0] ?? null,
                'context' => $props['context'] ?? '', // Département, région
            ];
        }, $features);
    }

    /**
     * Calculer la distance entre deux points GPS (formule Haversine)
     * Retourne la distance en kilomètres
     */
    public static function calculateDistance(
        float $lat1, 
        float $lon1, 
        float $lat2, 
        float $lon2
    ): float {
        $earthRadius = 6371; // Rayon de la Terre en km

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
