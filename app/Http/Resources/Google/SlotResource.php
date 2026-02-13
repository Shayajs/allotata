<?php

namespace App\Http\Resources\Google;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Représente un créneau de disponibilité selon la spec RwG v3.
 *
 * @see https://developers.google.com/maps-booking/reference/rest-api-v3/booking-server-spec#slot
 */
class SlotResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'merchant_id' => (string) $this->resource['merchant_id'],
            'service_id' => (string) $this->resource['service_id'],
            'start_sec' => (int) $this->resource['start_sec'],
            'duration_sec' => (int) $this->resource['duration_sec'],
            'availability_tag' => $this->resource['availability_tag'] ?? '',
            'resources' => $this->when(
                !empty($this->resource['staff_id']),
                fn () => [
                    'staff_id' => (string) $this->resource['staff_id'],
                    'staff_name' => $this->resource['staff_name'] ?? '',
                ]
            ),
        ];
    }
}
