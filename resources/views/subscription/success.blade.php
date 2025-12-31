<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Abonnement activé - Allo Tata</title>
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
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-8 text-center">
                <div class="mb-6">
                    <svg class="mx-auto w-16 h-16 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                @if(isset($pending) && $pending)
                    <div class="mb-6">
                        <svg class="mx-auto w-16 h-16 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-4">
                        Traitement en cours...
                    </h1>
                    <p class="text-slate-600 dark:text-slate-400 mb-8">
                        Votre paiement a été effectué avec succès. Nous synchronisons votre abonnement. Cela peut prendre quelques secondes. Veuillez rafraîchir la page dans quelques instants.
                    </p>
                    <div class="flex gap-4 justify-center">
                        <a href="{{ route('subscription.index') }}" class="inline-block px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                            Vérifier l'abonnement
                        </a>
                        <a href="{{ route('dashboard') }}" class="inline-block px-6 py-3 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-lg transition-all">
                            Retour au dashboard
                        </a>
                    </div>
                @else
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-4">
                        Abonnement activé avec succès !
                    </h1>
                    <p class="text-slate-600 dark:text-slate-400 mb-8">
                        Votre abonnement a été activé. Vous avez maintenant accès à toutes les fonctionnalités premium.
                    </p>
                    <a href="{{ route('dashboard') }}" class="inline-block px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                        Retour au dashboard
                    </a>
                @endif
            </div>
        </div>
    </body>
</html>

