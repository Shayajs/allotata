<?php

namespace App\Services\Audit;

use App\Models\ScheduledTaskLog;

class ScheduledTaskChecker extends BaseChecker
{
    public function key(): string
    {
        return 'scheduled_tasks';
    }

    public function label(): string
    {
        return 'Tâches planifiées';
    }

    public function run(): array
    {
        $items = [];
        $recommendations = [];
        $score = 100;

        // Tâches en erreur (7 derniers jours)
        $failedTasks = ScheduledTaskLog::where('created_at', '>=', now()->subDays(7))
            ->where('status', 'error')
            ->count();
        $totalTasks = ScheduledTaskLog::where('created_at', '>=', now()->subDays(7))->count();
        $failRate = $totalTasks > 0 ? round(($failedTasks / $totalTasks) * 100, 1) : 0;

        $items[] = ['label' => 'Exécutions (7j)', 'value' => $totalTasks, 'severity' => $totalTasks > 0 ? 'ok' : 'critical'];
        $items[] = ['label' => 'Échecs (7j)', 'value' => $failedTasks, 'severity' => $failedTasks > 5 ? 'critical' : ($failedTasks > 2 ? 'warning' : 'ok')];
        $items[] = ['label' => 'Taux d\'échec', 'value' => $failRate . '%', 'severity' => $failRate > 10 ? 'critical' : ($failRate > 5 ? 'warning' : 'ok')];

        $score -= min(25, $failedTasks * 3);

        if ($totalTasks === 0) {
            $score -= 30;
            $recommendations[] = 'Aucune tâche planifiée exécutée cette semaine — vérifier le cron/scheduler.';
        }

        // Tâches actuellement en cours (potentiellement bloquées)
        $runningTasks = ScheduledTaskLog::where('status', 'running')
            ->where('created_at', '<', now()->subHours(1))
            ->get();
        $items[] = ['label' => 'Tâches bloquées (>1h)', 'value' => $runningTasks->count(), 'severity' => $runningTasks->count() > 0 ? 'critical' : 'ok'];
        if ($runningTasks->count() > 0) {
            $score -= 15;
            $recommendations[] = 'Des tâches sont marquées "en cours" depuis plus d\'1h — probablement bloquées.';
        }

        // Dernière exécution par commande
        $commands = ScheduledTaskLog::getCommandLabels();
        $missingCommands = [];

        foreach ($commands as $command => $label) {
            $lastRun = ScheduledTaskLog::where('command', $command)
                ->orderByDesc('created_at')
                ->first();

            if (!$lastRun) {
                $missingCommands[] = $label;
            } elseif ($lastRun->created_at->lt(now()->subDays(3))) {
                $items[] = [
                    'label' => $label,
                    'value' => 'Dernière exécution: ' . $lastRun->created_at->diffForHumans(),
                    'severity' => 'warning',
                ];
            }
        }

        if (!empty($missingCommands)) {
            $items[] = ['label' => 'Commandes jamais exécutées', 'value' => implode(', ', $missingCommands), 'severity' => 'warning'];
            $score -= min(15, count($missingCommands) * 3);
            $recommendations[] = count($missingCommands) . ' commande(s) planifiée(s) n\'ont jamais été exécutée(s).';
        }

        // Commandes en échec récurrentes
        $recurringFails = ScheduledTaskLog::where('created_at', '>=', now()->subDays(7))
            ->where('status', 'error')
            ->selectRaw('command, COUNT(*) as count')
            ->groupBy('command')
            ->having('count', '>', 2)
            ->get();

        foreach ($recurringFails as $fail) {
            $label = $commands[$fail->command] ?? $fail->command;
            $items[] = ['label' => "Échecs récurrents: {$label}", 'value' => $fail->count . ' fois', 'severity' => 'critical'];
        }

        if ($recurringFails->isNotEmpty()) {
            $recommendations[] = 'Certaines commandes échouent de manière récurrente — investiguer.';
        }

        return $this->result($score, $items, $recommendations);
    }
}
