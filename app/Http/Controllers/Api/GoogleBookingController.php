<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Google\BookingFailureResource;
use App\Http\Resources\Google\BookingResource;
use App\Http\Resources\Google\SlotResource;
use App\Models\Entreprise;
use App\Models\Reservation;
use App\Models\TypeService;
use App\Services\ReservationSlotService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Booking Server pour Google Reserve with Google (RwG) v3.
 *
 * Endpoints standardisés appelés par les serveurs Google :
 * - HealthCheck : vérification que le serveur est opérationnel
 * - BatchAvailabilityLookup : confirmation temps réel de la disponibilité des créneaux
 * - CreateBooking : création d'une réservation depuis Google
 * - UpdateBooking : mise à jour (annulation) d'une réservation
 * - GetBookingStatus : récupérer le statut d'une réservation
 *
 * @see https://developers.google.com/maps-booking/reference/rest-api-v3/booking-server-spec
 */
class GoogleBookingController extends Controller
{
    // =========================================================================
    // HealthCheck
    // =========================================================================

    /**
     * GET /v3/HealthCheck
     *
     * Google appelle ce endpoint pour vérifier que le Booking Server est opérationnel.
     * Doit retourner un HTTP 200 avec un corps vide ou minimal.
     */
    public function healthCheck(): JsonResponse
    {
        return response()->json([
            'status' => 'SERVING',
        ]);
    }

    // =========================================================================
    // BatchAvailabilityLookup
    // =========================================================================

    /**
     * POST /v3/BatchAvailabilityLookup
     *
     * Google envoie une liste de créneaux à vérifier.
     * On confirme en temps réel si chaque créneau est disponible ou non.
     *
     * Request body (spec RwG v3) :
     * {
     *   "slot_time_range": {
     *     "start_sec": 1234567890,
     *     "end_sec": 1234571490
     *   },
     *   "slots": [
     *     { "merchant_id": "42", "service_id": "7", "start_sec": 1234567890, "duration_sec": 3600 },
     *     ...
     *   ]
     * }
     */
    public function batchAvailabilityLookup(Request $request): JsonResponse
    {
        $slots = $request->input('slots', []);

        if (empty($slots)) {
            return response()->json(['slot_time_availability' => []]);
        }

        $results = [];

        foreach ($slots as $slot) {
            $merchantId = $slot['merchant_id'] ?? null;
            $serviceId = $slot['service_id'] ?? null;
            $startSec = $slot['start_sec'] ?? null;
            $durationSec = $slot['duration_sec'] ?? null;

            if (!$merchantId || !$serviceId || !$startSec || !$durationSec) {
                $results[] = $this->buildSlotAvailability($slot, false);
                continue;
            }

            try {
                $entreprise = Entreprise::find($merchantId);
                $typeService = TypeService::where('id', $serviceId)
                    ->where('entreprise_id', $merchantId)
                    ->where('est_actif', true)
                    ->first();

                if (!$entreprise || !$typeService) {
                    $results[] = $this->buildSlotAvailability($slot, false);
                    continue;
                }

                if ($entreprise->prendRdvSurDemande()) {
                    $results[] = $this->buildSlotAvailability($slot, false);
                    continue;
                }

                $debut = Carbon::createFromTimestamp($startSec);
                $dureeMinutes = (int) ceil($durationSec / 60);

                // Vérification rapide via le service existant
                $disponible = ReservationSlotService::estCreneauDisponible(
                    $entreprise->id,
                    null, // pas de membre spécifique pour RwG
                    $debut,
                    $dureeMinutes
                );

                $results[] = $this->buildSlotAvailability($slot, $disponible);
            } catch (\Throwable $e) {
                Log::error('RwG BatchAvailabilityLookup erreur slot', [
                    'slot' => $slot,
                    'error' => $e->getMessage(),
                ]);
                $results[] = $this->buildSlotAvailability($slot, false);
            }
        }

        return response()->json([
            'slot_time_availability' => $results,
        ]);
    }

    // =========================================================================
    // CreateBooking
    // =========================================================================

