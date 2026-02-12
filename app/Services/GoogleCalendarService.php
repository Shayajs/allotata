<?php

namespace App\Services;

use App\Models\Entreprise;
use App\Models\Reservation;
use App\Models\MembreIndisponibilite;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Calendar\Event as GoogleEvent;
use Google\Service\Calendar\EventDateTime;
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
     */
    public function createEvent(Reservation $reservation): ?string
    {
        $entreprise = $reservation->entreprise;

        if (!$entreprise || !$entreprise->aGoogleCalendar()) {
            return null;
        }

        try {
            $calendar = $this->getCalendarService($entreprise);
            $event = $this->buildEventFromReservation($reservation);

            $calendarId = $entreprise->google_calendar_id ?? 'primary';
            $createdEvent = $calendar->events->insert($calendarId, $event);

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
     */
    public function updateEvent(Reservation $reservation): void
    {
        $entreprise = $reservation->entreprise;

        if (!$entreprise || !$entreprise->aGoogleCalendar() || !$reservation->google_event_id) {
            return;
        }

        try {
            $calendar = $this->getCalendarService($entreprise);
            $event = $this->buildEventFromReservation($reservation);
            $calendarId = $entreprise->google_calendar_id ?? 'primary';

            $calendar->events->update($calendarId, $reservation->google_event_id, $event);
        } catch (\Google\Service\Exception $e) {
            // 404 = l'événement a été supprimé côté Google
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
     */
    public function deleteEvent(Reservation $reservation): void
    {
        $entreprise = $reservation->entreprise;

        if (!$entreprise || !$entreprise->aGoogleCalendar() || !$reservation->google_event_id) {
            return;
        }

        try {
            $calendar = $this->getCalendarService($entreprise);
            $calendarId = $entreprise->google_calendar_id ?? 'primary';

            $calendar->events->delete($calendarId, $reservation->google_event_id);
            $reservation->updateQuietly(['google_event_id' => null]);
        } catch (\Google\Service\Exception $e) {
            if ($e->getCode() === 404 || $e->getCode() === 410) {
                // Événement déjà supprimé côté Google
                $reservation->updateQuietly(['google_event_id' => null]);
                return;
            }
            Log::error('Erreur suppression événement Google pour réservation #' . $reservation->id . ': ' . $e->getMessage());
            $this->handleApiError($e, $entreprise);
        } catch (\Exception $e) {
            Log::error('Erreur suppression événement Google pour réservation #' . $reservation->id . ': ' . $e->getMessage());
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
        // Ignorer les événements créés par Allotata
        $existingReservation = Reservation::where('entreprise_id', $entreprise->id)
            ->where('google_event_id', $event->getId())
            ->exists();

        if ($existingReservation) {
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
     * Construit un objet GoogleEvent à partir d'une Reservation.
     */
    protected function buildEventFromReservation(Reservation $reservation): GoogleEvent
    {
        $reservation->loadMissing(['entreprise', 'typeService', 'user']);

        // Titre de l'événement
        $clientName = $reservation->nom_client ?? $reservation->user?->name ?? 'Client';
        $serviceName = $reservation->typeService?->nom ?? $reservation->type_service ?? 'Réservation';
        $summary = "{$serviceName} - {$clientName}";

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

        // Dates
        $startDateTime = new EventDateTime();
        $endDateTime = new EventDateTime();

        if ($reservation->date_reservation) {
            $startDateTime->setDateTime($reservation->date_reservation->toRfc3339String());
            $startDateTime->setTimeZone(config('app.timezone', 'Europe/Paris'));

            $endDate = $reservation->date_fin ?? $reservation->date_reservation->copy()->addMinutes($reservation->duree_minutes ?? 60);
            $endDateTime->setDateTime($endDate->toRfc3339String());
            $endDateTime->setTimeZone(config('app.timezone', 'Europe/Paris'));
        }

        $event->setStart($startDateTime);
        $event->setEnd($endDateTime);

        // Lieu si disponible
        if ($reservation->lieu) {
            $event->setLocation($reservation->lieu);
        } elseif ($reservation->entreprise?->full_address) {
            $event->setLocation($reservation->entreprise->full_address);
        }

        return $event;
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
