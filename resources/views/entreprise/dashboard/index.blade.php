<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $entreprise->nom }} - Dashboard - Allo Tata</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
        @include('partials.theme-script')
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200 min-h-screen flex flex-col">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center gap-2 sm:gap-4">
                        <a href="{{ route('dashboard') }}" class="text-xl sm:text-2xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                            Allo Tata
                        </a>
                        
                        <!-- Menu Burger pour mobile web -->
                        @include('components.mobile-nav', ['navType' => 'entreprise', 'entreprise' => $entreprise, 'aGestionMultiPersonnes' => $aGestionMultiPersonnes ?? false, 'activeTab' => $activeTab ?? 'accueil'])
                        
                        <!-- Sélecteur d'entreprise -->
                        <div class="relative hidden md:block">
                            <button 
                                onclick="toggleEntrepriseSelector()"
                                class="flex items-center gap-2 px-3 py-2 bg-slate-100 dark:bg-slate-700 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition"
                            >
                                @if($entreprise->logo)
                                    <img src="{{ asset('media/' . $entreprise->logo) }}" alt="" class="w-6 h-6 rounded object-cover">
                                @else
                                    <div class="w-6 h-6 rounded bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white text-xs font-bold">
                                        {{ strtoupper(substr($entreprise->nom, 0, 1)) }}
                                    </div>
                                @endif
                                <span class="font-medium text-slate-900 dark:text-white max-w-32 truncate">{{ $entreprise->nom }}</span>
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            
                            <!-- Dropdown des entreprises -->
                            <div id="entreprise-selector" class="hidden absolute left-0 mt-2 w-64 bg-white dark:bg-slate-800 rounded-lg shadow-xl border border-slate-200 dark:border-slate-700 py-2 z-50">
                                <div class="px-3 py-2 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Mes entreprises</div>
                                <a href="{{ route('entreprise.dashboard', $entreprise->slug) }}" class="flex items-center gap-3 px-3 py-2 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400">
                                    @if($entreprise->logo)
                                        <img src="{{ asset('media/' . $entreprise->logo) }}" alt="" class="w-8 h-8 rounded object-cover">
                                    @else
                                        <div class="w-8 h-8 rounded bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white text-sm font-bold">
                                            {{ strtoupper(substr($entreprise->nom, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium truncate">{{ $entreprise->nom }}</p>
                                        <p class="text-xs text-green-600 dark:text-green-500">Entreprise actuelle</p>
                                    </div>
                                </a>
                                @foreach($autresEntreprises as $autre)
                                    <a href="{{ route('entreprise.dashboard', $autre->slug) }}" class="flex items-center gap-3 px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-900 dark:text-white transition">
                                        @if($autre->logo)
                                            <img src="{{ asset('media/' . $autre->logo) }}" alt="" class="w-8 h-8 rounded object-cover">
                                        @else
                                            <div class="w-8 h-8 rounded bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white text-sm font-bold">
                                                {{ strtoupper(substr($autre->nom, 0, 1)) }}
                                            </div>
                                        @endif
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium truncate">{{ $autre->nom }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $autre->type_activite }}</p>
                                        </div>
                                    </a>
                                @endforeach
                                <div class="border-t border-slate-200 dark:border-slate-700 mt-2 pt-2">
                                    <a href="{{ route('entreprise.create') }}" class="flex items-center gap-2 px-3 py-2 text-green-600 dark:text-green-400 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        Ajouter une entreprise
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2 sm:gap-3 desktop-nav-links">
                        <a href="{{ route('public.entreprise', $entreprise->slug) }}" target="_blank" class="hidden lg:flex items-center px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 transition touch-target">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                            <span class="hidden xl:inline ml-1">Page publique</span>
                        </a>
                        <a href="{{ route('tickets.create') }}" class="hidden lg:inline-flex items-center px-3 py-2 text-sm font-medium bg-purple-100 dark:bg-purple-900/30 hover:bg-purple-200 dark:hover:bg-purple-900/50 text-purple-800 dark:text-purple-400 rounded-lg transition touch-target">
                            🎫 <span class="hidden xl:inline ml-1">Support</span>
                        </a>
                        <a href="{{ route('dashboard') }}" class="hidden xl:inline-flex items-center px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 transition touch-target">
                            Mon compte
                        </a>
                        <span class="hidden xl:inline text-sm text-slate-500 dark:text-slate-400">{{ $user->name }}</span>
                        <form method="POST" action="{{ route('logout') }}" class="hidden xl:inline">
                            @csrf
                            <button type="submit" class="px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-red-600 dark:hover:text-red-400 transition touch-target">
                                Déconnexion
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl 2xl:max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 main-content flex-1 w-full">
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

            <!-- En-tête de l'entreprise -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-6 mb-4 sm:mb-6">
                <div class="flex items-center gap-3 sm:gap-4">
                    @if($entreprise->logo)
                        <img src="{{ asset('media/' . $entreprise->logo) }}" alt="{{ $entreprise->nom }}" class="w-16 h-16 rounded-xl object-cover border-2 border-slate-200 dark:border-slate-700">
                    @else
                        <div class="w-16 h-16 rounded-xl bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white text-2xl font-bold">
                            {{ strtoupper(substr($entreprise->nom, 0, 1)) }}
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white truncate">{{ $entreprise->nom }}</h1>
                            @if($entreprise->est_verifiee)
                                <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full">Vérifiée</span>
                            @else
                                <span class="px-2 py-1 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded-full">En attente</span>
                            @endif
                        </div>
                        <p class="text-slate-600 dark:text-slate-400">{{ $entreprise->type_activite }} @if($entreprise->ville) • {{ $entreprise->ville }} @endif</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($stats['nombre_avis'] > 0)
                            <div class="text-right">
                                <div class="flex items-center gap-1 text-yellow-500">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                    <span class="font-bold">{{ $stats['note_moyenne'] }}</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $stats['nombre_avis'] }} avis</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Layout avec Sidebar -->
            <div class="flex gap-6">
                <!-- Sidebar Navigation (hidden on mobile, icons only on tablet, full on desktop) -->
                <aside class="hidden md:flex flex-col w-16 xl:w-64 flex-shrink-0 sticky top-20 self-start h-[calc(100vh-6rem)] overflow-y-auto">
                    <nav class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-2 xl:p-3 space-y-1">
                        <!-- Accueil -->
                        <button 
                            onclick="showTab('accueil')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative {{ $activeTab === 'accueil' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}"
                            data-tab="accueil"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            <span class="hidden xl:inline">Accueil</span>
                            @if($stats['reservations_en_attente'] > 0)
                                <span class="xl:ml-auto px-2 py-0.5 text-xs bg-yellow-500 text-white rounded-full">{{ $stats['reservations_en_attente'] }}</span>
                            @endif
                            <!-- Tooltip for tablet -->
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Accueil</span>
                        </button>

                        <!-- Agenda -->
                        <button 
                            onclick="showTab('agenda')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative {{ $activeTab === 'agenda' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}"
                            data-tab="agenda"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span class="hidden xl:inline">Agenda</span>
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Agenda</span>
                        </button>

                        <!-- Services -->
                        <button 
                            onclick="showTab('mes-services')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative {{ $activeTab === 'services' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}"
                            data-tab="mes-services"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                            <span class="hidden xl:inline">Services</span>
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Services</span>
                        </button>

                        <!-- Stock -->
                        <button 
                            onclick="showTab('stock')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative {{ $activeTab === 'stock' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}"
                            data-tab="stock"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            <span class="hidden xl:inline">Stock</span>
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Stock</span>
                        </button>

                        @if($aGestionMultiPersonnes)
                        <!-- Équipe -->
                        <button 
                            onclick="showTab('equipe')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative {{ $activeTab === 'equipe' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}"
                            data-tab="equipe"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <span class="hidden xl:inline">Équipe</span>
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Équipe</span>
                        </button>
                        @endif

                        <!-- Réservations -->
                        <button 
                            onclick="showTab('reservations')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative {{ $activeTab === 'reservations' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}"
                            data-tab="reservations"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <span class="hidden xl:inline">Réservations</span>
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Réservations</span>
                        </button>

                        <!-- Factures -->
                        <button 
                            onclick="showTab('factures')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative {{ $activeTab === 'factures' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}"
                            data-tab="factures"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="hidden xl:inline">Factures</span>
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Factures</span>
                        </button>

                        <!-- Recettes (NOUVEAU) -->
                        <button 
                            onclick="showTab('finances')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative {{ $activeTab === 'finances' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}"
                            data-tab="finances"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="hidden xl:inline text-green-600 dark:text-green-400 font-bold italic">Recettes</span>
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Recettes</span>
                        </button>

                        <!-- Outils -->
                        <button 
                            onclick="showTab('outils')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative {{ $activeTab === 'outils' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}"
                            data-tab="outils"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                            </svg>
                            <span class="hidden xl:inline">Outils</span>
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Outils</span>
                        </button>

                        <!-- Messagerie -->
                        <button 
                            onclick="showTab('messagerie')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative {{ $activeTab === 'messagerie' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}"
                            data-tab="messagerie"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                            <span class="hidden xl:inline">Messagerie</span>
                            @php
                                $messagesNonLus = $conversations->sum(function($c) use ($user) {
                                    return $c->messagesNonLus($user->id);
                                });
                            @endphp
                            @if($messagesNonLus > 0)
                                <span class="xl:ml-auto px-2 py-0.5 text-xs bg-green-500 text-white rounded-full">{{ $messagesNonLus }}</span>
                            @endif
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Messagerie</span>
                        </button>

                        <div class="my-2 border-t border-slate-200 dark:border-slate-700"></div>

                        <!-- Abonnements -->
                        <button 
                            onclick="showTab('abonnements')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative {{ $activeTab === 'abonnements' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}"
                            data-tab="abonnements"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                            <span class="hidden xl:inline">Abonnements</span>
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Abonnements</span>
                        </button>

                        <!-- Paramètres -->
                        <button 
                            onclick="showTab('parametres')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative {{ $activeTab === 'parametres' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}"
                            data-tab="parametres"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span class="hidden xl:inline">Paramètres</span>
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Paramètres</span>
                        </button>
                    </nav>
                </aside>

                <!-- Main Content Area -->
                <main class="flex-1 min-w-0">
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-6">
                        <!-- Onglet Accueil -->
                        <div id="tab-accueil" class="tab-content {{ $activeTab !== 'accueil' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.accueil')
                        </div>

                        <!-- Onglet Agenda -->
                        <div id="tab-agenda" class="tab-content {{ $activeTab !== 'agenda' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.agenda')
                        </div>

                        <!-- Onglet Équipe (multi-personnes) -->
                        @if($aGestionMultiPersonnes)
                            <div id="tab-equipe" class="tab-content {{ $activeTab !== 'equipe' ? 'hidden' : '' }}">
                                @include('entreprise.dashboard.tabs.equipe')
                            </div>
                        @endif

                        <!-- Onglet Réservations -->
                        <div id="tab-reservations" class="tab-content {{ $activeTab !== 'reservations' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.reservations')
                        </div>

                        <!-- Onglet Factures -->
                        <div id="tab-factures" class="tab-content {{ $activeTab !== 'factures' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.factures')
                        </div>

                        <!-- Onglet Finances (Recettes) -->
                        <div id="tab-finances" class="tab-content {{ $activeTab !== 'finances' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.finances')
                        </div>

                        <!-- Onglet Outils -->
                        <div id="tab-outils" class="tab-content {{ $activeTab !== 'outils' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.outils')
                        </div>

                        <!-- Onglet Messagerie -->
                        <div id="tab-messagerie" class="tab-content {{ $activeTab !== 'messagerie' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.messagerie-liste')
                        </div>

                        <!-- Onglet Abonnements -->
                        <div id="tab-abonnements" class="tab-content {{ $activeTab !== 'abonnements' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.abonnements')
                        </div>

                        <!-- Onglet Services -->
                        <div id="tab-mes-services" class="tab-content {{ $activeTab !== 'services' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.services')
                        </div>

                        <!-- Onglet Stock -->
                        <div id="tab-stock" class="tab-content {{ $activeTab !== 'stock' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.stock')
                        </div>
                        
                        <!-- Onglet Paramètres -->
                        <div id="tab-parametres" class="tab-content {{ $activeTab !== 'parametres' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.parametres')
                        </div>
                    </div>
                </main>
            </div>
        </div>

        <script>
            // Gestion des onglets
            function showTab(tabName) {
                // Masquer tous les contenus
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.add('hidden');
                });

                // Réinitialiser tous les boutons de la sidebar
                document.querySelectorAll('.sidebar-tab').forEach(button => {
                    button.classList.remove('bg-green-100', 'dark:bg-green-900/30', 'text-green-700', 'dark:text-green-400');
                    button.classList.add('text-slate-600', 'dark:text-slate-400');
                });

                // Afficher le contenu sélectionné
                const tabContent = document.getElementById('tab-' + tabName);
                if (tabContent) {
                    tabContent.classList.remove('hidden');
                }

                // Activer le bouton sélectionné
                const activeButtons = document.querySelectorAll(`[data-tab="${tabName}"]`);
                activeButtons.forEach(button => {
                    button.classList.remove('text-slate-600', 'dark:text-slate-400');
                    button.classList.add('bg-green-100', 'dark:bg-green-900/30', 'text-green-700', 'dark:text-green-400');
                });

                // Mettre à jour l'URL sans recharger la page
                const url = new URL(window.location);
                url.searchParams.set('tab', tabName);
                window.history.replaceState({}, '', url);

                // Initialiser le calendrier si on affiche l'onglet agenda
                if (tabName === 'agenda' && typeof initCalendar === 'function') {
                    setTimeout(initCalendar, 100);
                }
            }

            // Sélecteur d'entreprise
            function toggleEntrepriseSelector() {
                const selector = document.getElementById('entreprise-selector');
                selector.classList.toggle('hidden');
            }

            // Fermer le sélecteur quand on clique ailleurs
            document.addEventListener('click', function(e) {
                const selector = document.getElementById('entreprise-selector');
                const button = e.target.closest('button');
                if (!e.target.closest('#entreprise-selector') && (!button || !button.onclick?.toString().includes('toggleEntrepriseSelector'))) {
                    selector?.classList.add('hidden');
                }
            });

            // Afficher l'onglet depuis l'URL
            const urlParams = new URLSearchParams(window.location.search);
            let tab = urlParams.get('tab') || '{{ $activeTab }}';
            
            // Correction pour l'onglet services
            if (tab === 'services') tab = 'mes-services';
            
            if (tab) {
                showTab(tab);
            }
        </script>

        <!-- Modal Services (Déplacé ici pour éviter les problèmes de z-index/transform) -->
        @include('entreprise.dashboard.tabs.services-modal-content')

        @include('partials.footer')
        @include('partials.cookie-banner')
    </body>
</html>
