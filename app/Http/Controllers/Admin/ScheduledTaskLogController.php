<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScheduledTaskLog;
use Illuminate\Http\Request;

class ScheduledTaskLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ScheduledTaskLog::orderBy('created_at', 'desc');

        // Filtre par commande
        if ($request->filled('command')) {
            $query->where('command', $request->command);
        }

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtre par date
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $logs = $query->paginate(50)->withQueryString();

        // Statistiques globales
        $stats = [
            'total' => ScheduledTaskLog::count(),
            'today' => ScheduledTaskLog::today()->count(),
            'success' => ScheduledTaskLog::today()->successful()->count(),
            'errors' => ScheduledTaskLog::today()->failed()->count(),
            'running' => ScheduledTaskLog::running()->count(),
        ];

        // Dernier run par commande
        $commandLabels = ScheduledTaskLog::getCommandLabels();
        $lastRuns = [];
        foreach (array_keys($commandLabels) as $command) {
            $lastRun = ScheduledTaskLog::forCommand($command)->latest()->first();
            $lastRuns[$command] = $lastRun;
        }

        // Commandes distinctes pour le filtre
        $commands = ScheduledTaskLog::select('command')
            ->distinct()
            ->orderBy('command')
            ->pluck('command');

        return view('admin.scheduled-tasks.index', [
            'logs' => $logs,
            'stats' => $stats,
            'lastRuns' => $lastRuns,
            'commandLabels' => $commandLabels,
            'commands' => $commands,
            'filters' => $request->only(['command', 'status', 'date']),
        ]);
    }

    /**
     * Nettoyer les anciens logs
     */
    public function cleanup(Request $request)
    {
        $days = $request->input('days', 30);
        $deleted = ScheduledTaskLog::cleanup($days);

        return back()->with('success', "{$deleted} log(s) de plus de {$days} jours supprimé(s).");
    }
}
