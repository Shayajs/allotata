<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Messagerie - Allo Tata</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script>
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>
        <style>
            .conversation-item {
                transition: all 0.2s ease;
            }
            .conversation-item:hover {
                transform: translateX(4px);
            }
            .badge-unread {
                animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
            }
            @keyframes pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.7; }
            }
            .search-input:focus + .search-icon {
                color: rgb(34, 197, 94);
            }
        </style>
    </head>
    <body class="bg-gradient-to-br from-slate-50 via-slate-100 to-slate-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200 min-h-screen">
        <!-- Navigation améliorée -->
        <nav class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-lg border-b border-slate-200/50 dark:border-slate-700/50 sticky top-0 z-50 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <a href="{{ route('dashboard') }}" class="text-2xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent hover:from-green-600 hover:to-orange-600 transition">
                        Allo Tata
                    </a>
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Retour au dashboard
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- En-tête amélioré -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h1 class="text-4xl font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-3">
                            <span class="text-5xl">💬</span>
                            <span>Messagerie</span>
                        </h1>
                        <p class="text-slate-600 dark:text-slate-400 text-lg">
                            Communiquez avec les entreprises ou vos clients
                        </p>
                    </div>
                    @php
                        $totalUnread = 0;
                        foreach($conversationsClient as $conv) {
                            $totalUnread += $conv->messagesNonLus(Auth::id());
                        }
                        foreach($conversationsGerant as $conv) {
                            $totalUnread += $conv->messagesNonLus(Auth::id());
                        }
                    @endphp
                    @if($totalUnread > 0)
                        <div class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 rounded-full text-white font-bold shadow-lg">
                            <span class="text-lg">{{ $totalUnread }}</span>
                            <span class="text-sm">message{{ $totalUnread > 1 ? 's' : '' }} non lu{{ $totalUnread > 1 ? 's' : '' }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Barres de recherche améliorées -->
            @if($conversationsClient->count() > 0 || $conversationsGerant->count() > 0)
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    @if($conversationsClient->count() > 0)
                        <div class="relative bg-white/80 dark:bg-slate-800/80 backdrop-blur-lg rounded-2xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 p-4">
                            <form method="GET" action="{{ route('messagerie.index') }}" class="relative">
                                <div class="absolute left-4 top-1/2 transform -translate-y-1/2 search-icon text-slate-400 dark:text-slate-500">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <input 
                                    type="text" 
                                    name="search_client" 
                                    value="{{ request('search_client') }}"
                                    placeholder="Rechercher une entreprise..."
                                    class="w-full pl-12 pr-4 py-3 border border-slate-300 dark:border-slate-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white/50 dark:bg-slate-700/50 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 transition-all search-input"
                                >
                            </form>
                        </div>
                    @endif
                    @if($conversationsGerant->count() > 0)
                        <div class="relative bg-white/80 dark:bg-slate-800/80 backdrop-blur-lg rounded-2xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 p-4">
                            <form method="GET" action="{{ route('messagerie.index') }}" class="relative">
                                <div class="absolute left-4 top-1/2 transform -translate-y-1/2 search-icon text-slate-400 dark:text-slate-500">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <input 
                                    type="text" 
                                    name="search_gerant" 
                                    value="{{ request('search_gerant') }}"
                                    placeholder="Rechercher un client..."
                                    class="w-full pl-12 pr-4 py-3 border border-slate-300 dark:border-slate-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white/50 dark:bg-slate-700/50 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 transition-all search-input"
                                >
                            </form>
                        </div>
                    @endif
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Conversations en tant que client -->
                @if($conversationsClient->count() > 0)
                    <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-lg rounded-2xl shadow-xl border border-slate-200/50 dark:border-slate-700/50 overflow-hidden">
                        <div class="bg-gradient-to-r from-green-500/10 to-orange-500/10 dark:from-green-500/20 dark:to-orange-500/20 border-b border-slate-200 dark:border-slate-700 px-6 py-4">
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                Mes conversations
                            </h2>
                        </div>
                        <div class="p-4 space-y-2 max-h-[600px] overflow-y-auto custom-scrollbar">
                            @foreach($conversationsClient as $conversation)
                                <a 
                                    href="{{ route('messagerie.show', $conversation->entreprise->slug) }}" 
                                    class="block conversation-item p-4 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-green-500 dark:hover:border-green-500 hover:bg-green-50/50 dark:hover:bg-green-900/10 transition-all cursor-pointer"
                                >
                                    <div class="flex items-start gap-4">
                                        <div class="relative flex-shrink-0">
                                            @if($conversation->entreprise->logo)
                                                <img 
                                                    src="{{ asset('media/' . $conversation->entreprise->logo) }}" 
                                                    alt="{{ $conversation->entreprise->nom }}"
                                                    class="w-14 h-14 rounded-xl object-cover border-2 border-slate-200 dark:border-slate-700 shadow-md"
                                                >
                                            @else
                                                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-green-500 to-orange-500 flex items-center justify-center text-white font-bold text-lg shadow-md">
                                                    {{ strtoupper(substr($conversation->entreprise->nom, 0, 1)) }}
                                                </div>
                                            @endif
                                            @if($conversation->messagesNonLus(Auth::id()) > 0)
                                                <span class="absolute -top-1 -right-1 w-5 h-5 bg-green-500 rounded-full border-2 border-white dark:border-slate-800 flex items-center justify-center">
                                                    <span class="text-xs font-bold text-white">{{ $conversation->messagesNonLus(Auth::id()) > 9 ? '9+' : $conversation->messagesNonLus(Auth::id()) }}</span>
                                                </span>
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between mb-1">
                                                <h3 class="font-bold text-slate-900 dark:text-white truncate text-lg">
                                                    {{ $conversation->entreprise->nom }}
                                                </h3>
                                                @if($conversation->dernierMessage)
                                                    <span class="text-xs text-slate-500 dark:text-slate-500 flex-shrink-0 ml-2">
                                                        {{ $conversation->dernierMessage->created_at->format('H:i') }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if($conversation->dernierMessage)
                                                <div class="flex items-center gap-2">
                                                    <p class="text-sm text-slate-600 dark:text-slate-400 truncate flex-1">
                                                        @if($conversation->messagesNonLus(Auth::id()) > 0)
                                                            <span class="font-semibold text-slate-900 dark:text-white">{{ $conversation->dernierMessage->contenu ?? '📷 Image' }}</span>
                                                        @else
                                                            {{ $conversation->dernierMessage->contenu ?? '📷 Image' }}
                                                        @endif
                                                    </p>
                                                </div>
                                                <p class="text-xs text-slate-500 dark:text-slate-500 mt-1">
                                                    {{ $conversation->dernierMessage->created_at->diffForHumans() }}
                                                </p>
                                            @else
                                                <p class="text-sm text-slate-400 dark:text-slate-500 italic">Aucun message</p>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Conversations en tant que gérant -->
                @if($conversationsGerant->count() > 0)
                    <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-lg rounded-2xl shadow-xl border border-slate-200/50 dark:border-slate-700/50 overflow-hidden">
                        <div class="bg-gradient-to-r from-orange-500/10 to-green-500/10 dark:from-orange-500/20 dark:to-green-500/20 border-b border-slate-200 dark:border-slate-700 px-6 py-4">
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                                Conversations clients
                            </h2>
                        </div>
                        <div class="p-4 space-y-2 max-h-[600px] overflow-y-auto custom-scrollbar">
                            @foreach($conversationsGerant as $conversation)
                                <a 
                                    href="{{ route('messagerie.show-gerant', [$conversation->entreprise->slug, $conversation->id]) }}" 
                                    class="block conversation-item p-4 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-orange-500 dark:hover:border-orange-500 hover:bg-orange-50/50 dark:hover:bg-orange-900/10 transition-all cursor-pointer"
                                >
                                    <div class="flex items-start gap-4">
                                        <div class="relative flex-shrink-0">
                                            <x-avatar :user="$conversation->user" size="xl" class="shadow-md" />
                                            @if($conversation->messagesNonLus(Auth::id()) > 0)
                                                <span class="absolute -top-1 -right-1 w-5 h-5 bg-orange-500 rounded-full border-2 border-white dark:border-slate-800 flex items-center justify-center">
                                                    <span class="text-xs font-bold text-white">{{ $conversation->messagesNonLus(Auth::id()) > 9 ? '9+' : $conversation->messagesNonLus(Auth::id()) }}</span>
                                                </span>
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between mb-1">
                                                <h3 class="font-bold text-slate-900 dark:text-white truncate text-lg">
                                                    {{ $conversation->user->name }}
                                                </h3>
                                                @if($conversation->dernierMessage)
                                                    <span class="text-xs text-slate-500 dark:text-slate-500 flex-shrink-0 ml-2">
                                                        {{ $conversation->dernierMessage->created_at->format('H:i') }}
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-xs text-slate-500 dark:text-slate-500 mb-1 flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                </svg>
                                                {{ $conversation->entreprise->nom }}
                                            </p>
                                            @if($conversation->dernierMessage)
                                                <div class="flex items-center gap-2">
                                                    <p class="text-sm text-slate-600 dark:text-slate-400 truncate flex-1">
                                                        @if($conversation->messagesNonLus(Auth::id()) > 0)
                                                            <span class="font-semibold text-slate-900 dark:text-white">{{ $conversation->dernierMessage->contenu ?? '📷 Image' }}</span>
                                                        @else
                                                            {{ $conversation->dernierMessage->contenu ?? '📷 Image' }}
                                                        @endif
                                                    </p>
                                                </div>
                                                <p class="text-xs text-slate-500 dark:text-slate-500 mt-1">
                                                    {{ $conversation->dernierMessage->created_at->diffForHumans() }}
                                                </p>
                                            @else
                                                <p class="text-sm text-slate-400 dark:text-slate-500 italic">Aucun message</p>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($conversationsClient->count() === 0 && $conversationsGerant->count() === 0)
                    <div class="lg:col-span-2 text-center py-16 bg-white/80 dark:bg-slate-800/80 backdrop-blur-lg rounded-2xl shadow-xl border border-slate-200/50 dark:border-slate-700/50">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-green-500/20 to-orange-500/20 mb-6">
                            <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                        </div>
                        <h3 class="mt-2 text-xl font-bold text-slate-900 dark:text-white mb-2">Aucune conversation</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 mb-6">
                            Commencez une conversation depuis la page d'une entreprise.
                        </p>
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-xl transition-all shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Explorer les entreprises
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <style>
            .custom-scrollbar::-webkit-scrollbar {
                width: 6px;
            }
            .custom-scrollbar::-webkit-scrollbar-track {
                background: transparent;
            }
            .custom-scrollbar::-webkit-scrollbar-thumb {
                background: rgb(203, 213, 225);
                border-radius: 3px;
            }
            .custom-scrollbar::-webkit-scrollbar-thumb:hover {
                background: rgb(148, 163, 184);
            }
            .dark .custom-scrollbar::-webkit-scrollbar-thumb {
                background: rgb(51, 65, 85);
            }
            .dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
                background: rgb(71, 85, 105);
            }
        </style>
    </body>
</html>

