<div>
    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Vue d'ensemble</h2>

    <!-- Suivi de complétion pour les nouvelles entreprises -->
    @include('components.entreprise-completion', ['entreprise' => $entreprise])

    <!-- Statistiques principales -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Total réservations</p>
            <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ $stats['total_reservations'] }}</p>
        </div>
        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Revenu total</p>
            <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ number_format($stats['revenu_total'], 2, ',', ' ') }} €</p>
        </div>
        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Revenu encaissé</p>
            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($stats['revenu_paye'], 2, ',', ' ') }} €</p>
        </div>
        <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-xl">
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Ce mois</p>
            <p class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($stats['revenu_ce_mois'], 2, ',', ' ') }} €</p>
            <div class="flex items-center gap-1 mt-1">
                @if($stats['evolution_revenu'] > 0)
                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                    <span class="text-xs text-green-600 dark:text-green-400">+{{ $stats['evolution_revenu'] }}%</span>
                @elseif($stats['evolution_revenu'] < 0)
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"></path>
                    </svg>
                    <span class="text-xs text-red-600 dark:text-red-400">{{ $stats['evolution_revenu'] }}%</span>
                @endif
                <span class="text-xs text-slate-500 dark:text-slate-400">({{ $stats['reservations_ce_mois'] }} résa.)</span>
            </div>
        </div>
    </div>

    <!-- Statuts des réservations -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="p-4 border border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">En attente</p>
                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['reservations_en_attente'] }}</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-yellow-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="p-4 border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 rounded-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Confirmées</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['reservations_confirmees'] }}</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-green-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="p-4 border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Terminées</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['reservations_terminees'] }}</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-blue-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="p-4 border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Annulées</p>
                    <p class="text-2xl font-bold text-slate-600 dark:text-slate-400">{{ $stats['reservations_annulees'] }}</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-slate-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Réservations en attente -->
    @if($reservationsEnAttente->count() > 0)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border-2 border-yellow-400 dark:border-yellow-600 rounded-xl p-6 mb-8">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-yellow-500 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Réservations en attente</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ $reservationsEnAttente->count() }} réservation(s) nécessitent votre attention</p>
                    </div>
                </div>
                <button onclick="showTab('reservations')" class="px-4 py-2 text-sm font-medium text-yellow-700 dark:text-yellow-400 hover:bg-yellow-100 dark:hover:bg-yellow-900/30 rounded-lg transition">
                    Voir tout
                </button>
            </div>
            
            <div class="space-y-3 max-h-80 overflow-y-auto">
                @foreach($reservationsEnAttente->take(5) as $reservation)
                    <div class="p-4 bg-white dark:bg-slate-800 rounded-lg border border-yellow-200 dark:border-yellow-800">
                        <div class="flex items-start gap-3">
                            <x-avatar :user="$reservation->user" size="md" class="flex-shrink-0" />
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-2">
                                    <h4 class="font-semibold text-slate-900 dark:text-white">{{ $reservation->nom_client_complet ?? 'Client non inscrit' }}</h4>
                                    <span class="text-sm text-slate-500 dark:text-slate-400 truncate">{{ $reservation->email_client_complet ?? 'N/A' }}</span>
                                </div>
                                <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">
                                    <span class="font-medium">{{ ($reservation->typeService ? $reservation->typeService->nom : null) ?? $reservation->type_service ?? 'Service' }}</span>
                                    - {{ $reservation->date_reservation->format('d/m/Y à H:i') }}
                                </p>
                                @if($reservation->lieu)
                                    <p class="text-sm text-slate-500 dark:text-slate-400">
                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        </svg>
                                        {{ $reservation->lieu }}
                                    </p>
                                @endif
                                <p class="text-sm font-semibold text-green-600 dark:text-green-400 mt-1">
                                    {{ number_format($reservation->prix, 2, ',', ' ') }} €
                                </p>
                            </div>
                            <a 
                                href="{{ route('reservations.show', [$entreprise->slug, $reservation->id]) }}" 
                                class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white text-sm font-semibold rounded-lg transition-all flex-shrink-0"
                            >
                                Gérer
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-6 mb-8">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-green-800 dark:text-green-400">Tout est à jour !</h3>
                    <p class="text-sm text-green-700 dark:text-green-500">Aucune réservation en attente de validation.</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Accès rapides -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <button onclick="showTab('agenda')" class="p-6 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl hover:bg-orange-100 dark:hover:bg-orange-900/30 transition text-left group">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-orange-500 flex items-center justify-center text-white group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 dark:text-white">Gérer l'agenda</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Horaires, services, calendrier</p>
                </div>
            </div>
        </button>

        <a href="{{ route('public.agenda', $entreprise->slug) }}" target="_blank" class="p-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl hover:bg-blue-100 dark:hover:bg-blue-900/30 transition text-left group block">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-500 flex items-center justify-center text-white group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 dark:text-white">Agenda public</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Ce que voient vos clients</p>
                </div>
            </div>
        </a>

        <button onclick="showTab('parametres')" class="p-6 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-xl hover:bg-purple-100 dark:hover:bg-purple-900/30 transition text-left group">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-purple-500 flex items-center justify-center text-white group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 dark:text-white">Paramètres</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Configurer l'entreprise</p>
                </div>
            </div>
        </button>
    </div>
</div>
