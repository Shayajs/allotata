<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'Dashboard') - Allo Tata</title>
        <script>
            // Configuration Reverb pour la présence en temps réel
            window.REVERB_APP_ID = '{{ env("REVERB_APP_ID", "reverb-app") }}';
            window.REVERB_APP_KEY = '{{ env("REVERB_APP_KEY", "reverb-key") }}';
            window.REVERB_HOST = '{{ env("REVERB_HOST", "127.0.0.2") }}';
            window.REVERB_PORT = '{{ env("REVERB_PORT", "8080") }}';
            window.REVERB_SCHEME = '{{ env("REVERB_SCHEME", "http") }}';
            window.currentUserId = {{ auth()->id() ?? 'null' }};
        </script>
        @include('partials.favicon')
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.theme-script')
        @stack('styles')
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
        @include('partials.super-user-banner')
        <!-- Navigation Desktop -->
        <nav class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 {{ session('original_admin_id') ? 'mt-[60px]' : '' }}">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center gap-4">
                        <!-- Menu Burger pour mobile web -->
                        @include('components.mobile-nav', ['navType' => 'dashboard'])
                        
                        <a href="{{ route('home') }}" class="flex items-center gap-3 text-xl font-bold">
                            @php
                                use App\Helpers\SiteHelper;
                                $logoUrl = SiteHelper::getLogo('transparent');
                                $siteName = SiteHelper::getSiteName();
                            @endphp
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-8 w-auto hidden sm:block" style="max-height: 32px;">
                            @endif
                            <span class="bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">{{ $siteName }}</span>
                        </a>
                    </div>
                    <!-- Liens desktop (masqués sur mobile) -->
                    <div class="hidden lg:flex items-center gap-4">
                        @auth
                            @if(Auth::user()->is_admin)
                                <a href="{{ route('admin.index') }}" class="px-4 py-2 text-sm font-medium bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                                    Administration
                                </a>
                            @endif
                            <a href="{{ route('notifications.index') }}" class="px-4 py-2 text-sm font-medium bg-orange-100 dark:bg-orange-900/30 hover:bg-orange-200 dark:hover:bg-orange-900/50 text-orange-800 dark:text-orange-400 rounded-lg transition relative flex items-center gap-2">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                </svg>
                                Notifications
                                @if(Auth::user()->nombre_notifications_non_lues > 0)
                                    <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">
                                        {{ Auth::user()->nombre_notifications_non_lues }}
                                    </span>
                                @endif
                            </a>
                            <a href="{{ route('messagerie.index') }}" class="px-4 py-2 text-sm font-medium bg-blue-100 dark:bg-blue-900/30 hover:bg-blue-200 dark:hover:bg-blue-900/50 text-blue-800 dark:text-blue-400 rounded-lg transition flex items-center gap-2">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                                Messagerie
                            </a>
                            <a href="{{ route('tickets.create') }}" class="px-4 py-2 text-sm font-medium bg-purple-100 dark:bg-purple-900/30 hover:bg-purple-200 dark:hover:bg-purple-900/50 text-purple-800 dark:text-purple-400 rounded-lg transition flex items-center gap-2">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                                </svg>
                                Support
                            </a>
                            <a href="{{ route('settings.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition flex items-center gap-2">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Paramètres
                            </a>
                            <span class="text-sm text-slate-600 dark:text-slate-400">
                                {{ Auth::user()->name }}
                            </span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-red-600 dark:hover:text-red-400 transition">
                                    Déconnexion
                                </button>
                            </form>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <!-- Contenu Principal -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pb-24 lg:pb-8">
            <!-- Messages Flash -->
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <p class="text-green-800 dark:text-green-400">{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <p class="text-red-800 dark:text-red-400">{{ session('error') }}</p>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    @foreach($errors->all() as $error)
                        <p class="text-red-800 dark:text-red-400">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            @yield('content')
        </main>

        @include('partials.footer')
        @include('partials.cookie-banner')

        @stack('scripts')
    </body>
</html>
