<?php

namespace App\Helpers;

use App\Models\Conversation;
use App\Models\Echeance;
use App\Models\Entreprise;
use App\Models\Message;
use App\Models\Reservation;
use App\Models\User;
use App\Services\EmailTemplateService;

class EmailHelper
{
    /**
     * Formate la date de réservation en gérant le cas date_butoire (date_reservation null).
     */
    private static function formatDateReservation(Reservation $reservation): string
    {
        if ($reservation->date_reservation) {
            return $reservation->date_reservation->format('d/m/Y à H:i');
        }
        if ($reservation->date_butoire) {
            return $reservation->date_butoire->format('d/m/Y').' (date butoire)';
        }

        return '—';
    }

    /**
     * Envoyer un email de bienvenue
     */
    public static function sendWelcome(User $user): bool
    {
        return EmailTemplateService::send('welcome', $user->email, [
            'nom_client' => $user->name,
            'url_dashboard' => route('dashboard'),
        ]);
    }

    /**
     * Envoyer une confirmation de réservation au client
     */
    public static function sendReservationConfirmationClient(Reservation $reservation): bool
    {
        $client = $reservation->user;
        $emailTo = $client?->email ?? $reservation->email_client;
        if (! $emailTo) {
            return false;
        }

        $nomClient = $client?->name ?? ($reservation->nom_client ?? 'Client');

        $data = [
            'nom_client' => $nomClient,
            'nom_entreprise' => $reservation->entreprise->nom,
            'nom_service' => $reservation->type_service ?? 'Service',
            'date_reservation' => self::formatDateReservation($reservation),
            'duree' => $reservation->duree_minutes ?? 30,
            'prix' => number_format($reservation->prix, 2, ',', ' '),
            'url_reservation' => route('public.reservation.show', $reservation->hash ?? $reservation->id),
        ];

        if ($reservation->lieu) {
            $data['lieu_html'] = '<p><strong>Lieu :</strong> '.htmlspecialchars($reservation->lieu).'</p>';
        } else {
            $data['lieu_html'] = '';
        }

        if ($reservation->membre && $reservation->membre->user) {
            $data['membre_html'] = '<p><strong>Avec :</strong> '.htmlspecialchars($reservation->membre->user->name).'</p>';
        } else {
            $data['membre_html'] = '';
        }

        if ($reservation->notes) {
            $data['notes_html'] = '<p><strong>Notes :</strong> '.nl2br(htmlspecialchars($reservation->notes)).'</p>';
        } else {
            $data['notes_html'] = '';
        }

        return EmailTemplateService::send('reservation_confirmation_client', $emailTo, $data);
    }

    /**
     * Envoyer un email de réservation en attente au client (statut en_attente).
     */
    public static function sendReservationPendingClient(Reservation $reservation): bool
    {
        $client = $reservation->user;
        $emailTo = $client?->email ?? $reservation->email_client;
        if (! $emailTo) {
            return false;
        }

        $nomClient = $client?->name ?? ($reservation->nom_client ?? 'Client');

        $data = [
            'nom_client' => $nomClient,
            'nom_entreprise' => $reservation->entreprise->nom,
            'nom_service' => $reservation->type_service ?? 'Service',
            'date_reservation' => self::formatDateReservation($reservation),
            'duree' => $reservation->duree_minutes ?? 30,
            'prix' => number_format($reservation->prix, 2, ',', ' '),
            'url_reservation' => route('public.reservation.show', $reservation->hash ?? $reservation->id),
        ];

        if ($reservation->lieu) {
            $data['lieu_html'] = '<p><strong>Lieu :</strong> '.htmlspecialchars($reservation->lieu).'</p>';
        } else {
            $data['lieu_html'] = '';
        }

        if ($reservation->membre && $reservation->membre->user) {
            $data['membre_html'] = '<p><strong>Avec :</strong> '.htmlspecialchars($reservation->membre->user->name).'</p>';
        } else {
            $data['membre_html'] = '';
        }

        if ($reservation->notes) {
            $data['notes_html'] = '<p><strong>Notes :</strong> '.nl2br(htmlspecialchars($reservation->notes)).'</p>';
        } else {
            $data['notes_html'] = '';
        }

        return EmailTemplateService::send('reservation_pending_client', $emailTo, $data);
    }

