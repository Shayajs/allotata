<div>
    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Réservations</h2>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <p class="text-green-800 dark:text-green-400">{{ session('success') }}</p>
                </div>
            @endif

            <!-- Barre de recherche et filtres -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
                <form method="GET" action="{{ route('reservations.index', $entreprise->slug) }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Rechercher
                            </label>
                            <input 
                                type="text" 
                                name="search" 
                                value="{{ request('search') }}"
                                placeholder="Client, service, lieu..."
                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Statut
                            </label>
                            <select 
                                name="statut" 
                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            >
                                <option value="">Tous les statuts</option>
                                <option value="en_attente" {{ request('statut') === 'en_attente' ? 'selected' : '' }}>En attente</option>
                                <option value="confirmee" {{ request('statut') === 'confirmee' ? 'selected' : '' }}>Confirmée</option>
                                <option value="terminee" {{ request('statut') === 'terminee' ? 'selected' : '' }}>Terminée</option>
                                <option value="annulee" {{ request('statut') === 'annulee' ? 'selected' : '' }}>Annulée</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Paiement
                            </label>
                            <select 
                                name="est_paye" 
                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            >
                                <option value="">Tous</option>
                                <option value="1" {{ request('est_paye') === '1' ? 'selected' : '' }}>Payé</option>
                                <option value="0" {{ request('est_paye') === '0' ? 'selected' : '' }}>Non payé</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Date début
                            </label>
                            <input 
                                type="date" 
                                name="date_debut" 
                                value="{{ request('date_debut') }}"
                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            >
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                                🔍 Rechercher
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Date fin
                        </label>
                        <input 
                            type="date" 
                            name="date_fin" 
                            value="{{ request('date_fin') }}"
                            class="w-full md:w-1/3 px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                    </div>
                    @if(request()->hasAny(['search', 'statut', 'est_paye', 'date_debut', 'date_fin']))
                        <a href="{{ route('reservations.index', $entreprise->slug) }}" class="text-sm text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400">
                            Réinitialiser les filtres
                        </a>
                    @endif
                </form>
            </div>

            <!-- Réservations en attente -->
            @if(isset($reservations['en_attente']) && $reservations['en_attente']->count() > 0)
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border-2 border-yellow-500 dark:border-yellow-600 rounded-xl shadow-sm p-6 mb-8">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4">
                        ⚠️ En attente de validation ({{ $reservations['en_attente']->count() }})
                    </h2>
                    <div class="space-y-4">
                        @foreach($reservations['en_attente'] as $reservation)
                            <div class="p-4 bg-white dark:bg-slate-800 rounded-lg border border-yellow-200 dark:border-yellow-800">
                                <div class="flex items-start gap-3">
                                    <x-avatar :user="$reservation->user" size="md" class="flex-shrink-0" />
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-2">
                                            <h3 class="font-semibold text-slate-900 dark:text-white">{{ $reservation->user->name }}</h3>
                                            <span class="text-sm text-slate-600 dark:text-slate-400 truncate">{{ $reservation->user->email }}</span>
                                        </div>
                                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">
                                            <strong>{{ $reservation->type_service ?? 'Service' }}</strong> - 
                                            {{ $reservation->date_reservation->format('d/m/Y à H:i') }}
                                            ({{ $reservation->duree_minutes }} min)
                                        </p>
                                        @if($aGestionMultiPersonnes && $reservation->membre)
                                            <p class="text-xs text-blue-600 dark:text-blue-400 mb-1">
                                                👤 Assigné à : {{ $reservation->membre->user->name ?? 'Membre' }}
                                            </p>
                                        @endif
                                        @if($reservation->lieu)
                                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">
                                                📍 {{ $reservation->lieu }}
                                            </p>
                                        @endif
                                        <p class="text-sm font-semibold text-green-600 dark:text-green-400">
                                            {{ number_format($reservation->prix, 2, ',', ' ') }} €
                                        </p>
                                    </div>
                                    <a 
                                        href="{{ route('reservations.show', [$entreprise->slug, $reservation->id]) }}" 
                                        class="px-4 py-2 text-sm bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all flex-shrink-0"
                                    >
                                        Gérer →
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Autres réservations -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-6">Toutes les réservations</h2>
                
                <div class="space-y-4">
                    @foreach($reservations as $statut => $reservationsStatut)
                        @if($statut !== 'en_attente' && $reservationsStatut->count() > 0)
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-slate-700 dark:text-slate-300 mb-3 capitalize">
                                    {{ $statut === 'confirmee' ? 'Confirmées' : ($statut === 'terminee' ? 'Terminées' : ($statut === 'annulee' ? 'Annulées' : $statut)) }}
                                    ({{ $reservationsStatut->count() }})
                                </h3>
                                <div class="space-y-3">
                                    @foreach($reservationsStatut as $reservation)
                                        <div class="p-4 border border-slate-200 dark:border-slate-700 rounded-lg hover:border-green-500 dark:hover:border-green-500 transition">
                                            <div class="flex items-start gap-3">
                                                <x-avatar :user="$reservation->user" size="sm" class="flex-shrink-0" />
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <h4 class="font-semibold text-slate-900 dark:text-white">{{ $reservation->user->name }}</h4>
                                                        <span class="text-sm text-slate-600 dark:text-slate-400">{{ $reservation->date_reservation->format('d/m/Y à H:i') }}</span>
                                                    </div>
                                                    <p class="text-sm text-slate-600 dark:text-slate-400">
                                                        {{ $reservation->type_service ?? 'Service' }} - {{ number_format($reservation->prix, 2, ',', ' ') }} €
                                                        @if($reservation->est_paye)
                                                            <span class="ml-2 text-green-600 dark:text-green-400">✓ Payé</span>
                                                        @else
                                                            <span class="ml-2 text-red-600 dark:text-red-400">✗ Non payé</span>
                                                        @endif
                                                    </p>
                                                    @if($aGestionMultiPersonnes && $reservation->membre)
                                                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                                            👤 Assigné à : {{ $reservation->membre->user->name ?? 'Membre' }}
                                                        </p>
                                                    @endif
                                                </div>
                                                <a 
                                                    href="{{ route('reservations.show', [$entreprise->slug, $reservation->id]) }}" 
                                                    class="px-3 py-1 text-sm text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition flex-shrink-0"
                                                >
                                                    Voir →
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach

                    @if($reservations->isEmpty() || $reservations->flatten()->isEmpty())
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-slate-900 dark:text-white">Aucune réservation</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Vous n'avez pas encore de réservations correspondant à ces critères.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
</div>
