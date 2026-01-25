<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Vérification quotidienne des essais gratuits
Schedule::command('essais:check-expiration')->dailyAt('09:00')->withoutOverlapping();

// Échéances mensuelles : qui doit payer ce mois (jour_facturation = aujourd'hui)
Schedule::command('subscriptions:check-echeances')->dailyAt('06:00')->withoutOverlapping();

// Réconciliation : vérification directe Stripe pour échéances en_attente (rattrapage webhook / success)
Schedule::command('subscriptions:reconcile-echeances')->dailyAt('06:30')->withoutOverlapping();

// Ancienne sync Stripe (désactivée – paiements ponctuels uniquement)
// Schedule::command('stripe:sync-subscriptions --from-stripe')->dailyAt('03:00')->withoutOverlapping();
