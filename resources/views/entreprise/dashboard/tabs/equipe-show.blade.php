<div class="space-y-6">
    <!-- En-tête avec informations du membre -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'equipe']) }}" class="inline-flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Retour à l'équipe
                </a>
            </div>
        </div>
        <div class="flex items-center gap-4">
            @if($membre->user && $membre->user->photo_profil)
                <img src="{{ asset('storage/' . $membre->user->photo_profil) }}" alt="{{ $membre->user->name }}" class="w-16 h-16 rounded-full object-cover border-2 border-slate-200 dark:border-slate-600">
            @else
                <div class="w-16 h-16 rounded-full bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white font-bold text-2xl">
                    {{ strtoupper(substr($membre->user->name ?? '?', 0, 1)) }}
                </div>
            @endif
            <div>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $membre->user->name ?? 'Membre' }}</h2>
                <div class="flex items-center gap-2 mt-1">
                    <span class="px-3 py-1 text-sm font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 rounded-full capitalize">
                        {{ $membre->role }}
                    </span>
                    @if($membre->user_id == $entreprise->user_id)
                        <span class="px-3 py-1 text-sm font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                            </svg>
                            Propriétaire
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Sous-onglets -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-1 mb-6">
        <nav class="flex gap-2">
            <button 
                onclick="showEquipeSubTab('agenda')"
                class="equipe-subtab flex-1 px-4 py-3 text-sm font-semibold rounded-lg transition-all {{ ($activeSubTab ?? 'agenda') === 'agenda' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}"
                data-subtab="agenda"
            >
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Agenda
            </button>
            <button 
                onclick="showEquipeSubTab('disponibilites')"
                class="equipe-subtab flex-1 px-4 py-3 text-sm font-semibold rounded-lg transition-all {{ ($activeSubTab ?? 'agenda') === 'disponibilites' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}"
                data-subtab="disponibilites"
            >
                ⏰ Disponibilités
            </button>
            <button 
                onclick="showEquipeSubTab('statistiques')"
                class="equipe-subtab flex-1 px-4 py-3 text-sm font-semibold rounded-lg transition-all {{ ($activeSubTab ?? 'agenda') === 'statistiques' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}"
                data-subtab="statistiques"
            >
                📊 Statistiques
            </button>
        </nav>
    </div>

    <!-- Contenu des sous-onglets -->
    <div id="equipe-subtab-agenda" class="equipe-subtab-content {{ ($activeSubTab ?? 'agenda') !== 'agenda' ? 'hidden' : '' }}">
        @include('entreprise.dashboard.tabs.equipe-agenda', ['membre' => $membre, 'entreprise' => $entreprise])
    </div>

    <div id="equipe-subtab-disponibilites" class="equipe-subtab-content {{ ($activeSubTab ?? 'agenda') !== 'disponibilites' ? 'hidden' : '' }}">
        @include('entreprise.dashboard.tabs.equipe-disponibilites', ['membre' => $membre, 'entreprise' => $entreprise])
    </div>

    <div id="equipe-subtab-statistiques" class="equipe-subtab-content {{ ($activeSubTab ?? 'agenda') !== 'statistiques' ? 'hidden' : '' }}">
        @include('entreprise.dashboard.tabs.equipe-statistiques', ['membre' => $membre, 'entreprise' => $entreprise, 'statsMois' => $statsMois ?? [], 'statsSemaine' => $statsSemaine ?? [], 'statsParJour' => $statsParJour ?? []])
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
