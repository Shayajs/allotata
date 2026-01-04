@extends('admin.layout')

@section('title', 'Dashboard')
@section('header', 'Dashboard')
@section('subheader', 'Vue d\'ensemble de la plateforme')

@section('content')
<!-- Alertes prioritaires -->
@if($alertes['entreprises_en_attente'] > 0 || $alertes['tickets_urgents'] > 0 || $alertes['contacts_non_lus'] > 0)
<div class="mb-6 space-y-3">
    @if($alertes['entreprises_en_attente'] > 0)
        <a href="{{ route('admin.entreprises.index') }}?statut=en_attente" class="block p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg hover:bg-orange-100 dark:hover:bg-orange-900/30 transition">
            <div class="flex items-center gap-3">
                <span class="text-2xl">🏢</span>
                <div>
                    <p class="font-semibold text-orange-800 dark:text-orange-300">{{ $alertes['entreprises_en_attente'] }} entreprise(s) en attente de validation</p>
                    <p class="text-sm text-orange-600 dark:text-orange-400">Cliquez pour valider les entreprises</p>
                </div>
            </div>
        </a>
    @endif
    @if($alertes['tickets_urgents'] > 0)
        <a href="{{ route('admin.tickets.index') }}?priorite=urgente&statut=ouvert" class="block p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition">
            <div class="flex items-center gap-3">
                <span class="text-2xl">🚨</span>
                <div>
                    <p class="font-semibold text-red-800 dark:text-red-300">{{ $alertes['tickets_urgents'] }} ticket(s) urgent(s) non traité(s)</p>
                    <p class="text-sm text-red-600 dark:text-red-400">Nécessite une attention immédiate</p>
                </div>
            </div>
        </a>
    @endif
    @if($alertes['contacts_non_lus'] > 0)
        <a href="{{ route('admin.contacts.index') }}?est_lu=0" class="block p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition">
            <div class="flex items-center gap-3">
                <span class="text-2xl">📬</span>
                <div>
                    <p class="font-semibold text-blue-800 dark:text-blue-300">{{ $alertes['contacts_non_lus'] }} message(s) de contact non lu(s)</p>
                    <p class="text-sm text-blue-600 dark:text-blue-400">Cliquez pour consulter les messages</p>
                </div>
            </div>
        </a>
    @endif
</div>
@endif

<!-- Statistiques principales -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Utilisateurs</p>
                <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ number_format($stats['total_users']) }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                <span class="text-xl">👥</span>
            </div>
        </div>
        <div class="mt-4 flex gap-2 text-xs">
            <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded">{{ $stats['total_clients'] }} clients</span>
            <span class="px-2 py-1 bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-400 rounded">{{ $stats['total_gerants'] }} gérants</span>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Entreprises</p>
                <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ number_format($stats['total_entreprises']) }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                <span class="text-xl">🏢</span>
            </div>
        </div>
        <div class="mt-4 flex gap-2 text-xs">
            <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded">{{ $stats['entreprises_verifiees'] }} vérifiées</span>
            <span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded">{{ $stats['entreprises_en_attente'] }} en attente</span>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Réservations</p>
                <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ number_format($stats['total_reservations']) }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                <span class="text-xl">📅</span>
            </div>
        </div>
        <div class="mt-4">
            <span class="text-xs text-slate-600 dark:text-slate-400">{{ $stats['reservations_payees'] }} payées ({{ $stats['total_reservations'] > 0 ? round(($stats['reservations_payees'] / $stats['total_reservations']) * 100) : 0 }}%)</span>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 relative overflow-hidden group">
        <div class="absolute right-0 top-0 w-32 h-32 bg-gradient-to-br from-green-500/10 to-emerald-600/10 rounded-bl-full -mr-8 -mt-8 pointer-events-none transition-transform group-hover:scale-110"></div>
        
        <div class="flex items-center justify-between relative z-10">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">MRR (Revenu Récurrent)</p>
                <p class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-600 to-emerald-600">
                    {{ number_format($stats['mrr'], 0, ',', ' ') }} €
                </p>
            </div>
            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg shadow-green-500/30 flex items-center justify-center text-white transform group-hover:rotate-12 transition-transform">
                <span class="text-xl">💎</span>
            </div>
        </div>
        <div class="mt-4 flex flex-col gap-2 relative z-10">
             <div class="flex justify-between items-end text-xs">
                <span class="text-slate-600 dark:text-slate-400 font-medium">{{ $stats['abonnements_actifs'] }} abonnés actifs</span>
                <span class="text-green-600 dark:text-green-400 font-bold bg-green-50 dark:bg-green-900/40 px-2 py-0.5 rounded-full border border-green-200 dark:border-green-800">
                    {{ number_format($stats['total_entreprises'] > 0 ? ($stats['abonnements_actifs'] / $stats['total_entreprises']) * 100 : 0, 1) }}% conv.
                </span>
             </div>
             <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                <div class="bg-gradient-to-r from-green-500 to-emerald-500 h-1.5 rounded-full shadow-[0_0_10px_rgba(34,197,94,0.5)]" style="width: {{ $stats['total_entreprises'] > 0 ? ($stats['abonnements_actifs'] / $stats['total_entreprises']) * 100 : 0 }}%"></div>
             </div>
             <p class="text-[10px] text-slate-400 leading-tight">
                {{ $stats['abonnements_stripe'] }} Stripe • {{ $stats['abonnements_manuels'] }} Manuels
             </p>
        </div>
    </div>
