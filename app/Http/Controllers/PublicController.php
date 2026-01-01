<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\Reservation;
use App\Models\TypeService;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PublicController extends Controller
{
    public function show($slug)
    {
        $entreprise = Entreprise::where('slug', $slug)
            ->with(['user', 'avis.user', 'avis.photos', 'realisationPhotos', 'typesServices.images', 'typesServices.imageCouverture'])
            ->firstOrFail();

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

        return view('public.entreprise', [
            'entreprise' => $entreprise,
            'slug' => $slug,
            'horaires' => $horaires,
            'services' => $services,
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
        $entreprise = Entreprise::where('slug', $slug)
            ->with(['typesServices' => function($query) {
                $query->where('est_actif', true);
            }])
            ->firstOrFail();

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
            
            // Vérifier d'abord s'il y a un jour exceptionnel pour cette date (prioritaire)
            $horaireExceptionnel = $horairesRaw->first(function($h) use ($dateString) {
                return $h->est_exceptionnel && $h->date_exception && $h->date_exception->format('Y-m-d') === $dateString;
            });
            
            // Si pas de jour exceptionnel, utiliser l'horaire régulier
            $horaire = $horaireExceptionnel ?? $horairesRaw->firstWhere('jour_semaine', $jourSemaine);
            
            // Calculer les créneaux disponibles pour ce jour
            $creneaux = [];
            if ($horaire && $horaire->heure_ouverture && $horaire->heure_fermeture) {
                $heureOuverture = \Carbon\Carbon::parse($horaire->heure_ouverture);
                $heureFermeture = \Carbon\Carbon::parse($horaire->heure_fermeture);
                
                // Trouver la durée minimale des services (pour calculer les créneaux)
                $dureeMinimale = $entreprise->typesServices->min('duree_minutes') ?? 30;
                
                // Générer des créneaux basés sur la durée minimale (minimum 30 minutes)
                $dureeCreneau = max(30, ceil($dureeMinimale / 30) * 30);
                
                $creneauActuel = $date->copy()->setTimeFromTimeString($heureOuverture->format('H:i'));
                $fermeture = $date->copy()->setTimeFromTimeString($heureFermeture->format('H:i'));
                
                // Si c'est aujourd'hui, commencer à partir de maintenant + 1 heure minimum
                if ($i === 0) {
                    $creneauActuel = max($creneauActuel, now()->addHour()->startOfHour());
                }
                
                // Récupérer toutes les réservations pour ce jour (y compris en attente pour bloquer le créneau)
                $reservationsDuJour = Reservation::where('entreprise_id', $entreprise->id)
                    ->whereDate('date_reservation', $date->format('Y-m-d'))
                    ->whereIn('statut', ['en_attente', 'confirmee'])
                    ->get();
                
                while ($creneauActuel->copy()->addMinutes($dureeCreneau)->lte($fermeture)) {
                    $debutCreneau = $creneauActuel->copy();
                    $finCreneau = $creneauActuel->copy()->addMinutes($dureeCreneau);
                    
                    // Vérifier si ce créneau chevauche avec une réservation existante
                    $estReserve = false;
                    foreach ($reservationsDuJour as $reservation) {
                        $debutReservation = \Carbon\Carbon::parse($reservation->date_reservation);
                        $finReservation = $debutReservation->copy()->addMinutes($reservation->duree_minutes ?? 30);
                        
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
            
            $jours[] = [
                'date' => $date,
                'jour_semaine' => $jourSemaine,
                'nom_jour' => $date->locale('fr')->dayName,
                'date_formatee' => $date->format('d/m/Y'),
                'date_input' => $date->format('Y-m-d'),
                'est_aujourdhui' => $i === 0,
                'horaire' => $horaire,
                'est_ferme' => !$horaire || !$horaire->heure_ouverture || !$horaire->heure_fermeture,
                'creneaux' => $creneaux,
            ];
        }

        return view('public.agenda', [
            'entreprise' => $entreprise,
            'horaires' => $horaires,
            'jours' => $jours,
            'isOwner' => $isOwner,
            'membres' => $membres,
            'aGestionMultiPersonnes' => $entreprise->aGestionMultiPersonnes(),
        ]);
    }

    /**
     * Créer une réservation
     */
    public function storeReservation(Request $request, $slug)
    {
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();

        $validated = $request->validate([
            'type_service_id' => 'required|exists:types_services,id',
            'date_reservation' => 'required|date|after:now',
            'heure_reservation' => 'required|date_format:H:i',
            'membre_id' => 'nullable|exists:entreprise_membres,id',
            'lieu' => 'nullable|string|max:255',
            'telephone_client' => 'required|string|max:20',
            'telephone_cache' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        // Vérifier que le type de service appartient à l'entreprise
        $typeService = TypeService::where('id', $validated['type_service_id'])
            ->where('entreprise_id', $entreprise->id)
            ->where('est_actif', true)
            ->firstOrFail();

        // Combiner date et heure
        $dateTime = $validated['date_reservation'] . ' ' . $validated['heure_reservation'];
        $debutReservation = \Carbon\Carbon::parse($dateTime);
        $heureReservation = \Carbon\Carbon::parse($validated['heure_reservation']);

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
        } elseif ($entreprise->aGestionMultiPersonnes()) {
            // Sélection automatique si multi-personnes et aucun membre spécifié
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

        // Vérifier si le créneau n'est pas déjà pris (y compris les réservations en attente)
        $finReservation = $debutReservation->copy()->addMinutes($typeService->duree_minutes);
        
        $queryReservations = Reservation::where('entreprise_id', $entreprise->id)
            ->whereIn('statut', ['en_attente', 'confirmee']);
        
        // Si un membre est spécifié, vérifier seulement ses créneaux
        if ($membreId) {
            $queryReservations->where('membre_id', $membreId);
        }
        
        $creneauDejaPris = $queryReservations->get()
            ->filter(function($r) use ($debutReservation, $finReservation) {
                $debutR = \Carbon\Carbon::parse($r->date_reservation);
                $finR = $debutR->copy()->addMinutes($r->duree_minutes ?? 30);
                // Vérifier le chevauchement
                return $debutReservation->lt($finR) && $finReservation->gt($debutR);
            })
            ->isNotEmpty();

        if ($creneauDejaPris) {
            return back()->withErrors(['error' => 'Ce créneau est déjà réservé. Veuillez choisir un autre horaire.']);
        }

        // Créer la réservation (en attente de confirmation par la tata)
        $reservation = Reservation::create([
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
            'statut' => 'en_attente', // En attente de confirmation par la tata
        ]);

        // Créer une notification pour le gérant
        $gerant = $entreprise->user;
        if ($gerant) {
            Notification::creer(
                $gerant->id,
                'reservation',
                'Nouvelle réservation',
                "Une nouvelle réservation a été demandée pour le {$reservation->date_reservation->format('d/m/Y à H:i')} par {$reservation->user->name}.",
                route('reservations.show', [$entreprise->slug, $reservation->id]),
                ['reservation_id' => $reservation->id, 'user_id' => $userId]
            );
        }

        // Créer une notification pour le client
        Notification::creer(
            $userId,
            'reservation',
            'Réservation en attente',
            "Votre demande de réservation pour {$entreprise->nom} le {$reservation->date_reservation->format('d/m/Y à H:i')} est en attente de confirmation.",
            route('dashboard'),
            ['reservation_id' => $reservation->id, 'entreprise_id' => $entreprise->id]
        );

        return redirect()->route('public.entreprise', $slug)
            ->with('success', 'Votre demande de réservation a été envoyée ! La tata va la valider prochainement.');
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
                $fin = $debut->copy()->addMinutes($reservation->duree_minutes ?? 30);
                
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
}

