<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $notification->titre }} - Allo Tata</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.theme-script')
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
                        <a href="{{ route('notifications.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                            ← Retour aux notifications
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-8">
                <div class="flex items-start gap-4 mb-6">
                    <div class="flex-shrink-0">
                        @if($notification->type === 'reservation')
                            <div class="w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                <span class="text-4xl">📅</span>
                            </div>
                        @elseif($notification->type === 'paiement')
                            <div class="w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                <span class="text-4xl">💳</span>
                            </div>
                        @elseif($notification->type === 'rappel')
                            <div class="w-16 h-16 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                                <span class="text-4xl">⏰</span>
                            </div>
                        @else
                            <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                                <span class="text-4xl">📢</span>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">
                            {{ $notification->titre }}
                        </h1>
                        <div class="flex items-center gap-4 text-sm text-slate-500 dark:text-slate-400 mb-4">
                            <span>{{ $notification->created_at->format('d/m/Y à H:i') }}</span>
                            @if($notification->est_lue)
                                <span>• Lu le {{ $notification->lue_at->format('d/m/Y à H:i') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="prose dark:prose-invert max-w-none mb-6">
                    <p class="text-slate-700 dark:text-slate-300 whitespace-pre-line text-lg leading-relaxed">
                        {{ $notification->message }}
                    </p>
                </div>

                @if($notification->lien)
                    <div class="border-t border-slate-200 dark:border-slate-700 pt-6">
                        <a href="{{ $notification->lien }}" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                            Voir les détails
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </body>
</html>

