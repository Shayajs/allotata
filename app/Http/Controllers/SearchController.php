<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        // Construire la requête de base
        $entrepriseQuery = Entreprise::query()
            ->with(['user', 'typesServices', 'avis']);

        // Appliquer la recherche par proximité si coordonnées fournies
        if ($villeLat && $villeLng && $rayon) {
            $lat = (float) $villeLat;
            $lng = (float) $villeLng;
            $radius = (float) $rayon;

            // Formule Haversine pour calculer la distance
            $haversine = "(
                6371 * acos(
                    cos(radians({$lat})) 
                    * cos(radians(latitude)) 
                    * cos(radians(longitude) - radians({$lng})) 
                    + sin(radians({$lat})) 
                    * sin(radians(latitude))
                )
            )";

            $entrepriseQuery->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->selectRaw("entreprises.*, {$haversine} AS distance")
                ->whereRaw("{$haversine} <= ?", [$radius]);
        } elseif ($villeFilter) {
            // Recherche par nom de ville si pas de coordonnées
            $entrepriseQuery->where('ville', 'LIKE', "%{$villeFilter}%");
        }

        // Filtrer par type d'activité
        if ($typeActivite) {
            $entrepriseQuery->where('type_activite', $typeActivite);
        }

        // Si pas de recherche texte mais des filtres sont appliqués
        if (empty($query) && ($villeFilter || $typeActivite)) {
            $allResults = $entrepriseQuery
                ->get()
                ->filter(function($entreprise) {
                    return $entreprise->aAbonnementActif();
                });

            // Trier par distance si recherche par proximité
            if ($villeLat && $villeLng && $rayon) {
                $allResults = $allResults->sortBy('distance');
            }

            return view('search.results', [
                'results' => $allResults->values(),
                'query' => $query,
                'count' => $allResults->count()
            ]);
        }

        if (empty($query)) {
            return view('search.results', [
                'results' => collect([]),
                'query' => '',
                'count' => 0
            ]);
        }

        // Séparer les mots-clés
        $keywords = preg_split('/\s+/', $query);
        $keywords = array_filter($keywords, function($keyword) {
            return strlen($keyword) >= 2; // Ignorer les mots trop courts
        });

        if (empty($keywords)) {
            return view('search.results', [
                'results' => collect([]),
                'query' => $query,
                'count' => 0
            ]);
        }

        // Recherche approfondie dans tous les champs disponibles
        $allResults = $entrepriseQuery
            ->where(function($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $q->where(function($subQ) use ($keyword) {
                        // Recherche dans le nom (pondération élevée)
                        $subQ->where('nom', 'LIKE', "%{$keyword}%")
                            // Recherche dans la description
                            ->orWhere('description', 'LIKE', "%{$keyword}%")
                            // Recherche dans le type d'activité
                            ->orWhere('type_activite', 'LIKE', "%{$keyword}%")
                            // Recherche dans la ville
                            ->orWhere('ville', 'LIKE', "%{$keyword}%")
                            // Recherche dans les mots-clés manuels
                            ->orWhere('mots_cles', 'LIKE', "%{$keyword}%")
                            // Recherche dans l'email
                            ->orWhere('email', 'LIKE', "%{$keyword}%")
                            // Recherche dans le téléphone
                            ->orWhere('telephone', 'LIKE', "%{$keyword}%")
                            // Recherche dans le statut juridique
                            ->orWhere('status_juridique', 'LIKE', "%{$keyword}%")
                            // Recherche dans le SIREN
                            ->orWhere('siren', 'LIKE', "%{$keyword}%")
                            // Recherche dans le code postal
                            ->orWhere('code_postal', 'LIKE', "%{$keyword}%")
                            // Recherche dans l'adresse
                            ->orWhere('adresse_rue', 'LIKE', "%{$keyword}%")
                            // Recherche dans les types de services (via relation)
                            ->orWhereHas('typesServices', function($typeQ) use ($keyword) {
                                $typeQ->where('nom', 'LIKE', "%{$keyword}%")
                                    ->orWhere('description', 'LIKE', "%{$keyword}%");
                            })
                            // Recherche dans le nom de l'utilisateur (gérant)
                            ->orWhereHas('user', function($userQ) use ($keyword) {
                                $userQ->where('name', 'LIKE', "%{$keyword}%")
                                    ->orWhere('email', 'LIKE', "%{$keyword}%");
                            });
                    });
                }
            })
            ->get()
            ->filter(function($entreprise) {
                // Filtrer uniquement les entreprises avec un abonnement actif
                return $entreprise->aAbonnementActif();
            })
            ->map(function($entreprise) use ($keywords, $query) {
                // Calculer un score de pertinence approfondi
                $score = 0;
                $lowerQuery = mb_strtolower($query);
                $lowerNom = mb_strtolower($entreprise->nom);
                $lowerDescription = mb_strtolower($entreprise->description ?? '');
                $lowerType = mb_strtolower($entreprise->type_activite ?? '');
                $lowerVille = mb_strtolower($entreprise->ville ?? '');
                $lowerMotsCles = mb_strtolower($entreprise->mots_cles ?? '');
                $lowerEmail = mb_strtolower($entreprise->email ?? '');
                $lowerTelephone = mb_strtolower($entreprise->telephone ?? '');
                $lowerStatusJuridique = mb_strtolower($entreprise->status_juridique ?? '');
                
                // Vérifier les services
                $servicesText = mb_strtolower($entreprise->typesServices->pluck('nom')->implode(' ') . ' ' . 
                                             $entreprise->typesServices->pluck('description')->implode(' '));
                
                // Vérifier le nom de l'utilisateur
                $userName = mb_strtolower($entreprise->user->name ?? '');
                $userEmail = mb_strtolower($entreprise->user->email ?? '');

                // Score pour correspondance exacte du nom
                if ($lowerNom === $lowerQuery) {
                    $score += 200; // Correspondance exacte = score très élevé
                } elseif (str_starts_with($lowerNom, $lowerQuery)) {
                    $score += 150; // Commence par la requête
                } elseif (str_contains($lowerNom, $lowerQuery)) {
                    $score += 100; // Contient la requête
                }

                // Score pour correspondance partielle dans tous les champs
                foreach ($keywords as $keyword) {
                    $lowerKeyword = mb_strtolower($keyword);
                    
                    // Nom (pondération très élevée)
                    if (str_starts_with($lowerNom, $lowerKeyword)) {
                        $score += 60;
                    } elseif (str_contains($lowerNom, $lowerKeyword)) {
                        $score += 50;
                    }
                    
                    // Mots-clés manuels (pondération élevée)
                    if (str_contains($lowerMotsCles, $lowerKeyword)) {
                        $score += 40;
                    }
                    
                    // Type d'activité
                    if (str_contains($lowerType, $lowerKeyword)) {
                        $score += 30;
                    }
                    
                    // Services
                    if (str_contains($servicesText, $lowerKeyword)) {
                        $score += 35;
                    }
                    
                    // Description
                    if (str_contains($lowerDescription, $lowerKeyword)) {
                        $score += 20;
                    }
                    
                    // Ville
                    if (str_contains($lowerVille, $lowerKeyword)) {
                        $score += 15;
                    }
                    
                    // Email
                    if (str_contains($lowerEmail, $lowerKeyword)) {
                        $score += 10;
                    }
                    
                    // Téléphone
                    if (str_contains($lowerTelephone, $lowerKeyword)) {
                        $score += 10;
                    }
                    
                    // Nom du gérant
                    if (str_contains($userName, $lowerKeyword)) {
                        $score += 25;
                    }
                    
                    // Email du gérant
                    if (str_contains($userEmail, $lowerKeyword)) {
                        $score += 10;
                    }
                }

                // Bonus si l'entreprise a des coordonnées GPS (meilleure qualité de données)
                if ($entreprise->hasCoordinates()) {
                    $score += 5;
                }

                $entreprise->relevance_score = $score;
                return $entreprise;
            });

        // Trier par distance si recherche par proximité, sinon par pertinence
        if ($villeLat && $villeLng && $rayon) {
            $allResults = $allResults->sortBy('distance');
        } else {
            $allResults = $allResults->sortByDesc('relevance_score');
        }

        return view('search.results', [
            'results' => $allResults->values(),
            'query' => $query,
            'count' => $allResults->count()
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
            ->with(['typesServices' => function($q) {
                $q->where('est_actif', true)->limit(3);
            }])
            ->where(function($q) use ($query) {
                $lowerQuery = mb_strtolower($query);
                $q->whereRaw('LOWER(nom) LIKE ?', ["%{$lowerQuery}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$lowerQuery}%"])
                    ->orWhereRaw('LOWER(type_activite) LIKE ?', ["%{$lowerQuery}%"])
                    ->orWhereRaw('LOWER(ville) LIKE ?', ["%{$lowerQuery}%"])
                    ->orWhereRaw('LOWER(mots_cles) LIKE ?', ["%{$lowerQuery}%"])
                    ->orWhereHas('typesServices', function($typeQ) use ($lowerQuery) {
                        $typeQ->whereRaw('LOWER(nom) LIKE ?', ["%{$lowerQuery}%"]);
                    });
            })
            ->get()
            ->filter(function($entreprise) {
                // Filtrer uniquement les entreprises avec un abonnement actif
                return $entreprise->aAbonnementActif();
            })
            ->take(8)
            ->map(function($entreprise) {
                return [
                    'id' => $entreprise->id,
                    'nom' => $entreprise->nom,
                    'type_activite' => $entreprise->type_activite,
                    'ville' => $entreprise->ville,
                    'slug' => $entreprise->slug,
                    'logo' => $entreprise->logo ? asset('media/' . $entreprise->logo) : null,
                    'est_verifiee' => $entreprise->est_verifiee,
                    'services' => $entreprise->typesServices->pluck('nom')->take(2)->toArray(),
                ];
            });

        return response()->json($results);
    }
}
