<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Vérification quotidienne des essais gratuits
Schedule::command('essais:check-expiration')->dailyAt('09:00')->withoutOverlapping();

// Synchronisation quotidienne des abonnements Stripe (sécurité contre les webhooks manqués)
Schedule::command('stripe:sync-subscriptions --from-stripe')->dailyAt('03:00')->withoutOverlapping();
