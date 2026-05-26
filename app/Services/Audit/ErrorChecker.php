<?php

namespace App\Services\Audit;

use App\Models\ErrorLog;
use App\Models\SiteAudit;

class ErrorChecker extends BaseChecker
{
    public function key(): string
    {
        return 'errors';
    }

    public function label(): string
    {
        return 'Erreurs';
    }

    public function run(): array
    {
        $lastAudit = SiteAudit::where('status', 'completed')->orderByDesc('id')->first();
        $since = $lastAudit?->completed_at ?? now()->subDays(30);

        $totalErrors = ErrorLog::where('created_at', '>=', $since)->count();
        $criticalErrors = ErrorLog::where('created_at', '>=', $since)->where('level', 'error')->count();
        $warningErrors = ErrorLog::where('created_at', '>=', $since)->where('level', 'warning')->count();

        $repeatedErrors = ErrorLog::where('created_at', '>=', $since)
            ->selectRaw('message, COUNT(*) as occurrences')
            ->groupBy('message')
            ->having('occurrences', '>', 3)
            ->orderByDesc('occurrences')
            ->limit(10)
            ->get();

        $unresolvedErrors = ErrorLog::where('est_vue', false)->count();

        $todayErrors = ErrorLog::whereDate('created_at', today())->count();
        $yesterdayErrors = ErrorLog::whereDate('created_at', today()->subDay())->count();

        $items = [
            ['label' => 'Erreurs depuis le dernier audit', 'value' => $totalErrors, 'severity' => $totalErrors > 50 ? 'critical' : ($totalErrors > 10 ? 'warning' : 'ok')],
            ['label' => 'Erreurs critiques', 'value' => $criticalErrors, 'severity' => $criticalErrors > 10 ? 'critical' : ($criticalErrors > 3 ? 'warning' : 'ok')],
            ['label' => 'Avertissements', 'value' => $warningErrors, 'severity' => $warningErrors > 20 ? 'warning' : 'ok'],
            ['label' => 'Erreurs non consultées', 'value' => $unresolvedErrors, 'severity' => $unresolvedErrors > 20 ? 'critical' : ($unresolvedErrors > 5 ? 'warning' : 'ok')],
            ['label' => "Erreurs aujourd'hui", 'value' => $todayErrors, 'severity' => $todayErrors > 10 ? 'critical' : ($todayErrors > 3 ? 'warning' : 'ok')],
            ['label' => 'Erreurs hier', 'value' => $yesterdayErrors, 'severity' => $yesterdayErrors > 10 ? 'warning' : 'ok'],
            ['label' => 'Erreurs répétées (>3 fois)', 'value' => $repeatedErrors->count(), 'severity' => $repeatedErrors->count() > 5 ? 'critical' : ($repeatedErrors->count() > 0 ? 'warning' : 'ok')],
        ];

        if ($repeatedErrors->isNotEmpty()) {
            foreach ($repeatedErrors->take(5) as $error) {
                $items[] = ['label' => 'Répétée ' . $error->occurrences . 'x', 'value' => \Str::limit($error->message, 80), 'severity' => 'info'];
            }
        }

        $recommendations = [];
        if ($criticalErrors > 10) {
            $recommendations[] = 'Nombre élevé d\'erreurs critiques — investiguer les causes racines.';
        }
        if ($unresolvedErrors > 20) {
            $recommendations[] = 'Beaucoup d\'erreurs non consultées — faire un tri régulier.';
        }
        if ($repeatedErrors->count() > 3) {
            $recommendations[] = 'Plusieurs erreurs se répètent — corriger les plus fréquentes en priorité.';
        }
        if ($todayErrors > $yesterdayErrors * 2 && $todayErrors > 5) {
            $recommendations[] = 'Pic d\'erreurs détecté aujourd\'hui — possible régression.';
        }

        $score = 100;
        $score -= min(30, $criticalErrors * 3);
        $score -= min(20, $warningErrors);
        $score -= min(20, $unresolvedErrors);
        $score -= min(15, $repeatedErrors->count() * 5);
        $score -= min(15, max(0, $todayErrors - 5) * 3);

        return $this->result($score, $items, $recommendations);
    }
}
