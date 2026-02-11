<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Session expirée - Allo Tata</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-script')
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full text-center">
            <!-- Icône d'erreur -->
            <div class="mb-8 flex justify-center">
                <div class="w-24 h-24 sm:w-32 sm:h-32 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                    <svg class="w-12 h-12 sm:w-16 sm:h-16 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
            </div>

            <!-- Titre -->
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 dark:text-white mb-4">
                Session expirée
            </h1>

            <!-- Message explicatif -->
            <p class="text-lg sm:text-xl text-slate-600 dark:text-slate-400 mb-2">
                Votre session a expiré pour des raisons de sécurité.
            </p>
            <p class="text-base sm:text-lg text-slate-500 dark:text-slate-500 mb-8">
                Cela peut arriver si vous êtes resté inactif trop longtemps ou si vous avez ouvert la page dans un autre onglet.
            </p>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <!-- Bouton Retour -->
                <button 
                    onclick="window.history.back()" 
                    class="w-full sm:w-auto px-6 py-3 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-medium rounded-lg transition-colors duration-200 flex items-center justify-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Retour
                </button>

                <!-- Bouton Recharger -->
                <button 
                    onclick="window.location.reload()" 
                    class="w-full sm:w-auto px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200 flex items-center justify-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Recharger la page
                </button>

                <!-- Bouton Accueil -->
                <a 
                    href="{{ route('home') }}" 
                    class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-green-500 to-orange-500 hover:from-green-600 hover:to-orange-600 text-white font-medium rounded-lg transition-all duration-200 flex items-center justify-center gap-2 shadow-lg hover:shadow-xl"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Page d'accueil
                </a>
            </div>

            <!-- Message d'aide supplémentaire -->
            <div class="mt-12 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg text-left">
                <p class="text-sm text-blue-800 dark:text-blue-400 mb-2 font-medium">
                    💡 Conseils pour éviter ce problème :
                </p>
                <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1 list-disc list-inside">
                    <li>Rechargez la page avant de soumettre un formulaire si vous êtes resté longtemps sur la page</li>
                    <li>Évitez d'ouvrir plusieurs onglets avec le même formulaire</li>
                    <li>Si le problème persiste, essayez de vous reconnecter</li>
                </ul>
            </div>
        </div>
    </div>

    @include('partials.cookie-banner')
</body>
</html>
