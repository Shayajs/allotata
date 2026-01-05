<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Administration') - Allo Tata</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @include('partials.theme-script')
    <style>
        [x-cloak] { display: none !important; }
        /* Force browser controls (scrollbars, dates, etc.) to match the theme */
        html.dark { color-scheme: dark; }
        html { color-scheme: light; }
    </style>
    @stack('styles')
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
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
    @include('components.mobile-nav', ['navType' => 'admin', 'id' => 'admin_mobile_nav', 'hideButton' => true])

    <div class="min-h-screen flex">
        <!-- Sidebar (PC) -->
        <aside class="w-64 bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700 flex-shrink-0 hidden lg:flex flex-col sticky top-0 h-screen overflow-y-auto">
            <!-- Logo -->
            <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                <a href="{{ route('admin.index') }}" class="text-xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                    🛡️ Allo Tata Admin
                </a>
            </div>

            <!-- Navigation -->
            <nav class="p-4 space-y-1">
                <!-- Dashboard -->
                <a href="{{ route('admin.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.index') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <span class="text-lg">📊</span>
                    <span class="font-medium">Dashboard</span>
                </a>

                <div class="pt-4 pb-2">
                    <p class="px-4 text-xs font-semibold text-slate-500 dark:text-slate-500 uppercase tracking-wider">Gestion</p>
                </div>

                <!-- Utilisateurs -->
                <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.users.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <span class="text-lg">👥</span>
                    <span class="font-medium">Utilisateurs</span>
                </a>

                <!-- Entreprises -->
                <a href="{{ route('admin.entreprises.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.entreprises.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <span class="text-lg">🏢</span>
                    <span class="font-medium">Entreprises</span>
                    @php
                        $entreprisesEnAttente = \App\Models\Entreprise::where('est_verifiee', false)->count();
                    @endphp
                    @if($entreprisesEnAttente > 0)
                        <span class="ml-auto px-2 py-0.5 text-xs bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 rounded-full">{{ $entreprisesEnAttente }}</span>
                    @endif
                </a>

                <!-- Réservations -->
                <a href="{{ route('admin.reservations.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.reservations.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <span class="text-lg">📅</span>
                    <span class="font-medium">Réservations</span>
                </a>

                <!-- Finances Globales (NOUVEAU) -->
                <a href="{{ route('admin.finances.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.finances.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <span class="text-lg">💰</span>
                    <span class="font-medium">Finances Entreprises</span>
                </a>

                <div class="pt-4 pb-2">
                    <p class="px-4 text-xs font-semibold text-slate-500 dark:text-slate-500 uppercase tracking-wider">Support</p>
                </div>

                <!-- Tickets -->
                <a href="{{ route('admin.tickets.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.tickets.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <span class="text-lg">🎫</span>
                    <span class="font-medium">Tickets</span>
                    @php
                        $ticketsOuverts = \App\Models\Ticket::where('statut', 'ouvert')->count();
                    @endphp
                    @if($ticketsOuverts > 0)
                        <span class="ml-auto px-2 py-0.5 text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full">{{ $ticketsOuverts }}</span>
                    @endif
                </a>

                <!-- Contacts -->
                <a href="{{ route('admin.contacts.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.contacts.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <span class="text-lg">📬</span>
                    <span class="font-medium">Contacts</span>
                    @php
                        $contactsNonLus = \App\Models\Contact::where('est_lu', false)->count();
                    @endphp
                    @if($contactsNonLus > 0)
                        <span class="ml-auto px-2 py-0.5 text-xs bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 rounded-full">{{ $contactsNonLus }}</span>
                    @endif
                </a>

                <div class="pt-4 pb-2">
                    <p class="px-4 text-xs font-semibold text-slate-500 dark:text-slate-500 uppercase tracking-wider">Contenu</p>
                </div>

                <!-- FAQs -->
                <a href="{{ route('admin.faqs.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.faqs.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <span class="text-lg">❓</span>
                    <span class="font-medium">FAQs</span>
                </a>

                <!-- Annonces -->
                <a href="{{ route('admin.announcements.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.announcements.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <span class="text-lg">📣</span>
                    <span class="font-medium">Annonces</span>
                </a>

                <div class="pt-4 pb-2">
                    <p class="px-4 text-xs font-semibold text-slate-500 dark:text-slate-500 uppercase tracking-wider">Système</p>
                </div>

                <!-- Erreurs -->
                <a href="{{ route('admin.errors.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.errors.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <span class="text-lg">⚠️</span>
                    <span class="font-medium">Erreurs</span>
                    @php
                        $erreursNonVues = \App\Models\ErrorLog::where('est_vue', false)->count();
                    @endphp
                    @if($erreursNonVues > 0)
                        <span class="ml-auto px-2 py-0.5 text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full">{{ $erreursNonVues }}</span>
                    @endif
                </a>

                <!-- Logs d'activité -->
                <a href="{{ route('admin.activity-logs.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.activity-logs.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <span class="text-lg">📝</span>
                    <span class="font-medium">Logs d'activité</span>
                </a>

                <!-- Exports -->
                <a href="{{ route('admin.exports.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.exports.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <span class="text-lg">📤</span>
                    <span class="font-medium">Exports</span>
                </a>

                <!-- Codes promo -->
                <a href="{{ route('admin.promo-codes.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.promo-codes.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <span class="text-lg">🎁</span>
                    <span class="font-medium">Codes promo</span>
                </a>

                <!-- Paramètres -->
                <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.settings.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <span class="text-lg">⚙️</span>
                    <span class="font-medium">Paramètres</span>
                </a>

                <div class="pt-4 pb-2">
                    <p class="px-4 text-xs font-semibold text-slate-500 dark:text-slate-500 uppercase tracking-wider">Facturation</p>
                </div>

                <!-- Prix Stripe -->
                <a href="{{ route('admin.stripe-prices.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.stripe-prices.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <span class="text-lg">💳</span>
                    <span class="font-medium">Prix Stripe</span>
                </a>

                <!-- Prix personnalisés -->
                <a href="{{ route('admin.custom-prices.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.custom-prices.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <span class="text-lg">💎</span>
                    <span class="font-medium">Prix personnalisés</span>
                </a>

                <!-- Abonnements -->
                <a href="{{ route('admin.subscriptions.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.subscriptions.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <span class="text-lg">💳</span>
                    <span class="font-medium">Abonnements</span>
                </a>

                <!-- Essais gratuits -->
                <a href="{{ route('admin.essais-gratuits.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.essais-gratuits.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <span class="text-lg">🎁</span>
                    <span class="font-medium">Essais gratuits</span>
                    @php
                        $essaisActifs = \App\Models\EssaiGratuit::actifs()->count();
                    @endphp
                    @if($essaisActifs > 0)
                        <span class="ml-auto px-2 py-0.5 text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full">{{ $essaisActifs }}</span>
                    @endif
                </a>
            </nav>
        </aside>

        <!-- Main content -->
        <main class="flex-1 flex flex-col min-w-0">
            <!-- Top bar -->
            <header class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 px-4 lg:px-8 py-4 sticky top-0 z-30">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <button onclick="toggleBurgerMenu('admin_mobile_nav')" class="lg:hidden p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg" aria-label="Menu">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <div>
                            <h1 class="text-lg lg:text-2xl font-bold text-slate-900 dark:text-white truncate">@yield('header', 'Administration')</h1>
                            @hasSection('subheader')
                                <p class="text-xs lg:text-sm text-slate-600 dark:text-slate-400 truncate hidden sm:block">@yield('subheader')</p>
                            @endif
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2 lg:gap-4">
                        <!-- Recherche globale (cachée sur mobile petit, à voir) -->
                        <form action="{{ route('admin.search') }}" method="GET" class="relative hidden md:block">
                            <input 
                                type="text" 
                                name="q" 
                                placeholder="Rechercher..."
                                value="{{ request('q') }}"
                                class="w-48 lg:w-64 px-4 py-2 pl-10 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm"
                            >
                            <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </form>

                        <!-- Dark mode toggle -->
                        <button 
                            id="theme-toggle"
                            class="p-2 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-600 transition"
                        >
                            <span class="dark:hidden">🌙</span>
                            <span class="hidden dark:inline">☀️</span>
                        </button>

                        <!-- Admin info & Actions -->
                        <div class="flex items-center gap-2 lg:gap-4 border-l border-slate-200 dark:border-slate-700 pl-2 lg:pl-4">
                            <div class="flex items-center gap-2 lg:gap-3">
                                <div class="text-right hidden xl:block">
                                    <div class="text-sm font-medium text-slate-900 dark:text-white">{{ auth()->user()->name }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">Administrateur</div>
                                </div>
                                <div class="w-8 h-8 rounded-full overflow-hidden flex items-center justify-center bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 flex-shrink-0">
                                    @if(auth()->user()->photo_profil)
                                        <img 
                                            src="/media/{{ auth()->user()->photo_profil }}" 
                                            alt="{{ auth()->user()->name }}" 
                                            class="w-full h-full object-cover"
                                        >
                                    @else
                                        <span class="text-sm font-bold">{{ substr(auth()->user()->name, 0, 1) }}</span>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="flex items-center gap-1 lg:gap-2">
                                <a href="{{ route('dashboard') }}" class="p-2 text-slate-500 hover:text-green-600 dark:text-slate-400 dark:hover:text-green-400 transition" title="Mon compte client">
                                    <span class="sr-only">Mon compte</span>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </a>
                                <form method="POST" action="{{ route('logout') }}" class="inline">
                                    @csrf
                                    <button type="submit" class="p-2 text-slate-500 hover:text-red-600 dark:text-slate-400 dark:hover:text-red-400 transition" title="Déconnexion">
                                        <span class="sr-only">Déconnexion</span>
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <div class="p-4 lg:p-8">
                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/50 border border-green-200 dark:border-green-700/50 rounded-lg shadow-sm">
                        <p class="text-green-800 dark:text-green-300 font-medium flex items-center gap-2">
                            <span class="text-xl">✅</span>
                            {{ session('success') }}
                        </p>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-700/50 rounded-lg shadow-sm">
                        <p class="text-red-800 dark:text-red-300 font-medium flex items-center gap-2">
                            <span class="text-xl">⚠️</span>
                            {{ session('error') }}
                        </p>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    @include('partials.cookie-banner')

    @stack('scripts')
    

</body>
</html>
