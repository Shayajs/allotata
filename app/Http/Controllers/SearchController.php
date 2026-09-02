<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Services\VisitorLocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SearchController extends Controller
{
    /**
     * Recherche d'entreprises par mots-clés
     */
    public function search(Request $request)
    {
        $query = $request->input('q', '');
        $query = trim($query);

        // Récupérer les filtres avancés
        $villeFilter = $request->input('ville_filter');
        $villeLat = $request->input('ville_lat');
        $villeLng = $request->input('ville_lng');
        $rayon = $request->input('rayon');
        $typeActivite = $request->input('type_activite');

        $visitorLocation = app(VisitorLocationService::class)->resolve($request);

        // Construire la requête de base
        $entrepriseQuery = Entreprise::query()
            ->where('est_verifiee', true)
            ->with(['user', 'typesServices', 'avis']);

        // Recherche par proximité : entreprises physiques dans le rayon + toutes les virtuelles
        if ($villeLat && $villeLng && $rayon) {
            $lat = (float) $villeLat;
            $lng = (float) $villeLng;
            $radius = (float) $rayon;

            $haversine = "(
                6371 * acos(
                    cos(radians({$lat}))
                    * cos(radians(latitude))
                    * cos(radians(longitude) - radians({$lng}))
                    + sin(radians({$lat}))
                    * sin(radians(latitude))
                )
            )";

            $entrepriseQuery->where(function ($q) use ($haversine, $radius) {
                $q->where('type_localisation', Entreprise::LOCALISATION_VIRTUEL)
                    ->orWhere(function ($physical) use ($haversine, $radius) {
                        $physical->where(function ($typeQ) {
                            $typeQ->where('type_localisation', Entreprise::LOCALISATION_PHYSIQUE)
                                ->orWhereNull('type_localisation');
                        })
                            ->whereNotNull('latitude')
                            ->whereNotNull('longitude')
                            ->whereRaw("{$haversine} <= ?", [$radius]);
                    });
            })->selectRaw("entreprises.*, CASE WHEN type_localisation = 'virtuel' THEN NULL ELSE {$haversine} END AS distance");
        } elseif ($villeFilter) {
            $entrepriseQuery->where(function ($q) use ($villeFilter) {
                $q->where('ville', 'LIKE', "%{$villeFilter}%")
                    ->orWhere('type_localisation', Entreprise::LOCALISATION_VIRTUEL);
            });
        }

        // Filtrer par type d'activité
        if ($typeActivite) {
            $entrepriseQuery->where('type_activite', $typeActivite);
        }

        // Si pas de recherche texte mais des filtres sont appliqués
        if (empty($query) && ($villeFilter || $typeActivite)) {
            $allResults = $entrepriseQuery
                ->get()
                ->filter(function ($entreprise) {
                    return $entreprise->estVisiblePubliquement();
                });

            // Trier par distance si recherche par proximité
            if ($villeLat && $villeLng && $rayon) {
                $allResults = $allResults->sortBy('distance');
            }

            return $this->renderResults($allResults, $query, $visitorLocation, [
                'sortedByProximity' => (bool) ($villeLat && $villeLng && $rayon),
            ]);
        }

        if (empty($query)) {
            return $this->searchAllByVisitorProximity($visitorLocation);
        }

        // Séparer les mots-clés
        $keywords = preg_split('/\s+/', $query);
        $keywords = array_filter($keywords, function ($keyword) {
            return strlen($keyword) >= 2; // Ignorer les mots trop courts
        });

        if (empty($keywords)) {
            return $this->renderResults(collect([]), $query, $visitorLocation);
        }

        $useBrowserProximity = VisitorLocationService::isBrowser($visitorLocation)
            && ! ($villeLat && $villeLng && $rayon);

        $allResults = $entrepriseQuery
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $q->where(function ($subQ) use ($keyword) {
                        $subQ->where('nom', 'LIKE', "%{$keyword}%")
                            ->orWhere('description', 'LIKE', "%{$keyword}%")
                            ->orWhere('type_activite', 'LIKE', "%{$keyword}%")
                            ->orWhere('ville', 'LIKE', "%{$keyword}%")
                            ->orWhere('mots_cles', 'LIKE', "%{$keyword}%")
                            ->orWhere('email', 'LIKE', "%{$keyword}%")
                            ->orWhere('telephone', 'LIKE', "%{$keyword}%")
                            ->orWhere('status_juridique', 'LIKE', "%{$keyword}%")
                            ->orWhere('siren', 'LIKE', "%{$keyword}%")
                            ->orWhere('code_postal', 'LIKE', "%{$keyword}%")
                            ->orWhere('adresse_rue', 'LIKE', "%{$keyword}%")
                            ->orWhereHas('typesServices', function ($typeQ) use ($keyword) {
                                $typeQ->where('nom', 'LIKE', "%{$keyword}%")
                                    ->orWhere('description', 'LIKE', "%{$keyword}%");
                            })
                            ->orWhereHas('user', function ($userQ) use ($keyword) {
                                $userQ->where('name', 'LIKE', "%{$keyword}%")
                                    ->orWhere('email', 'LIKE', "%{$keyword}%");
                            });
                    });
                }
            })
            ->get()
            ->filter(fn ($entreprise) => $entreprise->estVisiblePubliquement())
            ->map(fn ($entreprise) => $this->scoreEntreprise(
                $entreprise,
                $keywords,
                $query,
                $visitorLocation,
                $useBrowserProximity
            ));

        // Trier par distance si recherche par proximité, sinon par pertinence
        if ($villeLat && $villeLng && $rayon) {
            $allResults = $allResults->sortBy('distance');
        } else {
            $allResults = $allResults->sortByDesc('relevance_score');
        }

        return $this->renderResults($allResults, $query, $visitorLocation, [
            'sortedByProximity' => (bool) ($villeLat && $villeLng && $rayon),
        ]);
    }

    /**
     * Recherche en temps réel (autocomplete) - API
     */
    public function autocomplete(Request $request)
    {
        $query = $request->input('q', '');
        $query = trim($query);

        if (empty($query) || strlen($query) < 2) {
            return response()->json([]);
        }

        // Recherche rapide pour l'autocomplete (limité à 8 résultats)
        $results = Entreprise::query()
            ->where('est_verifiee', true)
            ->with(['user', 'typesServices' => function ($q) {
                $q->where('est_actif', true)->limit(3);
            }])
            ->where(function ($q) use ($query) {
                $lowerQuery = mb_strtolower($query);
                $q->whereRaw('LOWER(nom) LIKE ?', ["%{$lowerQuery}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$lowerQuery}%"])
                    ->orWhereRaw('LOWER(type_activite) LIKE ?', ["%{$lowerQuery}%"])
                    ->orWhereRaw('LOWER(ville) LIKE ?', ["%{$lowerQuery}%"])
                    ->orWhereRaw('LOWER(mots_cles) LIKE ?', ["%{$lowerQuery}%"])
                    ->orWhereHas('typesServices', function ($typeQ) use ($lowerQuery) {
                        $typeQ->whereRaw('LOWER(nom) LIKE ?', ["%{$lowerQuery}%"]);
                    });
            })
            ->get()
            ->filter(function ($entreprise) {
                // Filtrer uniquement les entreprises avec un abonnement actif
                return $entreprise->estVisiblePubliquement();
            })
            ->take(8)
            ->map(function ($entreprise) {
                return [
                    'id' => $entreprise->id,
                    'nom' => $entreprise->nom,
                    'type_activite' => $entreprise->type_activite,
                    'ville' => $entreprise->ville,
                    'slug' => $entreprise->slug,
                    'logo' => $entreprise->logo ? asset('media/'.$entreprise->logo) : null,
                    'est_verifiee' => $entreprise->est_verifiee,
                    'services' => $entreprise->typesServices->pluck('nom')->take(2)->toArray(),
                ];
            });

        return response()->json($results);
    }

    /**
     * Liste toutes les entreprises actives, triées par proximité (GPS, IP France ou Paris).
     */
    private function searchAllByVisitorProximity(array $visitorLocation)
    {
        $virtuelles = Entreprise::query()
            ->where('est_verifiee', true)
            ->virtuelle()
            ->with(['user', 'typesServices', 'avis'])
            ->get()
            ->filter(fn ($entreprise) => $entreprise->estVisiblePubliquement());

        $physiques = Entreprise::query()
            ->where('est_verifiee', true)
            ->physique()
            ->with(['user', 'typesServices', 'avis'])
            ->orderByDistanceFrom($visitorLocation['latitude'], $visitorLocation['longitude'])
            ->get()
            ->filter(fn ($entreprise) => $entreprise->estVisiblePubliquement());

        $allResults = $physiques->concat($virtuelles);

        return $this->renderResults($allResults, '', $visitorLocation, [
            'sortedByProximity' => true,
        ]);
    }

    /**
     * @param  Collection<int, Entreprise>  $results
     * @param  array{latitude: float, longitude: float, city: ?string, source: string}  $visitorLocation
     * @param  array<string, mixed>  $extra
     */
    private function renderResults($results, string $query, array $visitorLocation, array $extra = []): View
    {
        $results = $this->attachVisitorDistance(collect($results), $visitorLocation);

        return view('search.results', array_merge([
            'results' => $results->values(),
            'query' => $query,
            'count' => $results->count(),
            'visitorLocation' => $visitorLocation,
            'sortedByProximity' => false,
        ], $extra));
    }

    /**
     * @param  Collection<int, Entreprise>  $results
     * @param  array{latitude: float, longitude: float, city: ?string, source: string}  $visitorLocation
     * @return Collection<int, Entreprise>
     */
    private function attachVisitorDistance(Collection $results, array $visitorLocation): Collection
    {
        $precise = in_array($visitorLocation['source'] ?? '', ['browser', 'user'], true);

        return $results->map(function ($entreprise) use ($visitorLocation, $precise) {
            if (isset($entreprise->distance) || ! $precise || ! $entreprise->hasCoordinates()) {
                return $entreprise;
            }

            $entreprise->distance = $this->haversineKm(
                $visitorLocation['latitude'],
                $visitorLocation['longitude'],
                (float) $entreprise->latitude,
                (float) $entreprise->longitude,
            );

            return $entreprise;
        });
    }

    /**
     * @param  array<int, string>  $keywords
     * @param  array{latitude: float, longitude: float, city: ?string, source: string}  $visitorLocation
     */
    private function scoreEntreprise($entreprise, array $keywords, string $query, array $visitorLocation, bool $useBrowserProximity)
    {
        $score = 0;
        $lowerQuery = mb_strtolower($query);
        $lowerNom = mb_strtolower($entreprise->nom);
        $lowerDescription = mb_strtolower($entreprise->description ?? '');
        $lowerType = mb_strtolower($entreprise->type_activite ?? '');
        $lowerVille = mb_strtolower($entreprise->ville ?? '');
        $lowerMotsCles = mb_strtolower($entreprise->mots_cles ?? '');
        $lowerEmail = mb_strtolower($entreprise->email ?? '');
        $lowerTelephone = mb_strtolower($entreprise->telephone ?? '');

        $servicesText = mb_strtolower($entreprise->typesServices->pluck('nom')->implode(' ').' '.
                                     $entreprise->typesServices->pluck('description')->implode(' '));

        $userName = mb_strtolower($entreprise->user->name ?? '');
        $userEmail = mb_strtolower($entreprise->user->email ?? '');

        if ($lowerNom === $lowerQuery) {
            $score += 200;
        } elseif (str_starts_with($lowerNom, $lowerQuery)) {
            $score += 150;
        } elseif (str_contains($lowerNom, $lowerQuery)) {
            $score += 100;
        }

        foreach ($keywords as $keyword) {
            $lowerKeyword = mb_strtolower($keyword);

            if (str_starts_with($lowerNom, $lowerKeyword)) {
                $score += 60;
            } elseif (str_contains($lowerNom, $lowerKeyword)) {
                $score += 50;
            }

            if (str_contains($lowerMotsCles, $lowerKeyword)) {
                $score += 40;
            }

            if (str_contains($lowerType, $lowerKeyword)) {
                $score += 30;
            }

            if (str_contains($servicesText, $lowerKeyword)) {
                $score += 35;
            }

            if (str_contains($lowerDescription, $lowerKeyword)) {
                $score += 20;
            }

            if (str_contains($lowerVille, $lowerKeyword)) {
                $score += 15;
            }

            if (str_contains($lowerEmail, $lowerKeyword)) {
                $score += 10;
            }

            if (str_contains($lowerTelephone, $lowerKeyword)) {
                $score += 10;
            }

            if (str_contains($userName, $lowerKeyword)) {
                $score += 25;
            }

            if (str_contains($userEmail, $lowerKeyword)) {
                $score += 10;
            }
        }

        if ($entreprise->estVirtuelle()) {
            $score += 8;
        } elseif ($entreprise->hasCoordinates()) {
            $score += 5;
        }

        if ($useBrowserProximity && $entreprise->hasCoordinates()) {
            $distance = $this->haversineKm(
                $visitorLocation['latitude'],
                $visitorLocation['longitude'],
                (float) $entreprise->latitude,
                (float) $entreprise->longitude,
            );
            $entreprise->distance = $distance;
            $score += (int) max(0, round(25 - min($distance, 25)));
        }

        $entreprise->relevance_score = $score;

        return $entreprise;
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
