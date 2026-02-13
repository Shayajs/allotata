<?php

namespace App\Http\Resources\Google;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Représente un échec de réservation selon la spec RwG v3.
 *
 * @see https://developers.google.com/maps-booking/reference/rest-api-v3/booking-server-spec#bookingfailure
 */
class BookingFailureResource extends JsonResource
{
    /**
     * Causes d'échec RwG standardisées.
     */
    public const CAUSE_SLOT_UNAVAILABLE = 'SLOT_UNAVAILABLE';
    public const CAUSE_PAYMENT_ERROR = 'PAYMENT_ERROR_CARD_TYPE_REJECTED';
    public const CAUSE_BOOKING_ALREADY_CANCELLED = 'BOOKING_ALREADY_CANCELLED';
    public const CAUSE_BOOKING_NOT_CANCELLABLE = 'BOOKING_NOT_CANCELLABLE';
    public const CAUSE_OVERLAPPING_RESERVATION = 'OVERLAPPING_RESERVATION';
    public const CAUSE_MERCHANT_INTERNAL_ERROR = 'BOOKING_NOT_FOUND';

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'cause' => $this->resource['cause'] ?? self::CAUSE_SLOT_UNAVAILABLE,
            'description' => $this->resource['description'] ?? '',
        ];
    }
}
