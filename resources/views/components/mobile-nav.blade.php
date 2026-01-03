@php
    // Déterminer le type de navigation et les liens selon le contexte
    $navType = $navType ?? 'dashboard'; // dashboard, entreprise, admin, public
    $currentRoute = request()->route()->getName();
    $user = auth()->user();
    $uniqueId = 'mobile_nav_' . uniqid();
@endphp

<!-- Menu Burger pour Web Mobile -->
<div class="mobile-nav-burger" style="z-index: 100 !important;">
    <!-- Bouton burger -->
    <button 
        id="{{ $uniqueId }}_button"
        class="burger-button"
        aria-label="Ouvrir le menu"
        aria-expanded="false"
        onclick="toggleBurgerMenu('{{ $uniqueId }}')"
    >
        <svg class="burger-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
    </button>

    <!-- Overlay -->
    <div id="{{ $uniqueId }}_overlay" class="burger-overlay" onclick="closeBurgerMenu('{{ $uniqueId }}')"></div>

    <!-- Drawer -->
    <div id="{{ $uniqueId }}_drawer" class="burger-drawer">
        <div class="burger-drawer-content">
            <!-- Header du drawer -->
            <div class="pb-4 mb-4 border-b border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-lg font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                        Allo Tata
                    </span>
                    <button onclick="closeBurgerMenu('{{ $uniqueId }}')" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                @if($user)
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ $user->name }}</p>
                    @if($user->email)
                        <p class="text-xs text-slate-500 dark:text-slate-500">{{ $user->email }}</p>
                    @endif
                @endif
            </div>

            <!-- Liens de navigation selon le contexte -->
            @if($navType === 'dashboard' && $user)
                <a href="{{ route('dashboard') }}" class="{{ $currentRoute === 'dashboard' ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                    <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Dashboard
                </a>
                @if($user->est_gerant)
                    <a href="{{ route('entreprise.create') }}" class="{{ $currentRoute === 'entreprise.create' ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                        <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Créer une entreprise
                    </a>
                @endif
                @if($user->is_admin)
                    <a href="{{ route('admin.index') }}" class="{{ str_starts_with($currentRoute, 'admin.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                        <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        Administration
                    </a>
                @endif
                <a href="{{ route('notifications.index') }}" class="{{ $currentRoute === 'notifications.index' ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                    <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    Notifications
                    @if(isset($user->nombre_notifications_non_lues) && $user->nombre_notifications_non_lues > 0)
                        <span class="ml-auto px-2 py-0.5 text-xs bg-red-500 text-white rounded-full">{{ $user->nombre_notifications_non_lues }}</span>
                    @endif
                </a>
                <a href="{{ route('messagerie.index') }}" class="{{ str_starts_with($currentRoute, 'messagerie.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                    <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    Messagerie
                </a>
                <a href="{{ route('tickets.index') }}" class="{{ str_starts_with($currentRoute, 'tickets.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                    <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Support
                </a>
                <a href="{{ route('settings.index') }}" class="{{ str_starts_with($currentRoute, 'settings.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                    <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Paramètres
                </a>
                <div class="pt-4 mt-4 border-t border-slate-200 dark:border-slate-700">
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-3 rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                            <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            Déconnexion
                        </button>
                    </form>
                </div>
            @elseif($navType === 'admin' && $user && $user->is_admin)
                <a href="{{ route('admin.index') }}" class="{{ $currentRoute === 'admin.index' ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                    <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Dashboard Admin
                </a>
                <a href="{{ route('admin.users.index') }}" class="{{ str_starts_with($currentRoute, 'admin.users.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                    <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    Utilisateurs
                </a>
                <a href="{{ route('admin.entreprises.index') }}" class="{{ str_starts_with($currentRoute, 'admin.entreprises.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                    <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    Entreprises
                </a>
                <a href="{{ route('admin.reservations.index') }}" class="{{ str_starts_with($currentRoute, 'admin.reservations.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                    <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Réservations
                </a>
                <a href="{{ route('admin.tickets.index') }}" class="{{ str_starts_with($currentRoute, 'admin.tickets.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                    <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 11-1.896.632L9.333 5H7v14h2.333l1.498-4.493a1 1 0 111.896.632l-1.498 4.493a1 1 0 01-.948.684H7a2 2 0 01-2-2V5z"></path>
                    </svg>
                    Tickets
                </a>
                <a href="{{ route('admin.contacts.index') }}" class="{{ str_starts_with($currentRoute, 'admin.contacts.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                    <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    Contacts
                </a>
                <a href="{{ route('admin.faqs.index') }}" class="{{ str_starts_with($currentRoute, 'admin.faqs.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                    <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    FAQs
                </a>
                <a href="{{ route('admin.announcements.index') }}" class="{{ str_starts_with($currentRoute, 'admin.announcements.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                    <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
                    </svg>
                    Annonces
                </a>
                <a href="{{ route('admin.errors.index') }}" class="{{ str_starts_with($currentRoute, 'admin.errors.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                    <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    Erreurs
                </a>
            @elseif($navType === 'entreprise' && isset($entreprise) && $user)
                <a href="{{ route('dashboard') }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                    <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Mon compte
                </a>
                <a href="{{ route('public.entreprise', $entreprise->slug) }}" target="_blank" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                    <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                    Page publique
                </a>
                <div class="pt-2 mt-2 border-t border-slate-200 dark:border-slate-700">
                    <p class="px-4 py-2 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Navigation entreprise</p>
                </div>
                @if(isset($activeTab))
                    <a href="{{ route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'accueil']) }}" class="{{ $activeTab === 'accueil' ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                        <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        Accueil
                    </a>
                    <a href="{{ route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'agenda']) }}" class="{{ $activeTab === 'agenda' ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                        <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Agenda
                    </a>
                    @if(isset($aGestionMultiPersonnes) && $aGestionMultiPersonnes)
                        <a href="{{ route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'equipe']) }}" class="{{ $activeTab === 'equipe' ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                            <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            Équipe
                        </a>
                    @endif
                    <a href="{{ route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'reservations']) }}" class="{{ $activeTab === 'reservations' ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                        <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        Réservations
                    </a>
                    <a href="{{ route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'factures']) }}" class="{{ $activeTab === 'factures' ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                        <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Factures
                    </a>
                    <a href="{{ route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'messagerie']) }}" class="{{ $activeTab === 'messagerie' ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                        <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        Messagerie
                    </a>
                    <a href="{{ route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'parametres']) }}" class="{{ $activeTab === 'parametres' ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                        <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Paramètres
                    </a>
                @endif
                <div class="pt-4 mt-4 border-t border-slate-200 dark:border-slate-700">
                    <a href="{{ route('dashboard') }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                        <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        Retour Site
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-3 rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                            <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            Déconnexion
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>


<script>
if (typeof toggleBurgerMenu === 'undefined') {
    window.toggleBurgerMenu = function(id) {
        const drawer = document.getElementById(id + '_drawer');
        const overlay = document.getElementById(id + '_overlay');
        const button = document.getElementById(id + '_button');
        
        if (!drawer || !overlay || !button) return;

        const isOpen = drawer.classList.contains('open');
        
        if (isOpen) {
            closeBurgerMenu(id);
        } else {
            drawer.classList.add('open');
            overlay.classList.add('open');
            button.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
        }
    }
}

if (typeof closeBurgerMenu === 'undefined') {
    window.closeBurgerMenu = function(id) {
        const drawer = document.getElementById(id + '_drawer');
        const overlay = document.getElementById(id + '_overlay');
        const button = document.getElementById(id + '_button');
        
        if (!drawer || !overlay || !button) return;

        drawer.classList.remove('open');
        overlay.classList.remove('open');
        button.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }
}

// Fermer le menu au clic sur Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const openedDrawers = document.querySelectorAll('.burger-drawer.open');
        openedDrawers.forEach(drawer => {
            const id = drawer.id.replace('_drawer', '');
            closeBurgerMenu(id);
        });
    }
});
</script>