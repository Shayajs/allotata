<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Réservations</h2>
        <button 
            onclick="openCreateReservationModal()"
            class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all flex items-center gap-2"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Créer une réservation
        </button>
    </div>

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
                                    @if($reservation->user)
                                        <x-avatar :user="$reservation->user" size="md" class="flex-shrink-0" />
                                    @else
                                        <div class="w-10 h-10 rounded-full bg-slate-300 dark:bg-slate-600 flex items-center justify-center text-slate-600 dark:text-slate-300 font-semibold flex-shrink-0">
                                            {{ strtoupper(substr($reservation->nom_client ?? 'N', 0, 1)) }}
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-2 flex-wrap">
                                            <h3 class="font-semibold text-slate-900 dark:text-white">{{ $reservation->user ? $reservation->user->name : ($reservation->nom_client ?? 'N/A') }}</h3>
                                            <span class="text-sm text-slate-600 dark:text-slate-400 truncate">{{ $reservation->user ? $reservation->user->email : ($reservation->email_client ?? 'N/A') }}</span>
                                            @if($reservation->estPourClienteNonInscrite())
                                                <span class="px-2 py-1 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 rounded-full">Cliente non inscrite</span>
                                            @endif
                                            @if($reservation->creee_manuellement)
                                                <span class="px-2 py-1 text-xs bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-400 rounded-full">Créée manuellement</span>
                                            @endif
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
                                                @if($reservation->user)
                                                    <x-avatar :user="$reservation->user" size="sm" class="flex-shrink-0" />
                                                @else
                                                    <div class="w-8 h-8 rounded-full bg-slate-300 dark:bg-slate-600 flex items-center justify-center text-slate-600 dark:text-slate-300 font-semibold text-sm flex-shrink-0">
                                                        {{ strtoupper(substr($reservation->nom_client ?? 'N', 0, 1)) }}
                                                    </div>
                                                @endif
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                                                        <h4 class="font-semibold text-slate-900 dark:text-white">{{ $reservation->user ? $reservation->user->name : ($reservation->nom_client ?? 'N/A') }}</h4>
                                                        <span class="text-sm text-slate-600 dark:text-slate-400">{{ $reservation->date_reservation->format('d/m/Y à H:i') }}</span>
                                                        @if($reservation->estPourClienteNonInscrite())
                                                            <span class="px-2 py-0.5 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 rounded-full">Non inscrite</span>
                                                        @endif
                                                        @if($reservation->creee_manuellement)
                                                            <span class="px-2 py-0.5 text-xs bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-400 rounded-full">Manuelle</span>
                                                        @endif
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

