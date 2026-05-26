<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\SiteAudit;
use App\Services\Audit\BackupChecker;
use App\Services\Audit\ContactChecker;
use App\Services\Audit\DatabaseChecker;
use App\Services\Audit\EmailChecker;
use App\Services\Audit\ErrorChecker;
use App\Services\Audit\GdprChecker;
use App\Services\Audit\PerformanceChecker;
use App\Services\Audit\QueueChecker;
use App\Services\Audit\RouteChecker;
use App\Services\Audit\ScheduledTaskChecker;
use App\Services\Audit\SecurityChecker;
use App\Services\Audit\SmsChecker;
use App\Services\Audit\StorageChecker;
use App\Services\Audit\StripeChecker;
use App\Services\Audit\SubscriptionChecker;
use App\Services\Audit\UserActivityChecker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunSiteAudit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 1;

    public function __construct(
        private int $auditId
    ) {}

    public function handle(): void
    {
        $audit = SiteAudit::find($this->auditId);

        if (!$audit) {
            return;
        }

        $audit->update(['status' => 'running', 'started_at' => now()]);

        $checkers = [
            new ErrorChecker(),
            new SecurityChecker(),
            new StripeChecker(),
            new ContactChecker(),
            new PerformanceChecker(),
            new EmailChecker(),
            new SmsChecker(),
            new BackupChecker(),
            new ScheduledTaskChecker(),
            new GdprChecker(),
            new SubscriptionChecker(),
            new DatabaseChecker(),
            new StorageChecker(),
            new QueueChecker(),
            new RouteChecker(),
            new UserActivityChecker(),
        ];

        $resultats = [];
        $resume = [];

        foreach ($checkers as $checker) {
            try {
                $result = $checker->run();
                $resultats[$checker->key()] = $result;
                $resume[$checker->key()] = [
                    'label' => $result['label'],
                    'score' => $result['score'],
                    'status' => $result['status'],
                    'items_count' => count($result['items']),
                    'recommendations_count' => count($result['recommendations']),
                ];
            } catch (\Throwable $e) {
                Log::error("Audit checker {$checker->key()} failed", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $resultats[$checker->key()] = [
                    'key' => $checker->key(),
                    'label' => $checker->label(),
                    'score' => 0,
                    'status' => 'error',
                    'items' => [['label' => 'Erreur d\'exécution', 'value' => $e->getMessage(), 'severity' => 'critical']],
                    'recommendations' => ['Ce checker a échoué — vérifier les logs.'],
                ];
                $resume[$checker->key()] = [
                    'label' => $checker->label(),
                    'score' => 0,
                    'status' => 'error',
                    'items_count' => 1,
                    'recommendations_count' => 1,
                ];
            }
        }

        $noteGlobale = $this->calculateGlobalScore($resume);

        $audit->update([
            'status' => 'completed',
            'note_globale' => $noteGlobale,
            'resultats' => $resultats,
            'resume' => $resume,
            'completed_at' => now(),
            'duration_seconds' => now()->diffInSeconds($audit->started_at),
        ]);

        Notification::creer(
            $audit->user_id,
            'audit',
            'Audit du site terminé',
            "Note globale : {$noteGlobale}/100",
            '/admin/audits/' . $audit->id,
            ['audit_id' => $audit->id, 'note' => $noteGlobale]
        );
    }

    private function calculateGlobalScore(array $resume): int
    {
        $weights = [
            'security' => 2.0,
            'errors' => 1.5,
            'stripe' => 1.5,
            'backups' => 1.5,
            'subscriptions' => 1.2,
            'scheduled_tasks' => 1.2,
            'contacts' => 1.0,
            'performance' => 1.0,
            'emails' => 1.0,
            'sms' => 1.0,
            'gdpr' => 1.2,
            'database' => 1.0,
            'storage' => 1.0,
            'queues' => 1.0,
            'routes' => 1.0,
            'users' => 0.8,
        ];

        $totalWeight = 0;
        $weightedSum = 0;

        foreach ($resume as $key => $data) {
            $weight = $weights[$key] ?? 1.0;
            $weightedSum += $data['score'] * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? (int) round($weightedSum / $totalWeight) : 0;
    }

    public function failed(\Throwable $exception): void
    {
        $audit = SiteAudit::find($this->auditId);

        if ($audit) {
            $audit->update([
                'status' => 'failed',
                'completed_at' => now(),
                'duration_seconds' => $audit->started_at ? now()->diffInSeconds($audit->started_at) : 0,
            ]);

            Notification::creer(
                $audit->user_id,
                'audit',
                'Audit échoué',
                'L\'audit du site a rencontré une erreur fatale.',
                '/admin/audits/' . $audit->id,
                ['audit_id' => $audit->id, 'error' => $exception->getMessage()]
            );
        }
    }
}