    /**
     * Envoyer une notification de nouvelle réservation au gérant
     */
    public static function sendReservationConfirmationGerant(Reservation $reservation): bool
    {
        $gerant = $reservation->entreprise->user;
        if (! $gerant) {
            return false;
        }

        $client = $reservation->user;
        $nomClient = $client ? $client->name : ($reservation->nom_client ?? 'Client non inscrit');

        $data = [
            'nom_client' => $nomClient,
            'nom_entreprise' => $reservation->entreprise->nom,
            'nom_service' => $reservation->type_service ?? 'Service',
            'date_reservation' => self::formatDateReservation($reservation),
            'duree' => $reservation->duree_minutes ?? 30,
            'prix' => number_format($reservation->prix, 2, ',', ' '),
            'telephone' => $reservation->telephone_client
                ?? $reservation->telephone_client_non_inscrit
                ?? 'N/A',
            'url_reservation' => route('reservations.show', [$reservation->entreprise->slug, $reservation->id]),
        ];

        if ($reservation->lieu) {
            $data['lieu_html'] = '<p><strong>Lieu :</strong> '.htmlspecialchars($reservation->lieu).'</p>';
        } else {
            $data['lieu_html'] = '';
        }

        if ($reservation->notes) {
            $data['notes_html'] = '<p><strong>Notes du client :</strong> '.nl2br(htmlspecialchars($reservation->notes)).'</p>';
        } else {
            $data['notes_html'] = '';
        }

        return EmailTemplateService::send('reservation_confirmation_gerant', $gerant->email, $data);
    }

    /**
     * Envoyer un rappel de réservation (supporte clients inscrits et invités).
     */
    public static function sendReservationReminder(Reservation $reservation, int $hoursBefore = 24): bool
    {
        $client = $reservation->user;
        $emailTo = $client?->email ?? $reservation->email_client;
        if (! $emailTo) {
            return false;
        }

        $nomClient = $client?->name ?? ($reservation->nom_client ?? 'Client');
        $contact = $reservation->entreprise->telephone ?? $reservation->entreprise->email ?? 'N/A';

        $data = [
            'nom_client' => $nomClient,
            'nom_entreprise' => $reservation->entreprise->nom,
            'nom_service' => $reservation->type_service ?? 'Service',
            'date_reservation' => self::formatDateReservation($reservation),
            'duree' => $reservation->duree_minutes ?? 30,
            'heures_avant' => $hoursBefore,
            'contact_entreprise' => $contact,
            'url_reservation' => route('public.reservation.show', $reservation->hash ?? $reservation->id),
        ];

        if ($reservation->lieu) {
            $data['lieu_html'] = '<p><strong>Lieu :</strong> '.htmlspecialchars($reservation->lieu).'</p>';
        } else {
            $data['lieu_html'] = '';
        }

        if ($reservation->membre && $reservation->membre->user) {
            $data['membre_html'] = '<p><strong>Avec :</strong> '.htmlspecialchars($reservation->membre->user->name).'</p>';
        } else {
            $data['membre_html'] = '';
        }

        return EmailTemplateService::send('reservation_reminder', $emailTo, $data);
    }

    /**
     * Envoyer une notification de paiement reçu
     */
    public static function sendPaymentReceived(Reservation $reservation): bool
    {
        $client = $reservation->user;
        if (! $client) {
            return false;
        }

        $data = [
            'nom_client' => $client->name,
            'nom_entreprise' => $reservation->entreprise->nom,
            'nom_service' => $reservation->type_service ?? 'Service',
            'date_reservation' => self::formatDateReservation($reservation),
            'montant' => number_format($reservation->prix, 2, ',', ' '),
            'date_paiement' => ($reservation->date_paiement ?? now())->format('d/m/Y à H:i'),
            'url_reservation' => route('public.reservation.show', $reservation->hash ?? $reservation->id),
        ];

        return EmailTemplateService::send('payment_received', $client->email, $data);
    }

    /**
     * Envoyer une notification de nouveau message
     */
    public static function sendNewMessage(Message $message, Conversation $conversation): bool
    {
        $recipient = null;
        $nomClient = 'Client';

        if ($conversation->user_id) {
            $recipient = $conversation->user;
            $nomClient = $recipient->name;
        } else {
            $recipient = $conversation->entreprise->user;
        }

        if (! $recipient) {
            return false;
        }

        $apercu = $message->contenuPourAffichage();
        if ($apercu === null && $message->image) {
            $apercu = '📷 Photo jointe';
        }
        if ($apercu === null) {
            $apercu = 'Nouveau message';
        }

        $data = [
            'nom_client' => $nomClient,
            'nom_entreprise' => $conversation->entreprise->nom,
            'contenu_message' => $apercu,
            'url_messagerie' => $conversation->user_id
                ? route('messagerie.show', $conversation->entreprise->slug)
                : route('messagerie.show-gerant', [$conversation->entreprise->slug, $conversation->id]),
        ];

        return EmailTemplateService::send('new_message', $recipient->email, $data);
    }

