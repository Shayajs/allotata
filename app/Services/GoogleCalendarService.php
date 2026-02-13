<?php

namespace App\Services;

use App\Models\Entreprise;
use App\Models\Reservation;
use App\Models\RendezVous;
use App\Models\MembreIndisponibilite;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Calendar\Event as GoogleEvent;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\EventReminders;
use Google\Service\Calendar\EventReminder;
use Google\Service\Calendar\Channel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleCalendarService
{
    /**
     * Génère l'URL de redirection OAuth pour connecter Google Calendar.
     */
    public function getAuthUrl(Entreprise $entreprise): string
    {
        $client = $this->createBaseClient();
        $client->setState($entreprise->id);

        return $client->createAuthUrl();
    }

    /**
     * Traite le callback OAuth : échange le code contre des tokens et les stocke.
     */
    public function handleCallback(string $code, Entreprise $entreprise): void
    {
        $client = $this->createBaseClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);

        Log::info('Google Calendar OAuth token reçu pour entreprise #' . $entreprise->id, [
            'has_access_token' => isset($token['access_token']),
            'has_refresh_token' => isset($token['refresh_token']),
            'expires_in' => $token['expires_in'] ?? null,
            'error' => $token['error'] ?? null,
        ]);

        if (isset($token['error'])) {
            throw new \Exception('Erreur Google OAuth : ' . ($token['error_description'] ?? $token['error']));
        }

        if (empty($token['access_token'])) {
            throw new \Exception('Google n\'a pas retourné de token d\'accès.');
        }

        $refreshToken = $token['refresh_token'] ?? $entreprise->google_refresh_token;

        if (empty($refreshToken)) {
            Log::warning('Google Calendar : aucun refresh_token reçu pour entreprise #' . $entreprise->id . '. L\'utilisateur devra peut-être révoquer l\'accès dans son compte Google et reconnecter.');
        }

        $entreprise->update([
            'google_access_token' => $token['access_token'],
            'google_refresh_token' => $refreshToken,
            'google_token_expires_at' => now()->addSeconds($token['expires_in'] ?? 3600),
            'google_calendar_id' => 'primary',
        ]);
    }

    /**
     * Révoque le token et nettoie les données Google de l'entreprise.
     */
    public function disconnect(Entreprise $entreprise): void
    {
        try {
            if ($entreprise->google_access_token) {
                $client = $this->getAuthenticatedClient($entreprise);
                $client->revokeToken();
            }
        } catch (\Exception $e) {
            Log::warning('Erreur lors de la révocation du token Google pour entreprise #' . $entreprise->id . ': ' . $e->getMessage());
        }

        // Arrêter le watch si actif
        try {
            if ($entreprise->google_watch_channel_id) {
                $this->stopWatch($entreprise);
            }
        } catch (\Exception $e) {
            Log::warning('Erreur lors de l\'arrêt du watch Google pour entreprise #' . $entreprise->id . ': ' . $e->getMessage());
        }

        $entreprise->update([
            'google_access_token' => null,
            'google_refresh_token' => null,
            'google_token_expires_at' => null,
            'google_calendar_id' => null,
            'google_watch_channel_id' => null,
            'google_watch_expiration' => null,
            'google_sync_token' => null,
        ]);
    }

    /**
     * Crée un événement Google Calendar à partir d'une réservation.
     * Gère les multi_rendez_vous en créant un événement par sous-RDV.
     */
    public function createEvent(Reservation $reservation): ?string
    {
        $entreprise = $reservation->entreprise;

        if (!$entreprise || !$entreprise->aGoogleCalendar()) {
            return null;
        }

        try {
            $calendar = $this->getCalendarService($entreprise);
            $calendarId = $entreprise->google_calendar_id ?? 'primary';
            $result = $this->buildEventFromReservation($reservation);

            // Multi rendez-vous : tableau d'événements [{event, rdv}, ...]
            if (is_array($result) && !empty($result) && isset($result[0]['event'])) {
                $firstEventId = null;
                foreach ($result as $item) {
                    /** @var GoogleEvent $event */
                    $event = $item['event'];
                    /** @var RendezVous $rdv */
                    $rdv = $item['rdv'];

                    $createdEvent = $calendar->events->insert($calendarId, $event);
                    $rdv->updateQuietly(['google_event_id' => $createdEvent->getId()]);

                    if (!$firstEventId) {
                        $firstEventId = $createdEvent->getId();
                    }

                    Log::info("Google Calendar : créé événement RDV #{$rdv->id} (event: {$createdEvent->getId()}) pour réservation #{$reservation->id}");
                }

                // Stocker le premier event_id sur la réservation parente pour référence
                $reservation->updateQuietly(['google_event_id' => $firstEventId]);
                return $firstEventId;
            }

            // Événement unique (ponctuel, multi_jours, date_butoire)
            /** @var GoogleEvent $result */
            $createdEvent = $calendar->events->insert($calendarId, $result);
            $reservation->updateQuietly(['google_event_id' => $createdEvent->getId()]);

            return $createdEvent->getId();
        } catch (\Exception $e) {
            Log::error('Erreur création événement Google pour réservation #' . $reservation->id . ': ' . $e->getMessage());
            $this->handleApiError($e, $entreprise);
            return null;
        }
    }

    /**
     * Met à jour un événement Google Calendar existant.
     * Gère les multi_rendez_vous en mettant à jour chaque sous-événement.
     */
    public function updateEvent(Reservation $reservation): void
    {
        $entreprise = $reservation->entreprise;

        if (!$entreprise || !$entreprise->aGoogleCalendar()) {
            return;
        }

        try {
            $calendar = $this->getCalendarService($entreprise);
            $calendarId = $entreprise->google_calendar_id ?? 'primary';
            $result = $this->buildEventFromReservation($reservation);

            // Multi rendez-vous : tableau d'événements [{event, rdv}, ...]
            if (is_array($result) && !empty($result) && isset($result[0]['event'])) {
                foreach ($result as $item) {
                    /** @var GoogleEvent $event */
                    $event = $item['event'];
                    /** @var RendezVous $rdv */
                    $rdv = $item['rdv'];

                    if ($rdv->google_event_id) {
                        // Mettre à jour l'événement existant
                        try {
                            $calendar->events->update($calendarId, $rdv->google_event_id, $event);
                        } catch (\Google\Service\Exception $e) {
                            if ($e->getCode() === 404) {
                                // Recréer si supprimé côté Google
                                $createdEvent = $calendar->events->insert($calendarId, $event);
                                $rdv->updateQuietly(['google_event_id' => $createdEvent->getId()]);
                            } else {
                                throw $e;
                            }
                        }
                    } else {
                        // Créer un nouvel événement pour ce sous-RDV
                        $createdEvent = $calendar->events->insert($calendarId, $event);
                        $rdv->updateQuietly(['google_event_id' => $createdEvent->getId()]);
                        Log::info("Google Calendar : créé événement manquant RDV #{$rdv->id} (event: {$createdEvent->getId()})");
                    }
                }

                // Supprimer les événements de sous-RDV annulés
                $reservation->loadMissing('rendezVous');
                foreach ($reservation->rendezVous as $rdv) {
                    if ($rdv->estAnnule() && $rdv->google_event_id) {
                        $this->deleteSingleEvent($calendar, $calendarId, $rdv->google_event_id);
                        $rdv->updateQuietly(['google_event_id' => null]);
                    }
                }

                return;
            }

            // Événement unique (ponctuel, multi_jours, date_butoire)
            if (!$reservation->google_event_id) {
                return;
            }

            /** @var GoogleEvent $result */
            $calendar->events->update($calendarId, $reservation->google_event_id, $result);
        } catch (\Google\Service\Exception $e) {
            if ($e->getCode() === 404) {
                $reservation->updateQuietly(['google_event_id' => null]);
                return;
            }
            Log::error('Erreur mise à jour événement Google pour réservation #' . $reservation->id . ': ' . $e->getMessage());
            $this->handleApiError($e, $entreprise);
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour événement Google pour réservation #' . $reservation->id . ': ' . $e->getMessage());
            $this->handleApiError($e, $entreprise);
        }
    }

    /**
     * Supprime un événement Google Calendar.
     * Gère les multi_rendez_vous en supprimant tous les sous-événements.
     */
    public function deleteEvent(Reservation $reservation): void
    {
        $entreprise = $reservation->entreprise;

        if (!$entreprise || !$entreprise->aGoogleCalendar()) {
            return;
        }

        try {
            $calendar = $this->getCalendarService($entreprise);
            $calendarId = $entreprise->google_calendar_id ?? 'primary';

            // Multi rendez-vous : supprimer tous les sous-événements
            if ($reservation->estMultiRendezVous()) {
                $reservation->loadMissing('rendezVous');
                foreach ($reservation->rendezVous as $rdv) {
                    if ($rdv->google_event_id) {
                        $this->deleteSingleEvent($calendar, $calendarId, $rdv->google_event_id);
                        $rdv->updateQuietly(['google_event_id' => null]);
                    }
                }
            }

            // Supprimer l'événement principal (existe toujours même pour multi_rendez_vous)
            if ($reservation->google_event_id) {
                $this->deleteSingleEvent($calendar, $calendarId, $reservation->google_event_id);
                $reservation->updateQuietly(['google_event_id' => null]);
            }
        } catch (\Exception $e) {
            Log::error('Erreur suppression événement Google pour réservation #' . $reservation->id . ': ' . $e->getMessage());
            $this->handleApiError($e, $entreprise);
        }
    }

    /**
     * Supprime un seul événement Google Calendar par ID (sans erreur si déjà supprimé).
     */
    protected function deleteSingleEvent(GoogleCalendar $calendar, string $calendarId, string $eventId): void
    {
        try {
            $calendar->events->delete($calendarId, $eventId);
        } catch (\Google\Service\Exception $e) {
            if ($e->getCode() !== 404 && $e->getCode() !== 410) {
                throw $e;
            }
            // 404/410 = déjà supprimé, on ignore silencieusement
        }
    }

    /**
     * Crée un événement Google Calendar pour un seul sous-rendez-vous.
     * Utilisé par le RendezVousObserver.
     */
    public function createEventForRendezVous(RendezVous $rdv): ?string
    {
        $rdv->loadMissing(['reservation.entreprise', 'reservation.typeService', 'reservation.user']);
        $reservation = $rdv->reservation;
        $entreprise = $reservation?->entreprise;

        if (!$entreprise || !$entreprise->aGoogleCalendar()) {
            return null;
        }

        try {
            $calendar = $this->getCalendarService($entreprise);
            $calendarId = $entreprise->google_calendar_id ?? 'primary';

            // Construire un événement individuel pour ce sous-RDV
            $reservation->loadMissing('rendezVous');
            $result = $this->buildMultiRendezVousEvents($reservation);

            if (is_array($result)) {
                foreach ($result as $item) {
                    if ($item['rdv']->id === $rdv->id) {
                        $createdEvent = $calendar->events->insert($calendarId, $item['event']);
                        $rdv->updateQuietly(['google_event_id' => $createdEvent->getId()]);
                        return $createdEvent->getId();
                    }
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Erreur création événement Google pour RDV #{$rdv->id} : " . $e->getMessage());
            $this->handleApiError($e, $entreprise);
            return null;
        }
    }

    /**
     * Met à jour l'événement Google Calendar d'un seul sous-rendez-vous.
     */
    public function updateEventForRendezVous(RendezVous $rdv): void
    {
        if (!$rdv->google_event_id) {
            return;
        }

        $rdv->loadMissing(['reservation.entreprise', 'reservation.typeService', 'reservation.user']);
        $reservation = $rdv->reservation;
        $entreprise = $reservation?->entreprise;

        if (!$entreprise || !$entreprise->aGoogleCalendar()) {
            return;
        }

        try {
            $calendar = $this->getCalendarService($entreprise);
            $calendarId = $entreprise->google_calendar_id ?? 'primary';

            $reservation->loadMissing('rendezVous');
            $result = $this->buildMultiRendezVousEvents($reservation);

            if (is_array($result)) {
                foreach ($result as $item) {
                    if ($item['rdv']->id === $rdv->id) {
                        $calendar->events->update($calendarId, $rdv->google_event_id, $item['event']);
                        return;
                    }
                }
            }
        } catch (\Google\Service\Exception $e) {
            if ($e->getCode() === 404) {
                $rdv->updateQuietly(['google_event_id' => null]);
                return;
            }
            Log::error("Erreur mise à jour événement Google pour RDV #{$rdv->id} : " . $e->getMessage());
            $this->handleApiError($e, $entreprise);
        } catch (\Exception $e) {
            Log::error("Erreur mise à jour événement Google pour RDV #{$rdv->id} : " . $e->getMessage());
        }
    }

    /**
     * Supprime l'événement Google Calendar d'un seul sous-rendez-vous.
     */
    public function deleteEventForRendezVous(RendezVous $rdv): void
    {
        if (!$rdv->google_event_id) {
            return;
        }

        $rdv->loadMissing(['reservation.entreprise']);
        $entreprise = $rdv->reservation?->entreprise;

        if (!$entreprise || !$entreprise->aGoogleCalendar()) {
            return;
        }

        try {
            $calendar = $this->getCalendarService($entreprise);
            $calendarId = $entreprise->google_calendar_id ?? 'primary';

            $this->deleteSingleEvent($calendar, $calendarId, $rdv->google_event_id);
            $rdv->updateQuietly(['google_event_id' => null]);
        } catch (\Exception $e) {
            Log::error("Erreur suppression événement Google pour RDV #{$rdv->id} : " . $e->getMessage());
        }
    }

    /**
     * Inscrit un webhook (watch) pour recevoir les notifications de changements.
     */
    public function setupWatch(Entreprise $entreprise): void
    {
        if (!$entreprise->aGoogleCalendar()) {
            return;
        }

        try {
            // Arrêter l'ancien watch si actif
            if ($entreprise->google_watch_channel_id) {
                $this->stopWatch($entreprise);
            }

            $calendar = $this->getCalendarService($entreprise);
            $calendarId = $entreprise->google_calendar_id ?? 'primary';

            $channel = new Channel();
            $channel->setId(Str::uuid()->toString());
            $channel->setType('web_hook');
            $channel->setAddress(config('app.url') . '/webhooks/google-calendar');
            $channel->setParams(['ttl' => '604800']); // 7 jours en secondes

            $watchResponse = $calendar->events->watch($calendarId, $channel);

            $entreprise->update([
                'google_watch_channel_id' => $watchResponse->getId(),
                'google_watch_expiration' => now()->addSeconds(604800),
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur setup watch Google pour entreprise #' . $entreprise->id . ': ' . $e->getMessage());
        }
    }

    /**
     * Arrête un webhook actif.
     */
    public function stopWatch(Entreprise $entreprise): void
    {
        if (!$entreprise->google_watch_channel_id) {
            return;
        }

        try {
            $calendar = $this->getCalendarService($entreprise);

            $channel = new Channel();
            $channel->setId($entreprise->google_watch_channel_id);
            $channel->setResourceId($entreprise->google_calendar_id ?? 'primary');

            $calendar->channels->stop($channel);
        } catch (\Exception $e) {
            Log::warning('Erreur arrêt watch Google pour entreprise #' . $entreprise->id . ': ' . $e->getMessage());
        }

        $entreprise->update([
            'google_watch_channel_id' => null,
            'google_watch_expiration' => null,
        ]);
    }

    /**
     * Synchronisation incrémentale : récupère les changements depuis Google
     * et crée des MembreIndisponibilite pour les événements non-Allotata.
     */
    public function syncIncrementalChanges(Entreprise $entreprise): void
    {
        if (!$entreprise->aGoogleCalendar()) {
            return;
        }

        try {
            $calendar = $this->getCalendarService($entreprise);
            $calendarId = $entreprise->google_calendar_id ?? 'primary';

            $params = [
                'singleEvents' => true,
                'showDeleted' => true,
            ];

            if ($entreprise->google_sync_token) {
                $params['syncToken'] = $entreprise->google_sync_token;
            } else {
                // Première sync : ne récupérer que les événements futurs
                $params['timeMin'] = now()->toRfc3339String();
                $params['timeMax'] = now()->addMonths(3)->toRfc3339String();
            }

            $events = $calendar->events->listEvents($calendarId, $params);

            foreach ($events->getItems() as $event) {
                $this->processGoogleEvent($event, $entreprise);
            }

            // Stocker le syncToken pour la prochaine itération
            if ($events->getNextSyncToken()) {
                $entreprise->update(['google_sync_token' => $events->getNextSyncToken()]);
            }
        } catch (\Google\Service\Exception $e) {
            // 410 Gone = syncToken invalide, on reset
            if ($e->getCode() === 410) {
                $entreprise->update(['google_sync_token' => null]);
                Log::info('SyncToken Google invalide pour entreprise #' . $entreprise->id . ', reset effectué.');
                return;
            }
            Log::error('Erreur sync incrémentale Google pour entreprise #' . $entreprise->id . ': ' . $e->getMessage());
            $this->handleApiError($e, $entreprise);
        } catch (\Exception $e) {
            Log::error('Erreur sync incrémentale Google pour entreprise #' . $entreprise->id . ': ' . $e->getMessage());
        }
    }

    /**
     * Traite un événement individuel venant de Google.
     * Si c'est un événement créé par Allotata (google_event_id), on l'ignore.
     * Sinon, on crée/met à jour une MembreIndisponibilite.
     */
    protected function processGoogleEvent(GoogleEvent $event, Entreprise $entreprise): void
    {
        // Ignorer les événements créés par Allotata (réservation ou sous-rendez-vous)
        $existingReservation = Reservation::where('entreprise_id', $entreprise->id)
            ->where('google_event_id', $event->getId())
            ->exists();

        if ($existingReservation) {
            return;
        }

        $existingRdv = RendezVous::where('google_event_id', $event->getId())
            ->whereHas('reservation', function ($q) use ($entreprise) {
                $q->where('entreprise_id', $entreprise->id);
            })
            ->exists();

        if ($existingRdv) {
            return;
        }

        // Ignorer les événements annulés
        if ($event->getStatus() === 'cancelled') {
            return;
        }

        // Récupérer les dates
        $start = $event->getStart();
        $end = $event->getEnd();

        if (!$start || !$end) {
            return;
        }

        $dateDebut = $start->getDateTime() ? new \DateTime($start->getDateTime()) : null;
        $dateFin = $end->getDateTime() ? new \DateTime($end->getDateTime()) : null;

        // Événements journée entière
        if (!$dateDebut && $start->getDate()) {
            $dateDebut = new \DateTime($start->getDate());
            $dateFin = $end->getDate() ? new \DateTime($end->getDate()) : (clone $dateDebut)->modify('+1 day');
        }

        if (!$dateDebut) {
            return;
        }

        // Récupérer le premier membre (gérant) pour l'indisponibilité
        $membre = $entreprise->membres()->first();
        if (!$membre) {
            return;
        }

        // Créer ou mettre à jour l'indisponibilité
        MembreIndisponibilite::updateOrCreate(
            [
                'membre_id' => $membre->id,
                'raison' => 'google:' . $event->getId(),
            ],
            [
                'date_debut' => $dateDebut->format('Y-m-d'),
                'date_fin' => $dateFin ? $dateFin->format('Y-m-d') : $dateDebut->format('Y-m-d'),
                'heure_debut' => $dateDebut->format('H:i') !== '00:00' ? $dateDebut->format('H:i') : null,
                'heure_fin' => $dateFin && $dateFin->format('H:i') !== '00:00' ? $dateFin->format('H:i') : null,
            ]
        );
    }

    // =========================================================================
    // Méthodes privées
    // =========================================================================

    /**
     * Crée un client Google de base (sans authentification).
     */
    protected function createBaseClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect_uri'));
        $client->addScope(GoogleCalendar::CALENDAR);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setIncludeGrantedScopes(true);

        return $client;
    }

    /**
     * Retourne un client Google authentifié pour une entreprise.
     * Rafraîchit le token si nécessaire.
     */
    public function getAuthenticatedClient(Entreprise $entreprise): GoogleClient
    {
        $client = $this->createBaseClient();

        $client->setAccessToken([
            'access_token' => $entreprise->google_access_token,
            'refresh_token' => $entreprise->google_refresh_token,
            'expires_in' => $entreprise->google_token_expires_at
                ? now()->diffInSeconds($entreprise->google_token_expires_at, false)
                : 0,
        ]);

        // Rafraîchir si expiré
        if ($client->isAccessTokenExpired() && $entreprise->google_refresh_token) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($entreprise->google_refresh_token);

            if (!isset($newToken['error'])) {
                $entreprise->updateQuietly([
                    'google_access_token' => $newToken['access_token'],
                    'google_token_expires_at' => now()->addSeconds($newToken['expires_in'] ?? 3600),
                ]);
            } else {
                Log::error('Erreur refresh token Google pour entreprise #' . $entreprise->id . ': ' . ($newToken['error_description'] ?? $newToken['error']));
                throw new \Exception('Le token Google a expiré et ne peut pas être rafraîchi. Veuillez reconnecter Google Calendar.');
            }
        }

        return $client;
    }

    /**
     * Retourne un service Google Calendar authentifié.
     */
    protected function getCalendarService(Entreprise $entreprise): GoogleCalendar
    {
        $client = $this->getAuthenticatedClient($entreprise);
        return new GoogleCalendar($client);
    }

    /**
     * Construit un ou plusieurs objets GoogleEvent à partir d'une Reservation,
     * en fonction du type_structure du service (ponctuel, multi_jours, date_butoire, multi_rendez_vous).
     *
     * @return GoogleEvent|GoogleEvent[] Un seul événement ou un tableau d'événements (multi_rendez_vous)
     */
    protected function buildEventFromReservation(Reservation $reservation): GoogleEvent|array
    {
        $reservation->loadMissing(['entreprise', 'typeService', 'user', 'rendezVous']);

        // Déterminer le type de structure
        if ($reservation->estDateButoire()) {
            return $this->buildDateButoireEvent($reservation);
        }

        if ($reservation->estMultiJours()) {
            return $this->buildMultiJoursEvent($reservation);
        }

        if ($reservation->estMultiRendezVous()) {
            return $this->buildMultiRendezVousEvents($reservation);
        }

        // Par défaut : ponctuel
        return $this->buildPonctuelEvent($reservation);
    }

    /**
     * Construit la base commune d'un GoogleEvent (titre, description, couleur, lieu).
     */
    protected function buildBaseEvent(Reservation $reservation, ?string $summaryOverride = null, ?string $descriptionExtra = null): GoogleEvent
    {
        $clientName = $reservation->nom_client ?? $reservation->user?->name ?? 'Client';
        $serviceName = $reservation->typeService?->nom ?? $reservation->type_service ?? 'Réservation';
        $summary = $summaryOverride ?? "{$serviceName} - {$clientName}";

        // Description
        $description = "Réservation Allotata #{$reservation->id}\n";
        $description .= "Service : {$serviceName}\n";
        $description .= "Client : {$clientName}\n";

        if ($reservation->prix) {
            $description .= "Prix : " . number_format($reservation->prix, 2, ',', ' ') . " €\n";
        }

        if ($reservation->notes) {
            $description .= "Notes : {$reservation->notes}\n";
        }

        if ($descriptionExtra) {
            $description .= $descriptionExtra . "\n";
        }

        $description .= "Statut : {$reservation->statut}";

        // Couleur selon le statut
        $colorId = match ($reservation->statut) {
            'confirmee' => '10', // Vert (basil)
            'en_attente' => '5', // Jaune (banana)
            'annulee' => '11', // Rouge (tomato)
            'terminee' => '8', // Gris (graphite)
            default => '1',
        };

        $event = new GoogleEvent();
        $event->setSummary($summary);
        $event->setDescription($description);
        $event->setColorId($colorId);

        // Lieu si disponible
        if ($reservation->lieu) {
            $event->setLocation($reservation->lieu);
        } elseif ($reservation->entreprise?->full_address) {
            $event->setLocation($reservation->entreprise->full_address);
        }

        return $event;
    }

    /**
     * Événement ponctuel : créneau avec heure de début et de fin.
     */
    protected function buildPonctuelEvent(Reservation $reservation): GoogleEvent
    {
        $event = $this->buildBaseEvent($reservation);

        $tz = config('app.timezone', 'Europe/Paris');
        $startDateTime = new EventDateTime();
        $endDateTime = new EventDateTime();

        if ($reservation->date_reservation) {
            $startDateTime->setDateTime($reservation->date_reservation->toRfc3339String());
            $startDateTime->setTimeZone($tz);

            $endDate = $reservation->date_fin ?? $reservation->date_reservation->copy()->addMinutes($reservation->duree_minutes ?? 60);
            $endDateTime->setDateTime($endDate->toRfc3339String());
            $endDateTime->setTimeZone($tz);
        }

        $event->setStart($startDateTime);
        $event->setEnd($endDateTime);

        return $event;
    }

    /**
     * Événement date butoire : journée entière (all-day) avec rappel J-1.
     * S'affiche en bandeau en haut du jour dans Google Calendar.
     */
    protected function buildDateButoireEvent(Reservation $reservation): GoogleEvent
    {
        $dateBut = $reservation->date_butoire ?? $reservation->date_reservation?->format('Y-m-d');

        $descExtra = "⏰ À terminer avant le " . ($dateBut ? \Carbon\Carbon::parse($dateBut)->format('d/m/Y') : '???');
        $event = $this->buildBaseEvent($reservation, null, $descExtra);

        if ($dateBut) {
            $dateStr = \Carbon\Carbon::parse($dateBut)->format('Y-m-d');

            // Événement journée entière : setDate() au lieu de setDateTime()
            $startDate = new EventDateTime();
            $startDate->setDate($dateStr);

            // Google requiert que end soit le jour suivant pour un événement d'un seul jour
            $endDate = new EventDateTime();
            $endDate->setDate(\Carbon\Carbon::parse($dateBut)->addDay()->format('Y-m-d'));

            $event->setStart($startDate);
            $event->setEnd($endDate);

            // Rappel la veille (popup à 9h, soit 15h avant minuit = 900 minutes)
            $reminder = new EventReminder();
            $reminder->setMethod('popup');
            $reminder->setMinutes(900); // 15 heures avant = veille à 9h

            $reminders = new EventReminders();
            $reminders->setUseDefault(false);
            $reminders->setOverrides([$reminder]);

            $event->setReminders($reminders);
        }

        return $event;
    }

    /**
     * Événement multi-jours : s'étale sur plusieurs jours.
     * Utilise le format journée entière si les heures sont à minuit, sinon événement avec heures.
     */
    protected function buildMultiJoursEvent(Reservation $reservation): GoogleEvent
    {
        $dateDebut = $reservation->date_reservation;
        $dateFin = $reservation->date_fin;

        $descExtra = '';
        if ($dateDebut && $dateFin) {
            $descExtra = "📅 Du " . $dateDebut->format('d/m/Y') . " au " . $dateFin->format('d/m/Y');
        }

        $event = $this->buildBaseEvent($reservation, null, $descExtra);

        $tz = config('app.timezone', 'Europe/Paris');

        if ($dateDebut && $dateFin) {
            // Vérifier si ce sont des journées entières (heures à 00:00)
            $isAllDay = $dateDebut->format('H:i') === '00:00' && $dateFin->format('H:i') === '00:00';

            if ($isAllDay) {
                $startDate = new EventDateTime();
                $startDate->setDate($dateDebut->format('Y-m-d'));

                // Google requiert le lendemain de la date de fin pour les all-day
                $endDate = new EventDateTime();
                $endDate->setDate($dateFin->addDay()->format('Y-m-d'));

                $event->setStart($startDate);
                $event->setEnd($endDate);
            } else {
                $startDateTime = new EventDateTime();
                $startDateTime->setDateTime($dateDebut->toRfc3339String());
                $startDateTime->setTimeZone($tz);

                $endDateTime = new EventDateTime();
                $endDateTime->setDateTime($dateFin->toRfc3339String());
                $endDateTime->setTimeZone($tz);

                $event->setStart($startDateTime);
                $event->setEnd($endDateTime);
            }
        } elseif ($dateDebut) {
            // Fallback : traiter comme ponctuel
            $startDateTime = new EventDateTime();
            $startDateTime->setDateTime($dateDebut->toRfc3339String());
            $startDateTime->setTimeZone($tz);

            $endDate = $dateDebut->copy()->addMinutes($reservation->duree_minutes ?? 60);
            $endDateTime = new EventDateTime();
            $endDateTime->setDateTime($endDate->toRfc3339String());
            $endDateTime->setTimeZone($tz);

            $event->setStart($startDateTime);
            $event->setEnd($endDateTime);
        }

        return $event;
    }

    /**
     * Multi rendez-vous : retourne un tableau d'événements, un par RendezVous.
     * Chaque sous-rendez-vous devient un événement distinct dans Google Calendar.
     * Fallback en ponctuel si aucun RendezVous n'existe.
     *
     * @return GoogleEvent[]|GoogleEvent
     */
    protected function buildMultiRendezVousEvents(Reservation $reservation): array|GoogleEvent
    {
        $rdvs = $reservation->rendezVous;

        // Fallback : si aucun sous-rendez-vous n'existe, traiter comme ponctuel
        if ($rdvs->isEmpty()) {
            Log::info("Réservation #{$reservation->id} multi_rendez_vous sans sous-RDV, fallback ponctuel.");
            return $this->buildPonctuelEvent($reservation);
        }

        $clientName = $reservation->nom_client ?? $reservation->user?->name ?? 'Client';
        $serviceName = $reservation->typeService?->nom ?? $reservation->type_service ?? 'Réservation';
        $total = $rdvs->count();
        $tz = config('app.timezone', 'Europe/Paris');

        $events = [];
        foreach ($rdvs as $index => $rdv) {
            $numero = $index + 1;
            $summaryOverride = "RDV {$numero}/{$total} - {$serviceName} - {$clientName}";
            $descExtra = "📋 Rendez-vous {$numero} sur {$total}";
            if ($rdv->titre) {
                $descExtra .= " : {$rdv->titre}";
            }
            if ($rdv->notes) {
                $descExtra .= "\nNotes RDV : {$rdv->notes}";
            }

            $event = $this->buildBaseEvent($reservation, $summaryOverride, $descExtra);

            // Dates du sous-rendez-vous
            if ($rdv->date_heure) {
                $startDateTime = new EventDateTime();
                $startDateTime->setDateTime($rdv->date_heure->toRfc3339String());
                $startDateTime->setTimeZone($tz);

                $endDate = $rdv->date_heure->copy()->addMinutes($rdv->duree_minutes ?? $reservation->duree_minutes ?? 60);
                $endDateTime = new EventDateTime();
                $endDateTime->setDateTime($endDate->toRfc3339String());
                $endDateTime->setTimeZone($tz);

                $event->setStart($startDateTime);
                $event->setEnd($endDateTime);
            }

            // Lieu du sous-rendez-vous (peut différer de la réservation parente)
            if ($rdv->lieu) {
                $event->setLocation($rdv->lieu);
            }

            // Stocker l'ID du RendezVous dans les propriétés étendues pour le mapping
            $event->setExtendedProperties(new \Google\Service\Calendar\EventExtendedProperties([
                'private' => [
                    'allotata_rdv_id' => (string) $rdv->id,
                    'allotata_reservation_id' => (string) $reservation->id,
                ],
            ]));

            $events[] = ['event' => $event, 'rdv' => $rdv];
        }

        return $events;
    }

    /**
     * Gère les erreurs API Google (déconnexion si token invalide).
     */
    protected function handleApiError(\Exception $e, Entreprise $entreprise): void
    {
        if ($e instanceof \Google\Service\Exception) {
            $code = $e->getCode();

            // 401 Unauthorized = token révoqué
            if ($code === 401) {
                Log::warning('Token Google révoqué pour entreprise #' . $entreprise->id . ', déconnexion automatique.');
                $entreprise->updateQuietly([
                    'google_access_token' => null,
                    'google_refresh_token' => null,
                    'google_token_expires_at' => null,
                    'google_watch_channel_id' => null,
                    'google_watch_expiration' => null,
                    'google_sync_token' => null,
                ]);
            }
        }
    }
}
