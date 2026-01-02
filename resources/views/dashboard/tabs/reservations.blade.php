{{-- Onglet Réservations (pour les clients) --}}
<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Mes Réservations</h2>
        <a href="{{ route('factures.index') }}" class="px-4 py-2 text-sm font-medium bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white rounded-lg transition">
            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Mes Factures
        </a>
    </div>

    {{-- Barre de recherche et filtres --}}
    @if($reservations->count() > 0 || request()->hasAny(['search', 'statut', 'est_paye']))
        <div class="mb-6 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg border border-slate-200 dark:border-slate-600">
            <form method="GET" action="{{ route('dashboard') }}" class="space-y-4">
                <input type="hidden" name="tab" value="reservations">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Rechercher</label>
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ request('search') }}"
                            placeholder="Entreprise, service, lieu..."
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Statut</label>
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
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Paiement</label>
                        <select 
                            name="est_paye" 
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                            <option value="">Tous</option>
                            <option value="1" {{ request('est_paye') === '1' ? 'selected' : '' }}>Payé</option>
                            <option value="0" {{ request('est_paye') === '0' ? 'selected' : '' }}>Non payé</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                            🔍 Rechercher
                        </button>
                        @if(request()->hasAny(['search', 'statut', 'est_paye']))
                            <a href="{{ route('dashboard') }}?tab=reservations" class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                                ✕
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    @endif

    @if($reservations->count() > 0)
        <div class="space-y-4">
            @foreach($reservations as $reservation)
                <div class="p-5 border border-slate-200 dark:border-slate-700 rounded-lg hover:border-green-500 dark:hover:border-green-500 transition-all">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">
                                        {{ $reservation->entreprise->nom }}
                                    </h3>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">
                                        {{ $reservation->entreprise->type_activite }}
                                        @if($reservation->type_service) - {{ $reservation->type_service }} @endif
                                    </p>
                                </div>
                                <span class="px-3 py-1 text-xs font-medium rounded-full
                                    @if($reservation->statut === 'confirmee') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                    @elseif($reservation->statut === 'annulee') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                                    @elseif($reservation->statut === 'terminee') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                                    @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400
                                    @endif">
                                    @if($reservation->statut === 'confirmee') Confirmée
                                    @elseif($reservation->statut === 'annulee') Annulée
                                    @elseif($reservation->statut === 'terminee') Terminée
                                    @else En attente
                                    @endif
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-slate-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-slate-900 dark:text-white">Date et heure</p>
                                        <p class="text-sm text-slate-600 dark:text-slate-400">
                                            {{ $reservation->date_reservation->format('d/m/Y à H:i') }}
                                        </p>
                                    </div>
                                </div>
                                
                                @if($reservation->lieu)
                                    <div class="flex items-start gap-3">
                                        <svg class="w-5 h-5 text-slate-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        <div>
                                            <p class="text-sm font-medium text-slate-900 dark:text-white">Lieu</p>
                                            <p class="text-sm text-slate-600 dark:text-slate-400">{{ $reservation->lieu }}</p>
                                        </div>
                                    </div>
                                @endif
                                
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-slate-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-slate-900 dark:text-white">Prix</p>
                                        <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                            {{ number_format($reservation->prix, 2, ',', ' ') }} €
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-slate-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-slate-900 dark:text-white">Paiement</p>
                                        <p class="text-sm @if($reservation->est_paye) text-green-600 dark:text-green-400 font-semibold @else text-red-600 dark:text-red-400 font-semibold @endif">
                                            @if($reservation->est_paye)
                                                ✓ Payé
                                                @if($reservation->date_paiement) le {{ $reservation->date_paiement->format('d/m/Y') }} @endif
                                            @else
                                                ✗ Non payé
                                            @endif
                                        </p>
                                        @if($reservation->est_paye && $reservation->facture)
                                            <a href="{{ route('factures.show', $reservation->facture->id) }}" class="mt-1 inline-block text-xs text-green-600 dark:text-green-400 hover:underline">
                                                Voir la facture →
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            @if($reservation->notes)
                                <div class="mt-4 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Notes :</p>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ $reservation->notes }}</p>
                                </div>
                            @endif

                            {{-- Actions pour le client --}}
                            @if(in_array($reservation->statut, ['en_attente', 'confirmee']))
                                <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700 flex flex-wrap gap-2">
                                    @if($reservation->statut === 'en_attente')
                                        <button 
                                            onclick="openModifyModal({{ $reservation->id }}, '{{ $reservation->date_reservation->format('Y-m-d') }}', '{{ $reservation->date_reservation->format('H:i') }}', '{{ addslashes($reservation->lieu ?? '') }}', '{{ addslashes($reservation->notes ?? '') }}')"
                                            class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition"
                                        >
                                            ✏️ Modifier
                                        </button>
                                    @endif
                                    
                                    @if(!$reservation->est_paye)
                                        <form action="{{ route('dashboard.reservation.cancel', $reservation->id) }}" method="POST" class="inline-block">
                                            @csrf
                                            <button 
                                                type="submit" 
                                                onclick="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')"
                                                class="px-4 py-2 text-sm bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition"
                                            >
                                                ✗ Annuler
                                            </button>
                                        </form>
                                    @else
                                        <span class="px-4 py-2 text-sm bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400 font-semibold rounded-lg cursor-not-allowed">
                                            ✗ Annulation impossible (payé)
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-slate-900 dark:text-white">Aucune réservation</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Vous n'avez pas encore de réservations.
            </p>
            <div class="mt-6">
                <a href="{{ route('home') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600">
                    Rechercher une entreprise
                </a>
            </div>
        </div>
    @endif
</div>
