<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Notifications - Allo Tata</title>
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
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <a href="{{ route('dashboard') }}" class="text-2xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                        Allo Tata
                    </a>
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                            Retour au dashboard
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">
                        📬 Notifications
                    </h1>
                    <p class="text-slate-600 dark:text-slate-400">
                        @if($nombreNonLues > 0)
                            Vous avez {{ $nombreNonLues }} notification{{ $nombreNonLues > 1 ? 's' : '' }} non lue{{ $nombreNonLues > 1 ? 's' : '' }}
                        @else
                            Aucune notification non lue
                        @endif
                    </p>
                </div>
                @if($nombreNonLues > 0)
                    <form action="{{ route('notifications.marquer-toutes-lues') }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-lg transition text-sm font-medium">
                            Tout marquer comme lu
                        </button>
                    </form>
                @endif
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <p class="text-green-800 dark:text-green-400">{{ session('success') }}</p>
                </div>
            @endif

            <!-- Filtres -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 mb-6">
                <form method="GET" action="{{ route('notifications.index') }}" class="flex flex-wrap gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Type
                        </label>
                        <select 
                            name="type" 
                            class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                            <option value="">Tous les types</option>
                            <option value="reservation" {{ request('type') === 'reservation' ? 'selected' : '' }}>Réservations</option>
                            <option value="paiement" {{ request('type') === 'paiement' ? 'selected' : '' }}>Paiements</option>
                            <option value="rappel" {{ request('type') === 'rappel' ? 'selected' : '' }}>Rappels</option>
                            <option value="systeme" {{ request('type') === 'systeme' ? 'selected' : '' }}>Système</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Statut
                        </label>
                        <select 
                            name="statut" 
                            class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                            <option value="">Toutes</option>
                            <option value="non_lue" {{ request('statut') === 'non_lue' ? 'selected' : '' }}>Non lues</option>
                            <option value="lue" {{ request('statut') === 'lue' ? 'selected' : '' }}>Lues</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                            🔍 Filtrer
                        </button>
                    </div>
                    @if(request()->hasAny(['type', 'statut']))
                        <div class="flex items-end">
                            <a href="{{ route('notifications.index') }}" class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition text-sm">
                                Réinitialiser
                            </a>
                        </div>
                    @endif
                </form>
            </div>

            <!-- Liste des notifications -->
            @if($notifications->count() > 0)
                <div class="space-y-3">
                    @foreach($notifications as $notification)
                        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 hover:border-green-500 dark:hover:border-green-500 transition-all {{ !$notification->est_lue ? 'ring-2 ring-green-500/20' : '' }}">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0">
                                    @if($notification->type === 'reservation')
                                        <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                            <span class="text-2xl">📅</span>
                                        </div>
                                    @elseif($notification->type === 'paiement')
                                        <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                            <span class="text-2xl">💳</span>
                                        </div>
                                    @elseif($notification->type === 'rappel')
                                        <div class="w-12 h-12 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                                            <span class="text-2xl">⏰</span>
                                        </div>
                                    @else
                                        <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                                            <span class="text-2xl">📢</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                                                    {{ $notification->titre }}
                                                </h3>
                                                @if(!$notification->est_lue)
                                                    <span class="px-2 py-0.5 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full">
                                                        Nouveau
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-slate-600 dark:text-slate-400 mb-2 whitespace-pre-line">
                                                {{ $notification->message }}
                                            </p>
                                            <div class="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
                                                <span>{{ $notification->created_at->format('d/m/Y à H:i') }}</span>
                                                @if($notification->est_lue)
                                                    <span>Lu le {{ $notification->lue_at->format('d/m/Y à H:i') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            @if($notification->lien)
                                                <a href="{{ $notification->lien }}" class="px-3 py-1 text-sm bg-green-100 dark:bg-green-900/30 hover:bg-green-200 dark:hover:bg-green-900/50 text-green-800 dark:text-green-400 rounded-lg transition">
                                                    Voir →
                                                </a>
                                            @endif
                                            @if(!$notification->est_lue)
                                                <form action="{{ route('notifications.marquer-lue', $notification->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="px-3 py-1 text-sm bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-lg transition">
                                                        Marquer lu
                                                    </button>
                                                </form>
                                            @endif
                                            <form action="{{ route('notifications.destroy', $notification->id) }}" method="POST" onsubmit="return confirm('Supprimer cette notification ?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-3 py-1 text-sm bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 text-red-800 dark:text-red-400 rounded-lg transition">
                                                    Supprimer
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $notifications->links() }}
                </div>
            @else
                <div class="text-center py-12 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-slate-900 dark:text-white">Aucune notification</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Vous n'avez pas encore de notifications.
                    </p>
                </div>
            @endif
        </div>
    </body>
</html>

