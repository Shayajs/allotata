<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                Gestion de l'équipe
                <x-course-link-badge page-key="entreprise.equipe" :course-links="$courseLinks ?? []" />
            </h2>
            <p class="text-slate-600 dark:text-slate-400">
                Gérez les membres de votre équipe, leurs disponibilités et leurs performances
            </p>
        </div>
        <button onclick="toggleAddMemberForm()" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition text-sm">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Ajouter un membre
        </button>
    </div>

    <!-- Formulaire d'ajout de membre (masqué par défaut) -->
    <div id="add-member-form" class="hidden mb-6 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Ajouter un membre
        </h3>
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

    <!-- Invitations en cours -->
    @if($invitationsEnCours && $invitationsEnCours->count() > 0)
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                Invitations en cours
            </h3>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach($invitationsEnCours as $invitation)
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-slate-900 dark:text-white truncate">{{ $invitation->email }}</p>
                                <p class="text-xs text-slate-600 dark:text-slate-400 capitalize mt-1">
                                    Rôle : {{ $invitation->role }}
                                </p>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded-full whitespace-nowrap ml-2">
                                @if($invitation->estEnAttenteCompte())
                                    <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    En attente de compte
                                @elseif($invitation->estEnAttenteAcceptation())
                                    <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    En attente d'acceptation
                                @endif
                            </span>
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                            <p>Envoyée le {{ $invitation->created_at->format('d/m/Y à H:i') }}</p>
                            @if($invitation->expire_at)
                                <p>Expire le {{ $invitation->expire_at->format('d/m/Y') }}</p>
                            @endif
                            @if($invitation->invitePar)
                                <p>Par {{ $invitation->invitePar->name }}</p>
                            @endif
                        </div>
                        @if($invitation->estExpiree())
                            <div class="mt-2 p-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded text-xs text-red-800 dark:text-red-300">
                                <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                Cette invitation a expiré
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($membresAvecStats->count() > 0)
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">👥 Membres actifs</h3>
        </div>
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($membresAvecStats as $item)
                @php
                    $membre = $item['membre'];
                    $stats = $item['stats'];
                @endphp
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-all">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="relative" data-user-id="{{ $membre->user->id ?? null }}">
                            @if($membre->user && $membre->user->photo_profil)
                                <img src="{{ asset('storage/' . $membre->user->photo_profil) }}" alt="{{ $membre->user->name }}" class="w-12 h-12 rounded-full object-cover border-2 border-slate-200 dark:border-slate-600">
                            @else
                                <div class="w-12 h-12 rounded-full bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white font-bold text-lg">
                                    {{ strtoupper(substr($membre->user->name ?? '?', 0, 1)) }}
                                </div>
                            @endif
                            @if($membre->user)
                                <div class="absolute bottom-0 right-0">
                                    <x-presence-badge :user="$membre->user" size="sm" />
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="font-semibold text-slate-900 dark:text-white truncate">{{ $membre->user->name ?? 'Utilisateur' }}</h3>
                                @if($membre->id == 0 || ($membre->user_id == $entreprise->user_id && $membre->id != 0))
                                    <span class="px-2 py-0.5 text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full">
                                        👑 Propriétaire
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 capitalize">
                                {{ $membre->role }}
                                @if(config('app.debug'))
                                    <span class="text-slate-400">(ID: {{ $membre->id }}, User ID: {{ $membre->user_id }})</span>
                                @endif
                            </p>
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
                        @php
                            // Utiliser 'gerant' uniquement si c'est un membre virtuel (id == 0)
                            // Sinon, utiliser l'ID réel du membre même s'il est propriétaire
                            $membreId = ($membre->id == 0) ? 'gerant' : $membre->id;
                        @endphp
                        <a href="{{ route('entreprise.equipe.show', [$entreprise->slug, $membreId]) }}" class="flex-1 px-3 py-2 text-sm bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-lg transition text-center">
                            Voir détails
                        </a>
                        <a href="{{ route('entreprise.equipe.show', [$entreprise->slug, $membreId]) }}?tab=agenda" class="px-3 py-2 text-sm bg-green-100 dark:bg-green-900/20 hover:bg-green-200 dark:hover:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg transition">
                            📅
                        </a>
                        <a href="{{ route('entreprise.equipe.statistiques', [$entreprise->slug, $membreId]) }}" class="px-3 py-2 text-sm bg-blue-100 dark:bg-blue-900/20 hover:bg-blue-200 dark:hover:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg transition">
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
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">
                @if($invitationsEnCours && $invitationsEnCours->count() > 0)
                    Aucun membre actif
                @else
                    Aucun membre
                @endif
            </h3>
            <p class="text-slate-600 dark:text-slate-400 mb-6">
                @if($invitationsEnCours && $invitationsEnCours->count() > 0)
                    Vous avez des invitations en attente ci-dessus. Les membres apparaîtront ici une fois qu'ils auront accepté leur invitation.
                @else
                    Ajoutez des membres à votre équipe pour commencer à gérer leurs disponibilités et leurs performances.
                @endif
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
