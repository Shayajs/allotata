<?php

namespace App\Observers;

use App\Models\ErrorLog;
use App\Services\AdminNotificationService;

class ErrorLogObserver
{
    public function created(ErrorLog $errorLog): void
    {
        try {
            app(AdminNotificationService::class)->notifyErrorLog($errorLog);
        } catch (\Throwable $e) {
            \Log::warning('ErrorLogObserver: notification admin échouée', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