    /**
     * Envoyer une notification d'annulation de réservation au client
     * (supporte clients inscrits et invités).
     */
    public static function sendReservationCancelledClient(Reservation $reservation, string $cancelledBy = 'client'): bool
    {
        $client = $reservation->user;
        $emailTo = $client?->email ?? $reservation->email_client;
        if (! $emailTo) {
            return false;
        }

        $nomClient = $client?->name ?? ($reservation->nom_client ?? 'Client');
        $dateStr = self::formatDateReservation($reservation);

        if ($cancelledBy === 'client') {
            $messageAnnulation = '<p>Nous vous confirmons l\'annulation de votre réservation du <strong>'.$dateStr.'</strong>.</p>';
        } else {
            $messageAnnulation = '<p>Votre réservation du <strong>'.$dateStr.'</strong> a été annulée par <strong>'.$reservation->entreprise->nom.'</strong>.</p>';
        }

        $remboursementHtml = '';
        if (! $reservation->est_paye) {
            $remboursementHtml = '<p>Si un paiement avait été effectué, il sera remboursé selon nos conditions générales.</p>';
        }

        $data = [
            'nom_client' => $nomClient,
            'nom_entreprise' => $reservation->entreprise->nom,
            'nom_service' => $reservation->type_service ?? 'Service',
            'date_reservation' => $dateStr,
            'prix' => number_format($reservation->prix, 2, ',', ' '),
            'message_annulation' => $messageAnnulation,
            'remboursement_html' => $remboursementHtml,
            'url_entreprise' => route('public.entreprise', $reservation->entreprise->slug),
        ];

        return EmailTemplateService::send('reservation_cancelled_client', $emailTo, $data);
    }

    /**
     * Envoyer une notification d'annulation de réservation au gérant.
     */
    public static function sendReservationCancelledGerant(Reservation $reservation): bool
    {
        $gerant = $reservation->entreprise->user;
        if (! $gerant) {
            return false;
        }

        $client = $reservation->user;
        $nomClient = $client ? $client->name : ($reservation->nom_client ?? 'Client non inscrit');

        $data = [
            'nom_client' => $nomClient,
            'nom_entreprise' => $reservation->entreprise->nom,
            'nom_service' => $reservation->type_service ?? 'Service',
            'date_reservation' => self::formatDateReservation($reservation),
            'prix' => number_format($reservation->prix, 2, ',', ' '),
            'url_reservation' => route('reservations.show', [$reservation->entreprise->slug, $reservation->id]),
        ];

        return EmailTemplateService::send('reservation_cancelled_gerant', $gerant->email, $data);
    }

    /**
     * Envoyer un rapport hebdomadaire
     */
    public static function sendWeeklyReport(User $user, Entreprise $entreprise, array $stats): bool
    {
        $data = [
            'nom_gerant' => $user->name,
            'nom_entreprise' => $entreprise->nom,
            'total_reservations' => $stats['total_reservations'] ?? 0,
            'reservations_confirmees' => $stats['reservations_confirmees'] ?? 0,
            'reservations_en_attente' => $stats['reservations_en_attente'] ?? 0,
            'revenu_total' => number_format($stats['revenu_total'] ?? 0, 2, ',', ' '),
            'revenu_paye' => number_format($stats['revenu_paye'] ?? 0, 2, ',', ' '),
            'url_dashboard' => route('entreprise.dashboard', $entreprise->slug),
        ];

        return EmailTemplateService::send('weekly_report', $user->email, $data);
    }

    /**
     * Envoyer un rapport mensuel
     */
    public static function sendMonthlyReport(User $user, Entreprise $entreprise, array $stats): bool
    {
        $data = [
            'nom_gerant' => $user->name,
            'nom_entreprise' => $entreprise->nom,
            'total_reservations' => $stats['total_reservations'] ?? 0,
            'reservations_confirmees' => $stats['reservations_confirmees'] ?? 0,
            'reservations_en_attente' => $stats['reservations_en_attente'] ?? 0,
            'reservations_terminees' => $stats['reservations_terminees'] ?? 0,
            'revenu_total' => number_format($stats['revenu_total'] ?? 0, 2, ',', ' '),
            'revenu_paye' => number_format($stats['revenu_paye'] ?? 0, 2, ',', ' '),
            'url_dashboard' => route('entreprise.dashboard', $entreprise->slug),
        ];

        return EmailTemplateService::send('monthly_report', $user->email, $data);
    }

    /**
     * Envoyer un email de récupération SCA (3D Secure requis)
     *
     * Quand la banque exige une authentification 3DS en mode off_session,
     * on envoie un email au client avec un lien pour finaliser le paiement.
     */
    public static function sendPaymentAuthenticationRequired(User $user, Echeance $echeance, string $paymentIntentId): bool
    {
        $authenticateUrl = route('payment.authenticate', ['payment_intent_id' => $paymentIntentId]);

        $data = [
            'nom_client' => $user->name,
            'montant' => number_format($echeance->montant_final ?? $echeance->montant_du ?? 0, 2, ',', ' ').' €',
            'libelle_echeance' => $echeance->libelle(),
            'periode' => $echeance->periode_debut->format('d/m/Y').' - '.$echeance->periode_fin->format('d/m/Y'),
            'url_authenticate' => $authenticateUrl,
            'url_checkout' => route('checkout.index'),
        ];

        return EmailTemplateService::send('payment_authentication_required', $user->email, $data);
    }
}
