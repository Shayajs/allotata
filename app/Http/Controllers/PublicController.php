<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\Reservation;
use App\Models\TypeService;
use App\Models\Produit;
use App\Models\CommandeProduit;
use App\Models\Notification;
use App\Models\EntrepriseVisite;
use App\Mail\ReservationConfirmationEmail;
use App\Mail\ReservationCancelledEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class PublicController extends Controller
{
    public function show($slug)
    {
        // Enregistrer la visite (de manière asynchrone pour ne pas bloquer)
        // Seulement si l'utilisateur a consenti au tracking
        try {
            $user = Auth::user();
            $entreprise = Entreprise::where('slug', $slug)->first();
            if ($entreprise && ($user === null || ($user && ($user->tracking_consent ?? true)))) {
                EntrepriseVisite::enregistrerVisite($entreprise, 'accueil', $user);
            }
        } catch (\Exception $e) {
            \Log::warning('Erreur lors du tracking de visite: ' . $e->getMessage());
        }

        // Cache de 10 minutes pour les pages publiques d'entreprise
        $cacheKey = "entreprise_public_{$slug}";
        $entreprise = \Illuminate\Support\Facades\Cache::remember($cacheKey, 600, function () use ($slug) {
            return Entreprise::where('slug', $slug)
                ->with([
                    'user:id,name,email',
                    'avis' => function($query) {
                        $query->where('est_approuve', true)
                              ->with(['user:id,name', 'photos'])
                              ->latest()
                              ->limit(5);
                    },
                    'realisationPhotos:id,entreprise_id,photo_path,ordre',
                    'typesServices' => function($query) {
                        $query->where('est_actif', true)
                              ->with(['images:id,type_service_id,image_path', 'imageCouverture:id,type_service_id,image_path']);
                    },
                    'produits' => function($query) {
                        $query->where('est_actif', true)
                              ->with([
                                  'stock:id,produit_id,quantite_disponible',
                                  'images:id,produit_id,image_path',
                                  'imageCouverture:id,produit_id,image_path',
                                  'promotionActive:id,produit_id,prix_promotion,date_debut,date_fin'
                              ]);
                    }
                ])
                ->firstOrFail();
        });

        // Recharger le user avec toutes ses colonnes pour vérifier l'abonnement
        // (car il est mis en cache avec seulement id,name,email)
        if ($entreprise->user_id) {
            // Forcer le rechargement en désactivant puis rechargeant la relation
            $entreprise->unsetRelation('user');
            $entreprise->load('user'); // Recharge toutes les colonnes du user
        }

        // Vérifier si l'entreprise a un abonnement actif (via son gérant)
        // MAIS permettre au propriétaire de voir sa propre entreprise même sans abonnement
        $user = Auth::user();
        $isOwner = $user && $user->id === $entreprise->user_id;
        
        if (!$entreprise->aAbonnementActif() && !$isOwner) {
            abort(404, 'Cette entreprise n\'est pas disponible en ligne.');
        }

        // Charger les horaires d'ouverture
        $horaires = $entreprise->horairesOuverture()
            ->orderBy('jour_semaine')
            ->get();

        // Charger les avis avec pagination et photos
        $avis = $entreprise->avis()->with(['user', 'photos'])->paginate(5);

        // Vérifier si l'utilisateur connecté peut laisser un avis
        $peutLaisserAvis = false;
        $userAvis = null;
        
        if (Auth::check()) {
            $user = Auth::user();
            
            // Vérifier si l'utilisateur a déjà laissé un avis
            $userAvis = \App\Models\Avis::where('user_id', $user->id)
                ->where('entreprise_id', $entreprise->id)
                ->first();
            
            // Vérifier si l'utilisateur peut laisser un avis (réservation payée et terminée)
            if (!$userAvis) {
                $peutLaisserAvis = \App\Models\Reservation::where('user_id', $user->id)
                    ->where('entreprise_id', $entreprise->id)
                    ->where('est_paye', true)
                    ->where('statut', 'terminee')
                    ->exists();
            }
        }
        
        // Charger les services actifs avec leurs images
        $services = $entreprise->typesServices()
            ->where('est_actif', true)
            ->with(['images', 'imageCouverture'])
            ->orderBy('prix')
            ->get();

        // Charger les produits actifs avec leurs images, stocks et promotions
        $produits = $entreprise->produits()
            ->where('est_actif', true)
            ->with(['stock', 'images', 'imageCouverture', 'promotionActive'])
            ->get()
            ->filter(function($produit) {
                return $produit->estDisponible();
            });

        return view('public.entreprise', [
            'entreprise' => $entreprise,
            'slug' => $slug,
            'horaires' => $horaires,
            'services' => $services,
            'produits' => $produits,
            'avis' => $avis,
            'userAvis' => $userAvis,
            'peutLaisserAvis' => $peutLaisserAvis,
            'isOwner' => $isOwner,
        ]);
    }

    /**
     * Afficher la page de prise de rendez-vous
     */
    public function agenda($slug)
    {
        // Enregistrer la visite (seulement si consentement)
        try {
            $user = Auth::user();
            $entreprise = Entreprise::where('slug', $slug)->first();
            if ($entreprise && ($user === null || ($user && ($user->tracking_consent ?? true)))) {
                EntrepriseVisite::enregistrerVisite($entreprise, 'agenda', $user);
            }
        } catch (\Exception $e) {
            \Log::warning('Erreur lors du tracking de visite: ' . $e->getMessage());
        }

        $entreprise = Entreprise::where('slug', $slug)
            ->with(['typesServices' => function($query) {
                $query->where('est_actif', true)->with('options.choices');
            }])
            ->firstOrFail();

        // Recharger le user avec toutes ses colonnes pour vérifier l'abonnement
        if ($entreprise->user_id) {
            // Forcer le rechargement en désactivant puis rechargeant la relation
            $entreprise->unsetRelation('user');
            $entreprise->load('user'); // Recharge toutes les colonnes du user
        }

        // Vérifier si l'entreprise a un abonnement actif (via son gérant)
        // MAIS permettre au propriétaire de voir sa propre entreprise même sans abonnement
        $user = Auth::user();
        $isOwner = $user && $user->id === $entreprise->user_id;
        
        if (!$entreprise->aAbonnementActif() && !$isOwner) {
            abort(404, 'Cette entreprise n\'est pas disponible en ligne.');
        }

        // Si l'entreprise n'accepte les RDV que via messagerie, rediriger vers la messagerie
        if ($entreprise->rdv_uniquement_messagerie) {
            return redirect()->route('messagerie.show', $slug)
                ->with('info', 'Cette entreprise accepte les rendez-vous uniquement via la messagerie. Veuillez contacter l\'entreprise pour prendre rendez-vous.');
        }

        // Charger les membres si l'entreprise a la gestion multi-personnes
        $membres = collect([]);
        if ($entreprise->aGestionMultiPersonnes()) {
            $membres = $entreprise->membres()
                ->where('est_actif', true)
                ->with('user')
                ->get();
        }

        $horairesRaw = $entreprise->horairesOuverture()
            ->orderBy('jour_semaine')
            ->orderBy('ordre_plage')
            ->get();

        // Formater les horaires pour le JSON (pour FullCalendar)
        $horaires = $horairesRaw->map(function($horaire) {
            return [
                'id' => $horaire->id,
                'jour_semaine' => $horaire->jour_semaine,
                'heure_ouverture' => $horaire->heure_ouverture ? \Carbon\Carbon::parse($horaire->heure_ouverture)->format('H:i') : null,
                'heure_fermeture' => $horaire->heure_fermeture ? \Carbon\Carbon::parse($horaire->heure_fermeture)->format('H:i') : null,
                'est_exceptionnel' => $horaire->est_exceptionnel,
                'date_exception' => $horaire->date_exception ? $horaire->date_exception->format('Y-m-d') : null,
            ];
        });

        // Calculer les 7 prochains jours (de aujourd'hui à 7 jours plus tard)
        $jours = [];
        $aujourdhui = now();
        
        for ($i = 0; $i < 7; $i++) {
            $date = $aujourdhui->copy()->addDays($i);
            $jourSemaine = $date->dayOfWeek; // 0 = dimanche, 1 = lundi, etc.
            $dateString = $date->format('Y-m-d');
            
            // Utiliser le service ExceptionDateService pour récupérer les horaires applicables
            $exceptionDateService = app(\App\Services\ExceptionDateService::class);
            $plagesHoraires = $exceptionDateService->getHorairesForDate($entreprise, $date);
            
            // Calculer les créneaux disponibles pour ce jour (pour toutes les plages)
            $creneaux = [];
            
            if ($plagesHoraires->isNotEmpty()) {
                // Trouver la durée minimale des services (pour calculer les créneaux)
                $dureeMinimale = $entreprise->typesServices->min('duree_minutes') ?? 30;
                
                // Générer des créneaux basés sur la durée minimale (minimum 30 minutes)
                $dureeCreneau = max(30, ceil($dureeMinimale / 30) * 30);
                
                // Récupérer toutes les réservations pour ce jour (y compris en attente pour bloquer le créneau)
                $reservationsDuJour = Reservation::where('entreprise_id', $entreprise->id)
                    ->whereDate('date_reservation', $date->format('Y-m-d'))
                    ->whereIn('statut', ['en_attente', 'confirmee'])
                    ->get();
                
                // Pour chaque plage horaire du jour, générer les créneaux
                foreach ($plagesHoraires as $plage) {
                    if (!$plage->heure_ouverture || !$plage->heure_fermeture) {
                        continue; // Plage fermée, on passe à la suivante
                    }
                    
                    $heureOuverture = \Carbon\Carbon::parse($plage->heure_ouverture);
                    $heureFermeture = \Carbon\Carbon::parse($plage->heure_fermeture);
                    
                    $creneauActuel = $date->copy()->setTimeFromTimeString($heureOuverture->format('H:i'));
                    $fermeture = $date->copy()->setTimeFromTimeString($heureFermeture->format('H:i'));
                    
                    // Si c'est aujourd'hui, commencer à partir de maintenant + 1 heure minimum
                    if ($i === 0) {
                        $creneauActuel = max($creneauActuel, now()->addHour()->startOfHour());
                    }
                    
                    // Générer les créneaux pour cette plage
                    while ($creneauActuel->copy()->addMinutes($dureeCreneau)->lte($fermeture)) {
                        $debutCreneau = $creneauActuel->copy();
                        $finCreneau = $creneauActuel->copy()->addMinutes($dureeCreneau);
                        
                        // Ne pas dépasser la fin de la plage
                        if ($finCreneau->gt($fermeture)) {
                            break;
                        }
                        
                        // Vérifier si ce créneau chevauche avec une réservation existante
                        $estReserve = false;
                        foreach ($reservationsDuJour as $reservation) {
                            $debutReservation = \Carbon\Carbon::parse($reservation->date_reservation);
                            $finReservation = $debutReservation->copy()->addMinutes((int) ($reservation->duree_minutes ?? 30));
                            
                            // Vérifier le chevauchement
                            if ($debutCreneau->lt($finReservation) && $finCreneau->gt($debutReservation)) {
                                $estReserve = true;
                                break;
                            }
                        }
                        
                        if (!$estReserve) {
                            $creneaux[] = [
                                'heure' => $creneauActuel->format('H:i'),
                                'datetime' => $creneauActuel->format('Y-m-d H:i:s'),
                                'date' => $creneauActuel->format('Y-m-d'),
                                'time' => $creneauActuel->format('H:i'),
                            ];
                        }
                        
                        $creneauActuel->addMinutes(30); // Incrémenter de 30 minutes pour plus de flexibilité
                    }
                }
            }
            
            // Trier les créneaux par heure pour avoir un ordre chronologique
            usort($creneaux, function($a, $b) {
                return strcmp($a['time'], $b['time']);
            });
            
            // Déterminer si le jour est fermé (pas de plages ou toutes les plages sont fermées)
            $horaire = $plagesHoraires->first();
            $estFerme = $plagesHoraires->isEmpty() || $plagesHoraires->every(function($p) {
                return !$p->heure_ouverture || !$p->heure_fermeture;
            });
            
            $jours[] = [
                'date' => $date,
                'jour_semaine' => $jourSemaine,
                'nom_jour' => $date->locale('fr')->dayName,
                'date_formatee' => $date->format('d/m/Y'),
                'date_input' => $date->format('Y-m-d'),
                'est_aujourdhui' => $i === 0,
                'horaire' => $horaire,
                'plages_horaires' => $plagesHoraires->map(function($p) {
                    return [
                        'heure_ouverture' => $p->heure_ouverture ? \Carbon\Carbon::parse($p->heure_ouverture)->format('H:i') : null,
                        'heure_fermeture' => $p->heure_fermeture ? \Carbon\Carbon::parse($p->heure_fermeture)->format('H:i') : null,
                    ];
                })->values(),
                'est_ferme' => $estFerme,
                'creneaux' => $creneaux,
            ];
        }

        // Récupérer les informations utilisateur pour préchargement (avec gestion d'erreur)
        $userInfo = null;
        if ($user) {
            try {
                $userInfo = [
                    'name' => $user->name ?? '',
                    'surname' => $user->surname ?? '',
                    'email' => $user->email ?? '',
                    'telephone' => $user->telephone ?? '',
                ];
            } catch (\Exception $e) {
                // En cas d'erreur, on laisse userInfo à null
                \Log::warning('Erreur lors de la récupération des informations utilisateur pour préchargement: ' . $e->getMessage());
                $userInfo = null;
            }
        }

        return view('public.agenda', [
            'entreprise' => $entreprise,
            'horaires' => $horaires,
            'jours' => $jours,
            'isOwner' => $isOwner,
            'membres' => $membres,
            'aGestionMultiPersonnes' => $entreprise->aGestionMultiPersonnes(),
            'userInfo' => $userInfo,
        ]);
    }

    /**
     * Afficher la page publique du store (vente de produits)
     */
    public function store($slug)
    {
        // Enregistrer la visite (seulement si consentement)
        try {
            $user = Auth::user();
            $entreprise = Entreprise::where('slug', $slug)->first();
            if ($entreprise && ($user === null || ($user && ($user->tracking_consent ?? true)))) {
                EntrepriseVisite::enregistrerVisite($entreprise, 'store', $user);
            }
        } catch (\Exception $e) {
            \Log::warning('Erreur lors du tracking de visite: ' . $e->getMessage());
        }

        $entreprise = Entreprise::where('slug', $slug)
            ->with(['produits' => function($query) {
                $query->where('est_actif', true)
                      ->with(['stock', 'images', 'imageCouverture', 'promotionActive']);
            }])
            ->firstOrFail();

        // Recharger le user avec toutes ses colonnes pour vérifier l'abonnement
        if ($entreprise->user_id) {
            // Forcer le rechargement en désactivant puis rechargeant la relation
            $entreprise->unsetRelation('user');
            $entreprise->load('user'); // Recharge toutes les colonnes du user
        }

        // Vérifier si l'entreprise a un abonnement actif (via son gérant)
        $user = Auth::user();
        $isOwner = $user && $user->id === $entreprise->user_id;
        
        if (!$entreprise->aAbonnementActif() && !$isOwner) {
            abort(404, 'Cette entreprise n\'est pas disponible en ligne.');
        }

        // Filtrer uniquement les produits disponibles
        $produits = $entreprise->produits->filter(function($produit) {
            return $produit->estDisponible();
        });

        return view('public.store', [
            'entreprise' => $entreprise,
            'slug' => $slug,
            'produits' => $produits,
            'isOwner' => $isOwner,
        ]);
    }

    /**
     * Afficher la page dédiée aux services
     */
    public function services($slug)
    {
        // Enregistrer la visite (seulement si consentement)
        try {
            $user = Auth::user();
            $entreprise = Entreprise::where('slug', $slug)->first();
            if ($entreprise && ($user === null || ($user && ($user->tracking_consent ?? true)))) {
                EntrepriseVisite::enregistrerVisite($entreprise, 'services', $user);
            }
        } catch (\Exception $e) {
            \Log::warning('Erreur lors du tracking de visite: ' . $e->getMessage());
        }

        $entreprise = Entreprise::where('slug', $slug)
            ->with(['typesServices' => function($query) {
                $query->where('est_actif', true)
                      ->with(['images', 'imageCouverture', 'options.choices', 'serviceAvis' => function($q) {
                          $q->with(['user:id,name', 'photos', 'reservation']);
                      }]);
            }])
            ->with('realisationPhotos')
            ->firstOrFail();

        // Recharger le user avec toutes ses colonnes pour vérifier l'abonnement
        if ($entreprise->user_id) {
            $entreprise->unsetRelation('user');
            $entreprise->load('user');
        }

        // Vérifier si l'entreprise a un abonnement actif (via son gérant)
        $user = Auth::user();
        $isOwner = $user && $user->id === $entreprise->user_id;
        
        if (!$entreprise->aAbonnementActif() && !$isOwner) {
            abort(404, 'Cette entreprise n\'est pas disponible en ligne.');
        }

        $typesServices = $this->trierServices($entreprise->typesServices, $entreprise);

        return view('public.services', [
            'entreprise' => $entreprise,
            'slug' => $slug,
            'typesServices' => $typesServices,
            'isOwner' => $isOwner,
        ]);
    }

    /**
     * Afficher la page dédiée aux produits
     */
    public function produits($slug)
    {
        // Enregistrer la visite (seulement si consentement)
        try {
            $user = Auth::user();
            $entreprise = Entreprise::where('slug', $slug)->first();
            if ($entreprise && ($user === null || ($user && ($user->tracking_consent ?? true)))) {
                EntrepriseVisite::enregistrerVisite($entreprise, 'produits', $user);
            }
        } catch (\Exception $e) {
            \Log::warning('Erreur lors du tracking de visite: ' . $e->getMessage());
        }

        $entreprise = Entreprise::where('slug', $slug)
            ->with(['produits' => function($query) {
                $query->where('est_actif', true)
                      ->with(['stock', 'images', 'imageCouverture', 'promotionActive', 'produitAvis' => function($q) {
                          $q->with(['user:id,name', 'photos', 'reservation']);
                      }]);
            }])
            ->with('realisationPhotos')
            ->firstOrFail();

        // Recharger le user avec toutes ses colonnes pour vérifier l'abonnement
        if ($entreprise->user_id) {
            $entreprise->unsetRelation('user');
            $entreprise->load('user');
        }

        // Vérifier si l'entreprise a un abonnement actif (via son gérant)
        $user = Auth::user();
        $isOwner = $user && $user->id === $entreprise->user_id;
        
        if (!$entreprise->aAbonnementActif() && !$isOwner) {
            abort(404, 'Cette entreprise n\'est pas disponible en ligne.');
        }

        // #region agent log
        try {
            $logData = [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'B2',
                'location' => 'PublicController.php:' . __LINE__,
                'message' => 'Avant filtrage produits',
                'data' => [
                    'slug' => $slug,
                    'produits_total' => $entreprise->produits->count(),
                    'produits_actifs' => $entreprise->produits->where('est_actif', true)->count(),
                ],
                'timestamp' => time() * 1000,
            ];
            $logPath = base_path('.cursor/debug.log');
            $logDir = dirname($logPath);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            @file_put_contents($logPath, json_encode($logData) . "\n", FILE_APPEND);
        } catch (\Exception $e) {}
        // #endregion

        // Filtrer uniquement les produits disponibles
        $produits = $entreprise->produits->filter(function($produit) {
            $estDisponible = $produit->estDisponible();
            
            // #region agent log
            if (!$estDisponible) {
                $logData = [
                    'sessionId' => 'debug-session',
                    'runId' => 'run1',
                    'hypothesisId' => 'B2',
                    'location' => 'PublicController.php:' . __LINE__,
                    'message' => 'Produit filtré (non disponible)',
                    'data' => [
                        'produit_id' => $produit->id,
                        'nom' => $produit->nom,
                        'est_actif' => $produit->est_actif,
                        'gestion_stock' => $produit->gestion_stock,
                        'stock_quantite' => $produit->stock ? $produit->stock->quantite_disponible : null,
                        'images_count' => $produit->images->count(),
                    ],
                    'timestamp' => time() * 1000,
                ];
                try {
                    $logPath = base_path('.cursor/debug.log');
                    $logDir = dirname($logPath);
                    if (!is_dir($logDir)) {
                        @mkdir($logDir, 0755, true);
                    }
                    @file_put_contents($logPath, json_encode($logData) . "\n", FILE_APPEND);
                } catch (\Exception $e) {}
            }
            // #endregion
            
            return $estDisponible;
        });

        // Trier les produits selon le mode choisi
        $produits = $this->trierProduits($produits, $entreprise);

        // #region agent log
        try {
            $logData = [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'B2',
                'location' => 'PublicController.php:' . __LINE__,
                'message' => 'Après filtrage produits',
                'data' => [
                    'produits_disponibles' => $produits->count(),
                ],
                'timestamp' => time() * 1000,
            ];
            $logPath = base_path('.cursor/debug.log');
            $logDir = dirname($logPath);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            @file_put_contents($logPath, json_encode($logData) . "\n", FILE_APPEND);
        } catch (\Exception $e) {}
        // #endregion

        return view('public.produits', [
            'entreprise' => $entreprise,
            'slug' => $slug,
            'produits' => $produits,
            'isOwner' => $isOwner,
        ]);
    }

    /**
     * Créer une commande de produit
     */
    public function storeCommandeProduit(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();

        // Vérifier si l'entreprise a un abonnement actif
        if (!$entreprise->aAbonnementActif() && (!$user || $user->id !== $entreprise->user_id)) {
            abort(404, 'Cette entreprise n\'est pas disponible en ligne.');
        }

        $validated = $request->validate([
            'produit_id' => 'required|exists:produits,id',
            'quantite' => 'required|integer|min:1',
            'mode_livraison' => 'required|in:livraison,vente_sur_place,a_discuter',
            'adresse_livraison' => 'required_if:mode_livraison,livraison|nullable|string|max:255',
            'code_postal_livraison' => 'required_if:mode_livraison,livraison|nullable|string|max:10',
            'ville_livraison' => 'required_if:mode_livraison,livraison|nullable|string|max:100',
            'date_livraison_souhaitee' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'nom_client' => 'required_if:user_id,null|nullable|string|max:255',
            'email_client' => 'nullable|email|max:255',
            'telephone_client_non_inscrit' => 'required_if:user_id,null|nullable|string|max:20',
        ]);

        $produit = Produit::where('id', $validated['produit_id'])
            ->where('entreprise_id', $entreprise->id)
            ->where('est_actif', true)
            ->with(['promotionActive', 'stock'])
            ->firstOrFail();

        // Vérifier la disponibilité
        if (!$produit->estDisponible()) {
            return back()->withErrors(['error' => 'Ce produit n\'est plus disponible.']);
        }

        // Vérifier les options de livraison/vente
        if ($validated['mode_livraison'] === 'livraison' && !$produit->livraisonDisponible()) {
            return back()->withErrors(['error' => 'La livraison n\'est pas disponible pour ce produit.']);
        }

        if ($validated['mode_livraison'] === 'vente_sur_place' && !$produit->venteSurPlaceDisponible()) {
            return back()->withErrors(['error' => 'La vente sur place n\'est pas disponible pour ce produit.']);
        }

        // Calculer le prix
        $promotion = $produit->promotionActive()->first();
        $prixUnitaire = $promotion ? $promotion->prix_promotion : $produit->prix;
        $prixTotal = $prixUnitaire * $validated['quantite'];

        // Vérifier le stock si gestion immédiate
        if ($produit->gestion_stock === 'disponible_immediatement') {
            $stock = $produit->stock;
            if (!$stock || $stock->quantite_disponible < $validated['quantite']) {
                return back()->withErrors(['error' => 'Stock insuffisant pour cette quantité.']);
            }
        }

        // Créer la commande
        $commandeData = [
            'entreprise_id' => $entreprise->id,
            'produit_id' => $produit->id,
            'quantite' => $validated['quantite'],
            'prix_unitaire' => $prixUnitaire,
            'prix_total' => $prixTotal,
            'mode_livraison' => $validated['mode_livraison'],
            'notes' => $validated['notes'] ?? null,
            'date_livraison_souhaitee' => $validated['date_livraison_souhaitee'] ?? null,
            'statut' => 'en_attente',
        ];

        if ($user) {
            $commandeData['user_id'] = $user->id;
            if ($user->telephone) {
                $commandeData['telephone_client'] = $user->telephone;
            }
        } else {
            $commandeData['nom_client'] = $validated['nom_client'];
            $commandeData['email_client'] = $validated['email_client'] ?? null;
            $commandeData['telephone_client_non_inscrit'] = $validated['telephone_client_non_inscrit'];
        }

        if ($validated['mode_livraison'] === 'livraison') {
            $commandeData['adresse_livraison'] = $validated['adresse_livraison'];
            $commandeData['code_postal_livraison'] = $validated['code_postal_livraison'];
            $commandeData['ville_livraison'] = $validated['ville_livraison'];
        }

        $commande = CommandeProduit::create($commandeData);

        // Créer une notification pour l'entreprise
        Notification::creer(
            $entreprise->user_id,
            'commande',
            'Nouvelle commande',
            "Nouvelle commande de {$validated['quantite']}x {$produit->nom} pour " . ($user ? $user->name : $validated['nom_client']),
            route('commandes.show', [$slug, $commande->id]),
            ['commande_id' => $commande->id, 'entreprise_id' => $entreprise->id]
        );

        // Envoyer un email à l'entreprise
        try {
            // TODO: Créer EmailHelper::sendNouvelleCommandeEntreprise($commande);
        } catch (\Exception $e) {
            \Log::error("Erreur lors de l'envoi de l'email de nouvelle commande : " . $e->getMessage());
        }

        return redirect()->route('public.produits', $slug)
            ->with('success', 'Votre commande a été enregistrée avec succès. L\'entreprise vous contactera bientôt.');
    }

    /**
     * Créer une réservation
     */
    public function storeReservation(Request $request, $slug)
    {
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();

        // Vérifier que le type de service appartient à l'entreprise (avant validation conditionnelle)
        $typeService = TypeService::where('id', $request->input('type_service_id'))
            ->where('entreprise_id', $entreprise->id)
            ->where('est_actif', true)
            ->firstOrFail();

        $isDateButoire = $typeService->estDateButoire();
        $rules = [
            'type_service_id' => 'required|exists:types_services,id',
            'membre_id' => 'nullable|exists:entreprise_membres,id',
            'lieu' => 'nullable|string|max:255',
            'telephone_client' => 'required|string|max:20',
            'telephone_cache' => 'boolean',
            'notes' => 'nullable|string',
        ];
        if ($isDateButoire) {
            $rules['date_butoire'] = 'required|date|after_or_equal:today';
        } else {
            $rules['date_reservation'] = 'required|date|after:now';
            $rules['heure_reservation'] = 'required|date_format:H:i';
        }
        $validated = $request->validate($rules);

        if ($isDateButoire) {
            $dateButoire = $validated['date_butoire'];
            $dateTime = $dateButoire . ' 00:00:00';
            $debutReservation = \Carbon\Carbon::parse($dateTime);
            $heureReservation = \Carbon\Carbon::parse('09:00'); // fictif pour sélection membre si besoin
        } else {
            $dateTime = $validated['date_reservation'] . ' ' . $validated['heure_reservation'];
            $debutReservation = \Carbon\Carbon::parse($dateTime);
            $heureReservation = \Carbon\Carbon::parse($validated['heure_reservation']);
        }

        // Vérifier si l'utilisateur est connecté
        $userId = Auth::id();
        if (!$userId) {
            return redirect()->route('login')
                ->with('error', 'Vous devez être connecté pour prendre un rendez-vous.');
        }

        // Gérer la sélection du membre
        $membreId = null;
        if (!empty($validated['membre_id'] ?? null)) {
            // Membre spécifié par l'utilisateur
            $membre = \App\Models\EntrepriseMembre::where('id', $validated['membre_id'])
                ->where('entreprise_id', $entreprise->id)
                ->where('est_actif', true)
                ->first();
            
            if (!$membre) {
                return back()->withErrors(['membre_id' => 'Membre invalide.']);
            }
            
            $membreId = $membre->id;
        } elseif ($entreprise->aGestionMultiPersonnes() && !$isDateButoire) {
            // Sélection automatique si multi-personnes et aucun membre spécifié (hors date butoire)
            $selectionService = app(\App\Services\MembreSelectionService::class);
            $membreSelectionne = $selectionService->selectionnerMembre(
                $entreprise,
                $debutReservation,
                $heureReservation,
                $typeService->duree_minutes
            );
            
            if ($membreSelectionne) {
                $membreId = $membreSelectionne->id;
            }
        }

        // Vérifier la disponibilité ET créer la réservation dans une transaction atomique (anti-doublon)
        $statutInitial = $entreprise->accepter_reservations_auto ? 'confirmee' : 'en_attente';
        
        $reservationData = [
            'user_id' => $userId,
            'entreprise_id' => $entreprise->id,
            'membre_id' => $membreId,
            'type_service_id' => $typeService->id,
            'date_reservation' => $dateTime,
            'lieu' => $validated['lieu'] ?? null,
            'telephone_client' => $validated['telephone_client'],
            'telephone_cache' => $validated['telephone_cache'] ?? false,
            'notes' => $validated['notes'] ?? null,
            'prix' => $typeService->prix,
            'duree_minutes' => $typeService->duree_minutes,
            'type_service' => $typeService->nom,
            'statut' => $statutInitial,
        ];
        if ($isDateButoire) {
            $reservationData['date_butoire'] = $validated['date_butoire'];
        }

        $reservation = \App\Services\ReservationSlotService::reserverSiDisponible(
            $entreprise->id,
            $membreId,
            $debutReservation,
            (int) $typeService->duree_minutes,
            fn () => Reservation::create($reservationData),
            $isDateButoire
        );

        if (!$reservation) {
            return back()->withErrors(['error' => 'Ce créneau est déjà réservé. Veuillez choisir un autre horaire.']);
        }

        // Gérer les options sélectionnées
        if ($request->has('service_options')) {
            $totalPrixSupplementaire = 0;
            $totalTempsSupplementaire = 0;
            $optionsLog = [];

            foreach ($request->service_options as $optionId => $choiceId) {
                $choice = \App\Models\ServiceOptionChoice::find($choiceId);
                if ($choice) {
                    $totalPrixSupplementaire += $choice->prix_supplementaire;
                    $totalTempsSupplementaire += $choice->temps_supplementaire;
                    $optionsLog[] = $choice->option->nom . ': ' . $choice->nom;
                }
            }

            if (!empty($optionsLog)) {
                $reservation->update([
                    'prix' => $reservation->prix + $totalPrixSupplementaire,
                    'duree_minutes' => $reservation->duree_minutes + $totalTempsSupplementaire,
                    'notes' => ($reservation->notes ? $reservation->notes . "\n\n" : "") . "Options sélectionnées :\n- " . implode("\n- ", $optionsLog)
                ]);
            }
        }

        // Marquer la visite comme ayant passé une commande (seulement si consentement)
        try {
            $user = Auth::user();
            if ($user === null || ($user && ($user->tracking_consent ?? true))) {
                $sessionId = \Illuminate\Support\Facades\Session::getId();
                $visite = EntrepriseVisite::where('entreprise_id', $entreprise->id)
                    ->where('session_id', $sessionId)
                    ->where('a_passe_commande', false)
                    ->latest()
                    ->first();
                
                if ($visite) {
                    $visite->marquerReservation();
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Erreur lors du marquage de réservation dans visite: ' . $e->getMessage());
        }

        // Invalider le cache des statistiques
        \App\Services\CacheService::clearEntrepriseCache($entreprise->id, $entreprise->slug);

        // Créer une notification pour le gérant
        $gerant = $entreprise->user;
        if ($gerant) {
            $nomClient = $reservation->user ? $reservation->user->name : ($reservation->nom_client ?? 'Client');
            $titreNotification = $statutInitial === 'confirmee' 
                ? 'Nouvelle réservation confirmée automatiquement'
                : 'Nouvelle réservation';
            $messageNotification = $statutInitial === 'confirmee'
                ? "Une nouvelle réservation a été automatiquement confirmée pour le {$reservation->date_reservation->format('d/m/Y à H:i')} par {$nomClient}."
                : "Une nouvelle réservation a été demandée pour le {$reservation->date_reservation->format('d/m/Y à H:i')} par {$nomClient}.";
            
            Notification::creer(
                $gerant->id,
                'reservation',
                $titreNotification,
                $messageNotification,
                route('reservations.show', [$entreprise->slug, $reservation->id]),
                ['reservation_id' => $reservation->id, 'user_id' => $userId]
            );

            // Envoyer un email au gérant
            try {
                $reservation->refresh();
                \App\Helpers\EmailHelper::sendReservationConfirmationGerant($reservation);
            } catch (\Exception $e) {
                \Log::error("Erreur lors de l'envoi de l'email au gérant : " . $e->getMessage());
            }
        }

        // Créer une notification pour le client (uniquement si inscrit)
        if ($userId) {
            if ($statutInitial === 'confirmee') {
                // Réservation confirmée automatiquement
                Notification::creer(
                    $userId,
                    'reservation',
                    'Réservation confirmée',
                    "Votre réservation pour {$entreprise->nom} le {$reservation->date_reservation->format('d/m/Y à H:i')} a été confirmée !",
                    route('dashboard'),
                    ['reservation_id' => $reservation->id, 'entreprise_id' => $entreprise->id]
                );

                // Envoyer un email de confirmation au client
                try {
                    $reservation->refresh();
                    \App\Helpers\EmailHelper::sendReservationConfirmationClient($reservation);
                } catch (\Exception $e) {
                    \Log::error("Erreur lors de l'envoi de l'email de confirmation : " . $e->getMessage());
                }
            } else {
                // Réservation en attente
                Notification::creer(
                    $userId,
                    'reservation',
                    'Réservation en attente',
                    "Votre demande de réservation pour {$entreprise->nom} le {$reservation->date_reservation->format('d/m/Y à H:i')} est en attente de confirmation.",
                    route('dashboard'),
                    ['reservation_id' => $reservation->id, 'entreprise_id' => $entreprise->id]
                );
            }
        }

        // Message de succès selon le statut
        $messageSuccess = $statutInitial === 'confirmee'
            ? 'Votre réservation a été confirmée avec succès !'
            : 'Votre demande de réservation a été envoyée ! La tata va la valider prochainement.';

        return redirect()->route('public.entreprise', $slug)
            ->with('success', $messageSuccess);
    }

    /**
     * API : Récupérer les réservations pour l'agenda public (format JSON pour FullCalendar)
     * Ne montre pas les détails, juste "Indisponible" pour préserver la confidentialité
     */
    public function getReservations($slug)
    {
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        // Récupérer le membre sélectionné depuis la requête (si multi-personnes)
        $membreId = request()->get('membre_id');

        // Récupérer les réservations confirmées et en attente
        $query = Reservation::where('entreprise_id', $entreprise->id)
            ->whereIn('statut', ['en_attente', 'confirmee']);
        
        // Filtrer par membre si spécifié
        if ($membreId && $entreprise->aGestionMultiPersonnes()) {
            $query->where('membre_id', $membreId);
        }
        
        $reservations = $query->get()
            ->map(function($reservation) {
                $debut = \Carbon\Carbon::parse($reservation->date_reservation);
                $fin = $debut->copy()->addMinutes((int) ($reservation->duree_minutes ?? 30));
                
                return [
                    'id' => $reservation->id,
                    'title' => 'Indisponible', // Ne pas montrer les détails dans l'agenda public
                    'start' => $debut->toIso8601String(),
                    'end' => $fin->toIso8601String(),
                    'color' => '#9ca3af', // Gris pour indiquer l'indisponibilité
                    'display' => 'block',
                    'extendedProps' => [
                        'statut' => 'indisponible', // Ne pas exposer le statut réel
                    ],
                ];
            });

        return response()->json($reservations);
    }

    /**
     * Afficher une réservation publique (accessible via lien partagé)
     * Accepte soit le hash complet soit l'alias court (pour SMS)
     */
    public function showReservation($hash)
    {
        $reservation = Reservation::findByHash($hash);
        
        if (!$reservation) {
            abort(404, 'Réservation non trouvée.');
        }
        
        // Charger les relations nécessaires
        $reservation->load(['user', 'entreprise.user', 'entreprise.avis', 'typeService', 'membre.user']);

        $entreprise = $reservation->entreprise;

        // Vérifier si l'utilisateur connecté est autorisé à voir cette réservation
        $user = Auth::user();
        $peutVoir = false;
        $peutAnnuler = false;

        if ($user) {
            // L'utilisateur peut voir s'il est :
            // - Le client qui a fait la réservation
            // - Le propriétaire de l'entreprise
            // - Un membre assigné à la réservation
            $peutVoir = $reservation->user_id === $user->id 
                     || $entreprise->user_id === $user->id
                     || ($reservation->membre && $reservation->membre->user_id === $user->id)
                     || $user->is_admin;
            
            // Peut annuler si :
            // - C'est le client qui a fait la réservation
            // - La réservation n'est pas passée
            // - La réservation n'est pas payée
            // - La réservation n'est pas déjà annulée ou terminée
            $peutAnnuler = $reservation->user_id === $user->id
                        && $reservation->date_reservation->isFuture()
                        && !$reservation->est_paye
                        && !in_array($reservation->statut, ['annulee', 'terminee']);
        } else {
            // Un utilisateur non connecté peut voir les réservations créées manuellement pour des clients non inscrits
            // mais seulement si on a le bon email/téléphone (on ne vérifie pas pour simplifier, mais on pourrait ajouter un token)
            $peutVoir = $reservation->creee_manuellement && !$reservation->user_id;
        }

        if (!$peutVoir) {
            abort(403, 'Vous n\'êtes pas autorisé à voir cette réservation.');
        }

        // Charger les horaires de l'entreprise
        $horaires = $entreprise->horairesOuverture()
            ->orderBy('jour_semaine')
            ->get();

        return view('public.reservation', [
            'reservation' => $reservation,
            'entreprise' => $entreprise,
            'horaires' => $horaires,
            'peutAnnuler' => $peutAnnuler,
        ]);
    }

    /**
     * Annuler une réservation
     * Accepte soit le hash complet soit l'alias court (pour SMS)
     */
    public function annulerReservation(Request $request, $hash)
    {
        $reservation = Reservation::findByHash($hash);
        
        if (!$reservation) {
            abort(404, 'Réservation non trouvée.');
        }
        
        // Charger les relations nécessaires
        $reservation->load(['entreprise', 'user']);

        $user = Auth::user();

        // Vérifier les permissions
        if (!$user) {
            abort(403, 'Vous devez être connecté pour annuler une réservation.');
        }

        if ($reservation->user_id !== $user->id && !$user->is_admin) {
            abort(403, 'Vous n\'êtes pas autorisé à annuler cette réservation.');
        }

        // Vérifier que la réservation peut être annulée
        if ($reservation->date_reservation->isPast()) {
            return back()->withErrors(['error' => 'Vous ne pouvez pas annuler une réservation passée.']);
        }

        if ($reservation->est_paye) {
            return back()->withErrors(['error' => 'Vous ne pouvez pas annuler une réservation déjà payée.']);
        }

        if (in_array($reservation->statut, ['annulee', 'terminee'])) {
            return back()->withErrors(['error' => 'Cette réservation est déjà annulée ou terminée.']);
        }

        // Annuler la réservation
        $reservation->statut = 'annulee';
        $reservation->save();

        // Notifier le gérant
        $gerant = $reservation->entreprise->user;
        if ($gerant) {
            $nomClient = $reservation->user ? $reservation->user->name : ($reservation->nom_client ?? 'Client');
            Notification::creer(
                $gerant->id,
                'reservation',
                'Réservation annulée',
                "La réservation du {$reservation->date_reservation->format('d/m/Y à H:i')} par {$nomClient} a été annulée.",
                route('reservations.show', [$reservation->entreprise->slug, $reservation->id]),
                ['reservation_id' => $reservation->id]
            );

            // Envoyer un email au gérant (via template si nécessaire)
            // Note: Pas de template spécifique pour le gérant lors d'annulation client
        }

        // Envoyer un email au client s'il est inscrit
        if ($reservation->user_id) {
            try {
                $reservation->refresh();
                \App\Helpers\EmailHelper::sendReservationCancelledClient($reservation, 'client');
            } catch (\Exception $e) {
                \Log::error("Erreur lors de l'envoi de l'email d'annulation au client : " . $e->getMessage());
            }
        }

        return back()->with('success', 'La réservation a été annulée avec succès.');
    }

    /**
     * Trier les services selon le mode choisi par l'entreprise
     */
    private function trierServices($services, Entreprise $entreprise)
    {
        $mode = $entreprise->mode_ordre_services ?? 'manuel';

        switch ($mode) {
            case 'ventes':
                return $this->trierServicesParVentes($services);
            case 'statistiques':
                return $this->trierServicesParStatistiques($services, $entreprise);
            case 'manuel':
            default:
                return $this->trierServicesManuel($services);
        }
    }

    /**
     * Trier les produits selon le mode choisi par l'entreprise
     */
    private function trierProduits($produits, Entreprise $entreprise)
    {
        $mode = $entreprise->mode_ordre_produits ?? 'manuel';

        switch ($mode) {
            case 'ventes':
                return $this->trierProduitsParVentes($produits);
            case 'statistiques':
                return $this->trierProduitsParStatistiques($produits, $entreprise);
            case 'manuel':
            default:
                return $this->trierProduitsManuel($produits);
        }
    }

    /**
     * Trier les services manuellement (par ordre_affichage)
     */
    private function trierServicesManuel($services)
    {
        return $services->sortBy([
            ['ordre_affichage', 'asc'],
            ['nom', 'asc']
        ])->values();
    }

    /**
     * Trier les produits manuellement (par ordre_affichage)
     */
    private function trierProduitsManuel($produits)
    {
        return $produits->sortBy([
            ['ordre_affichage', 'asc'],
            ['nom', 'asc']
        ])->values();
    }

    /**
     * Trier les services par nombre de réservations terminées
     */
    private function trierServicesParVentes($services)
    {
        return $services->map(function($service) {
            // Compter les réservations terminées (confirmées ou terminées)
            $nbVentes = Reservation::where('type_service_id', $service->id)
                ->whereIn('statut', ['confirmee', 'terminee'])
                ->count();
            
            $service->nb_ventes = $nbVentes;
            return $service;
        })->sortByDesc('nb_ventes')->values();
    }

    /**
     * Trier les produits par nombre de commandes terminées
     */
    private function trierProduitsParVentes($produits)
    {
        return $produits->map(function($produit) {
            // Compter les commandes terminées (statut = 'terminee')
            $nbVentes = CommandeProduit::where('produit_id', $produit->id)
                ->where('statut', 'terminee')
                ->count();
            
            $produit->nb_ventes = $nbVentes;
            return $produit;
        })->sortByDesc('nb_ventes')->values();
    }

    /**
     * Trier les services par statistiques (clics)
     */
    private function trierServicesParStatistiques($services, Entreprise $entreprise)
    {
        $statsController = new \App\Http\Controllers\EntrepriseStatistiqueController();
        $topServices = $statsController->getTopServices($entreprise->id, 365); // 1 an
        
        // Créer un tableau de mapping id => nb_clics
        $clicsParService = [];
        foreach ($topServices as $top) {
            $clicsParService[$top['id']] = $top['nb_clics'];
        }

        return $services->map(function($service) use ($clicsParService) {
            $service->nb_clics = $clicsParService[$service->id] ?? 0;
            return $service;
        })->sortByDesc('nb_clics')->values();
    }

    /**
     * Trier les produits par statistiques (clics)
     */
    private function trierProduitsParStatistiques($produits, Entreprise $entreprise)
    {
        $statsController = new \App\Http\Controllers\EntrepriseStatistiqueController();
        $topProduits = $statsController->getTopProduits($entreprise->id, 365); // 1 an
        
        // Créer un tableau de mapping id => nb_clics
        $clicsParProduit = [];
        foreach ($topProduits as $top) {
            $clicsParProduit[$top['id']] = $top['nb_clics'];
        }

        return $produits->map(function($produit) use ($clicsParProduit) {
            $produit->nb_clics = $clicsParProduit[$produit->id] ?? 0;
            return $produit;
        })->sortByDesc('nb_clics')->values();
    }
}

