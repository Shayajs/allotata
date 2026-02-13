{{-- Onglet Emploi du temps — Vue fusionnée multi-entreprises --}}
<div>
    {{-- Légende des entreprises --}}
    @if($entreprises->count() > 1)
        <div class="mb-4 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex flex-wrap items-center gap-4">
                <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Entreprises :</span>
                @php
                    $palette = ['#22c55e', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'];
                @endphp
                @foreach($entreprises->filter(fn($e) => !$e->trashed())->values() as $i => $ent)
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $palette[$i % count($palette)] }}"></span>
                        <span class="text-sm text-slate-600 dark:text-slate-400">{{ $ent->nom }}</span>
                    </div>
                @endforeach
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full flex-shrink-0 bg-indigo-500"></span>
                    <span class="text-sm text-slate-600 dark:text-slate-400 italic">Google Calendar</span>
                </div>
            </div>
        </div>
    @endif

    {{-- Conteneur du calendrier --}}
    <div id="dashboard-emploi-du-temps-calendar"></div>
</div>

<script>
(function() {
    const eventsEndpoint = '{{ route("dashboard.emploi-du-temps.events") }}';
    let dashboardCalendarInstance = null;

    function initDashboardEmploiDuTemps() {
        if (dashboardCalendarInstance) return;
        if (typeof EmploiDuTemps === 'undefined') {
            setTimeout(initDashboardEmploiDuTemps, 200);
            return;
        }

        dashboardCalendarInstance = new EmploiDuTemps('dashboard-emploi-du-temps-calendar', {
            endpoint: eventsEndpoint,
            view: 'week',
            googleConnected: true,
            showGoogleBanner: false,
            onEventClick: function(event) {
                // Pour le dashboard user, on redirige vers la réservation
                if (event.meta && event.meta.hash) {
                    window.open('/r/' + event.meta.hash, '_blank');
                }
            },
            onBlockClick: function(event) {
                // Pas de blocage depuis le dashboard user, juste info
                alert('Événement : ' + (event.title || 'Sans titre') + '\nEntreprise : ' + (event.entreprise_name || '-'));
            }
        });
    }

    // Exposer pour le système de tabs
    window.initDashboardEmploiDuTemps = initDashboardEmploiDuTemps;

    // Si l'onglet est déjà actif au chargement, initialiser
    const activeTab = new URLSearchParams(window.location.search).get('tab');
    if (activeTab === 'emploi-du-temps') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initDashboardEmploiDuTemps, 300);
        });
    }
})();
</script>