    /**
     * POST /v3/CreateBooking
     *
     * Google envoie les informations de réservation.
     * On crée la Reservation dans Allotata et retourne le booking confirmé.
     *
     * Request body (spec RwG v3) :
     * {
     *   "slot": { "merchant_id": "42", "service_id": "7", "start_sec": ..., "duration_sec": ... },
     *   "user_information": { "user_id": "...", "given_name": "...", "family_name": "...", "telephone": "...", "email": "..." },
     *   "idempotency_token": "abc123"
     * }
     */
    public function createBooking(Request $request): JsonResponse
    {
        $slot = $request->input('slot', []);
        $userInfo = $request->input('user_information', []);
        $idempotencyToken = $request->input('idempotency_token', '');

        $merchantId = $slot['merchant_id'] ?? null;
        $serviceId = $slot['service_id'] ?? null;
        $startSec = $slot['start_sec'] ?? null;
        $durationSec = $slot['duration_sec'] ?? null;

        // Idempotence : vérifier si une réservation existe déjà avec ce token
        if ($idempotencyToken) {
            $existing = Reservation::where('hash', $idempotencyToken)->first();
            if ($existing) {
                return response()->json([
                    'booking' => new BookingResource($existing),
                ]);
            }
        }

        // Validations de base
        if (!$merchantId || !$serviceId || !$startSec || !$durationSec) {
            return response()->json([
                'booking_failure' => new BookingFailureResource([
                    'cause' => BookingFailureResource::CAUSE_SLOT_UNAVAILABLE,
                    'description' => 'Paramètres de créneau manquants.',
                ]),
            ], 400);
        }

        $entreprise = Entreprise::find($merchantId);
        $typeService = TypeService::where('id', $serviceId)
            ->where('entreprise_id', $merchantId)
            ->where('est_actif', true)
            ->first();

        if (!$entreprise || !$typeService) {
            return response()->json([
                'booking_failure' => new BookingFailureResource([
                    'cause' => BookingFailureResource::CAUSE_SLOT_UNAVAILABLE,
                    'description' => 'Marchand ou service introuvable.',
                ]),
            ], 404);
        }

        if ($entreprise->prendRdvSurDemande()) {
            return response()->json([
                'booking_failure' => new BookingFailureResource([
                    'cause' => BookingFailureResource::CAUSE_SLOT_UNAVAILABLE,
                    'description' => 'Cette entreprise ne propose pas de créneaux en ligne.',
                ]),
            ], 409);
        }

        $debut = Carbon::createFromTimestamp($startSec);
        $dureeMinutes = (int) ceil($durationSec / 60);
        $fin = $debut->copy()->addMinutes($dureeMinutes);

        // Déterminer le statut initial
        $statutInitial = $entreprise->accepter_reservations_auto ? 'confirmee' : 'en_attente';

        $clientName = trim(($userInfo['given_name'] ?? '') . ' ' . ($userInfo['family_name'] ?? ''));

        $reservationData = [
            'entreprise_id' => $entreprise->id,
            'type_service_id' => $typeService->id,
            'type_service' => $typeService->nom,
            'date_reservation' => $debut,
            'date_fin' => $fin,
            'duree_minutes' => $dureeMinutes,
            'prix' => $typeService->prix,
            'statut' => $statutInitial,
            'nom_client' => $clientName ?: 'Client Google',
            'email_client' => $userInfo['email'] ?? null,
            'telephone_client_non_inscrit' => $userInfo['telephone'] ?? null,
            'hash' => $idempotencyToken ?: Str::random(64),
            'notes' => 'Réservation via Reserve with Google',
        ];

        // Vérifier la disponibilité et créer la réservation (anti-doublon)
        $reservation = ReservationSlotService::reserverSiDisponible(
            $entreprise->id,
            null,
            $debut,
            $dureeMinutes,
            fn () => Reservation::create($reservationData)
        );

        if (!$reservation) {
            return response()->json([
                'booking_failure' => new BookingFailureResource([
                    'cause' => BookingFailureResource::CAUSE_SLOT_UNAVAILABLE,
                    'description' => 'Le créneau demandé n\'est plus disponible.',
                ]),
            ], 409);
        }

        Log::info('RwG CreateBooking : réservation créée', [
            'booking_id' => $reservation->id,
            'entreprise_id' => $entreprise->id,
            'service' => $typeService->nom,
            'start' => $debut->toDateTimeString(),
        ]);

        return response()->json([
            'booking' => new BookingResource($reservation),
        ]);
    }

