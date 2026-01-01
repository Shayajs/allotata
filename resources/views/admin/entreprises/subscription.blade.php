<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Gestion abonnement - {{ $entreprise->nom }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.theme-script')
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
        @include('admin.partials.nav')

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <a href="{{ route('admin.entreprises.show', $entreprise) }}" class="text-sm text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 mb-4 inline-block">
                    ← Retour à l'entreprise
                </a>
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">
                    Gestion de l'abonnement manuel
                </h1>
                <p class="text-slate-600 dark:text-slate-400">
                    {{ $entreprise->nom }} - Gérée par {{ $entreprise->user->name }}
                </p>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <p class="text-green-800 dark:text-green-400">{{ session('success') }}</p>
                </div>
            @endif

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">Activer un abonnement manuel</h2>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-6">
                    Utilisez cette fonctionnalité pour gérer manuellement l'abonnement d'une entreprise (paiement direct, ristourne, etc.).
                </p>

                <form action="{{ route('admin.entreprises.activate-subscription', $entreprise) }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Date de fin de l'abonnement *
                            </label>
                            <input 
                                type="date" 
                                name="date_fin" 
                                required
                                min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            >
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                L'abonnement sera actif jusqu'à cette date incluse.
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Notes (optionnel)
                            </label>
                            <textarea 
                                name="notes" 
                                rows="3"
                                placeholder="Ex: Ristourne de 50%, paiement direct, etc."
                                maxlength="500"
                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            ></textarea>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                Notes internes pour référence (non visibles par l'entreprise).
                            </p>
                        </div>

                        <div class="flex gap-4">
                            <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                                Activer l'abonnement
                            </button>
                            <a href="{{ route('admin.entreprises.show', $entreprise) }}" class="px-6 py-3 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                                Annuler
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </body>
</html>

