<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Allo Tata - Plateforme de gestion pour entrepreneurs</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            @keyframes gradient-flow {
                0% {
                    background-position: 0% 50%;
                }
                50% {
                    background-position: 100% 50%;
                }
                100% {
                    background-position: 0% 50%;
                }
            }
            .animate-gradient {
                background: linear-gradient(90deg, #22c55e, #4ade80, #f97316, #fb923c, #f97316, #4ade80, #22c55e);
                background-size: 200% 200%;
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
                animation: gradient-flow 4s ease-in-out infinite;
            }
        </style>
        @include('partials.theme-script')
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
        <!-- Messages de session -->
        @if(session('success'))
            <div class="fixed top-16 left-1/2 transform -translate-x-1/2 z-50 max-w-md w-full mx-4">
                <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg shadow-lg">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-sm text-green-800 dark:text-green-300">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
            <script>
                setTimeout(() => {
                    document.querySelector('.fixed.top-16').style.display = 'none';
                }, 5000);
            </script>
        @endif

        @if(session('error'))
            <div class="fixed top-16 left-1/2 transform -translate-x-1/2 z-50 max-w-md w-full mx-4">
                <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg shadow-lg">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-sm text-red-800 dark:text-red-300">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
            <script>
                setTimeout(() => {
                    document.querySelector('.fixed.top-16').style.display = 'none';
                }, 5000);
            </script>
        @endif

        <!-- Navigation -->
        <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                            Allo Tata
                        </h1>
                    </div>
                    <div class="flex items-center gap-4">
            @if (Route::has('login'))
                    @auth
                                <a href="{{ url('/dashboard') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                            Dashboard
                        </a>
                    @else
                                <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                                    Connexion
                                </a>
                                @if (Route::has('signup'))
                                    <a href="{{ route('signup') }}" class="px-4 py-2 text-sm font-medium bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white rounded-lg transition">
                                        Inscription
                            </a>
                        @endif
                    @endauth
            @endif
                        <button 
                            id="theme-toggle"
                            class="p-2 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors"
                            aria-label="Basculer le thème"
                        >
                            <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                                    </svg>
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="pt-32 pb-20 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="text-center">
                    <h1 class="text-5xl sm:text-6xl lg:text-7xl font-bold mb-6">
                        <span class="block text-slate-900 dark:text-white">Concentrez-vous sur l'essentiel,</span>
                        <span class="block animate-gradient">
                            Allo Tata
                            </span>
                        <span class="block text-slate-900 dark:text-white">simplifie votre quotidien artisanal.</span>
                    </h1>
                    <p class="text-xl sm:text-2xl text-slate-600 dark:text-slate-400 max-w-3xl mx-auto mb-10">
                        Allo Tata est la plateforme tout-en-un pour gérer votre agenda, votre clientèle, vos finances et bien plus encore. 
                        Conçue spécialement pour les micro-entreprises.
                    </p>
                    
                    <!-- Zone de recherche -->
                    <div class="max-w-3xl mx-auto mb-10">
                        <form action="{{ route('search') }}" method="GET" class="relative" id="search-form">
                            <div class="relative">
                                <input 
                                    type="text" 
                                    name="q" 
                                    id="search-input-home"
                                    value="{{ request('q') }}"
                                    placeholder="Rechercher une entreprise, un service, une ville..." 
                                    autocomplete="off"
                                    class="w-full px-6 py-4 pl-14 pr-32 text-lg bg-white dark:bg-slate-800 border-2 border-slate-300 dark:border-slate-600 rounded-2xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500"
                                >
                                <svg class="absolute left-5 top-1/2 transform -translate-y-1/2 w-6 h-6 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                <button 
                                    type="submit"
                                    class="absolute right-2 top-1/2 transform -translate-y-1/2 px-6 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-xl transition-all"
                                >
                                    Rechercher
                                </button>
                            </div>
                            <!-- Résultats en temps réel -->
                            <div id="autocomplete-results-home" class="hidden absolute top-full left-0 right-0 mt-2 bg-white dark:bg-slate-800 border-2 border-slate-300 dark:border-slate-600 rounded-2xl shadow-2xl z-50 max-h-96 overflow-y-auto">
                                <div id="autocomplete-list-home" class="p-2"></div>
                            </div>
                        </form>
                        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">
                            Recherchez par nom d'entreprise, type de service, ville, mots-clés, services proposés...
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        @auth
                            <a href="{{ route('dashboard') }}" class="px-8 py-4 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-1">
                                Accéder au dashboard
                            </a>
                        @else
                            @if (Route::has('signup'))
                                <a href="{{ route('signup') }}" class="px-8 py-4 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-1">
                                    Commencer gratuitement
                                </a>
                            @endif
                        @endauth
                        <a href="#fonctionnalites" class="px-8 py-4 bg-white dark:bg-slate-800 border-2 border-slate-300 dark:border-slate-600 text-slate-900 dark:text-white font-semibold rounded-xl hover:border-green-500 dark:hover:border-green-500 transition-all">
                            Découvrir les fonctionnalités
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="fonctionnalites" class="py-20 px-4 sm:px-6 lg:px-8 bg-white dark:bg-slate-800">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-16">
                    <h2 class="text-4xl sm:text-5xl font-bold mb-4 text-slate-900 dark:text-white">
                        Tout ce dont vous avez besoin
                    </h2>
                    <p class="text-xl text-slate-600 dark:text-slate-400">
                        Une plateforme complète pour gérer votre entreprise en toute simplicité
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <!-- Agenda -->
                    <div class="p-6 bg-gradient-to-br from-green-50 to-green-100/50 dark:from-green-900/20 dark:to-green-800/10 rounded-2xl border border-green-200 dark:border-green-800 hover:shadow-xl transition-all transform hover:-translate-y-2">
                        <div class="w-14 h-14 bg-green-500 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-2 text-slate-900 dark:text-white">Gestion d'Agenda</h3>
                        <p class="text-slate-600 dark:text-slate-400">
                            Organisez vos rendez-vous, gérez vos créneaux disponibles et ne manquez plus jamais un client.
                        </p>
                    </div>

                    <!-- Clientèle -->
                    <div class="p-6 bg-gradient-to-br from-orange-50 to-orange-100/50 dark:from-orange-900/20 dark:to-orange-800/10 rounded-2xl border border-orange-200 dark:border-orange-800 hover:shadow-xl transition-all transform hover:-translate-y-2">
                        <div class="w-14 h-14 bg-orange-500 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-2 text-slate-900 dark:text-white">Gestion de Clientèle</h3>
                        <p class="text-slate-600 dark:text-slate-400">
                            Suivez vos clients, leurs préférences et leur historique pour offrir un service personnalisé.
                        </p>
                    </div>

                    <!-- Finances -->
                    <div class="p-6 bg-gradient-to-br from-green-50 to-green-100/50 dark:from-green-900/20 dark:to-green-800/10 rounded-2xl border border-green-200 dark:border-green-800 hover:shadow-xl transition-all transform hover:-translate-y-2">
                        <div class="w-14 h-14 bg-green-500 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-2 text-slate-900 dark:text-white">Gestion Financière</h3>
                        <p class="text-slate-600 dark:text-slate-400">
                            Suivez vos revenus, vos dépenses et analysez votre rentabilité avec des rapports détaillés.
                        </p>
                    </div>

                    <!-- Dashboard -->
                    <div class="p-6 bg-gradient-to-br from-orange-50 to-orange-100/50 dark:from-orange-900/20 dark:to-orange-800/10 rounded-2xl border border-orange-200 dark:border-orange-800 hover:shadow-xl transition-all transform hover:-translate-y-2">
                        <div class="w-14 h-14 bg-orange-500 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-2 text-slate-900 dark:text-white">Dashboard Complet</h3>
                        <p class="text-slate-600 dark:text-slate-400">
                            Visualisez toutes vos données importantes en un seul endroit pour prendre les meilleures décisions.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Mini Dashboard ou CTA Section -->
        @auth
            <section class="py-20 px-4 sm:px-6 lg:px-8 bg-white dark:bg-slate-800">
                <div class="max-w-6xl mx-auto">
                    <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-8 text-center">
                        Votre tableau de bord
                    </h2>
                    
                    <div class="grid grid-cols-1 {{ ($user->est_client && isset($miniStats['client']) && $user->est_gerant && isset($miniStats['gerant'])) ? 'md:grid-cols-2' : 'max-w-2xl mx-auto' }} gap-6">
                        @if($user->est_client && isset($miniStats['client']))
                            <!-- Section Client -->
                            <div class="bg-gradient-to-br from-green-50 to-green-100/50 dark:from-green-900/20 dark:to-green-800/10 rounded-2xl border border-green-200 dark:border-green-800 p-6">
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Client</h3>
                                </div>
                                
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between pb-3 border-b border-green-200 dark:border-green-800">
                                        <span class="text-slate-600 dark:text-slate-400">Réservations totales</span>
                                        <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ $miniStats['client']['total_reservations'] }}</span>
                                    </div>
                                    <div class="flex items-center justify-between pb-3 border-b border-green-200 dark:border-green-800">
                                        <span class="text-slate-600 dark:text-slate-400">En attente</span>
                                        <span class="text-xl font-semibold text-yellow-600 dark:text-yellow-400">{{ $miniStats['client']['reservations_en_attente'] }}</span>
                                    </div>
                                    <div class="flex items-center justify-between pb-3 border-b border-green-200 dark:border-green-800">
                                        <span class="text-slate-600 dark:text-slate-400">Confirmées</span>
                                        <span class="text-xl font-semibold text-green-600 dark:text-green-400">{{ $miniStats['client']['reservations_confirmees'] }}</span>
                                    </div>
                                    <div class="flex items-center justify-between pb-3 border-b border-green-200 dark:border-green-800">
                                        <span class="text-slate-600 dark:text-slate-400">Terminées</span>
                                        <span class="text-xl font-semibold text-blue-600 dark:text-blue-400">{{ $miniStats['client']['reservations_terminees'] }}</span>
                                    </div>
                                    <div class="flex items-center justify-between pb-3 border-b border-green-200 dark:border-green-800">
                                        <span class="text-slate-600 dark:text-slate-400">Ce mois</span>
                                        <span class="text-xl font-semibold text-slate-900 dark:text-white">{{ $miniStats['client']['reservations_ce_mois'] }}</span>
                                    </div>
                                    <div class="flex items-center justify-between pt-2">
                                        <span class="text-slate-600 dark:text-slate-400 font-medium">Total dépensé</span>
                                        <span class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($miniStats['client']['total_depense'], 2, ',', ' ') }} €</span>
                                    </div>
                                </div>
                                
                                <a href="{{ route('dashboard') }}" class="mt-6 block text-center px-4 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white font-semibold rounded-lg transition">
                                    Voir mes réservations
                                </a>
                            </div>
                        @endif

                        @if($user->est_gerant && isset($miniStats['gerant']))
                            <!-- Section Gérant -->
                            <div class="bg-gradient-to-br from-orange-50 to-orange-100/50 dark:from-orange-900/20 dark:to-orange-800/10 rounded-2xl border border-orange-200 dark:border-orange-800 p-6">
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="w-12 h-12 bg-orange-500 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Gérant</h3>
                                </div>
                                
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between pb-3 border-b border-orange-200 dark:border-orange-800">
                                        <span class="text-slate-600 dark:text-slate-400">Entreprises</span>
                                        <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ $miniStats['gerant']['total_entreprises'] }}</span>
                                    </div>
                                    <div class="flex items-center justify-between pb-3 border-b border-orange-200 dark:border-orange-800">
                                        <span class="text-slate-600 dark:text-slate-400">Réservations totales</span>
                                        <span class="text-xl font-semibold text-slate-900 dark:text-white">{{ $miniStats['gerant']['total_reservations'] }}</span>
                                    </div>
                                    <div class="flex items-center justify-between pb-3 border-b border-orange-200 dark:border-orange-800">
                                        <span class="text-slate-600 dark:text-slate-400">Réservations ce mois</span>
                                        <span class="text-xl font-semibold text-orange-600 dark:text-orange-400">{{ $miniStats['gerant']['reservations_ce_mois'] }}</span>
                                    </div>
                                    <div class="flex items-center justify-between pb-3 border-b border-orange-200 dark:border-orange-800">
                                        <span class="text-slate-600 dark:text-slate-400">Revenu payé</span>
                                        <span class="text-xl font-semibold text-blue-600 dark:text-blue-400">{{ number_format($miniStats['gerant']['revenu_paye'], 2, ',', ' ') }} €</span>
                                    </div>
                                    <div class="flex items-center justify-between pt-2">
                                        <span class="text-slate-600 dark:text-slate-400 font-medium">Revenu total</span>
                                        <span class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($miniStats['gerant']['revenu_total'], 2, ',', ' ') }} €</span>
                                    </div>
                                </div>
                                
                                <a href="{{ route('dashboard') }}" class="mt-6 block text-center px-4 py-2 bg-orange-600 hover:bg-orange-700 dark:bg-orange-500 dark:hover:bg-orange-600 text-white font-semibold rounded-lg transition">
                                    Gérer mes entreprises
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        @else
            <!-- CTA Section pour les non connectés -->
            <section class="py-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-r from-green-600 to-orange-500">
                <div class="max-w-4xl mx-auto text-center">
                    <h2 class="text-4xl sm:text-5xl font-bold text-white mb-6">
                        Prêt à transformer votre activité ?
                    </h2>
                    <p class="text-xl text-white/90 mb-10">
                        Rejoignez des centaines d'entrepreneurs qui font confiance à Allo Tata pour gérer leur entreprise.
                    </p>
                    @if (Route::has('signup'))
                        <a href="{{ route('signup') }}" class="inline-block px-8 py-4 bg-white text-green-600 font-bold rounded-xl shadow-2xl hover:shadow-3xl transition-all transform hover:-translate-y-1 hover:scale-105">
                            Créer mon compte gratuitement
                        </a>
                    @endif
                </div>
            </section>
        @endauth

        @include('partials.footer')
        @include('partials.cookie-banner')

        <script>
            // Recherche en temps réel
            (function() {
                const searchInput = document.getElementById('search-input-home');
                const resultsContainer = document.getElementById('autocomplete-results-home');
                const resultsList = document.getElementById('autocomplete-list-home');
                let searchTimeout;
                let currentRequest = null;

                if (!searchInput) return;

                searchInput.addEventListener('input', function() {
                    const query = this.value.trim();

                    // Annuler la requête précédente si elle existe
                    if (currentRequest) {
                        currentRequest.abort();
                    }

                    // Masquer les résultats si la requête est trop courte
                    if (query.length < 2) {
                        resultsContainer.classList.add('hidden');
                        return;
                    }

                    // Délai pour éviter trop de requêtes
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        fetch(`{{ route('search.autocomplete') }}?q=${encodeURIComponent(query)}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.length === 0) {
                                    resultsList.innerHTML = '<div class="p-4 text-center text-slate-500 dark:text-slate-400">Aucun résultat trouvé</div>';
                                } else {
                                    resultsList.innerHTML = data.map(entreprise => `
                                        <a href="/p/${entreprise.slug}" class="flex items-center gap-3 p-3 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg transition">
                                            ${entreprise.logo ? `<img src="${entreprise.logo}" alt="${entreprise.nom}" class="w-12 h-12 rounded-lg object-cover border border-slate-200 dark:border-slate-700">` : '<div class="w-12 h-12 rounded-lg bg-slate-200 dark:bg-slate-700"></div>'}
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <h3 class="font-semibold text-slate-900 dark:text-white truncate">${entreprise.nom}</h3>
                                                    ${!entreprise.est_verifiee ? '<span class="px-2 py-0.5 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded flex-shrink-0">⏳</span>' : ''}
                                                </div>
                                                <p class="text-sm text-green-600 dark:text-green-400">${entreprise.type_activite}</p>
                                                ${entreprise.ville ? `<p class="text-xs text-slate-500 dark:text-slate-400">${entreprise.ville}</p>` : ''}
                                                ${entreprise.services.length > 0 ? `<p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Services: ${entreprise.services.join(', ')}</p>` : ''}
                                            </div>
                                        </a>
                                    `).join('');
                                }
                                resultsContainer.classList.remove('hidden');
                            })
                            .catch(error => {
                                if (error.name !== 'AbortError') {
                                    console.error('Erreur de recherche:', error);
                                }
                            });
                    }, 300);
                });

                // Masquer les résultats quand on clique ailleurs
                document.addEventListener('click', function(e) {
                    if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
                        resultsContainer.classList.add('hidden');
                    }
                });

                // Soumettre le formulaire si on appuie sur Entrée
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        document.getElementById('search-form').submit();
                    }
                });
            })();
        </script>
    </body>
</html>
