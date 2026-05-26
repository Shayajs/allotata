<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Administration') - Allo Tata</title>
    <script>
        // Configuration Pusher pour la collaboration en temps réel
        // Pusher est un service WebSocket hébergé, beaucoup plus simple que Reverb
        window.PUSHER_APP_KEY = '{{ env("PUSHER_APP_KEY", "") }}';
        window.PUSHER_APP_CLUSTER = '{{ env("PUSHER_APP_CLUSTER", "mt1") }}';
        window.currentUserId = {{ auth()->id() ?? 'null' }};
    </script>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @stack('head-scripts')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @include('partials.theme-script')
    <style>
        [x-cloak] { display: none !important; }
        /* Force browser controls (scrollbars, dates, etc.) to match the theme */
        html.dark { color-scheme: dark; }
        html { color-scheme: light; }

        /* Responsive Tables Cards for Admin */
        @media (max-width: 1024px) {
            .table-responsive-to-cards table {
                border: none !important;
                background: transparent !important;
            }
            .table-responsive-to-cards thead {
                display: none !important;
            }
            .table-responsive-to-cards tbody, 
            .table-responsive-to-cards td, 
            .table-responsive-to-cards tr {
                display: block !important;
                width: 100% !important;
            }
            .table-responsive-to-cards tr {
                border: 1px solid rgba(226, 232, 240, 0.8) !important;
                margin-bottom: 1.5rem !important;
                border-radius: 1rem !important;
                overflow: hidden !important;
                background: white !important;
                box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1) !important;
                padding: 0.5rem 0 !important;
            }
            .dark .table-responsive-to-cards tr {
                background: #1e293b !important;
                border-color: #334155 !important;
            }
            .table-responsive-to-cards td {
                border: none !important;
                border-bottom: 1px solid rgba(226, 232, 240, 0.5) !important;
                position: relative !important;
                padding-left: 50% !important;
                padding-right: 1rem !important;
                text-align: right !important;
                min-height: 3.5rem !important;
                display: flex !important;
                align-items: center !important;
                justify-content: flex-end !important;
                white-space: normal !important;
            }
            .dark .table-responsive-to-cards td {
                border-bottom-color: rgba(51, 65, 85, 0.5) !important;
            }
            .table-responsive-to-cards td:last-child {
                border-bottom: none !important;
                background: rgba(248, 250, 252, 0.5) !important;
                margin-top: 0.5rem !important;
            }
            .dark .table-responsive-to-cards td:last-child {
                background: rgba(15, 23, 42, 0.2) !important;
            }
            .table-responsive-to-cards td::before {
                content: attr(data-label);
                position: absolute;
                left: 1rem;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                text-align: left;
                font-weight: 700;
                color: #64748b;
                font-size: 0.7rem;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }
            .dark .table-responsive-to-cards td::before {
                color: #94a3b8;
            }
            
            /* Ajustements spécifiques pour les cellules avec avatars/flex */
            .table-responsive-to-cards td > div {
                justify-content: flex-end !important;
                width: 100% !important;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
    @include('partials.offline-banner')
    @include('partials.super-user-banner')
    @include('components.mobile-nav', ['navType' => 'admin', 'id' => 'admin_mobile_nav', 'hideButton' => true])

    <div class="min-h-screen flex">
        <!-- Sidebar (PC) – cachée en PWA mobile via .nav-sidebar -->
        <aside class="nav-sidebar w-64 bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700 flex-shrink-0 hidden lg:flex flex-col sticky top-0 h-screen overflow-y-auto">
            <!-- Logo -->
            <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                <a href="{{ route('home') }}" class="flex items-center gap-3 text-xl font-bold">
                    @php
                        use App\Helpers\SiteHelper;
                        $logoUrl = SiteHelper::getLogo('transparent');
                        $siteName = SiteHelper::getSiteName();
                    @endphp
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-8 w-auto hidden sm:block" style="max-height: 32px;">
                    @endif
                    <span class="bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">{{ $siteName }} Admin</span>
                </a>
            </div>

            <!-- Navigation -->
            <nav class="p-4 space-y-1 flex-1">
                <!-- Dashboard -->
                <a href="{{ route('admin.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.index') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span class="font-medium">Dashboard</span>
                </a>

                <!-- Kanban -->
                <a href="{{ route('admin.kanban.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.kanban.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
                    </svg>
                    <span class="font-medium">Kanban</span>
                </a>

                <!-- Notes -->
                <a href="{{ route('admin.notes.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.notes.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="font-medium">Notes</span>
                </a>

                {{-- ========== GESTION ========== --}}
                <div class="pt-4 pb-2">
                    <p class="px-4 text-xs font-semibold text-slate-500 dark:text-slate-500 uppercase tracking-wider">Gestion</p>
                </div>

                <!-- Utilisateurs -->
                <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.users.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span class="font-medium">Utilisateurs</span>
                </a>

                <!-- Entreprises -->
                <a href="{{ route('admin.entreprises.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.entreprises.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <span class="font-medium">Entreprises</span>
                    @php $entreprisesEnAttente = \App\Models\Entreprise::where('est_verifiee', false)->count(); @endphp
                    @if($entreprisesEnAttente > 0)
                        <span class="ml-auto px-2 py-0.5 text-xs bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 rounded-full">{{ $entreprisesEnAttente }}</span>
                    @endif
                </a>

                <!-- Réservations -->
                <a href="{{ route('admin.reservations.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.reservations.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span class="font-medium">Réservations</span>
                </a>

                {{-- ========== COMMUNICATION ========== --}}
                <div class="pt-4 pb-2">
                    <p class="px-4 text-xs font-semibold text-slate-500 dark:text-slate-500 uppercase tracking-wider">Communication</p>
                </div>

                <!-- Tickets -->
                <a href="{{ route('admin.tickets.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.tickets.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                    </svg>
                    <span class="font-medium">Tickets Support</span>
                    @php $ticketsOuverts = \App\Models\Ticket::where('statut', 'ouvert')->count(); @endphp
                    @if($ticketsOuverts > 0)
                        <span class="ml-auto px-2 py-0.5 text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full">{{ $ticketsOuverts }}</span>
                    @endif
                </a>

                <!-- Contacts -->
                <a href="{{ route('admin.contacts.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.contacts.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <span class="font-medium">Contacts</span>
                    @php $contactsNonLus = \App\Models\Contact::where('est_lu', false)->count(); @endphp
                    @if($contactsNonLus > 0)
                        <span class="ml-auto px-2 py-0.5 text-xs bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 rounded-full">{{ $contactsNonLus }}</span>
                    @endif
                </a>

                <!-- Messagerie interne -->
                <a href="{{ route('admin.messagerie-interne.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.messagerie-interne.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    <span class="font-medium">Messagerie interne</span>
                </a>

                <!-- Forum -->
                <a href="{{ route('admin.forum.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.forum.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path>
                    </svg>
                    <span class="font-medium">Forum</span>
                </a>

                <!-- Templates Emails -->
                <a href="{{ route('admin.email-templates.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.email-templates.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                    </svg>
                    <span class="font-medium">Emails & Templates</span>
                </a>

                <!-- Notifications Push -->
                <a href="{{ route('admin.push-notifications.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.push-notifications.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <span class="font-medium">Notifications Push</span>
                </a>

                {{-- ========== CONTENU ========== --}}
                <div class="pt-4 pb-2">
                    <p class="px-4 text-xs font-semibold text-slate-500 dark:text-slate-500 uppercase tracking-wider">Contenu</p>
                </div>

                <!-- FAQs -->
                <a href="{{ route('admin.faqs.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.faqs.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="font-medium">FAQs</span>
                </a>

                <!-- Annonces -->
                <a href="{{ route('admin.announcements.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.announcements.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
                    </svg>
                    <span class="font-medium">Annonces</span>
                </a>

                <!-- Cours -->
                <a href="{{ route('admin.courses.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.courses.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <span class="font-medium">Cours</span>
                </a>

                <!-- Médiathèque -->
                <a href="{{ route('admin.media.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.media.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span class="font-medium">Médiathèque</span>
                </a>

                {{-- ========== FINANCES ========== --}}
                <div class="pt-4 pb-2">
                    <p class="px-4 text-xs font-semibold text-slate-500 dark:text-slate-500 uppercase tracking-wider">Finances</p>
                </div>

                <!-- Finances Entreprises -->
                <a href="{{ route('admin.finances.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.finances.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="font-medium">Finances Entreprises</span>
                </a>

                <!-- Factures -->
                <a href="{{ route('admin.factures.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.factures.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="font-medium">Factures</span>
                </a>

                <!-- Statistiques -->
                <a href="{{ route('admin.statistiques.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.statistiques.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span class="font-medium">Statistiques</span>
                </a>

                {{-- ========== ABONNEMENTS ========== --}}
                <div class="pt-4 pb-2">
                    <p class="px-4 text-xs font-semibold text-slate-500 dark:text-slate-500 uppercase tracking-wider">Abonnements</p>
                </div>

                <!-- Abonnements -->
                <a href="{{ route('admin.subscriptions.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.subscriptions.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    <span class="font-medium">Abonnements</span>
                </a>

                <!-- Paiements / Échéances -->
                <a href="{{ route('admin.echeances.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.echeances.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span class="font-medium">Paiements</span>
                </a>

                <!-- Essais gratuits -->
                <a href="{{ route('admin.essais-gratuits.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.essais-gratuits.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
                    </svg>
                    <span class="font-medium">Essais gratuits</span>
                    @php $essaisActifs = \App\Models\EssaiGratuit::actifs()->count(); @endphp
                    @if($essaisActifs > 0)
                        <span class="ml-auto px-2 py-0.5 text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full">{{ $essaisActifs }}</span>
                    @endif
                </a>

                <!-- Codes promo -->
                <a href="{{ route('admin.promo-codes.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.promo-codes.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                    <span class="font-medium">Codes promo</span>
                </a>

                <!-- Prix Stripe -->
                <a href="{{ route('admin.stripe-prices.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.stripe-prices.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span class="font-medium">Tarifs</span>
                </a>

                <!-- Prix personnalisés -->
                <a href="{{ route('admin.custom-prices.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.custom-prices.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                    </svg>
                    <span class="font-medium">Prix personnalisés</span>
                </a>

                <!-- Webhooks Stripe -->
                <a href="{{ route('admin.stripe-webhooks.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.stripe-webhooks.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                    </svg>
                    <span class="font-medium">Webhooks Stripe</span>
                </a>

                <!-- Tests Stripe -->
                <a href="{{ route('admin.stripe-tests.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.stripe-tests.*') ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}" style="border: 1px dashed rgba(245, 158, 11, 0.3);">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                    </svg>
                    <span class="font-medium">Tests Stripe</span>
                </a>

                {{-- ========== OUTILS ========== --}}
                <div class="pt-4 pb-2">
                    <p class="px-4 text-xs font-semibold text-slate-500 dark:text-slate-500 uppercase tracking-wider">Outils</p>
                </div>

                <!-- Documentation Dev -->
                <a href="{{ route('dev.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('dev.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <span class="font-medium">Documentation Dev</span>
                </a>

                <!-- BrightShell ERP -->
                <a href="{{ route('brightshell.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('brightshell.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}" style="border: 1px solid rgba(74, 111, 165, 0.2);">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #4a6fa5;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                    </svg>
                    <span class="font-medium" style="color: #4a6fa5;">BrightShell ERP</span>
                </a>

                {{-- ========== SYSTÈME ========== --}}
                <div class="pt-4 pb-2">
                    <p class="px-4 text-xs font-semibold text-slate-500 dark:text-slate-500 uppercase tracking-wider">Système</p>
                </div>

                <!-- Erreurs -->
                <a href="{{ route('admin.errors.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.errors.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <span class="font-medium">Erreurs</span>
                    @php $erreursNonVues = \App\Models\ErrorLog::where('est_vue', false)->count(); @endphp
                    @if($erreursNonVues > 0)
                        <span class="ml-auto px-2 py-0.5 text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full">{{ $erreursNonVues }}</span>
                    @endif
                </a>

                <!-- Logs d'activité -->
                <a href="{{ route('admin.activity-logs.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.activity-logs.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    <span class="font-medium">Logs d'activité</span>
                </a>

                <!-- Email Logs -->
                <a href="{{ route('admin.email-logs.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.email-logs.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <span class="font-medium">Logs Emails</span>
                </a>

                <!-- SMS Logs -->
                <a href="{{ route('admin.sms-logs.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.sms-logs.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    <span class="font-medium">Logs SMS</span>
                </a>

                <!-- Tâches CRON -->
                <a href="{{ route('admin.scheduled-tasks.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.scheduled-tasks.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="font-medium">Tâches CRON</span>
                </a>

                <!-- Exports -->
                <a href="{{ route('admin.exports.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.exports.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    <span class="font-medium">Exports</span>
                </a>

                <!-- RGPD -->
                <a href="{{ route('admin.gdpr.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.gdpr.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    <span class="font-medium">RGPD</span>
                    @php $gdprPending = \App\Models\GdprRequest::where('status', 'pending')->count(); @endphp
                    @if($gdprPending > 0)
                        <span class="ml-auto px-2 py-0.5 text-xs bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 rounded-full">{{ $gdprPending }}</span>
                    @endif
                </a>

                <!-- Paramètres -->
                <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.settings.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span class="font-medium">Paramètres</span>
                </a>

                <!-- Base de données -->
                <a href="{{ route('admin.database.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ request()->routeIs('admin.database.*') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                    </svg>
                    <span class="font-medium">Base de données</span>
                </a>
            </nav>
        </aside>

        <!-- Main content -->
        <main class="flex-1 flex flex-col min-w-0">
            <!-- Top bar -->
            <header class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 px-4 lg:px-8 py-4 sticky top-0 z-30">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <button onclick="toggleBurgerMenu('admin_mobile_nav')" class="lg:hidden pwa-hide-mobile p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg" aria-label="Menu">
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
                        <!-- Recherche globale -->
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
                            <svg class="w-5 h-5 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                            </svg>
                            <svg class="w-5 h-5 hidden dark:inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
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
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ session('success') }}
                        </p>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-700/50 rounded-lg shadow-sm">
                        <p class="text-red-800 dark:text-red-300 font-medium flex items-center gap-2">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            {{ session('error') }}
                        </p>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    {{-- Bottom bar PWA admin (visible uniquement en PWA mobile) --}}
    <x-nav.pwa-admin-bottom-bar />

    @include('partials.cookie-banner')

    @stack('scripts')

    <script>
        window.VAPID_PUBLIC_KEY = '{{ config("webpush.vapid.public_key") }}';
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }
    </script>
</body>
</html>
