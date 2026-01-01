<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Dashboard - Allo Tata</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.theme-script')
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center gap-4">
                        <!-- Menu Burger pour mobile web -->
                        @include('components.mobile-nav', ['navType' => 'dashboard'])
                        
                        <a href="{{ route('home') }}" class="text-2xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                            Allo Tata
                        </a>
                        @if($user->is_admin && config('app.debug'))
                            <span class="px-2 py-1 text-xs font-semibold bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded-full border border-yellow-300 dark:border-yellow-700">
                                🔧 DEBUG MODE
                            </span>
                        @endif
                    </div>
                    <!-- Liens desktop (masqués sur mobile) -->
                    <div class="hidden lg:flex items-center gap-4">
                        @if($user->is_admin)
                            <a href="{{ route('admin.index') }}" class="px-4 py-2 text-sm font-medium bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                                Administration
                            </a>
                        @endif
                        <a href="{{ route('notifications.index') }}" class="px-4 py-2 text-sm font-medium bg-orange-100 dark:bg-orange-900/30 hover:bg-orange-200 dark:hover:bg-orange-900/50 text-orange-800 dark:text-orange-400 rounded-lg transition relative">
                            📬 Notifications
                            @if($user->nombre_notifications_non_lues > 0)
                                <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">
                                    {{ $user->nombre_notifications_non_lues }}
                                </span>
                            @endif
                        </a>
                        <a href="{{ route('messagerie.index') }}" class="px-4 py-2 text-sm font-medium bg-blue-100 dark:bg-blue-900/30 hover:bg-blue-200 dark:hover:bg-blue-900/50 text-blue-800 dark:text-blue-400 rounded-lg transition relative">
                            💬 Messagerie
                            @php
                                $nonLus = 0;
                                if ($user->est_client) {
                                    $nonLus = \App\Models\Conversation::where('user_id', $user->id)
                                        ->where('est_archivee', false)
                                        ->get()
                                        ->sum(function($c) use ($user) {
                                            return $c->messagesNonLus($user->id);
                                        });
                                }
                                if ($user->est_gerant) {
                                    $entreprisesIds = $user->entreprises()->pluck('id');
                                    $nonLus += \App\Models\Conversation::whereIn('entreprise_id', $entreprisesIds)
                                        ->where('est_archivee', false)
                                        ->get()
                                        ->sum(function($c) use ($user) {
                                            return $c->messagesNonLus($user->id);
                                        });
                                }
                            @endphp
                            @if($nonLus > 0)
                                <span class="absolute -top-1 -right-1 w-5 h-5 bg-green-500 text-white text-xs rounded-full flex items-center justify-center">
                                    {{ $nonLus }}
                                </span>
                            @endif
                        </a>
                        <a href="{{ route('tickets.create') }}" class="px-4 py-2 text-sm font-medium bg-purple-100 dark:bg-purple-900/30 hover:bg-purple-200 dark:hover:bg-purple-900/50 text-purple-800 dark:text-purple-400 rounded-lg transition">
                            🎫 Support
                        </a>
                        <a href="{{ route('settings.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Paramètres
                        </a>
                        <span class="text-sm text-slate-600 dark:text-slate-400">
                            {{ $user->name }}
                        </span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-red-600 dark:hover:text-red-400 transition">
                                Déconnexion
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Bulle de notifications d'erreurs (Admin uniquement) -->
        @php
            $hasNotificationsColumn = \Illuminate\Support\Facades\Schema::hasColumn('users', 'notifications_erreurs_actives');
        @endphp
        @if($user->is_admin && $hasNotificationsColumn && isset($user->notifications_erreurs_actives) && $user->notifications_erreurs_actives)
            <div id="error-notifications-bubble" class="fixed bottom-4 right-4 z-50 hidden">
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-red-200 dark:border-red-800 max-w-md w-full">
                    <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <h3 class="font-semibold text-slate-900 dark:text-white">Erreurs récentes</h3>
                            <span id="error-count-badge" class="ml-2 px-2 py-1 bg-red-500 text-white text-xs rounded-full">0</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="markAllErrorsAsRead()" class="text-xs text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">
                                Tout marquer comme lu
                            </button>
                            <button onclick="toggleErrorNotifications()" class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div id="error-list" class="max-h-96 overflow-y-auto">
                        <div class="p-4 text-center text-slate-500 dark:text-slate-400 text-sm">
                            Aucune erreur récente
                        </div>
                    </div>
                </div>
            </div>
            <!-- Bouton flottant pour ouvrir les notifications -->
            <button 
                id="error-notifications-toggle"
                onclick="toggleErrorNotifications()"
                class="fixed bottom-4 right-4 z-40 w-14 h-14 bg-red-600 hover:bg-red-700 text-white rounded-full shadow-lg flex items-center justify-center transition-all hidden"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <span id="error-toggle-badge" class="absolute -top-1 -right-1 w-6 h-6 bg-white text-red-600 text-xs rounded-full flex items-center justify-center font-bold hidden">0</span>
            </button>
        @endif

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Messages de succès -->
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <p class="text-green-800 dark:text-green-400">{{ session('success') }}</p>
                </div>
            @endif

            <!-- Messages d'erreur -->
            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    @foreach($errors->all() as $error)
                        <p class="text-red-800 dark:text-red-400">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <!-- En-tête -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">
                    Bienvenue, {{ $user->name }} !
                </h1>
                <p class="text-slate-600 dark:text-slate-400">
                    Gérez votre compte et vos entreprises depuis ce tableau de bord.
                </p>
            </div>

            <!-- Statut des rôles -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
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

            <!-- Statistiques pour les gérants -->
            @if($user->est_gerant && $stats)
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-8">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Statistiques globales</h2>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Total réservations</p>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total_reservations'] }}</p>
                        </div>
                        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Revenu total</p>
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($stats['revenu_total'], 2, ',', ' ') }} €</p>
                        </div>
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Revenu payé</p>
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($stats['revenu_paye'], 2, ',', ' ') }} €</p>
                        </div>
                        <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Ce mois</p>
                            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($stats['revenu_ce_mois'], 2, ',', ' ') }} €</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $stats['reservations_ce_mois'] }} réservation(s)</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-4 border border-slate-200 dark:border-slate-700 rounded-lg">
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Confirmées</p>
                            <p class="text-xl font-bold text-slate-900 dark:text-white">{{ $stats['reservations_confirmees'] }}</p>
                        </div>
                        <div class="p-4 border border-slate-200 dark:border-slate-700 rounded-lg">
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">En attente</p>
                            <p class="text-xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['reservations_en_attente'] }}</p>
                        </div>
                        <div class="p-4 border border-slate-200 dark:border-slate-700 rounded-lg">
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Terminées</p>
                            <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['reservations_terminees'] }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Section Réservations en attente (pour les gérants) -->
            @if($user->est_gerant && $reservationsEnAttente->count() > 0)
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border-2 border-yellow-500 dark:border-yellow-600 rounded-xl shadow-sm p-6 mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-1">
                                ⚠️ Réservations en attente
                            </h2>
                            <p class="text-slate-600 dark:text-slate-400">
                                {{ $reservationsEnAttente->count() }} réservation(s) nécessitent votre validation
                            </p>
                        </div>
                    </div>
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        @foreach($reservationsEnAttente->take(5) as $reservation)
                            <div class="p-4 bg-white dark:bg-slate-800 rounded-lg border border-yellow-200 dark:border-yellow-800">
                                <div class="flex items-start gap-3">
                                    <x-avatar :user="$reservation->user" size="md" class="flex-shrink-0" />
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-2">
                                            <h3 class="font-semibold text-slate-900 dark:text-white">{{ $reservation->user->name }}</h3>
                                            <span class="text-sm text-slate-600 dark:text-slate-400">{{ $reservation->entreprise->nom }}</span>
                                        </div>
                                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">
                                            {{ $reservation->type_service ?? 'Service' }} - 
                                            {{ $reservation->date_reservation->format('d/m/Y à H:i') }}
                                        </p>
                                        <p class="text-sm font-semibold text-green-600 dark:text-green-400">
                                            {{ number_format($reservation->prix, 2, ',', ' ') }} €
                                        </p>
                                    </div>
                                    <a 
                                        href="{{ route('reservations.show', [$reservation->entreprise->slug, $reservation->id]) }}" 
                                        class="px-4 py-2 text-sm bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all flex-shrink-0"
                                    >
                                        Gérer →
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($reservationsEnAttente->count() > 5)
                        <div class="mt-4 text-center">
                            <a href="#" class="text-sm text-green-600 dark:text-green-400 hover:underline">
                                Voir toutes les réservations en attente ({{ $reservationsEnAttente->count() }})
                            </a>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Section Réservations (pour les clients) -->
            @if($user->est_client)
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-8">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">
                            Mes Réservations
                        </h2>
                        <a href="{{ route('factures.index') }}" class="px-4 py-2 text-sm font-medium bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white rounded-lg transition">
                            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Mes Factures
                        </a>
                    </div>

                    <!-- Barre de recherche et filtres -->
                    @if($reservations->count() > 0 || request()->hasAny(['search', 'statut', 'est_paye']))
                        <div class="mb-6 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg border border-slate-200 dark:border-slate-600">
                            <form method="GET" action="{{ route('dashboard') }}" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                            Rechercher
                                        </label>
                                        <input 
                                            type="text" 
                                            name="search" 
                                            value="{{ request('search') }}"
                                            placeholder="Entreprise, service, lieu..."
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
                                    <div class="flex items-end gap-2">
                                        <button type="submit" class="flex-1 px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                                            🔍 Rechercher
                                        </button>
                                        @if(request()->hasAny(['search', 'statut', 'est_paye']))
                                            <a href="{{ route('dashboard') }}" class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition">
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
                                                        @if($reservation->type_service)
                                                            - {{ $reservation->type_service }}
                                                        @endif
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
                                                        <p class="text-sm 
                                                            @if($reservation->est_paye) text-green-600 dark:text-green-400 font-semibold
                                                            @else text-red-600 dark:text-red-400 font-semibold
                                                            @endif">
                                                            @if($reservation->est_paye)
                                                                ✓ Payé
                                                                @if($reservation->date_paiement)
                                                                    le {{ $reservation->date_paiement->format('d/m/Y') }}
                                                                @endif
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

                                            <!-- Actions pour le client -->
                                            @if(in_array($reservation->statut, ['en_attente', 'confirmee']))
                                                <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700 flex flex-wrap gap-2">
                                                    @if($reservation->statut === 'en_attente')
                                                        <!-- Modifier la réservation -->
                                                        <button 
                                                            onclick="openModifyModal({{ $reservation->id }}, '{{ $reservation->date_reservation->format('Y-m-d') }}', '{{ $reservation->date_reservation->format('H:i') }}', '{{ addslashes($reservation->lieu ?? '') }}', '{{ addslashes($reservation->notes ?? '') }}')"
                                                            class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition"
                                                        >
                                                            ✏️ Modifier
                                                        </button>
                                                    @endif
                                                    
                                                    <!-- Annuler la réservation (seulement si non payée) -->
                                                    @if(!$reservation->est_paye)
                                                        <form action="{{ route('dashboard.reservation.cancel', $reservation->id) }}" method="POST" class="inline-block">
                                                            @csrf
                                                            <button 
                                                                type="submit" 
                                                                onclick="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ? L\'entreprise sera notifiée.')"
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
            @endif

            <!-- Section Entreprises -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">
                        @if($user->est_gerant)
                            Mes Entreprises
                        @else
                            Créer mon entreprise
                        @endif
                    </h2>
                    <div class="flex gap-3">
                        @if($entreprisesAutres && $entreprisesAutres->count() > 0)
                            <a href="{{ route('dashboard.entreprises-autres') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-all">
                                Entreprises autres
                            </a>
                        @endif
                        <a href="{{ route('entreprise.create') }}" class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                            + {{ $user->est_gerant ? 'Ajouter une entreprise' : 'Créer mon entreprise' }}
                        </a>
                    </div>
                </div>

                @if($entreprises->count() > 0)
                    <div class="space-y-6">
                        @foreach($entreprises as $entreprise)
                            <div class="p-6 border border-slate-200 dark:border-slate-700 rounded-xl hover:border-green-500 dark:hover:border-green-500 transition-all">
                                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                                    <div class="flex-1">
                                        <div class="flex items-start justify-between mb-4">
                                            <div class="flex items-start gap-4 flex-1">
                                                @if($entreprise->logo)
                                                    <img 
                                                        src="{{ asset('media/' . $entreprise->logo) }}" 
                                                        alt="Logo {{ $entreprise->nom }}"
                                                        class="w-16 h-16 rounded-lg object-cover border-2 border-slate-200 dark:border-slate-700 flex-shrink-0"
                                                    >
                                                @endif
                                                <div class="flex-1">
                                                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-1">
                                                        {{ $entreprise->nom }}
                                                    </h3>
                                                <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">
                                                    {{ $entreprise->type_activite }}
                                                    @if($entreprise->ville)
                                                        • {{ $entreprise->ville }}
                                                    @endif
                                                </p>
                                                    @if($entreprise->est_verifiee)
                                                        <span class="inline-block px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded">✓ Vérifiée</span>
                                                    @else
                                                        <span class="inline-block px-2 py-1 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded">⏳ En attente de vérification</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        @if(isset($entreprise->stats))
                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                                                <div class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                                    <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">Réservations</p>
                                                    <p class="text-lg font-bold text-slate-900 dark:text-white">{{ $entreprise->stats['total_reservations'] }}</p>
                                                </div>
                                                <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                                    <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">Revenu total</p>
                                                    <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ number_format($entreprise->stats['revenu_total'], 2, ',', ' ') }} €</p>
                                                </div>
                                                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                                    <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">Revenu payé</p>
                                                    <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ number_format($entreprise->stats['revenu_paye'], 2, ',', ' ') }} €</p>
                                                </div>
                                                <div class="p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                                                    <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">Ce mois</p>
                                                    <p class="text-lg font-bold text-orange-600 dark:text-orange-400">{{ $entreprise->stats['reservations_ce_mois'] }}</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <a href="{{ route('entreprise.dashboard', $entreprise->slug) }}" class="px-4 py-2 text-sm font-medium text-center bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white rounded-lg transition-all shadow-sm hover:shadow-md relative">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            Gérer l'entreprise
                                            @if(isset($entreprise->stats['reservations_en_attente']) && $entreprise->stats['reservations_en_attente'] > 0)
                                                <span class="absolute -top-1 -right-1 w-5 h-5 bg-yellow-500 text-white text-xs rounded-full flex items-center justify-center animate-pulse">
                                                    {{ $entreprise->stats['reservations_en_attente'] }}
                                                </span>
                                            @endif
                                        </a>
                                        <a href="{{ route('public.entreprise', $entreprise->slug) }}" target="_blank" class="px-4 py-2 text-sm font-medium text-center bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white rounded-lg transition">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                            Page publique
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-slate-900 dark:text-white">Aucune entreprise</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            @if($user->est_gerant)
                                Ajoutez une nouvelle entreprise pour commencer.
                            @else
                                Créez votre première entreprise pour proposer vos services.
                            @endif
                        </p>
                        <div class="mt-6">
                            <a href="{{ route('entreprise.create') }}" class="inline-flex items-center px-6 py-3 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600">
                                + Créer mon entreprise
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if($user->is_admin && $user->notifications_erreurs_actives)
        <script>
            let lastErrorId = 0;
            let errorNotificationsVisible = false;
            let errorPollInterval = null;

            function toggleErrorNotifications() {
                errorNotificationsVisible = !errorNotificationsVisible;
                const bubble = document.getElementById('error-notifications-bubble');
                const toggle = document.getElementById('error-notifications-toggle');
                
                if (errorNotificationsVisible) {
                    bubble.classList.remove('hidden');
                    toggle.classList.add('hidden');
                } else {
                    bubble.classList.add('hidden');
                    toggle.classList.remove('hidden');
                }
            }

            function markAllErrorsAsRead() {
                fetch('{{ route('admin.errors.mark-all-read') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchErrors();
                    }
                });
            }

            function markErrorAsRead(errorId) {
                fetch(`/admin/errors/${errorId}/read`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchErrors();
                    }
                });
            }

            function fetchErrors() {
                fetch(`{{ route('admin.errors.index') }}?last_id=${lastErrorId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.errors && data.errors.length > 0) {
                            updateErrorList(data.errors);
                            lastErrorId = Math.max(...data.errors.map(e => e.id), lastErrorId);
                            
                            // Afficher le bouton toggle si la bulle est fermée
                            if (!errorNotificationsVisible) {
                                document.getElementById('error-notifications-toggle').classList.remove('hidden');
                            }
                        } else {
                            // Mettre à jour la liste même s'il n'y a pas d'erreurs
                            updateErrorList([]);
                        }
                    })
                    .catch(error => console.error('Error fetching errors:', error));
            }

            function updateErrorList(errors) {
                const errorList = document.getElementById('error-list');
                const errorCountBadge = document.getElementById('error-count-badge');
                const errorToggleBadge = document.getElementById('error-toggle-badge');
                
                if (errors.length === 0) {
                    errorList.innerHTML = '<div class="p-4 text-center text-slate-500 dark:text-slate-400 text-sm">Aucune erreur récente</div>';
                    errorCountBadge.textContent = '0';
                    errorCountBadge.classList.add('hidden');
                    errorToggleBadge.classList.add('hidden');
                    return;
                }

                errorCountBadge.textContent = errors.length;
                errorCountBadge.classList.remove('hidden');
                errorToggleBadge.textContent = errors.length;
                errorToggleBadge.classList.remove('hidden');

                errorList.innerHTML = errors.map(error => {
                    const isDebugMode = @json(config('app.debug'));
                    let detailsHtml = '';
                    
                    if (isDebugMode) {
                        detailsHtml = `
                            <div class="mt-2 space-y-1">
                                ${error.context ? `
                                    <details class="text-xs">
                                        <summary class="cursor-pointer text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200">Contexte (${Object.keys(error.context || {}).length} éléments)</summary>
                                        <pre class="mt-1 p-2 bg-slate-100 dark:bg-slate-800 rounded text-xs overflow-x-auto">${escapeHtml(JSON.stringify(error.context, null, 2))}</pre>
                                    </details>
                                ` : ''}
                                ${error.trace ? `
                                    <details class="text-xs">
                                        <summary class="cursor-pointer text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200">Stack trace</summary>
                                        <pre class="mt-1 p-2 bg-slate-100 dark:bg-slate-800 rounded text-xs overflow-x-auto max-h-40 overflow-y-auto">${escapeHtml(error.trace)}</pre>
                                    </details>
                                ` : ''}
                                ${error.ip ? `<p class="text-xs text-slate-500 dark:text-slate-400">IP: ${escapeHtml(error.ip)}</p>` : ''}
                            </div>
                        `;
                    }
                    
                    return `
                        <div class="p-4 border-b border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer" onclick="markErrorAsRead(${error.id})">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="px-2 py-0.5 text-xs rounded ${error.level === 'error' ? 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400' : 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400'}">
                                            ${error.level}
                                        </span>
                                        <span class="text-xs text-slate-500 dark:text-slate-400">${error.created_at}</span>
                                        ${isDebugMode ? '<span class="px-1.5 py-0.5 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded">DEBUG</span>' : ''}
                                    </div>
                                    <p class="text-sm font-medium text-slate-900 dark:text-white mb-1">${escapeHtml(error.message)}</p>
                                    ${error.file ? `<p class="text-xs text-slate-500 dark:text-slate-400 font-mono">${escapeHtml(error.file)}:${error.line}</p>` : ''}
                                    ${error.url ? `<p class="text-xs text-slate-500 dark:text-slate-400 mt-1">${error.method} ${escapeHtml(error.url)}</p>` : ''}
                                    ${detailsHtml}
                                </div>
                                <button onclick="event.stopPropagation(); markErrorAsRead(${error.id})" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    `;
                }).join('');
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Démarrer le polling toutes les 5 secondes
            function startErrorPolling() {
                fetchErrors(); // Premier appel immédiat
                errorPollInterval = setInterval(fetchErrors, 5000);
            }

            // Démarrer le polling au chargement de la page
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', startErrorPolling);
            } else {
                startErrorPolling();
            }

            // Arrêter le polling quand la page est cachée
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    if (errorPollInterval) {
                        clearInterval(errorPollInterval);
                        errorPollInterval = null;
                    }
                } else {
                    if (!errorPollInterval) {
                        startErrorPolling();
                    }
                }
            });
        </script>
        @endif

        <!-- Modale de modification de réservation -->
        <div id="modify-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Modifier la réservation</h3>
                    <button onclick="closeModifyModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="modify-form" method="POST" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Date *</label>
                            <input 
                                type="date" 
                                name="date_reservation" 
                                id="modify-date"
                                required
                                min="{{ date('Y-m-d') }}"
                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Heure *</label>
                            <input 
                                type="time" 
                                name="heure_reservation" 
                                id="modify-heure"
                                required
                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            >
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Lieu (optionnel)</label>
                        <input 
                            type="text" 
                            name="lieu" 
                            id="modify-lieu"
                            placeholder="Adresse du rendez-vous"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Notes (optionnel)</label>
                        <textarea 
                            name="notes" 
                            id="modify-notes"
                            rows="3"
                            placeholder="Informations complémentaires..."
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white resize-none"
                        ></textarea>
                    </div>
                    
                    <div class="flex gap-3 pt-4">
                        <button 
                            type="button"
                            onclick="closeModifyModal()"
                            class="flex-1 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-lg transition"
                        >
                            Annuler
                        </button>
                        <button 
                            type="submit"
                            class="flex-1 px-4 py-2 text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition"
                        >
                            Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openModifyModal(reservationId, date, heure, lieu, notes) {
                document.getElementById('modify-form').action = `/dashboard/reservation/${reservationId}/modify`;
                document.getElementById('modify-date').value = date;
                document.getElementById('modify-heure').value = heure;
                document.getElementById('modify-lieu').value = lieu || '';
                document.getElementById('modify-notes').value = notes || '';
                document.getElementById('modify-modal').classList.remove('hidden');
            }

            function closeModifyModal() {
                document.getElementById('modify-modal').classList.add('hidden');
            }

            // Fermer la modale en cliquant en dehors
            document.getElementById('modify-modal')?.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModifyModal();
                }
            });
        </script>
    </body>
</html>

