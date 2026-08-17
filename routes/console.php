<?php

use App\Models\ScheduledTaskLog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// =============================================
// Helper : enregistrer chaque tâche avec logging
// =============================================

/**
 * Enregistre une tâche planifiée avec logging automatique (before/after/onFailure).
 */
if (!function_exists('scheduleWithLogging')) {
    function scheduleWithLogging(string $command, string $description = ''): \Illuminate\Console\Scheduling\Event
    {
        $event = Schedule::command($command);

        $event->before(function () use ($command, $description) {
            try {
                ScheduledTaskLog::create([
                    'command' => $command,
                    'description' => $description,
                    'status' => 'running',
                    'started_at' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::warning("ScheduledTaskLog before error: {$e->getMessage()}");
            }
        });

        $event->after(function () use ($command) {
            try {
                $log = ScheduledTaskLog::where('command', $command)
                    ->where('status', 'running')
                    ->latest()
                    ->first();

                if ($log) {
                    $duration = $log->started_at ? now()->diffInMilliseconds($log->started_at) / 1000 : null;
                    $log->update([
                        'status' => 'success',
                        'exit_code' => 0,
                        'finished_at' => now(),
                        'duration_seconds' => $duration,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning("ScheduledTaskLog after error: {$e->getMessage()}");
            }
        });

        $event->onFailure(function () use ($command) {
            try {
                $log = ScheduledTaskLog::where('command', $command)
                    ->where('status', 'running')
                    ->latest()
                    ->first();

                if ($log) {
                    $duration = $log->started_at ? now()->diffInMilliseconds($log->started_at) / 1000 : null;
                    $log->update([
                        'status' => 'error',
                        'exit_code' => 1,
                        'finished_at' => now(),
                        'duration_seconds' => $duration,
                        'output' => 'La tâche a échoué. Consultez les logs Laravel pour plus de détails.',
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning("ScheduledTaskLog failure error: {$e->getMessage()}");
            }
        });

        return $event;
    }
}

// =============================================
// ABONNEMENTS & PAIEMENTS
// =============================================

// Créer les échéances Stripe (Premium + options entreprise) selon le jour de facturation
scheduleWithLogging('subscriptions:check-echeances', 'Échéances abonnements Stripe')
    ->dailyAt('06:00')->withoutOverlapping();

// Factures compta pour abonnements manuels (Premium + options entreprise)
scheduleWithLogging('subscriptions:generate-invoices', 'Factures abonnements manuels')
    ->dailyAt('06:05')->withoutOverlapping();

// Auto-charge des échéances a_payer + retry des échecs (3 tentatives, cancel après 7j)
scheduleWithLogging('subscriptions:process-payments', 'Auto-charge & retry paiements')
    ->dailyAt('06:15')->withoutOverlapping();

scheduleWithLogging('play:sync-purchases', 'Sync achats Google Play')
    ->dailyAt('06:20')->withoutOverlapping();

// Réconciliation matin : rattrapage webhooks Stripe ratés (vérifie les en_attente)
scheduleWithLogging('subscriptions:reconcile-echeances', 'Réconciliation Stripe (matin)')
    ->dailyAt('06:30')->withoutOverlapping();

// Réconciliation soir : 2e passe pour ne rater aucun paiement de la journée
scheduleWithLogging('subscriptions:reconcile-echeances', 'Réconciliation Stripe (soir)')
    ->dailyAt('20:00')->withoutOverlapping();

// =============================================
// ESSAIS GRATUITS
// =============================================

// Vérifie les essais expirants : rappel J-2, arrêt + notif (plus de nouvel essai), relance J+3
scheduleWithLogging('essais:check-expiration', 'Vérification essais gratuits')
    ->dailyAt('09:00')->withoutOverlapping();

// =============================================
// RAPPELS RÉSERVATIONS (email + SMS si Twilio configuré)
// =============================================

// Rappel 24h avant le rendez-vous (toutes les heures)
scheduleWithLogging('reservations:send-reminders --hours=24', 'Rappels RDV (24h)')
    ->hourly()->withoutOverlapping();

// Rappel 2h avant le rendez-vous (toutes les heures, décalé de 30 min)
scheduleWithLogging('reservations:send-reminders --hours=2', 'Rappels RDV (2h)')
    ->hourlyAt(30)->withoutOverlapping();

// =============================================
// RAPPORTS
// =============================================

// Rapport hebdomadaire aux gérants — chaque lundi à 9h
scheduleWithLogging('reports:send-weekly', 'Rapports hebdomadaires')
    ->weeklyOn(1, '09:00')->withoutOverlapping();

// Rapport mensuel aux gérants — le 1er du mois à 9h
scheduleWithLogging('reports:send-monthly', 'Rapports mensuels')
    ->monthlyOn(1, '09:00')->withoutOverlapping();

// =============================================
// SAUVEGARDE BASE DE DONNÉES
// =============================================

// Backup quotidien à 2h du matin (conserve les 30 dernières)
scheduleWithLogging('db:backup --keep=30', 'Sauvegarde BDD')
    ->dailyAt('02:00')->withoutOverlapping();

// =============================================
// GOOGLE CALENDAR
// =============================================

// Renouveler les webhooks Google Calendar (expirent après 7 jours)
scheduleWithLogging('google-calendar:renew-watches', 'Renouvellement watches Google Calendar')
    ->dailyAt('05:00')->withoutOverlapping();

// Synchronisation complète bidirectionnelle Google Calendar (toutes les 15 min)
// Pousse les réservations sans google_event_id + récupère les changements Google
scheduleWithLogging('google-calendar:sync-all', 'Sync bidirectionnelle Google Calendar')
    ->everyFifteenMinutes()->withoutOverlapping();

// Régénérer le feed Reserve with Google (Merchant Feed) — tous les jours à 4h
scheduleWithLogging('google:generate-merchant-feed', 'Génération feed RwG Merchant')
    ->dailyAt('04:00')->withoutOverlapping();

// =============================================
// RGPD
// =============================================

// Traiter les demandes RGPD (suppressions après délai de grâce + nettoyage exports)
scheduleWithLogging('gdpr:process-requests', 'Traitement demandes RGPD')
    ->dailyAt('07:00')->withoutOverlapping();

// =============================================
// NETTOYAGE & MAINTENANCE
// =============================================

// Supprimer les invitations expirées — tous les jours à 3h
scheduleWithLogging('invitations:nettoyer', 'Nettoyage invitations')
    ->dailyAt('03:00')->withoutOverlapping();

// Nettoyer les présences obsolètes — toutes les 10 minutes
scheduleWithLogging('presence:cleanup', 'Nettoyage présences')
    ->everyTenMinutes()->withoutOverlapping();

// Nettoyer les anciens logs de tâches planifiées — 1 fois par semaine
Schedule::call(function () {
    try {
        ScheduledTaskLog::cleanup(30);
    } catch (\Throwable $e) {
        Log::warning("ScheduledTaskLog cleanup error: {$e->getMessage()}");
    }
})->weeklyOn(0, '04:00');