<!-- Modale de création de réservation -->
<div id="create-reservation-modal" class="hidden fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="modal-content max-w-3xl w-full max-h-[90vh] flex flex-col overflow-hidden">
        <div class="sticky top-0 z-10 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 px-6 py-4 flex items-center justify-between flex-shrink-0">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Créer une réservation</h3>
            <button onclick="closeCreateReservationModal()" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="overflow-y-auto flex-1">
            <form id="create-reservation-form" method="POST" action="{{ route('reservations.store-manuelle', $entreprise->slug) }}" class="p-6 space-y-6">
            @csrf
            
            <!-- Recherche de cliente -->
            <div class="relative">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Rechercher une cliente inscrite (optionnel)
                </label>
                <input 
                    type="text" 
                    id="search-client-input"
                    placeholder="Nom ou email de la cliente..."
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    autocomplete="off"
                >
                <div id="search-results" class="absolute z-50 w-full mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded-lg shadow-lg max-h-64 overflow-y-auto hidden"></div>
            </div>

            <!-- Informations clientes -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Nom complet <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="nom_client" 
                        id="nom_client"
                        required
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                    <input type="hidden" name="user_id" id="user_id">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="email" 
                        name="email_client" 
                        id="email_client"
                        required
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Téléphone <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="telephone_client_non_inscrit" 
                        id="telephone_client_non_inscrit"
                        required
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>
            </div>

            <!-- Date et heure -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Date <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="date" 
                        name="date_reservation" 
                        id="date_reservation"
                        required
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Heure <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="time" 
                        name="heure_reservation" 
                        id="heure_reservation"
                        required
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>
            </div>

            <!-- Type de service -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Type de service
                </label>
                <select 
                    name="type_service_id" 
                    id="type_service_id"
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    onchange="updateServiceInfo()"
                >
                    <option value="">Sélectionner un service ou saisir manuellement</option>
                    @if(isset($typesServices))
                        @foreach($typesServices as $typeService)
                            <option value="{{ $typeService->id }}" data-prix="{{ $typeService->prix }}" data-duree="{{ $typeService->duree_minutes }}">
                                {{ $typeService->nom }} - {{ number_format($typeService->prix, 2, ',', ' ') }} € ({{ $typeService->duree_minutes }} min)
                            </option>
                        @endforeach
                    @endif
                </select>
                <input 
                    type="text" 
                    name="type_service" 
                    id="type_service"
                    placeholder="Ou saisir un type de service manuellement"
                    class="w-full mt-2 px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                >
            </div>

            <!-- Prix et durée -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Prix (€) <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        name="prix" 
                        id="prix"
                        step="0.01"
                        min="0"
                        required
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Durée (minutes) <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        name="duree_minutes" 
                        id="duree_minutes"
                        min="1"
                        required
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>
            </div>

            <!-- Lieu et membre -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Lieu (optionnel)
                    </label>
                    <input 
                        type="text" 
                        name="lieu" 
                        id="lieu"
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>
                @if($aGestionMultiPersonnes ?? false)
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Membre assigné (optionnel)
                        </label>
                        <select 
                            name="membre_id" 
                            id="membre_id"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                            <option value="">Aucun membre spécifique</option>
                            @if(isset($membresAvecStats))
                                @foreach($membresAvecStats as $membreStat)
                                    <option value="{{ $membreStat['membre']->id }}">
                                        {{ $membreStat['membre']->user->name ?? 'Membre' }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                @endif
            </div>

            <!-- Statut et paiement -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Statut <span class="text-red-500">*</span>
                    </label>
                    <select 
                        name="statut" 
                        id="statut"
                        required
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                        <option value="confirmee">Confirmée</option>
                        <option value="en_attente">En attente</option>
                        <option value="terminee">Terminée</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Paiement
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input 
                                type="checkbox" 
                                name="est_paye" 
                                id="est_paye"
                                value="1"
                                class="rounded border-slate-300 text-green-600 focus:ring-green-500"
                                onchange="toggleDatePaiement()"
                            >
                            <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Réservation payée</span>
                        </label>
                        <input 
                            type="date" 
                            name="date_paiement" 
                            id="date_paiement"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white hidden"
                        >
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Notes (optionnel)
                </label>
                <textarea 
                    name="notes" 
                    id="notes"
                    rows="3"
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                ></textarea>
            </div>

            <!-- Boutons -->
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                <button 
                    type="button"
                    onclick="closeCreateReservationModal()"
                    class="px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition"
                >
                    Annuler
                </button>
                <button 
                    type="submit"
                    class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all"
                >
                    Créer la réservation
                </button>
            </div>
        </form>
        </div>
    </div>
</div>

<script>
    let searchTimeout;
    const searchUrl = '{{ route("reservations.search-clients", $entreprise->slug) }}';

    function openCreateReservationModal() {
        document.getElementById('create-reservation-modal').classList.remove('hidden');
    }

    function closeCreateReservationModal() {
        document.getElementById('create-reservation-modal').classList.add('hidden');
        document.getElementById('create-reservation-form').reset();
        const resultsDiv = document.getElementById('search-results');
        resultsDiv.classList.add('hidden');
        resultsDiv.innerHTML = '';
        document.getElementById('user_id').value = '';
        
        // Réinitialiser les styles des champs
        const nomClient = document.getElementById('nom_client');
        const emailClient = document.getElementById('email_client');
        const telClient = document.getElementById('telephone_client_non_inscrit');
        nomClient.classList.remove('bg-green-50', 'dark:bg-green-900/20');
        emailClient.classList.remove('bg-green-50', 'dark:bg-green-900/20');
        telClient.classList.remove('bg-green-50', 'dark:bg-green-900/20');
        
        // Réinitialiser les champs requis
        nomClient.required = true;
        emailClient.required = true;
        telClient.required = true;
    }

    function toggleDatePaiement() {
        const estPaye = document.getElementById('est_paye').checked;
        const datePaiement = document.getElementById('date_paiement');
        if (estPaye) {
            datePaiement.classList.remove('hidden');
            if (!datePaiement.value) {
                datePaiement.value = new Date().toISOString().split('T')[0];
            }
        } else {
            datePaiement.classList.add('hidden');
        }
    }

    function updateServiceInfo() {
        const select = document.getElementById('type_service_id');
        const selectedOption = select.options[select.selectedIndex];
        if (selectedOption.value) {
            document.getElementById('prix').value = selectedOption.dataset.prix;
            document.getElementById('duree_minutes').value = selectedOption.dataset.duree;
            document.getElementById('type_service').value = selectedOption.text.split(' - ')[0];
        }
    }

    // Recherche de clientes avec debounce
    const searchInput = document.getElementById('search-client-input');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const query = e.target.value.trim();
            const resultsDiv = document.getElementById('search-results');
            
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                resultsDiv.classList.add('hidden');
                resultsDiv.innerHTML = '';
                return;
            }

            // Afficher un indicateur de chargement
            resultsDiv.innerHTML = '<div class="p-3 text-sm text-slate-500 dark:text-slate-400 text-center">🔍 Recherche en cours...</div>';
            resultsDiv.classList.remove('hidden');

            searchTimeout = setTimeout(() => {
                fetch(`${searchUrl}?q=${encodeURIComponent(query)}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Erreur de réponse');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.length === 0) {
                            resultsDiv.innerHTML = '<div class="p-3 text-sm text-slate-500 dark:text-slate-400 text-center">Aucune cliente trouvée</div>';
                            resultsDiv.classList.remove('hidden');
                            return;
                        }

                    let html = '';
                    data.forEach(client => {
                        const nameEscaped = client.name.replace(/'/g, "\\'").replace(/"/g, '&quot;');
                        const emailEscaped = client.email.replace(/'/g, "\\'").replace(/"/g, '&quot;');
                        const telEscaped = (client.telephone || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
                        html += `
                            <div 
                                class="p-3 hover:bg-green-50 dark:hover:bg-green-900/20 cursor-pointer border-b border-slate-200 dark:border-slate-600 last:border-b-0 transition-colors"
                                onclick="selectClient(${client.id}, '${nameEscaped}', '${emailEscaped}', '${telEscaped}')"
                            >
                                <div class="font-medium text-slate-900 dark:text-white">${client.name}</div>
                                <div class="text-sm text-slate-600 dark:text-slate-400">${client.email}</div>
                                ${client.telephone ? `<div class="text-xs text-slate-500 dark:text-slate-400 mt-1">📞 ${client.telephone}</div>` : ''}
                            </div>
                        `;
                    });
                    resultsDiv.innerHTML = html;
                    resultsDiv.classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Erreur lors de la recherche:', error);
                    resultsDiv.innerHTML = '<div class="p-3 text-sm text-red-500 dark:text-red-400 text-center">Erreur lors de la recherche</div>';
                    resultsDiv.classList.remove('hidden');
                });
            }, 300);
        });

        // Afficher les résultats quand l'input a le focus et qu'il y a du texte
        searchInput.addEventListener('focus', function(e) {
            const query = e.target.value.trim();
            const resultsDiv = document.getElementById('search-results');
            if (query.length >= 2 && resultsDiv.innerHTML && !resultsDiv.classList.contains('hidden')) {
                resultsDiv.classList.remove('hidden');
            }
        });
    }

    function selectClient(userId, name, email, telephone) {
        document.getElementById('user_id').value = userId;
        document.getElementById('nom_client').value = name;
        document.getElementById('email_client').value = email;
        if (telephone) {
            document.getElementById('telephone_client_non_inscrit').value = telephone;
        }
        document.getElementById('search-client-input').value = name;
        document.getElementById('search-results').classList.add('hidden');
        document.getElementById('search-results').innerHTML = '';
        
        // Rendre les champs non obligatoires si cliente inscrite et les désactiver visuellement
        const nomClient = document.getElementById('nom_client');
        const emailClient = document.getElementById('email_client');
        const telClient = document.getElementById('telephone_client_non_inscrit');
        
        nomClient.required = false;
        emailClient.required = false;
        telClient.required = false;
        
        // Ajouter un style visuel pour indiquer que c'est prérempli
        nomClient.classList.add('bg-green-50', 'dark:bg-green-900/20');
        emailClient.classList.add('bg-green-50', 'dark:bg-green-900/20');
        if (telephone) {
            telClient.classList.add('bg-green-50', 'dark:bg-green-900/20');
        }
        
        // Afficher un message de confirmation
        const searchInput = document.getElementById('search-client-input');
        searchInput.classList.add('border-green-500');
        setTimeout(() => {
            searchInput.classList.remove('border-green-500');
        }, 2000);
    }

    // Fermer les résultats si on clique ailleurs
    document.addEventListener('click', function(e) {
        const searchInput = document.getElementById('search-client-input');
        const resultsDiv = document.getElementById('search-results');
        if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
            resultsDiv.classList.add('hidden');
        }
    });

    // Fermer la modale en cliquant en dehors
    document.getElementById('create-reservation-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeCreateReservationModal();
        }
    });
</script>