    // =========================================================================
    // UpdateBooking
    // =========================================================================

    /**
     * POST /v3/UpdateBooking
     *
     * Google peut demander une mise à jour (principalement annulation).
     *
     * Request body :
     * {
     *   "booking": { "booking_id": "123", "status": "CANCELED" }
     * }
     */
    public function updateBooking(Request $request): JsonResponse
    {
        $bookingData = $request->input('booking', []);
        $bookingId = $bookingData['booking_id'] ?? null;
        $newStatus = $bookingData['status'] ?? null;

        if (!$bookingId) {
            return response()->json([
                'booking_failure' => new BookingFailureResource([
                    'cause' => BookingFailureResource::CAUSE_MERCHANT_INTERNAL_ERROR,
                    'description' => 'booking_id manquant.',
                ]),
            ], 400);
        }

        $reservation = Reservation::find($bookingId);

        if (!$reservation) {
            return response()->json([
                'booking_failure' => new BookingFailureResource([
                    'cause' => BookingFailureResource::CAUSE_MERCHANT_INTERNAL_ERROR,
                    'description' => 'Réservation introuvable.',
                ]),
            ], 404);
        }

        // Traiter l'annulation
        if ($newStatus === 'CANCELED') {
            if ($reservation->statut === 'annulee') {
                return response()->json([
                    'booking_failure' => new BookingFailureResource([
                        'cause' => BookingFailureResource::CAUSE_BOOKING_ALREADY_CANCELLED,
                        'description' => 'La réservation est déjà annulée.',
                    ]),
                ], 400);
            }

            if ($reservation->est_paye) {
                return response()->json([
                    'booking_failure' => new BookingFailureResource([
                        'cause' => BookingFailureResource::CAUSE_BOOKING_NOT_CANCELLABLE,
                        'description' => 'Une réservation payée ne peut pas être annulée via cette API.',
                    ]),
                ], 400);
            }

            $reservation->update(['statut' => 'annulee']);

            Log::info('RwG UpdateBooking : réservation annulée', [
                'booking_id' => $reservation->id,
            ]);
        }

        return response()->json([
            'booking' => new BookingResource($reservation->fresh()),
        ]);
    }

    // =========================================================================
    // GetBookingStatus
    // =========================================================================

    /**
     * POST /v3/GetBookingStatus
     *
     * Google demande le statut actuel d'une réservation.
     *
     * Request body :
     * { "booking_id": "123" }
     */
    public function getBookingStatus(Request $request): JsonResponse
    {
        $bookingId = $request->input('booking_id');

        if (!$bookingId) {
            return response()->json([
                'booking_failure' => new BookingFailureResource([
                    'cause' => BookingFailureResource::CAUSE_MERCHANT_INTERNAL_ERROR,
                    'description' => 'booking_id manquant.',
                ]),
            ], 400);
        }

        $reservation = Reservation::with(['entreprise', 'typeService', 'user'])->find($bookingId);

        if (!$reservation) {
            return response()->json([
                'booking_failure' => new BookingFailureResource([
                    'cause' => BookingFailureResource::CAUSE_MERCHANT_INTERNAL_ERROR,
                    'description' => 'Réservation introuvable.',
                ]),
            ], 404);
        }

        return response()->json([
            'booking' => new BookingResource($reservation),
        ]);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Construit un objet slot_time_availability pour la réponse BatchAvailabilityLookup.
     */
    protected function buildSlotAvailability(array $slot, bool $available): array
    {
        return [
            'slot' => [
                'merchant_id' => (string) ($slot['merchant_id'] ?? ''),
                'service_id' => (string) ($slot['service_id'] ?? ''),
                'start_sec' => (int) ($slot['start_sec'] ?? 0),
                'duration_sec' => (int) ($slot['duration_sec'] ?? 0),
            ],
            'count_available' => $available ? 1 : 0,
            'last_online_cancellable_sec' => $available
                ? max(0, (int) ($slot['start_sec'] ?? 0) - 7200) // 2h avant
                : 0,
        ];
    }
}
