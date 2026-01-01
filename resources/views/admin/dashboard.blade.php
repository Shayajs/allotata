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

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Abonnements actifs</p>
                <p class="text-3xl font-bold text-green-600">{{ number_format($stats['abonnements_actifs']) }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                <span class="text-xl">💳</span>
            </div>
        </div>
        <div class="mt-4">
            <span class="text-xs text-slate-600 dark:text-slate-400">{{ $stats['abonnements_manuels'] }} manuels • {{ $stats['abonnements_stripe'] }} Stripe</span>
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

    <!-- Dernières inscriptions -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 lg:col-span-2">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">👤 Derniers utilisateurs inscrits</h2>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300">Voir tous →</a>
        </div>
        <div class="space-y-3">
            @foreach($derniersUtilisateurs as $user)
                <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-slate-200 dark:bg-slate-600 rounded-full flex items-center justify-center">
                            <span class="text-sm">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                        </div>
                        <div>
                            <p class="font-medium text-slate-900 dark:text-white">{{ $user->name }}</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">{{ $user->email }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="flex gap-1">
                            @if($user->est_client)
                                <span class="px-2 py-0.5 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded">Client</span>
                            @endif
                            @if($user->est_gerant)
                                <span class="px-2 py-0.5 text-xs bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-400 rounded">Gérant</span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $user->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            @endforeach
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
    // Configuration couleurs selon le thème
    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#e2e8f0' : '#1e293b';
    const gridColor = isDark ? '#334155' : '#e2e8f0';

    // Graphique des inscriptions
    const inscriptionsCtx = document.getElementById('inscriptionsChart').getContext('2d');
    new Chart(inscriptionsCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($chartData['inscriptions']['labels']) !!},
            datasets: [{
                label: 'Inscriptions',
                data: {!! json_encode($chartData['inscriptions']['data']) !!},
                borderColor: '#22c55e',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { 
                    grid: { color: gridColor },
                    ticks: { color: textColor, maxRotation: 45, minRotation: 45 }
                },
                y: { 
                    grid: { color: gridColor },
                    ticks: { color: textColor },
                    beginAtZero: true
                }
            }
        }
    });

    // Graphique des réservations
    const reservationsCtx = document.getElementById('reservationsChart').getContext('2d');
    new Chart(reservationsCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($chartData['reservations']['labels']) !!},
            datasets: [{
                label: 'Réservations',
                data: {!! json_encode($chartData['reservations']['data']) !!},
                backgroundColor: 'rgba(168, 85, 247, 0.7)',
                borderColor: '#a855f7',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { 
                    grid: { color: gridColor },
                    ticks: { color: textColor, maxRotation: 45, minRotation: 45 }
                },
                y: { 
                    grid: { color: gridColor },
                    ticks: { color: textColor },
                    beginAtZero: true
                }
            }
        }
    });

    // Graphique des tickets
    const ticketsCtx = document.getElementById('ticketsChart').getContext('2d');
    new Chart(ticketsCtx, {
        type: 'doughnut',
        data: {
            labels: ['Ouverts', 'En cours', 'Résolus', 'Fermés'],
            datasets: [{
                data: {!! json_encode($chartData['tickets']) !!},
                backgroundColor: [
                    'rgba(249, 115, 22, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(148, 163, 184, 0.8)'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: textColor }
                }
            }
        }
    });
</script>
@endpush
