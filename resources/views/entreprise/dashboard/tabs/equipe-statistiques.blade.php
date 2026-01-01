<div>
    <div class="mb-6">
        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Statistiques de {{ $membre->user->name ?? 'Membre' }}</h3>
        <p class="text-slate-600 dark:text-slate-400">Analyse des performances et de la charge de travail</p>
    </div>

    <!-- Stats rapides -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">Cette semaine</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $statsSemaine['nombre_reservations'] }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">réservations</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">Ce mois</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($statsMois['revenu_total'], 0, ',', ' ') }}€</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">revenu total</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">Temps travaillé</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ round($statsMois['duree_totale_minutes'] / 60, 1) }}h</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">ce mois</p>
        </div>
    </div>

    <!-- Graphiques -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
        <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Évolution sur 30 jours</h4>
        <canvas id="statsChart" height="100"></canvas>
    </div>

    <!-- Détails par période -->
    <div class="grid gap-6 md:grid-cols-2">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h4 class="font-semibold text-slate-900 dark:text-white mb-4">Cette semaine</h4>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-slate-600 dark:text-slate-400">Réservations</span>
                    <span class="font-semibold text-slate-900 dark:text-white">{{ $statsSemaine['nombre_reservations'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-600 dark:text-slate-400">Revenu</span>
                    <span class="font-semibold text-green-600 dark:text-green-400">{{ number_format($statsSemaine['revenu_total'], 2, ',', ' ') }}€</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-600 dark:text-slate-400">Temps</span>
                    <span class="font-semibold text-blue-600 dark:text-blue-400">{{ round($statsSemaine['duree_totale_minutes'] / 60, 1) }}h</span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h4 class="font-semibold text-slate-900 dark:text-white mb-4">Ce mois</h4>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-slate-600 dark:text-slate-400">Réservations</span>
                    <span class="font-semibold text-slate-900 dark:text-white">{{ $statsMois['nombre_reservations'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-600 dark:text-slate-400">Revenu</span>
                    <span class="font-semibold text-green-600 dark:text-green-400">{{ number_format($statsMois['revenu_total'], 2, ',', ' ') }}€</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-600 dark:text-slate-400">Temps</span>
                    <span class="font-semibold text-blue-600 dark:text-blue-400">{{ round($statsMois['duree_totale_minutes'] / 60, 1) }}h</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('statsChart');
        if (!ctx) return;

        const statsParJour = @json($statsParJour ?? []);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: statsParJour.map(s => s.date),
                datasets: [
                    {
                        label: 'Réservations',
                        data: statsParJour.map(s => s.reservations),
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Revenu (€)',
                        data: statsParJour.map(s => s.revenu),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        yAxisID: 'y1',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: window.matchMedia('(prefers-color-scheme: dark)').matches ? '#e2e8f0' : '#1e293b'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: window.matchMedia('(prefers-color-scheme: dark)').matches ? '#e2e8f0' : '#1e293b' },
                        grid: { color: window.matchMedia('(prefers-color-scheme: dark)').matches ? '#334155' : '#e2e8f0' }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        ticks: { color: window.matchMedia('(prefers-color-scheme: dark)').matches ? '#e2e8f0' : '#1e293b' },
                        grid: { drawOnChartArea: false }
                    },
                    x: {
                        ticks: { color: window.matchMedia('(prefers-color-scheme: dark)').matches ? '#e2e8f0' : '#1e293b' },
                        grid: { color: window.matchMedia('(prefers-color-scheme: dark)').matches ? '#334155' : '#e2e8f0' }
                    }
                }
            }
        });
    });
</script>
