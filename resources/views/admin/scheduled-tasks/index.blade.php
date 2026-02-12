@extends('admin.layout')

@section('title', 'Tâches planifiées')
@section('header', 'Tâches planifiées (CRON)')
@section('subheader', 'Historique et état des tâches automatiques du système')

@section('content')

    {{-- Statistiques du jour --}}
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-sm text-slate-500 dark:text-slate-400">Total logs</div>
            <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-sm text-slate-500 dark:text-slate-400">Aujourd'hui</div>
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['today'] }}</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-sm text-slate-500 dark:text-slate-400">Succès aujourd'hui</div>
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['success'] }}</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-sm text-slate-500 dark:text-slate-400">Erreurs aujourd'hui</div>
            <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['errors'] }}</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-sm text-slate-500 dark:text-slate-400">En cours</div>
            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['running'] }}</div>
        </div>
    </div>

    {{-- État des tâches (dernier run par commande) --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">État des tâches</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($commandLabels as $command => $label)
                @php $lastRun = $lastRuns[$command] ?? null; @endphp
                <div class="border border-slate-200 dark:border-slate-700 rounded-lg p-4 {{ $lastRun && $lastRun->status === 'error' ? 'border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/10' : '' }}">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $label }}</span>
                        @if($lastRun)
                            {!! $lastRun->status_badge !!}
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400">Jamais exécutée</span>
                        @endif
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 font-mono mb-1">{{ $command }}</div>
                    @if($lastRun)
                        <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400 mt-2">
                            <span title="Dernier run">{{ $lastRun->started_at?->diffForHumans() ?? $lastRun->created_at->diffForHumans() }}</span>
                            @if($lastRun->duration_seconds)
                                <span title="Durée">{{ $lastRun->duration_seconds }}s</span>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Filtres --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 mb-6">
        <form method="GET" action="{{ route('admin.scheduled-tasks.index') }}" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Commande</label>
                <select name="command" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                    <option value="">Toutes</option>
                    @foreach($commands as $cmd)
                        <option value="{{ $cmd }}" {{ ($filters['command'] ?? '') === $cmd ? 'selected' : '' }}>
                            {{ $commandLabels[$cmd] ?? $cmd }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[150px]">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Statut</label>
                <select name="status" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                    <option value="">Tous</option>
                    <option value="success" {{ ($filters['status'] ?? '') === 'success' ? 'selected' : '' }}>Succès</option>
                    <option value="error" {{ ($filters['status'] ?? '') === 'error' ? 'selected' : '' }}>Erreur</option>
                    <option value="running" {{ ($filters['status'] ?? '') === 'running' ? 'selected' : '' }}>En cours</option>
                </select>
            </div>
            <div class="min-w-[150px]">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Date</label>
                <input type="date" name="date" value="{{ $filters['date'] ?? '' }}" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium transition-colors">
                    Filtrer
                </button>
                <a href="{{ route('admin.scheduled-tasks.index') }}" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-600 text-sm font-medium transition-colors">
                    Réinitialiser
                </a>
            </div>
        </form>
    </div>

    {{-- Actions --}}
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-slate-500 dark:text-slate-400">
            {{ $logs->total() }} résultat(s)
        </p>
        <form method="POST" action="{{ route('admin.scheduled-tasks.cleanup') }}" onsubmit="return confirm('Supprimer les logs de plus de 30 jours ?')">
            @csrf
            <button type="submit" class="px-3 py-1.5 bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 text-sm font-medium transition-colors">
                Nettoyer (+30 jours)
            </button>
        </form>
    </div>

    {{-- Tableau des logs --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tâche</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Statut</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Durée</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sortie</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors {{ $log->status === 'error' ? 'bg-red-50/50 dark:bg-red-900/5' : '' }}">
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">
                                <div>{{ $log->started_at?->format('d/m/Y') ?? $log->created_at->format('d/m/Y') }}</div>
                                <div class="text-xs text-slate-400">{{ $log->started_at?->format('H:i:s') ?? $log->created_at->format('H:i:s') }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="font-medium text-slate-900 dark:text-white">{{ $log->label }}</div>
                                <div class="text-xs text-slate-400 dark:text-slate-500 font-mono">{{ $log->command }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                {!! $log->status_badge !!}
                                @if($log->exit_code !== null && $log->exit_code !== 0)
                                    <span class="text-xs text-red-500 ml-1">(code: {{ $log->exit_code }})</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">
                                @if($log->duration_seconds)
                                    {{ $log->duration_seconds }}s
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300 max-w-md">
                                @if($log->output)
                                    <details class="cursor-pointer">
                                        <summary class="text-blue-600 dark:text-blue-400 hover:underline text-xs">Voir la sortie</summary>
                                        <pre class="mt-2 p-2 bg-slate-100 dark:bg-slate-900 rounded text-xs overflow-x-auto max-h-40 whitespace-pre-wrap">{{ $log->output }}</pre>
                                    </details>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">
                                Aucun log de tâche planifiée pour le moment.
                                <br>
                                <span class="text-sm">Les logs apparaîtront ici dès que le scheduler Laravel s'exécutera.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

@endsection
