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
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200 min-h-screen flex flex-col">
        <!-- Bandeau Impersonation -->
        @if(session('original_admin_id'))
        <div class="bg-red-600 text-white px-4 py-3 flex items-center justify-between shadow-lg relative z-[100]">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                <div>
                    <span class="font-bold uppercase tracking-wider text-sm">Mode Super-User</span>
                    <span class="text-red-100 text-sm hidden sm:inline ml-2">| Connecté en tant que <strong class="text-white underline">{{ auth()->user()->name }}</strong></span>
                </div>
            </div>
            <form action="{{ route('stop-impersonating') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-1.5 bg-white dark:bg-slate-800 text-red-700 dark:text-red-300 rounded-lg text-xs sm:text-sm font-bold hover:bg-red-50 dark:hover:bg-slate-700 transition-colors uppercase tracking-wide flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    Quitter
                </button>
            </form>
        </div>
        @endif

        <!-- Navigation -->
        <nav class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
            <div class="max-w-7xl 2xl:max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
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

        @php
            $activeTab = request('tab', 'accueil');
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

        <div class="max-w-7xl 2xl:max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">
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
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Accueil</span>
                        </button>

                        <!-- Mes Entreprises -->
                        @if($user->est_gerant || $entreprises->count() > 0)
                        <button 
                            onclick="showTab('entreprises')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative {{ $activeTab === 'entreprises' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}"
                            data-tab="entreprises"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            <span class="hidden xl:inline">Mes entreprises</span>
                            @if(isset($stats['reservations_en_attente']) && $stats['reservations_en_attente'] > 0)
                                <span class="xl:ml-auto px-2 py-0.5 text-xs bg-yellow-500 text-white rounded-full">{{ $stats['reservations_en_attente'] }}</span>
                            @endif
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Mes entreprises</span>
                        </button>

                        <!-- Mes Abonnements -->
                        <button 
                            onclick="showTab('abonnements')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative {{ $activeTab === 'abonnements' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}"
                            data-tab="abonnements"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                            <span class="hidden xl:inline">Mes abonnements</span>
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Mes abonnements</span>
                        </button>
                        @endif

                        <!-- Réservations -->
                        @if($user->est_client)
                        <button 
                            onclick="showTab('reservations')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative {{ $activeTab === 'reservations' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}"
                            data-tab="reservations"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span class="hidden xl:inline">Mes réservations</span>
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Mes réservations</span>
                        </button>
                        @endif

                        <!-- Factures -->
                        <button 
                            onclick="showTab('factures')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative {{ $activeTab === 'factures' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}"
                            data-tab="factures"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="hidden xl:inline">Mes factures</span>
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Mes factures</span>
                        </button>

                        <div class="my-2 border-t border-slate-200 dark:border-slate-700"></div>

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
                            @if($nonLus > 0)
                                <span class="xl:ml-auto px-2 py-0.5 text-xs bg-green-500 text-white rounded-full">{{ $nonLus }}</span>
                            @endif
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Messagerie</span>
                        </button>

                        <!-- Notifications -->
                        <button 
                            onclick="showTab('notifications')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative {{ $activeTab === 'notifications' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}"
                            data-tab="notifications"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            <span class="hidden xl:inline">Notifications</span>
                            @if($user->nombre_notifications_non_lues > 0)
                                <span class="xl:ml-auto px-2 py-0.5 text-xs bg-orange-500 text-white rounded-full">{{ $user->nombre_notifications_non_lues }}</span>
                            @endif
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Notifications</span>
                        </button>

                        <div class="my-2 border-t border-slate-200 dark:border-slate-700"></div>

                        <!-- Support -->
                        <button 
                            onclick="showTab('support')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative {{ $activeTab === 'support' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}"
                            data-tab="support"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <span class="hidden xl:inline">Support</span>
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Support</span>
                        </button>
                    </nav>
                </aside>

                <!-- Main Content Area -->
                <main class="flex-1 min-w-0">
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-6">
                        <!-- Onglet Accueil -->
                        <div id="tab-accueil" class="tab-content {{ $activeTab !== 'accueil' ? 'hidden' : '' }}">
                            @include('dashboard.tabs.accueil')
                        </div>

                        <!-- Onglet Entreprises -->
                        <div id="tab-entreprises" class="tab-content {{ $activeTab !== 'entreprises' ? 'hidden' : '' }}">
                            @include('dashboard.tabs.entreprises')
                        </div>

                        <!-- Onglet Abonnements -->
                        <div id="tab-abonnements" class="tab-content {{ $activeTab !== 'abonnements' ? 'hidden' : '' }}">
                            @include('dashboard.tabs.abonnements')
                        </div>

                        <!-- Onglet Réservations -->
                        @if($user->est_client)
                            <div id="tab-reservations" class="tab-content {{ $activeTab !== 'reservations' ? 'hidden' : '' }}">
                                @include('dashboard.tabs.reservations')
                            </div>
                        @endif

                        <!-- Onglet Factures -->
                        <div id="tab-factures" class="tab-content {{ $activeTab !== 'factures' ? 'hidden' : '' }}">
                            @include('dashboard.tabs.factures')
                        </div>

                        <!-- Onglet Messagerie -->
                        <div id="tab-messagerie" class="tab-content {{ $activeTab !== 'messagerie' ? 'hidden' : '' }}">
                            @include('dashboard.tabs.messagerie')
                        </div>

                        <!-- Onglet Notifications -->
                        <div id="tab-notifications" class="tab-content {{ $activeTab !== 'notifications' ? 'hidden' : '' }}">
                            @include('dashboard.tabs.notifications')
                        </div>

                        <!-- Onglet Support -->
                        <div id="tab-support" class="tab-content {{ $activeTab !== 'support' ? 'hidden' : '' }}">
                            @include('dashboard.tabs.support')
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
            }
        </script>

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
    @include('partials.footer')
    @include('partials.cookie-banner')
    </body>
</html>
