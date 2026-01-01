<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $membre->user->name ?? 'Membre' }} - {{ $entreprise->nom }} - Allo Tata</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.20/index.global.min.css' rel='stylesheet' />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.20/index.global.min.js'></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        @include('partials.theme-script')
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}" class="text-2xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                            Allo Tata
                        </a>
                        
                        <!-- Lien retour entreprise -->
                        <a href="{{ route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'equipe']) }}" class="flex items-center gap-2 px-3 py-2 bg-slate-100 dark:bg-slate-700 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition">
                            @if($entreprise->logo)
                                <img src="{{ asset('storage/' . $entreprise->logo) }}" alt="" class="w-6 h-6 rounded object-cover">
                            @else
                                <div class="w-6 h-6 rounded bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white text-xs font-bold">
                                    {{ strtoupper(substr($entreprise->nom, 0, 1)) }}
                                </div>
                            @endif
                            <span class="font-medium text-slate-900 dark:text-white max-w-32 truncate">{{ $entreprise->nom }}</span>
                        </a>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <!-- Toggle thème -->
                        <button 
                            onclick="toggleTheme()"
                            class="theme-toggle-btn p-2 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-600 transition"
                        >
                            <span class="dark:hidden">🌙</span>
                            <span class="hidden dark:inline">☀️</span>
                        </button>
                        
                        <a href="{{ route('dashboard') }}" class="px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 transition">
                            Mon compte
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-red-600 dark:hover:text-red-400 transition">
                                Déconnexion
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
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

            <!-- Contenu du membre -->
            @include('entreprise.dashboard.tabs.equipe-show')
        </div>

        <script>
            function toggleTheme() {
                const html = document.documentElement;
                html.classList.toggle('dark');
                
                // Sauvegarder la préférence dans un cookie (expire dans 1 an)
                const theme = html.classList.contains('dark') ? 'dark' : 'light';
                const expires = new Date();
                expires.setFullYear(expires.getFullYear() + 1);
                document.cookie = `theme=${theme}; expires=${expires.toUTCString()}; path=/; SameSite=Lax`;
            }
        </script>
    </body>
</html>
