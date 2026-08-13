<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Reservation;

class ReservationClientNotificationService
{
    public function notifyPrise(Reservation $reservation): ?Notification
    {
        if (! $reservation->user_id) {
            return null;
        }

        $reservation->loadMissing(['entreprise', 'user', 'typeService', 'rendezVous']);
        $confirmee = $reservation->statut === 'confirmee';

        return Notification::creer(
            $reservation->user_id,
            'reservation',
            $confirmee ? 'Réservation confirmée' : 'Réservation en attente',
            $this->messagePrise($reservation),
            route('dashboard'),
            ['reservation_id' => $reservation->id, 'entreprise_id' => $reservation->entreprise_id]
        );
    }

    public function notifyAnnulation(Reservation $reservation, string $suffix = ''): ?Notification
    {
        if (! $reservation->user_id) {
            return null;
        }

        $reservation->loadMissing(['entreprise', 'user', 'typeService', 'rendezVous']);
        $message = $this->messageAnnulation($reservation);
        if ($suffix !== '') {
            $message .= $suffix;
        }

        return Notification::creer(
            $reservation->user_id,
            'reservation',
            'Réservation annulée',
            $message,
            route('dashboard'),
            ['reservation_id' => $reservation->id, 'entreprise_id' => $reservation->entreprise_id]
        );
    }

    public function notifyRappel(Reservation $reservation): ?Notification
    {
        if (! $reservation->user_id) {
            return null;
        }

        $reservation->loadMissing(['entreprise', 'user', 'typeService', 'rendezVous']);

        return Notification::creer(
            $reservation->user_id,
            'rappel_rdv',
            'Rappel de rendez-vous',
            $this->messageRappel($reservation),
            route('dashboard'),
            ['reservation_id' => $reservation->id, 'entreprise_id' => $reservation->entreprise_id]
        );
    }

    public function messagePrise(Reservation $reservation): string
    {
        $vars = $this->placeholders($reservation);
        $custom = trim((string) ($reservation->entreprise?->notif_message_prise ?? ''));
        if ($custom !== '') {
            return $this->applyPlaceholders($custom, $vars);
        }

        if ($reservation->statut === 'confirmee') {
            return "Votre réservation pour {$vars['nom_entreprise']} le {$vars['date_complete']} a été confirmée !";
        }

        return "Votre demande de réservation pour {$vars['nom_entreprise']} le {$vars['date_complete']} est en attente de confirmation.";
    }

    public function messageAnnulation(Reservation $reservation): string
    {
        $vars = $this->placeholders($reservation);
        $custom = trim((string) ($reservation->entreprise?->notif_message_annulation ?? ''));
        if ($custom !== '') {
            return $this->applyPlaceholders($custom, $vars);
        }

        return "Votre réservation pour {$vars['nom_entreprise']} le {$vars['date_complete']} a été annulée.";
    }

    public function messageRappel(Reservation $reservation): string
    {
        $vars = $this->placeholders($reservation);
        $heure = $vars['heure'] !== '—' ? $vars['heure'] : '';

        if ($heure !== '') {
            return "Rappel : demain à {$heure} vous avez rendez-vous chez {$vars['nom_entreprise']} pour {$vars['prestations']}.";
        }

        return "Rappel : demain vous avez rendez-vous chez {$vars['nom_entreprise']} pour {$vars['prestations']}.";
    }

    /**
     * @param  array<string, string>  $vars
     */
    public function applyPlaceholders(string $template, array $vars): string
    {
        return strtr($template, [
            '{nom_client}' => $vars['nom_client'],
            '{nom_entreprise}' => $vars['nom_entreprise'],
            '{prestations}' => $vars['prestations'],
            '{date}' => $vars['date'],
            '{heure}' => $vars['heure'],
            '{lieu}' => $vars['lieu'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function placeholders(Reservation $reservation): array
    {
        $reservation->loadMissing(['entreprise', 'user', 'typeService', 'rendezVous']);

        $dt = $reservation->date_reservation;
        $isDateButoire = $reservation->estDateButoire();

        if ($dt) {
            $date = $dt->format('d/m/Y');
            $heure = $isDateButoire ? '—' : $dt->format('H\hi');
            $dateComplete = $isDateButoire
                ? $dt->format('d/m/Y').' (date butoire)'
                : $dt->format('d/m/Y à H:i');
        } elseif ($reservation->date_butoire) {
            $date = $reservation->date_butoire->format('d/m/Y');
            $heure = '—';
            $dateComplete = $date.' (date butoire)';
        } else {
            $date = '—';
            $heure = '—';
            $dateComplete = '—';
        }

        return [
            'nom_client' => $reservation->user?->name ?? ($reservation->nom_client ?? 'Client'),
            'nom_entreprise' => $reservation->entreprise?->nom ?? 'l\'entreprise',
            'prestations' => $this->prestationsLabel($reservation),
            'date' => $date,
            'heure' => $heure,
            'lieu' => $reservation->lieu ?: '—',
            'date_complete' => $dateComplete,
        ];
    }

    private function prestationsLabel(Reservation $reservation): string
    {
        if ($reservation->rendezVous->isNotEmpty()) {
            $parts = $reservation->rendezVous
                ->map(function ($rdv) {
                    $titre = $rdv->titre ?: 'Rendez-vous';
                    if ($rdv->date_heure) {
                        return $titre.' ('.$rdv->date_heure->format('d/m/Y à H:i').')';
                    }

                    return $titre;
                })
                ->filter()
                ->implode(', ');

            if ($parts !== '') {
                return $parts;
            }
        }

        return $reservation->type_service
            ?: ($reservation->typeService?->nom ?? 'prestation');
    }
}
