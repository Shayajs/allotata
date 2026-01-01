<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="{{ route('entreprise.equipe.index', $entreprise->slug) }}" class="inline-flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 transition mb-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Retour à l'équipe
            </a>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $membre->user->name ?? 'Membre' }}</h2>
            <p class="text-slate-600 dark:text-slate-400 capitalize">{{ $membre->role }}</p>
        </div>
    </div>

    <!-- Sous-onglets -->
    <div class="border-b border-slate-200 dark:border-slate-700 mb-6">
        <nav class="flex gap-4">
            <button 
                onclick="showEquipeSubTab('agenda')"
                class="equipe-subtab px-4 py-2 text-sm font-medium border-b-2 {{ ($activeSubTab ?? 'agenda') === 'agenda' ? 'border-green-500 text-green-600 dark:text-green-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' }}"
                data-subtab="agenda"
            >
                📅 Agenda
            </button>
            <button 
                onclick="showEquipeSubTab('disponibilites')"
                class="equipe-subtab px-4 py-2 text-sm font-medium border-b-2 {{ ($activeSubTab ?? 'agenda') === 'disponibilites' ? 'border-green-500 text-green-600 dark:text-green-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' }}"
                data-subtab="disponibilites"
            >
                ⏰ Disponibilités
            </button>
            <button 
                onclick="showEquipeSubTab('statistiques')"
                class="equipe-subtab px-4 py-2 text-sm font-medium border-b-2 {{ ($activeSubTab ?? 'agenda') === 'statistiques' ? 'border-green-500 text-green-600 dark:text-green-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' }}"
                data-subtab="statistiques"
            >
                📊 Statistiques
            </button>
        </nav>
    </div>

    <!-- Contenu des sous-onglets -->
    <div id="equipe-subtab-agenda" class="equipe-subtab-content {{ ($activeSubTab ?? 'agenda') !== 'agenda' ? 'hidden' : '' }}">
        @include('entreprise.dashboard.tabs.equipe-agenda', ['membre' => $membre])
    </div>

    <div id="equipe-subtab-disponibilites" class="equipe-subtab-content {{ ($activeSubTab ?? 'agenda') !== 'disponibilites' ? 'hidden' : '' }}">
        @include('entreprise.dashboard.tabs.equipe-disponibilites', ['membre' => $membre])
    </div>

    <div id="equipe-subtab-statistiques" class="equipe-subtab-content {{ ($activeSubTab ?? 'agenda') !== 'statistiques' ? 'hidden' : '' }}">
        @include('entreprise.dashboard.tabs.equipe-statistiques', ['membre' => $membre, 'statsMois' => $statsMois ?? [], 'statsSemaine' => $statsSemaine ?? [], 'statsParJour' => $statsParJour ?? []])
    </div>

    <script>
        function showEquipeSubTab(tabName) {
            // Masquer tous les contenus
            document.querySelectorAll('.equipe-subtab-content').forEach(content => {
                content.classList.add('hidden');
            });

            // Désactiver tous les boutons
            document.querySelectorAll('.equipe-subtab').forEach(button => {
                button.classList.remove('border-green-500', 'text-green-600', 'dark:text-green-400');
                button.classList.add('border-transparent', 'text-slate-500', 'dark:text-slate-400');
            });

            // Afficher le contenu sélectionné
            document.getElementById('equipe-subtab-' + tabName).classList.remove('hidden');

            // Activer le bouton sélectionné
            const activeButton = document.querySelector(`[data-subtab="${tabName}"]`);
            activeButton.classList.remove('border-transparent', 'text-slate-500', 'dark:text-slate-400');
            activeButton.classList.add('border-green-500', 'text-green-600', 'dark:text-green-400');
        }
    </script>
</div>
