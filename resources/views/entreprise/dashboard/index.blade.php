<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <script>
            // Configuration Reverb pour la présence en temps réel
            window.REVERB_APP_ID = '{{ env("REVERB_APP_ID", "reverb-app") }}';
            window.REVERB_APP_KEY = '{{ env("REVERB_APP_KEY", "reverb-key") }}';
            window.REVERB_HOST = '{{ env("REVERB_HOST", "127.0.0.2") }}';
            window.REVERB_PORT = '{{ env("REVERB_PORT", "8080") }}';
            window.REVERB_SCHEME = '{{ env("REVERB_SCHEME", "http") }}';
            window.currentUserId = {{ auth()->id() ?? 'null' }};
        </script>
        <title>{{ $entreprise->nom }} - Dashboard - Allo Tata</title>
        @include('partials.favicon')
        
        <!-- PWA / Manifest Dynamique -->
        <link rel="manifest" href="{{ route('manifest.show', $entreprise->slug) }}">
        <meta name="theme-color" content="#0f172a">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="{{ $entreprise->nom }}">
        <link rel="apple-touch-icon" href="{{ route('manifest.icon', ['slug' => $entreprise->slug, 'size' => 192]) }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
        @include('partials.theme-script')
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js');
                });
            }
        </script>
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200 min-h-screen flex flex-col">
        @include('partials.super-user-banner')
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
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                            </svg>
                            <span class="hidden xl:inline ml-1">Support</span>
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

                        <!-- Commandes Produits -->
                        <button 
                            onclick="showTab('commandes')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative {{ $activeTab === 'commandes' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}"
                            data-tab="commandes"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span class="hidden xl:inline">Commandes</span>
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Commandes</span>
                            @if(isset($commandesEnAttente) && $commandesEnAttente > 0)
                                <span class="ml-auto px-2 py-0.5 text-xs font-bold bg-red-500 text-white rounded-full">{{ $commandesEnAttente }}</span>
                            @endif
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

                        <!-- Statistiques -->
                        <button 
                            onclick="showTab('statistiques')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative {{ $activeTab === 'statistiques' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}"
                            data-tab="statistiques"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <span class="hidden xl:inline">Statistiques</span>
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Statistiques</span>
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

                        <!-- Fidélisation -->
                        <button 
                            onclick="showTab('fidelisation')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative {{ $activeTab === 'fidelisation' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}"
                            data-tab="fidelisation"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                            </svg>
                            <span class="hidden xl:inline">Fidélisation</span>
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Fidélisation</span>
                        </button>

                        <!-- Site Web Vitrine -->
                        @if($aSiteWebActif)
                            <a 
                                href="{{ route('site-web.show', $entreprise->slug_web ?? $entreprise->slug) }}?mode=edit"
                                class="w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 text-blue-700 dark:text-blue-400 hover:from-blue-100 hover:to-indigo-100 dark:hover:from-blue-900/30 dark:hover:to-indigo-900/30 border border-blue-200/50 dark:border-blue-800/50"
                            >
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                                </svg>
                                <span class="hidden xl:inline font-semibold">Site Web</span>
                                <svg class="hidden xl:inline w-4 h-4 ml-auto text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                                <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Site Web</span>
                            </a>
                        @else
                            <button 
                                onclick="document.getElementById('site-web-upsell-overlay').classList.remove('hidden')"
                                class="w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative text-slate-400 dark:text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-600 dark:hover:text-slate-300"
                            >
                                <div class="relative flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                                    </svg>
                                    <svg class="w-3 h-3 absolute -bottom-0.5 -right-0.5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <span class="hidden xl:inline">Site Web</span>
                                <svg class="hidden xl:inline w-3.5 h-3.5 ml-auto text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Site Web (verrouillé)</span>
                            </button>
                        @endif

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

                        <!-- Installer -->
                        <button 
                            onclick="showTab('installer')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative {{ $activeTab === 'installer' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}"
                            data-tab="installer"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            <span class="hidden xl:inline">Installer</span>
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Installer</span>
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

                        <!-- Onglet Statistiques -->
                        <div id="tab-statistiques" class="tab-content {{ $activeTab !== 'statistiques' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.statistiques', [
                                'entreprise' => $entreprise,
                                'stats' => $statsStatistiques ?? [
                                    'total_visites' => 0,
                                    'visites_exploration' => 0,
                                    'visites_rapides' => 0,
                                    'reservations' => 0,
                                    'taux_conversion' => 0,
                                    'temps_moyen_avant_reservation' => 0,
                                    'evolution_visites' => [],
                                    'repartition_pages' => ['accueil' => 0, 'agenda' => 0, 'store' => 0, 'services' => 0, 'produits' => 0],
                                    'temps_moyen_par_page' => ['accueil' => 0, 'agenda' => 0, 'store' => 0, 'services' => 0, 'produits' => 0],
                                    'taux_rebond' => 0
                                ],
                                'visiteursSansReservation' => $visiteursSansReservation ?? collect([]),
                                'topServices' => $topServices ?? [],
                                'topProduits' => $topProduits ?? []
                            ])
                        </div>

                        <!-- Onglet Outils -->
                        <div id="tab-outils" class="tab-content {{ $activeTab !== 'outils' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.outils')
                        </div>

                        <!-- Onglet Messagerie -->
                        <div id="tab-messagerie" class="tab-content {{ $activeTab !== 'messagerie' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.messagerie-liste')
                        </div>

                        <!-- Onglet Fidélisation -->
                        <div id="tab-fidelisation" class="tab-content {{ $activeTab !== 'fidelisation' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.fidelisation')
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

                        <!-- Onglet Commandes Produits -->
                        <div id="tab-commandes" class="tab-content {{ $activeTab !== 'commandes' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.commandes')
                        </div>
                        
                        <!-- Onglet Paramètres -->
                        <div id="tab-parametres" class="tab-content {{ $activeTab !== 'parametres' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.parametres')
                        </div>
                        
                        <!-- Onglet Installer -->
                        <div id="tab-installer" class="tab-content {{ $activeTab !== 'installer' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.installer')
                        </div>
                    </div>
                </main>
            </div>
        </div>

        <script>
            // Gestion des onglets
            async function showTab(tabName) {
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
                    
            // Recharger le contenu via Ajax (surtout pour les stocks)
            // Liste des onglets qui peuvent être rechargés
            // Exclure mes-services et services de reloadableTabs car la modal est incluse dans index.blade.php
            // et recharger l'onglet briserait les événements JS de la modal s'ils étaient dans l'onglet
            const reloadableTabs = ['stock', 'commandes', 'reservations', 'agenda'];
                    
                    if (reloadableTabs.includes(tabName)) {
                        // Cache pour éviter les rechargements trop fréquents (délai de 2 secondes)
                        const now = Date.now();
                        const lastReload = window.tabReloadCache = window.tabReloadCache || {};
                        const lastReloadTime = lastReload[tabName] || 0;
                        const RELOAD_DELAY = 2000; // 2 secondes
                        
                        // Ne recharger que si ça fait plus de 2 secondes depuis le dernier rechargement
                        if (now - lastReloadTime > RELOAD_DELAY) {
                            lastReload[tabName] = now;
                            
                            // Afficher un indicateur de chargement discret (petit point vert en haut à droite)
                            const existingIndicator = tabContent.querySelector('#tab-loading-indicator');
                            if (!existingIndicator) {
                                const loadingIndicator = document.createElement('div');
                                loadingIndicator.id = 'tab-loading-indicator';
                                loadingIndicator.className = 'absolute top-2 right-2 w-2 h-2 bg-green-500 rounded-full animate-pulse opacity-75';
                                loadingIndicator.style.zIndex = '1000';
                                if (getComputedStyle(tabContent).position === 'static') {
                                    tabContent.style.position = 'relative';
                                }
                                tabContent.appendChild(loadingIndicator);
                            }
                            
                            // Recharger le contenu en arrière-plan
                            fetch(`/m/{{ $entreprise->slug }}/reload-tab/${tabName}`, {
                                method: 'GET',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                },
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error(`HTTP error! status: ${response.status}`);
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.success && data.html) {
                                    // Remplacer le contenu de l'onglet
                                    tabContent.innerHTML = data.html;
                                    
                                    // Réexécuter les scripts dans le nouveau contenu
                                    const scripts = tabContent.querySelectorAll('script');
                                    scripts.forEach(oldScript => {
                                        const newScript = document.createElement('script');
                                        Array.from(oldScript.attributes).forEach(attr => {
                                            newScript.setAttribute(attr.name, attr.value);
                                        });
                                        newScript.textContent = oldScript.textContent;
                                        oldScript.parentNode.replaceChild(newScript, oldScript);
                                    });
                                    
                                    // Réinitialiser les fonctions spécifiques selon l'onglet
                                    if (tabName === 'agenda' && typeof initCalendar === 'function') {
                                        setTimeout(initCalendar, 100);
                                    }
                                    
                                    // Déclencher un événement personnalisé pour permettre aux scripts de se réinitialiser
                                    const reloadEvent = new CustomEvent('tabReloaded', { detail: { tab: tabName } });
                                    window.dispatchEvent(reloadEvent);
                                }
                            })
                            .catch(error => {
                                console.error('Erreur lors du rechargement de l\'onglet:', error);
                                // En cas d'erreur, on garde le contenu existant (silencieux)
                            })
                            .finally(() => {
                                // Retirer l'indicateur de chargement
                                const indicator = tabContent.querySelector('#tab-loading-indicator');
                                if (indicator) {
                                    indicator.remove();
                                }
                            });
                        }
                    }
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
        
        <!-- Modal Produits (Déplacé ici pour éviter les problèmes de z-index/transform) -->
        @include('entreprise.dashboard.tabs.stock-modal-content')

        <!-- Overlay Upsell Site Web Vitrine -->
        @if(!$aSiteWebActif)
        @php
            $userHasPremium = auth()->user()->aAbonnementActif();
            $peutEssayerSiteWeb = $entreprise->peutDemarrerEssai('site_web');
        @endphp
        <div id="site-web-upsell-overlay" class="hidden fixed inset-0 z-[9999] flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="document.getElementById('site-web-upsell-overlay').classList.add('hidden')"></div>
            
            <!-- Card -->
            <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 max-w-md w-full overflow-hidden">
                <!-- Header gradient -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-8 text-center">
                    <div class="w-16 h-16 mx-auto bg-white/20 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white">Votre site vitrine professionnel</h3>
                    <p class="text-blue-100 mt-2 text-sm">Donnez une vitrine en ligne a votre activite</p>
                </div>

                <!-- Content -->
                <div class="px-6 py-6 space-y-4">
                    <!-- Avertissement Premium requis -->
                    @if(!$userHasPremium)
                        <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl">
                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-400">Abonnement Premium requis</p>
                                    <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-1">Les options d'entreprise necessitent un abonnement Premium actif.</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Prix -->
                    <div class="flex items-center justify-center gap-2 bg-green-50 dark:bg-green-900/20 rounded-xl p-4 border border-green-200 dark:border-green-800">
                        <span class="text-3xl font-extrabold text-green-600 dark:text-green-400">{{ $subscriptionPrices['site_web']['formatted'] }}</span>
                        <span class="text-slate-500 dark:text-slate-400 text-sm">{{ $subscriptionPrices['site_web']['period'] ?? '/mois' }}</span>
                    </div>

                    <!-- Avantages -->
                    <ul class="space-y-2.5 text-sm">
                        <li class="flex items-start gap-2.5">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-slate-700 dark:text-slate-300">Page vitrine personnalisee accessible via /w/{{ $entreprise->slug_web ?? $entreprise->slug }}</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-slate-700 dark:text-slate-300">Visible par tous vos clients et prospects</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-slate-700 dark:text-slate-300">Agenda, reservation et contact integres</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-slate-700 dark:text-slate-300">Aucune connaissance technique requise</span>
                        </li>
                    </ul>

                    <!-- Boutons -->
                    <div class="space-y-3 pt-2">
                        @if(!$userHasPremium)
                            <!-- Pas de Premium : rediriger vers l'abonnement Premium -->
                            <a href="{{ route('subscription.index') }}" class="w-full py-3 px-4 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-xl shadow-lg transition-all duration-200 flex items-center justify-center gap-2">
                                <span>🚀</span>
                                Obtenir l'abonnement Premium
                            </a>
                        @else
                            <!-- Essai gratuit si eligible -->
                            @if($peutEssayerSiteWeb)
                                <form action="{{ route('essai-gratuit.entreprise', $entreprise->slug) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="type" value="site_web">
                                    <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-orange-500 to-yellow-500 hover:from-orange-600 hover:to-yellow-600 text-white font-semibold rounded-xl shadow-lg shadow-orange-500/25 transition-all duration-200 transform hover:scale-[1.02] flex items-center justify-center gap-2">
                                        <span class="text-lg">🎁</span>
                                        Essayer gratuitement pendant 7 jours
                                    </button>
                                </form>
                                <p class="text-xs text-center text-slate-400 dark:text-slate-500">Sans engagement &bull; Sans carte bancaire</p>
                                <div class="relative flex items-center justify-center py-1">
                                    <span class="absolute inset-x-0 h-px bg-slate-200 dark:bg-slate-700"></span>
                                    <span class="relative px-4 bg-white dark:bg-slate-800 text-xs text-slate-400 dark:text-slate-500">ou</span>
                                </div>
                            @endif

                            <!-- Bouton souscrire -->
                            <form action="{{ route('entreprise.subscriptions.checkout', $entreprise->slug) }}" method="POST">
                                @csrf
                                <input type="hidden" name="type" value="site_web">
                                <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg shadow-blue-500/25 transition-all duration-200 transform hover:scale-[1.02] flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    S'abonner a Site Web Vitrine ({{ $subscriptionPrices['site_web']['formatted'] }}{{ $subscriptionPrices['site_web']['period'] ?? '/mois' }})
                                </button>
                            </form>
                        @endif

                        <!-- Separateur -->
                        <div class="relative flex items-center justify-center py-1">
                            <span class="absolute inset-x-0 h-px bg-slate-200 dark:bg-slate-700"></span>
                            <span class="relative px-4 bg-white dark:bg-slate-800 text-xs text-slate-400 dark:text-slate-500">ou bien</span>
                        </div>

                        <!-- Bouton secondaire : Creer quand meme -->
                        <a 
                            href="{{ route('site-web.show', $entreprise->slug_web ?? $entreprise->slug) }}?mode=edit"
                            class="w-full py-2.5 px-4 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300 font-medium rounded-xl transition-all duration-200 flex items-center justify-center gap-2 text-sm"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Creer mon site quand meme
                        </a>
                        <p class="text-xs text-center text-slate-400 dark:text-slate-500">
                            Votre site ne sera pas visible publiquement tant que l'abonnement n'est pas actif.
                        </p>
                    </div>
                </div>

                <!-- Close button -->
                <button 
                    onclick="document.getElementById('site-web-upsell-overlay').classList.add('hidden')"
                    class="absolute top-3 right-3 w-8 h-8 flex items-center justify-center rounded-full bg-white/20 hover:bg-white/30 text-white transition"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        @endif

        @include('partials.footer')
        @include('partials.cookie-banner')
    </body>
</html>
