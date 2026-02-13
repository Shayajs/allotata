<?php

namespace App\Http\Resources\Google;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Représente une réservation confirmée selon la spec RwG v3.
 *
 * @see https://developers.google.com/maps-booking/reference/rest-api-v3/booking-server-spec#booking
 */
class BookingResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        /** @var Reservation $reservation */
        $reservation = $this->resource;

        return [
            'booking_id' => (string) $reservation->id,
            'slot' => [
                'merchant_id' => (string) $reservation->entreprise_id,
                'service_id' => (string) ($reservation->type_service_id ?? 0),
                'start_sec' => $reservation->date_reservation
                    ? (int) $reservation->date_reservation->timestamp
                    : 0,
                'duration_sec' => ($reservation->duree_minutes ?? 60) * 60,
            ],
            'user_information' => [
                'user_id' => (string) ($reservation->user_id ?? ''),
                'given_name' => $this->extractGivenName($reservation),
                'family_name' => $this->extractFamilyName($reservation),
                'telephone' => $reservation->telephone_client
                    ?? $reservation->telephone_client_non_inscrit
                    ?? '',
                'email' => $reservation->email_client
                    ?? $reservation->user?->email
                    ?? '',
            ],
            'status' => $this->mapStatut($reservation->statut),
            'payment_information' => [
                'prepayment_status' => $reservation->est_paye ? 'PREPAYMENT_PROVIDED' : 'PREPAYMENT_NOT_PROVIDED',
                'price' => [
                    'price_micros' => (int) (($reservation->prix ?? 0) * 1_000_000),
                    'currency_code' => 'EUR',
                ],
            ],
        ];
    }

    /**
     * Mappe le statut Allotata → statut RwG.
     */
    protected function mapStatut(?string $statut): string
    {
        return match ($statut) {
            'confirmee' => 'CONFIRMED',
            'en_attente' => 'PENDING_MERCHANT_CONFIRMATION',
            'annulee' => 'CANCELED',
            'terminee' => 'CONFIRMED',
            default => 'PENDING_MERCHANT_CONFIRMATION',
        };
    }

    /**
     * Extrait le prénom du client.
     */
    protected function extractGivenName(Reservation $reservation): string
    {
        $name = $reservation->nom_client ?? $reservation->user?->name ?? '';
        $parts = explode(' ', $name, 2);
        return $parts[0] ?? '';
    }

    /**
     * Extrait le nom de famille du client.
     */
    protected function extractFamilyName(Reservation $reservation): string
    {
        $name = $reservation->nom_client ?? $reservation->user?->name ?? '';
        $parts = explode(' ', $name, 2);
        return $parts[1] ?? '';
    }
}