</div>

<!-- Graphiques -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Inscriptions par jour -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">📈 Nouvelles inscriptions (30 derniers jours)</h2>
        <div class="relative" style="height: 250px;">
            <canvas id="inscriptionsChart"></canvas>
        </div>
    </div>

    <!-- Réservations par jour -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">📅 Réservations (30 derniers jours)</h2>
        <div class="relative" style="height: 250px;">
            <canvas id="reservationsChart"></canvas>
        </div>
    </div>
</div>

<!-- Répartition et activité récente -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Répartition des tickets -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">🎫 Tickets par statut</h2>
        <div class="relative" style="height: 220px;">
            <canvas id="ticketsChart"></canvas>
        </div>
    </div>

    <!-- Flux d'activité (War Room) -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-0 overflow-hidden lg:col-span-2 flex flex-col h-full">
        <div class="p-6 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 flex justify-between items-center">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span class="relative flex h-3 w-3">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
                Activité en Direct
            </h2>
            <span class="text-[10px] font-mono font-bold text-slate-500 bg-slate-200 dark:bg-slate-700 px-2 py-1 rounded border border-slate-300 dark:border-slate-600">LIVE FEED</span>
        </div>
        
        <div class="flex-1 overflow-y-auto p-0 min-h-[300px] max-h-[500px]">
            <div class="divide-y divide-slate-100 dark:divide-slate-700">
                @foreach($activityFeed as $activity)
                    <div class="p-4 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors group relative border-l-4 {{ $activity['type'] == 'reservation' ? 'border-blue-500' : ($activity['type'] == 'finance' ? 'border-yellow-500' : 'border-green-500') }}">
                        <div class="flex gap-4">
                            <!-- Icone -->
                            <div class="flex-shrink-0 mt-0.5">
                                <span class="flex items-center justify-center w-10 h-10 rounded-xl {{ 'bg-'.$activity['color'].'-100 dark:bg-'.$activity['color'].'-900/30 text-'.$activity['color'].'-600 dark:text-'.$activity['color'].'-400' }} border {{ 'border-'.$activity['color'].'-200 dark:border-'.$activity['color'].'-800' }} shadow-sm">
                                    {{ $activity['icon'] }}
                                </span>
                            </div>
                            
                            <!-- Contenu -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-0.5">
                                    <p class="text-sm font-bold text-slate-900 dark:text-white truncate pr-4">
                                        {{ $activity['text'] }}
                                    </p>
                                    <span class="text-xs font-medium text-slate-400 flex-shrink-0 whitespace-nowrap bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full">
                                        {{ $activity['time']->diffForHumans(null, true, true) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between mt-1">
                                    <p class="text-xs text-slate-500 dark:text-slate-400 capitalize">
                                        {{ $activity['time']->translatedFormat('d F H:i') }}
                                    </p>
                                    @if($activity['subtext'])
                                        <span class="inline-block px-2 py-0.5 rounded text-xs font-bold {{ 'bg-'.$activity['color'].'-50 dark:bg-'.$activity['color'].'-900/20 text-'.$activity['color'].'-700 dark:text-'.$activity['color'].'-300' }}">
                                            {{ $activity['subtext'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="p-3 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-100 dark:border-slate-700 text-center">
            <a href="{{ route('admin.index') }}" class="text-xs font-semibold text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 uppercase tracking-wider transition-colors">
                Voir tout l'historique
            </a>
        </div>
    </div>
</div>

<!-- Actions rapides -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <a href="{{ route('admin.users.index') }}" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 hover:border-green-500 dark:hover:border-green-500 transition-all group text-center">
        <span class="text-3xl">👥</span>
        <p class="mt-2 font-medium text-slate-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400">Utilisateurs</p>
    </a>
    <a href="{{ route('admin.entreprises.index') }}" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 hover:border-green-500 dark:hover:border-green-500 transition-all group text-center">
        <span class="text-3xl">🏢</span>
        <p class="mt-2 font-medium text-slate-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400">Entreprises</p>
    </a>
    <a href="{{ route('admin.tickets.index') }}" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 hover:border-green-500 dark:hover:border-green-500 transition-all group text-center">
        <span class="text-3xl">🎫</span>
        <p class="mt-2 font-medium text-slate-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400">Tickets</p>
    </a>
    <a href="{{ route('admin.exports.index') }}" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 hover:border-green-500 dark:hover:border-green-500 transition-all group text-center">
        <span class="text-3xl">📤</span>
        <p class="mt-2 font-medium text-slate-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400">Exports</p>
    </a>
</div>
@endsection

@push('scripts')
<script>
    // Configuration dynamique des couleurs
    function getThemeColors() {
        const isDark = document.documentElement.classList.contains('dark');
        return {
            text: isDark ? '#e2e8f0' : '#1e293b',
            grid: isDark ? '#334155' : '#e2e8f0',
            inscriptionsBg: isDark ? 'rgba(34, 197, 94, 0.2)' : 'rgba(34, 197, 94, 0.1)',
            reservationsBg: isDark ? 'rgba(168, 85, 247, 0.6)' : 'rgba(168, 85, 247, 0.7)'
        };
    }

    let inscriptionsChart = null;
    let reservationsChart = null;
    let ticketsChart = null;

    // Données (injectées par Blade)
    const inscriptionsData = {
        labels: {!! json_encode($chartData['inscriptions']['labels']) !!},
        data: {!! json_encode($chartData['inscriptions']['data']) !!}
    };
    
    const reservationsData = {
        labels: {!! json_encode($chartData['reservations']['labels']) !!},
        data: {!! json_encode($chartData['reservations']['data']) !!}
    };

    const ticketsData = {!! json_encode($chartData['tickets']) !!};

    function initCharts() {
        const colors = getThemeColors();

        // 1. Inscriptions
        const inscriptionsCtx = document.getElementById('inscriptionsChart').getContext('2d');
        if (inscriptionsChart) inscriptionsChart.destroy();
        
        inscriptionsChart = new Chart(inscriptionsCtx, {
            type: 'line',
            data: {
                labels: inscriptionsData.labels,
                datasets: [{
                    label: 'Inscriptions',
                    data: inscriptionsData.data,
                    borderColor: '#22c55e',
                    backgroundColor: colors.inscriptionsBg,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { 
                        grid: { color: colors.grid },
                        ticks: { color: colors.text, maxRotation: 45, minRotation: 45 }
                    },
                    y: { 
                        grid: { color: colors.grid },
                        ticks: { color: colors.text },
                        beginAtZero: true
                    }
                }
            }
        });

        // 2. Réservations
        const reservationsCtx = document.getElementById('reservationsChart').getContext('2d');
        if (reservationsChart) reservationsChart.destroy();

        reservationsChart = new Chart(reservationsCtx, {
            type: 'bar',
            data: {
                labels: reservationsData.labels,
                datasets: [{
                    label: 'Réservations',
                    data: reservationsData.data,
                    backgroundColor: colors.reservationsBg,
                    borderColor: '#a855f7',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { 
                        grid: { color: colors.grid },
                        ticks: { color: colors.text, maxRotation: 45, minRotation: 45 }
                    },
                    y: { 
                        grid: { color: colors.grid },
                        ticks: { color: colors.text },
                        beginAtZero: true
                    }
                }
            }
        });

        // 3. Tickets
        const ticketsCtx = document.getElementById('ticketsChart').getContext('2d');
        if (ticketsChart) ticketsChart.destroy();

        ticketsChart = new Chart(ticketsCtx, {
            type: 'doughnut',
            data: {
                labels: ['Ouverts', 'En cours', 'Résolus', 'Fermés'],
                datasets: [{
                    data: ticketsData,
                    backgroundColor: [
                        'rgba(249, 115, 22, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(148, 163, 184, 0.8)'
                    ],
                    borderColor: document.documentElement.classList.contains('dark') ? '#1e293b' : '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: colors.text }
                    }
                }
            }
        });
    }

    // Initialisation
    initCharts();

    // Observer les changements de thème
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === "class") {
                initCharts();
            }
        });
    });

    observer.observe(document.documentElement, {
        attributes: true, 
        attributeFilter: ['class']
    });
</script>
@endpush
