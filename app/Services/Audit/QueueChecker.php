<?php

namespace App\Services\Audit;

use Illuminate\Support\Facades\DB;

class QueueChecker extends BaseChecker
{
    public function key(): string
    {
        return 'queues';
    }

    public function label(): string
    {
        return 'Files d\'attente (Queues)';
    }

    public function run(): array
    {
        $items = [];
        $recommendations = [];
        $score = 100;

        // Jobs en attente
        $pendingJobs = DB::table('jobs')->count();
        $items[] = ['label' => 'Jobs en attente', 'value' => $pendingJobs, 'severity' => $pendingJobs > 100 ? 'critical' : ($pendingJobs > 20 ? 'warning' : 'ok')];
        if ($pendingJobs > 100) {
            $score -= 20;
            $recommendations[] = "Beaucoup de jobs en attente ({$pendingJobs}) — vérifier que le worker est actif.";
        }

        // Jobs échoués
        $failedJobs = DB::table('failed_jobs')->count();
        $items[] = ['label' => 'Jobs échoués', 'value' => $failedJobs, 'severity' => $failedJobs > 10 ? 'critical' : ($failedJobs > 3 ? 'warning' : 'ok')];
        $score -= min(20, $failedJobs * 2);

        if ($failedJobs > 0) {
            // Jobs échoués récents
            $recentFailed = DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subDay())
                ->count();
            $items[] = ['label' => 'Échecs dernières 24h', 'value' => $recentFailed, 'severity' => $recentFailed > 5 ? 'critical' : ($recentFailed > 0 ? 'warning' : 'ok')];

            // Types de jobs en échec
            $failedTypes = DB::table('failed_jobs')
                ->selectRaw("payload, COUNT(*) as count")
                ->groupBy('payload')
                ->orderByDesc('count')
                ->limit(3)
                ->get();

            foreach ($failedTypes as $type) {
                $payload = json_decode($type->payload, true);
                $jobName = $payload['displayName'] ?? 'Inconnu';
                $items[] = ['label' => "Échec: {$jobName}", 'value' => $type->count . ' fois', 'severity' => 'info'];
            }
        }

        // Jobs anciens non traités (>1h en queue)
        $oldJobs = DB::table('jobs')
            ->where('created_at', '<', now()->subHour()->timestamp)
            ->count();
        $items[] = ['label' => 'Jobs en attente >1h', 'value' => $oldJobs, 'severity' => $oldJobs > 5 ? 'critical' : ($oldJobs > 0 ? 'warning' : 'ok')];
        if ($oldJobs > 5) {
            $score -= 15;
            $recommendations[] = 'Des jobs attendent depuis plus d\'une heure — le worker est-il en marche ?';
        }

        // Driver de queue
        $queueDriver = config('queue.default');
        $items[] = ['label' => 'Driver de queue', 'value' => $queueDriver, 'severity' => $queueDriver === 'sync' ? 'warning' : 'ok'];
        if ($queueDriver === 'sync') {
            $score -= 10;
            $recommendations[] = 'La queue est en mode synchrone — les jobs bloquent les requêtes.';
        }

        if ($failedJobs > 10) {
            $recommendations[] = "Purger les anciens jobs échoués : php artisan queue:flush.";
        }

        return $this->result($score, $items, $recommendations);
    }
}
