<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Fidélisation</h2>
    </div>

    <!-- Sous-onglets -->
    <div class="mb-6 border-b border-slate-200 dark:border-slate-700">
        <div class="flex space-x-1 overflow-x-auto">
            <button
                id="fidelisation-subtab-clients-reguliers"
                onclick="showFidelisationSubTab('clients-reguliers')"
                class="fidelisation-subtab-btn px-4 sm:px-6 py-3 text-sm font-medium text-center border-b-2 transition-colors bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 border-green-500 dark:border-green-400 whitespace-nowrap"
            >
                Clients réguliers
            </button>
            <button
                id="fidelisation-subtab-clients-risque"
                onclick="showFidelisationSubTab('clients-risque')"
                class="fidelisation-subtab-btn px-4 sm:px-6 py-3 text-sm font-medium text-center border-b-2 transition-colors text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600 border-transparent whitespace-nowrap"
            >
                Clients à risque
            </button>
            <button
                id="fidelisation-subtab-statistiques"
                onclick="showFidelisationSubTab('statistiques')"
                class="fidelisation-subtab-btn px-4 sm:px-6 py-3 text-sm font-medium text-center border-b-2 transition-colors text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600 border-transparent whitespace-nowrap"
            >
                Statistiques
            </button>
            <button
                id="fidelisation-subtab-actions"
                onclick="showFidelisationSubTab('actions')"
                class="fidelisation-subtab-btn px-4 sm:px-6 py-3 text-sm font-medium text-center border-b-2 transition-colors text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600 border-transparent whitespace-nowrap"
            >
                Actions
            </button>
        </div>
    </div>

    <!-- Contenu sous-onglet Clients réguliers -->
    <div id="fidelisation-subtab-content-clients-reguliers" class="fidelisation-subtab-content">
        <!-- Affichage de la norme quartier -->
        <div class="bg-gradient-to-r from-green-50 to-blue-50 dark:from-green-900/20 dark:to-blue-900/20 border border-green-200 dark:border-green-800 rounded-xl shadow-sm p-6 mb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Norme quartier</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        Basée sur l'analyse des entreprises du même quartier, la norme de régularité est de 
                        <span class="font-bold text-green-600 dark:text-green-400">{{ number_format($fidelisationData['norme_quartier'], 2, ',', ' ') }} visites/mois</span>
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-500 mt-1">
                        Les clients avec une fréquence supérieure ou égale à cette norme sont considérés comme réguliers.
                    </p>
                </div>
            </div>
        </div>

        <!-- Barre de recherche et filtres -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
            <form method="GET" action="{{ route('entreprise.dashboard', $entreprise->slug) }}" class="space-y-4">
                <input type="hidden" name="tab" value="fidelisation">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Rechercher
                        </label>
                        <input 
                            type="text" 
                            name="fidelisation_search" 
                            value="{{ request('fidelisation_search') }}"
                            placeholder="Nom, email..."
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Statut
                        </label>
                        <select 
                            name="fidelisation_statut" 
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                            <option value="">Tous les statuts</option>
                            <option value="regulier" {{ request('fidelisation_statut') === 'regulier' ? 'selected' : '' }}>Régulier</option>
                            <option value="occasionnel" {{ request('fidelisation_statut') === 'occasionnel' ? 'selected' : '' }}>Occasionnel</option>
                            <option value="nouveau" {{ request('fidelisation_statut') === 'nouveau' ? 'selected' : '' }}>Nouveau</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Trier par
                        </label>
                        <select 
                            name="fidelisation_sort" 
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                            <option value="plus_present" {{ request('fidelisation_sort', 'plus_present') === 'plus_present' ? 'selected' : '' }}>Plus présent</option>
                            <option value="moins_present" {{ request('fidelisation_sort') === 'moins_present' ? 'selected' : '' }}>Moins présent</option>
                            <option value="frequence_desc" {{ request('fidelisation_sort') === 'frequence_desc' ? 'selected' : '' }}>Fréquence décroissante</option>
                            <option value="frequence_asc" {{ request('fidelisation_sort') === 'frequence_asc' ? 'selected' : '' }}>Fréquence croissante</option>
                            <option value="derniere_visite_desc" {{ request('fidelisation_sort') === 'derniere_visite_desc' ? 'selected' : '' }}>Dernière visite récente</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                            🔍 Rechercher
                        </button>
                    </div>
                </div>
                @if(request()->hasAny(['fidelisation_search', 'fidelisation_statut', 'fidelisation_sort']))
                    <a href="{{ route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'fidelisation']) }}" class="text-sm text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400">
                        Réinitialiser les filtres
                    </a>
                @endif
            </form>
        </div>

        <!-- Tableau des clients -->
        @if(count($fidelisationData['clients']) > 0)
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 dark:bg-slate-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Client</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Réservations</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Fréquence</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Dernière visite</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                            @foreach($fidelisationData['clients'] as $clientData)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            @if($clientData['client'])
                                                <x-avatar :user="$clientData['client']" size="md" class="flex-shrink-0" />
                                                <div>
                                                    <div class="text-sm font-medium text-slate-900 dark:text-white">
                                                        <x-user-name :user="$clientData['client']" />
                                                    </div>
                                                    <div class="text-sm text-slate-500 dark:text-slate-400">
                                                        {{ $clientData['client']->email }}
                                                    </div>
                                                </div>
                                            @else
                                                <div class="w-10 h-10 rounded-full bg-slate-300 dark:bg-slate-600 flex items-center justify-center text-slate-600 dark:text-slate-300 font-semibold flex-shrink-0">
                                                    ?
                                                </div>
                                                <div class="text-sm font-medium text-slate-900 dark:text-white">
                                                    Client inconnu
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-slate-900 dark:text-white">
                                            {{ $clientData['nb_reservations'] }}
                                        </div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400">
                                            réservation{{ $clientData['nb_reservations'] > 1 ? 's' : '' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-slate-900 dark:text-white">
                                            {{ number_format($clientData['frequence_moyenne'], 2, ',', ' ') }} /mois
                                        </div>
                                        @if($clientData['frequence_moyenne'] >= $fidelisationData['norme_quartier'])
                                            <div class="text-xs text-green-600 dark:text-green-400">
                                                ✓ Au-dessus de la norme
                                            </div>
                                        @elseif($clientData['frequence_moyenne'] >= ($fidelisationData['norme_quartier'] * 0.5))
                                            <div class="text-xs text-yellow-600 dark:text-yellow-400">
                                                ≈ Proche de la norme
                                            </div>
                                        @else
                                            <div class="text-xs text-slate-500 dark:text-slate-400">
                                                En dessous de la norme
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($clientData['derniere_visite'])
                                            <div class="text-sm text-slate-900 dark:text-white">
                                                {{ $clientData['derniere_visite']->format('d/m/Y') }}
                                            </div>
                                            <div class="text-xs text-slate-500 dark:text-slate-400">
                                                {{ $clientData['derniere_visite']->diffForHumans() }}
                                            </div>
                                        @else
                                            <span class="text-sm text-slate-400 dark:text-slate-500">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($clientData['statut'] === 'regulier')
                                            <span class="px-2 py-1 text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full">
                                                Régulier
                                            </span>
                                        @elseif($clientData['statut'] === 'occasionnel')
                                            <span class="px-2 py-1 text-xs font-semibold bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded-full">
                                                Occasionnel
                                            </span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-semibold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 rounded-full">
                                                Nouveau
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
                <svg class="w-16 h-16 mx-auto text-slate-400 dark:text-slate-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Aucun client trouvé</h3>
                <p class="text-slate-600 dark:text-slate-400">
                    @if(request()->hasAny(['fidelisation_search', 'fidelisation_statut']))
                        Aucun client ne correspond à vos critères de recherche.
                    @else
                        Aucun client n'a encore effectué de réservation dans votre entreprise.
                    @endif
                </p>
            </div>
        @endif
    </div>

    <script>
        // Gestion des sous-onglets de fidélisation
        function showFidelisationSubTab(subtabName) {
            // Masquer tous les contenus
            document.querySelectorAll('.fidelisation-subtab-content').forEach(content => {
                content.classList.add('hidden');
            });

            // Réinitialiser tous les boutons
            document.querySelectorAll('.fidelisation-subtab-btn').forEach(button => {
                button.classList.remove('bg-green-100', 'dark:bg-green-900/30', 'text-green-700', 'dark:text-green-400', 'border-green-500', 'dark:border-green-400');
                button.classList.add('text-slate-500', 'dark:text-slate-400', 'border-transparent');
            });

            // Afficher le contenu sélectionné
            const subtabContent = document.getElementById('fidelisation-subtab-content-' + subtabName);
            if (subtabContent) {
                subtabContent.classList.remove('hidden');
            }

            // Activer le bouton sélectionné
            const activeButton = document.getElementById('fidelisation-subtab-' + subtabName);
            if (activeButton) {
                activeButton.classList.remove('text-slate-500', 'dark:text-slate-400', 'border-transparent');
                activeButton.classList.add('bg-green-100', 'dark:bg-green-900/30', 'text-green-700', 'dark:text-green-400', 'border-green-500', 'dark:border-green-400');
            }
        }

        // Initialiser le premier sous-onglet
        document.addEventListener('DOMContentLoaded', function() {
            showFidelisationSubTab('clients-reguliers');
            initStatistiquesCharts();
        });

        // Initialiser les graphiques de statistiques
        function initStatistiquesCharts() {
            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#e2e8f0' : '#1e293b';
            const gridColor = isDark ? '#334155' : '#e2e8f0';

            const statsData = @json($fidelisationStats['stats'] ?? []);

            // Graphique répartition des statuts
            const repartitionCtx = document.getElementById('repartitionStatutsChart');
            if (repartitionCtx && statsData) {
                new Chart(repartitionCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Réguliers', 'Occasionnels', 'Nouveaux'],
                        datasets: [{
                            data: [
                                statsData.clients_reguliers ?? 0,
                                statsData.clients_occasionnels ?? 0,
                                statsData.clients_nouveaux ?? 0
                            ],
                            backgroundColor: [
                                '#22c55e',
                                '#eab308',
                                '#3b82f6'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { color: textColor, padding: 15 }
                            }
                        }
                    }
                });
            }

            // Graphique évolution des clients
            const evolutionCtx = document.getElementById('evolutionClientsChart');
            if (evolutionCtx && statsData.evolution_mois) {
                new Chart(evolutionCtx, {
                    type: 'line',
                    data: {
                        labels: statsData.evolution_mois.map(m => m.mois),
                        datasets: [{
                            label: 'Nouveaux clients',
                            data: statsData.evolution_mois.map(m => m.clients),
                            borderColor: '#22c55e',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { labels: { color: textColor } }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { color: textColor, stepSize: 1 },
                                grid: { color: gridColor }
                            },
                            x: {
                                ticks: { color: textColor },
                                grid: { color: gridColor }
                            }
                        }
                    }
                });
            }
        }

        // Fonctions d'actions
        function envoyerRappel(clientId) {
            if (confirm('Voulez-vous envoyer un rappel à ce client ?')) {
                // TODO: Implémenter l'envoi de rappel
                alert('Fonctionnalité à venir : envoi de rappel personnalisé');
            }
        }

        function envoyerRappelsClientsRisque() {
            const count = {{ count($fidelisationClientsARisque['clients'] ?? []) }};
            if (count === 0) {
                alert('Aucun client à risque pour le moment.');
                return;
            }
            if (confirm(`Voulez-vous envoyer un rappel à ${count} client(s) à risque ?`)) {
                // TODO: Implémenter l'envoi groupé de rappels
                alert('Fonctionnalité à venir : envoi groupé de rappels');
            }
        }

        function envoyerOffreReguliers() {
            const count = {{ $fidelisationStats['stats']['clients_reguliers'] ?? 0 }};
            if (count === 0) {
                alert('Aucun client régulier pour le moment.');
                return;
            }
            if (confirm(`Voulez-vous créer une offre spéciale pour ${count} client(s) régulier(s) ?`)) {
                // TODO: Implémenter la création d'offre spéciale
                alert('Fonctionnalité à venir : création d\'offre spéciale');
            }
        }
    </script>
</div>
