{{-- Onglet Accueil - Résumé global --}}
<div>
    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Vue d'ensemble</h2>

    <!-- Statut des rôles -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-slate-900 dark:text-white">Statut Client</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        @if($user->est_client)
                            ✓ Vous pouvez effectuer des achats
                        @else
                            ✗ Client désactivé
                        @endif
                    </p>
                </div>
            </div>
        </div>
        <div class="p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-slate-900 dark:text-white">Statut Gérant</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        @if($user->est_gerant)
                            ✓ Vous gérez {{ $entreprises->count() }} entreprise(s)
                        @else
                            ✗ Aucune entreprise pour le moment
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques globales (pour les gérants) -->
    @if($user->est_gerant && $stats)
        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-6 mb-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">📊 Statistiques globales</h3>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                <div class="p-4 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Total réservations</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total_reservations'] }}</p>
                </div>
                <div class="p-4 bg-white dark:bg-slate-800 rounded-lg border border-green-200 dark:border-green-800">
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Revenu total</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($stats['revenu_total'], 2, ',', ' ') }} €</p>
                </div>
                <div class="p-4 bg-white dark:bg-slate-800 rounded-lg border border-blue-200 dark:border-blue-800">
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Revenu payé</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($stats['revenu_paye'], 2, ',', ' ') }} €</p>
                </div>
                <div class="p-4 bg-white dark:bg-slate-800 rounded-lg border border-orange-200 dark:border-orange-800">
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Ce mois</p>
                    <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($stats['revenu_ce_mois'], 2, ',', ' ') }} €</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $stats['reservations_ce_mois'] }} réservation(s)</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-center">
                    <p class="text-sm text-slate-600 dark:text-slate-400">Confirmées</p>
                    <p class="text-xl font-bold text-slate-900 dark:text-white">{{ $stats['reservations_confirmees'] }}</p>
                </div>
                <div class="p-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-center">
                    <p class="text-sm text-slate-600 dark:text-slate-400">En attente</p>
                    <p class="text-xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['reservations_en_attente'] }}</p>
                </div>
                <div class="p-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-center">
                    <p class="text-sm text-slate-600 dark:text-slate-400">Terminées</p>
                    <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['reservations_terminees'] }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Réservations en attente (pour les gérants) -->
    @if($user->est_gerant && $reservationsEnAttente->count() > 0)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border-2 border-yellow-500 dark:border-yellow-600 rounded-xl p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1 flex items-center gap-2">
                        <span class="text-2xl">⚠️</span> Réservations en attente
                    </h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm">
                        {{ $reservationsEnAttente->count() }} réservation(s) nécessitent votre validation
                    </p>
                </div>
                <button onclick="showTab('reservations')" class="px-4 py-2 text-sm bg-yellow-600 hover:bg-yellow-700 text-white font-semibold rounded-lg transition">
                    Voir tout →
                </button>
            </div>
            <div class="space-y-2">
                @foreach($reservationsEnAttente->take(3) as $reservation)
                    <div class="p-3 bg-white dark:bg-slate-800 rounded-lg border border-yellow-200 dark:border-yellow-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <x-avatar :user="$reservation->user" size="sm" />
                            <div>
                                <p class="font-medium text-slate-900 dark:text-white text-sm">{{ $reservation->nom_client_complet ?? 'Client non inscrit' }}</p>
                                <p class="text-xs text-slate-600 dark:text-slate-400">
                                    {{ $reservation->entreprise->nom }} - {{ $reservation->date_reservation->format('d/m à H:i') }}
                                </p>
                            </div>
                        </div>
                        <span class="text-sm font-semibold text-green-600 dark:text-green-400">
                            {{ number_format($reservation->prix, 2, ',', ' ') }} €
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Accès rapide aux entreprises -->
    @if($entreprises->count() > 0)
        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">🏢 Mes entreprises</h3>
                <button onclick="showTab('entreprises')" class="text-sm text-green-600 dark:text-green-400 hover:underline">
                    Voir tout →
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($entreprises->take(3) as $entreprise)
                    <a href="{{ route('entreprise.dashboard', $entreprise->slug) }}" class="p-4 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 hover:border-green-500 dark:hover:border-green-500 transition-all group">
                        <div class="flex items-center gap-3">
                            @if($entreprise->logo)
                                <img src="{{ asset('media/' . $entreprise->logo) }}" alt="{{ $entreprise->nom }}" class="w-12 h-12 rounded-lg object-cover">
                            @else
                                <div class="w-12 h-12 rounded-lg bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white font-bold">
                                    {{ strtoupper(substr($entreprise->nom, 0, 1)) }}
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-slate-900 dark:text-white truncate group-hover:text-green-600 dark:group-hover:text-green-400 transition">{{ $entreprise->nom }}</p>
                                <p class="text-xs text-slate-600 dark:text-slate-400">{{ $entreprise->type_activite }}</p>
                            </div>
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-green-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @else
        <div class="text-center py-12 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-slate-900 dark:text-white">Aucune entreprise</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Créez votre première entreprise pour proposer vos services.
            </p>
            <div class="mt-6">
                <a href="{{ route('entreprise.create') }}" class="inline-flex items-center px-6 py-3 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600">
                    + Créer mon entreprise
                </a>
            </div>
        </div>
    @endif
</div>
