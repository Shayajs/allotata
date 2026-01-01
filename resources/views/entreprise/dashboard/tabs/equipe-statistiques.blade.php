<div class="space-y-6">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Statistiques de {{ $membre->user->name ?? 'Membre' }}</h3>
        <p class="text-slate-600 dark:text-slate-400">Analyse des performances et de la charge de travail</p>
    </div>

    <!-- Stats rapides -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-700/50 dark:to-slate-800/50 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md transition">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-12 h-12 bg-slate-200 dark:bg-slate-700 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-600 dark:text-slate-400 uppercase tracking-wide">Cette semaine</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ $statsSemaine['nombre_reservations'] ?? 0 }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">réservations</p>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-xl shadow-sm border border-green-200 dark:border-green-800 p-6 hover:shadow-md transition">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-green-700 dark:text-green-400 uppercase tracking-wide">Ce mois</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ number_format($statsMois['revenu_total'] ?? 0, 0, ',', ' ') }}€</p>
                    <p class="text-xs text-green-600 dark:text-green-400 mt-1">revenu total</p>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl shadow-sm border border-blue-200 dark:border-blue-800 p-6 hover:shadow-md transition">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-blue-700 dark:text-blue-400 uppercase tracking-wide">Temps travaillé</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ round(($statsMois['duree_totale_minutes'] ?? 0) / 60, 1) }}h</p>
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">ce mois</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-6">📈 Évolution sur 30 jours</h4>
        <div style="height: 300px;">
            <canvas id="statsChart"></canvas>
        </div>
    </div>

    <!-- Détails par période -->
    <div class="grid gap-6 md:grid-cols-2">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h4 class="font-semibold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Cette semaine
            </h4>
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                    <span class="text-slate-600 dark:text-slate-400 font-medium">Réservations</span>
                    <span class="font-bold text-lg text-slate-900 dark:text-white">{{ $statsSemaine['nombre_reservations'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <span class="text-slate-600 dark:text-slate-400 font-medium">Revenu</span>
                    <span class="font-bold text-lg text-green-600 dark:text-green-400">{{ number_format($statsSemaine['revenu_total'] ?? 0, 2, ',', ' ') }}€</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <span class="text-slate-600 dark:text-slate-400 font-medium">Temps</span>
                    <span class="font-bold text-lg text-blue-600 dark:text-blue-400">{{ round(($statsSemaine['duree_totale_minutes'] ?? 0) / 60, 1) }}h</span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h4 class="font-semibold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Ce mois
            </h4>
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                    <span class="text-slate-600 dark:text-slate-400 font-medium">Réservations</span>
                    <span class="font-bold text-lg text-slate-900 dark:text-white">{{ $statsMois['nombre_reservations'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <span class="text-slate-600 dark:text-slate-400 font-medium">Revenu</span>
                    <span class="font-bold text-lg text-green-600 dark:text-green-400">{{ number_format($statsMois['revenu_total'] ?? 0, 2, ',', ' ') }}€</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <span class="text-slate-600 dark:text-slate-400 font-medium">Temps</span>
                    <span class="font-bold text-lg text-blue-600 dark:text-blue-400">{{ round(($statsMois['duree_totale_minutes'] ?? 0) / 60, 1) }}h</span>
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
        const isDark = document.documentElement.classList.contains('dark');
        const textColor = isDark ? '#e2e8f0' : '#1e293b';
        const gridColor = isDark ? '#334155' : '#e2e8f0';

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: statsParJour.map(s => {
                    const date = new Date(s.date);
                    return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' });
                }),
                datasets: [
                    {
                        label: 'Réservations',
                        data: statsParJour.map(s => s.reservations),
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Revenu (€)',
                        data: statsParJour.map(s => s.revenu),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        yAxisID: 'y1',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: textColor,
                            font: {
                                size: 12,
                                weight: '500'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: isDark ? 'rgba(30, 41, 59, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                        titleColor: textColor,
                        bodyColor: textColor,
                        borderColor: isDark ? '#475569' : '#cbd5e1',
                        borderWidth: 1
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { 
                            color: textColor,
                            font: { size: 11 }
                        },
                        grid: { 
                            color: gridColor,
                            drawBorder: false
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        ticks: { 
                            color: textColor,
                            font: { size: 11 }
                        },
                        grid: { 
                            drawOnChartArea: false,
                            drawBorder: false
                        }
                    },
                    x: {
                        ticks: { 
                            color: textColor,
                            font: { size: 11 }
                        },
                        grid: { 
                            color: gridColor,
                            drawBorder: false
                        }
                    }
                }
            }
        });
    });
</script>
