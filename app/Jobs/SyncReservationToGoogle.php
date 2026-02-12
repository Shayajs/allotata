<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Services\GoogleCalendarService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncReservationToGoogle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Nombre de tentatives max.
     */
    public int $tries = 3;

    /**
     * Délai entre les tentatives (en secondes).
     */
    public array $backoff = [10, 60, 300];

    public function __construct(
        public int $reservationId,
        public string $action // 'create', 'update', 'delete'
    ) {}

    public function handle(GoogleCalendarService $service): void
    {
        $reservation = Reservation::with('entreprise')->find($this->reservationId);

        if (!$reservation) {
            Log::info("SyncReservationToGoogle : réservation #{$this->reservationId} introuvable, abandon.");
            return;
        }

        if (!$reservation->entreprise?->aGoogleCalendar()) {
            return;
        }

        match ($this->action) {
            'create' => $service->createEvent($reservation),
            'update' => $service->updateEvent($reservation),
            'delete' => $service->deleteEvent($reservation),
            default => Log::warning("SyncReservationToGoogle : action inconnue '{$this->action}' pour réservation #{$this->reservationId}"),
        };
    }

    /**
     * Gestion des erreurs finales (après toutes les tentatives).
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("SyncReservationToGoogle : échec définitif pour réservation #{$this->reservationId} (action: {$this->action}): " . $exception->getMessage());
    }
}
