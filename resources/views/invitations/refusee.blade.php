<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invitation refusée - Allo Tata</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-script')
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 p-6 sm:p-8 text-center">
            <!-- Logo/Header -->
            <div class="mb-6">
                <a href="{{ route('home') }}" class="inline-block">
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                        Allo Tata
                    </h1>
                </a>
            </div>

            <!-- Icône de refus -->
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-400 text-3xl">
                ❌
            </div>

            <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">
                Invitation refusée
            </h2>
            <p class="text-slate-600 dark:text-slate-400 mb-4">
                Vous avez refusé cette invitation pour rejoindre <span class="font-semibold">{{ $invitation->entreprise->nom }}</span>.
            </p>
            @if($invitation->refuse_at)
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                    Refusée le {{ $invitation->refuse_at->format('d/m/Y à H:i') }}.
                </p>
            @endif

            <div class="space-y-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="block w-full px-4 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition">
                        Aller au dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="block w-full px-4 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition">
                        Se connecter
                    </a>
                @endauth
                <a href="{{ route('home') }}" class="block w-full px-4 py-3 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 font-semibold rounded-lg transition">
                    Retour à l'accueil
                </a>
            </div>
        </div>
    </div>
</body>
</html>
