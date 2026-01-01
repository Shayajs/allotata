<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">👥 Gestion de l'équipe</h2>
            <p class="text-slate-600 dark:text-slate-400">
                Gérez les membres de votre équipe, leurs disponibilités et leurs performances
            </p>
        </div>
        <button onclick="toggleAddMemberForm()" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition text-sm">
            ➕ Ajouter un membre
        </button>
    </div>

    <!-- Formulaire d'ajout de membre (masqué par défaut) -->
    <div id="add-member-form" class="hidden mb-6 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">➕ Ajouter un membre</h3>
        <form action="{{ route('entreprise.membres.store', $entreprise->slug) }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Email de l'utilisateur
                    </label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email"
                        required
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        placeholder="email@exemple.com"
                    >
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Si l'utilisateur n'existe pas, une invitation sera envoyée par email pour créer un compte.
                    </p>
                </div>
                <div>
                    <label for="role" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Rôle
                    </label>
                    <select 
                        name="role" 
                        id="role"
                        required
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                        <option value="administrateur">Administrateur</option>
                        <option value="membre" selected>Membre</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                    Ajouter le membre
                </button>
                <button type="button" onclick="toggleAddMemberForm()" class="px-6 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 font-semibold rounded-lg transition">
                    Annuler
                </button>
            </div>
        </form>
    </div>

    @if($membresAvecStats->count() > 0)
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($membresAvecStats as $item)
                @php
                    $membre = $item['membre'];
                    $stats = $item['stats'];
                @endphp
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-all">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white font-bold text-lg">
                            {{ strtoupper(substr($membre->user->name ?? '?', 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-slate-900 dark:text-white truncate">{{ $membre->user->name ?? 'Utilisateur' }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 capitalize">{{ $membre->role }}</p>
                        </div>
                    </div>

                    <!-- Stats rapides -->
                    <div class="grid grid-cols-3 gap-3 mb-4">
                        <div class="text-center p-2 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                            <p class="text-lg font-bold text-slate-900 dark:text-white">{{ $stats['reservations_mois'] }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Réservations</p>
                        </div>
                        <div class="text-center p-2 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ number_format($stats['revenu_mois'], 0, ',', ' ') }}€</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Revenu</p>
                        </div>
                        <div class="text-center p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ round($stats['duree_totale'] / 60, 1) }}h</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Temps</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2">
                        <a href="{{ route('entreprise.equipe.show', [$entreprise->slug, $membre]) }}" class="flex-1 px-3 py-2 text-sm bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-lg transition text-center">
                            Voir détails
                        </a>
                        <a href="{{ route('entreprise.equipe.show', [$entreprise->slug, $membre]) }}?tab=agenda" class="px-3 py-2 text-sm bg-green-100 dark:bg-green-900/20 hover:bg-green-200 dark:hover:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg transition">
                            📅
                        </a>
                        <a href="{{ route('entreprise.equipe.statistiques', [$entreprise->slug, $membre]) }}" class="px-3 py-2 text-sm bg-blue-100 dark:bg-blue-900/20 hover:bg-blue-200 dark:hover:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg transition">
                            📊
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Aucun membre</h3>
            <p class="text-slate-600 dark:text-slate-400 mb-6">
                Ajoutez des membres à votre équipe pour commencer à gérer leurs disponibilités et leurs performances.
            </p>
            <button onclick="toggleAddMemberForm()" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Ajouter un membre
            </button>
        </div>
    @endif
</div>

<script>
    function toggleAddMemberForm() {
        const form = document.getElementById('add-member-form');
        if (form) {
            form.classList.toggle('hidden');
            if (!form.classList.contains('hidden')) {
                form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                document.getElementById('email')?.focus();
            }
        }
    }
</script>
