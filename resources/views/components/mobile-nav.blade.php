@php
    // Déterminer le type de navigation et les liens selon le contexte
    $navType = $navType ?? 'dashboard'; // dashboard, entreprise, admin, public
    $currentRoute = request()->route()->getName();
    $user = auth()->user();
    $uniqueId = $id ?? ('mobile_nav_' . uniqid());
    $hideButton = $hideButton ?? false;
@endphp

<!-- Menu Burger pour Web Mobile -->
@if(!$hideButton)
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
</div>
@endif

<!-- Overlay -->
<div id="{{ $uniqueId }}_overlay" class="burger-overlay" onclick="closeBurgerMenu('{{ $uniqueId }}')"></div>

<!-- Drawer -->
<div id="{{ $uniqueId }}_drawer" class="burger-drawer">
    <div class="burger-drawer-content">
        <!-- Header du drawer -->
        <div class="pb-4 mb-4 border-b border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between mb-2">
                @php
                    use App\Helpers\SiteHelper;
                    $logoUrl = SiteHelper::getLogo('transparent');
                @endphp
                <a href="{{ route('home') }}" class="flex items-center">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Allo Tata" class="h-6 w-auto">
                    @else
                        <span class="text-lg font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                            Allo Tata
                        </span>
                    @endif
                </a>
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
            <a href="{{ route('checkout.index') }}" class="{{ str_starts_with($currentRoute, 'checkout.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                Espace Paiement
            </a>
            <a href="{{ route('settings.index') }}" class="{{ str_starts_with($currentRoute, 'settings.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Paramètres
            </a>
            <a href="{{ route('dashboard', ['tab' => 'installer']) }}" class="{{ request('tab') === 'installer' ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Installer
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
            
            <!-- Kanban -->
            <a href="{{ route('admin.kanban.index') }}" class="{{ str_starts_with($currentRoute, 'admin.kanban.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
                </svg>
                Kanban
            </a>
            
            <!-- Notes -->
            <a href="{{ route('admin.notes.index') }}" class="{{ str_starts_with($currentRoute, 'admin.notes.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Notes
            </a>
            
            {{-- ========== GESTION ========== --}}
            <div class="pt-2 mt-2 border-t border-slate-200 dark:border-slate-700">
                <p class="px-4 py-2 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Gestion</p>
            </div>
            
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

            {{-- ========== COMMUNICATION ========== --}}
            <div class="pt-2 mt-2 border-t border-slate-200 dark:border-slate-700">
                <p class="px-4 py-2 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Communication</p>
            </div>
            
            <a href="{{ route('admin.tickets.index') }}" class="{{ str_starts_with($currentRoute, 'admin.tickets.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                </svg>
                Tickets Support
            </a>
            <a href="{{ route('admin.contacts.index') }}" class="{{ str_starts_with($currentRoute, 'admin.contacts.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                Contacts
            </a>
            <a href="{{ route('admin.email-templates.index') }}" class="{{ str_starts_with($currentRoute, 'admin.email-templates.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                </svg>
                Emails & Templates
            </a>
            
            <!-- Messagerie interne -->
            <a href="{{ route('admin.messagerie-interne.index') }}" class="{{ str_starts_with($currentRoute, 'admin.messagerie-interne.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
                Messagerie interne
            </a>
            <a href="{{ route('admin.forum.index') }}" class="{{ str_starts_with($currentRoute, 'admin.forum.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path>
                </svg>
                Forum
            </a>
            <a href="{{ route('admin.push-notifications.index') }}" class="{{ str_starts_with($currentRoute, 'admin.push-notifications.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
                Notifications Push
            </a>
            
            {{-- ========== CONTENU ========== --}}
            <div class="pt-2 mt-2 border-t border-slate-200 dark:border-slate-700">
                <p class="px-4 py-2 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Contenu</p>
            </div>
            
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
            <a href="{{ route('admin.courses.index') }}" class="{{ str_starts_with($currentRoute, 'admin.courses.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
                Cours
            </a>
            <a href="{{ route('admin.media.index') }}" class="{{ str_starts_with($currentRoute, 'admin.media.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Médiathèque
            </a>

            {{-- ========== FINANCES ========== --}}
            <div class="pt-2 mt-2 border-t border-slate-200 dark:border-slate-700">
                <p class="px-4 py-2 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Finances</p>
            </div>
            
            <a href="{{ route('admin.finances.index') }}" class="{{ str_starts_with($currentRoute, 'admin.finances.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Finances Entreprises
            </a>
            <a href="{{ route('admin.factures.index') }}" class="{{ str_starts_with($currentRoute, 'admin.factures.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Factures
            </a>
            <a href="{{ route('admin.statistiques.index') }}" class="{{ str_starts_with($currentRoute, 'admin.statistiques.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Statistiques
            </a>

            {{-- ========== ABONNEMENTS ========== --}}
            <div class="pt-2 mt-2 border-t border-slate-200 dark:border-slate-700">
                <p class="px-4 py-2 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Abonnements</p>
            </div>
            
            <a href="{{ route('admin.subscriptions.index') }}" class="{{ str_starts_with($currentRoute, 'admin.subscriptions.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
                Abonnements
            </a>
            <a href="{{ route('admin.echeances.index') }}" class="{{ str_starts_with($currentRoute, 'admin.echeances.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                Paiements
            </a>
            <a href="{{ route('admin.essais-gratuits.index') }}" class="{{ str_starts_with($currentRoute, 'admin.essais-gratuits.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
                </svg>
                Essais gratuits
            </a>
            <a href="{{ route('admin.promo-codes.index') }}" class="{{ str_starts_with($currentRoute, 'admin.promo-codes.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                </svg>
                Codes promo
            </a>
            <a href="{{ route('admin.stripe-prices.index') }}" class="{{ str_starts_with($currentRoute, 'admin.stripe-prices.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                Tarifs
            </a>
            <a href="{{ route('admin.custom-prices.index') }}" class="{{ str_starts_with($currentRoute, 'admin.custom-prices.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                </svg>
                Prix personnalisés
            </a>
            <a href="{{ route('admin.stripe-webhooks.index') }}" class="{{ str_starts_with($currentRoute, 'admin.stripe-webhooks.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                </svg>
                Webhooks Stripe
            </a>
            <a href="{{ route('admin.stripe-tests.index') }}" class="{{ str_starts_with($currentRoute, 'admin.stripe-tests.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')" style="border: 1px dashed rgba(245, 158, 11, 0.3); border-radius: 0.5rem;">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                </svg>
                Tests Stripe
            </a>

            {{-- ========== OUTILS ========== --}}
            <div class="pt-2 mt-2 border-t border-slate-200 dark:border-slate-700">
                <p class="px-4 py-2 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Outils</p>
            </div>
            <a href="{{ route('dev.index') }}" class="{{ str_starts_with($currentRoute, 'dev.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
                Documentation Dev
            </a>
            <a href="{{ route('brightshell.index') }}" class="{{ str_starts_with($currentRoute, 'brightshell.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')" style="color: #4a6fa5;">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #4a6fa5;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                </svg>
                BrightShell ERP
            </a>

            {{-- ========== SYSTÈME ========== --}}
            <div class="pt-2 mt-2 border-t border-slate-200 dark:border-slate-700">
                <p class="px-4 py-2 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Système</p>
            </div>
            
            <a href="{{ route('admin.errors.index') }}" class="{{ str_starts_with($currentRoute, 'admin.errors.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                Erreurs
            </a>
            <a href="{{ route('admin.activity-logs.index') }}" class="{{ str_starts_with($currentRoute, 'admin.activity-logs.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
                Logs d'activité
            </a>
            <a href="{{ route('admin.email-logs.index') }}" class="{{ str_starts_with($currentRoute, 'admin.email-logs.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                Logs Emails
            </a>
            <a href="{{ route('admin.sms-logs.index') }}" class="{{ str_starts_with($currentRoute, 'admin.sms-logs.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                Logs SMS
            </a>
            <a href="{{ route('admin.scheduled-tasks.index') }}" class="{{ str_starts_with($currentRoute, 'admin.scheduled-tasks.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Tâches CRON
            </a>
            <a href="{{ route('admin.exports.index') }}" class="{{ str_starts_with($currentRoute, 'admin.exports.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Exports
            </a>
            <a href="{{ route('admin.gdpr.index') }}" class="{{ str_starts_with($currentRoute, 'admin.gdpr.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
                RGPD
            </a>
            <a href="{{ route('admin.settings.index') }}" class="{{ str_starts_with($currentRoute, 'admin.settings.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Paramètres
            </a>
            <a href="{{ route('admin.database.index') }}" class="{{ str_starts_with($currentRoute, 'admin.database.') ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                </svg>
                Base de données
            </a>
            
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
        @elseif($navType === 'entreprise' && isset($entreprise) && $user)
            @php
                $burgerNavItems = \App\Services\NavigationService::getEntrepriseItems($entreprise, $user, [
                    'a_gestion_multi_personnes' => $aGestionMultiPersonnes ?? false,
                    'a_site_web_actif' => $entreprise->aSiteWebActif(),
                    'site_web_url' => $entreprise->aSiteWebActif() ? route('site-web.show', $entreprise->slug_web ?? $entreprise->slug) . '?mode=edit' : null,
                ]);
                $burgerActiveTab = $activeTab ?? 'accueil';
            @endphp
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
            @foreach($burgerNavItems as $bItem)
                @if(isset($bItem['separator']))
                    <div class="pt-1 mt-1 border-t border-slate-200 dark:border-slate-700"></div>
                    @continue
                @endif
                @php
                    $bIconPath = \App\Services\NavigationService::getIconPath($bItem['icon'] ?? '');
                    $bIconExtra = isset($bItem['icon_extra']) ? \App\Services\NavigationService::getIconPath($bItem['icon_extra']) : null;
                    $bIsActive = $burgerActiveTab === ($bItem['tab'] ?? $bItem['key']);
                    $bIsLink = $bItem['is_link'] ?? false;
                    $bIsLocked = $bItem['locked'] ?? false;
                @endphp
                @if($bIsLink && $bIsLocked)
                    <button onclick="closeBurgerMenu('{{ $uniqueId }}'); setTimeout(function(){ document.getElementById('site-web-upsell-overlay')?.classList.remove('hidden'); }, 300);" class="w-full flex items-center px-4 py-3 rounded-lg text-slate-400 dark:text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors text-left">
                        <span class="relative inline-block mr-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $bIconPath }}"></path></svg>
                            <svg class="w-3 h-3 absolute -bottom-0.5 -right-0.5 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path></svg>
                        </span>
                        {{ $bItem['label'] }}
                        <svg class="w-3.5 h-3.5 ml-auto text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path></svg>
                    </button>
                @elseif($bIsLink && !$bIsLocked && ($bItem['url'] ?? null))
                    <a href="{{ $bItem['url'] }}" class="flex items-center px-4 py-3 rounded-lg text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 font-medium" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                        <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $bIconPath }}"></path></svg>
                        {{ $bItem['label'] }}
                        <svg class="w-4 h-4 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    </a>
                @else
                    <a href="{{ route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => $bItem['tab'] ?? $bItem['key']]) }}" class="{{ $bIsActive ? 'active' : '' }}" onclick="closeBurgerMenu('{{ $uniqueId }}')">
                        <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $bIconPath }}"></path>
                            @if($bIconExtra)<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $bIconExtra }}"></path>@endif
                        </svg>
                        <span class="{{ $bItem['label_class'] ?? '' }}">{{ $bItem['label'] }}</span>
                        @if(($bItem['badge'] ?? null) && $bItem['badge'] !== 'dot-red')
                            <span class="ml-auto px-2 py-0.5 text-xs bg-{{ $bItem['badge_color'] ?? 'green' }}-500 text-white rounded-full">{{ $bItem['badge'] }}</span>
                        @endif
                    </a>
                @endif
            @endforeach
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
        @endif
    </div>
</div>


<script>
if (typeof toggleBurgerMenu === 'undefined') {
    window.toggleBurgerMenu = function(id) {
        const drawer = document.getElementById(id + '_drawer');
        const overlay = document.getElementById(id + '_overlay');
        const button = document.getElementById(id + '_button');
        
        if (!drawer || !overlay) return;

        const isOpen = drawer.classList.contains('open');
        
        if (isOpen) {
            closeBurgerMenu(id);
        } else {
            drawer.classList.add('open');
            overlay.classList.add('open');
            if (button) button.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
        }
    }
}

if (typeof closeBurgerMenu === 'undefined') {
    window.closeBurgerMenu = function(id) {
        const drawer = document.getElementById(id + '_drawer');
        const overlay = document.getElementById(id + '_overlay');
        const button = document.getElementById(id + '_button');
        
        if (!drawer || !overlay) return;

        drawer.classList.remove('open');
        overlay.classList.remove('open');
        if (button) button.setAttribute('aria-expanded', 'false');
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