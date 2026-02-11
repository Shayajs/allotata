<?php

namespace App\Jobs;

use App\Models\Entreprise;
use App\Services\GoogleCalendarService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncGoogleCalendarChanges implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    public function __construct(
        public Entreprise $entreprise
    ) {}

    public function handle(GoogleCalendarService $service): void
    {
        if (!$this->entreprise->aGoogleCalendar()) {
            return;
        }

        $service->syncIncrementalChanges($this->entreprise);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SyncGoogleCalendarChanges : échec pour entreprise #{$this->entreprise->id}: " . $exception->getMessage());
    }
}
