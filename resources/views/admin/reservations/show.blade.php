<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Réservation #{{ $reservation->id }} - Admin</title>
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
        @include('admin.partials.nav')

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">Réservation #{{ $reservation->id }}</h1>
                    <p class="text-slate-600 dark:text-slate-400">{{ $reservation->entreprise->nom }}</p>
                </div>
                <a href="{{ route('admin.reservations.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                    ← Retour
                </a>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <p class="text-green-800 dark:text-green-400">{{ session('success') }}</p>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">Informations</h2>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Date et heure</dt>
                                <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $reservation->date_reservation->format('d/m/Y à H:i') }}</dd>
                            </div>
                            @if($reservation->lieu)
                                <div>
                                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Lieu</dt>
                                    <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $reservation->lieu }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Prix</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ number_format($reservation->prix, 2, ',', ' ') }} €</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Statut</dt>
                                <dd class="mt-1">
                                    <span class="px-2 py-1 text-xs rounded
                                        @if($reservation->statut === 'confirmee') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400
                                        @elseif($reservation->statut === 'annulee') bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400
                                        @elseif($reservation->statut === 'terminee') bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400
                                        @else bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400
                                        @endif">
                                        {{ ucfirst(str_replace('_', ' ', $reservation->statut)) }}
                                    </span>
                                </dd>
                            </div>
                            @if($reservation->notes)
                                <div>
                                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Notes</dt>
                                    <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $reservation->notes }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">Client</h2>
                        <div class="space-y-2">
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $reservation->user->name }}</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">{{ $reservation->user->email }}</p>
                            <a href="{{ route('admin.users.show', $reservation->user) }}" class="text-sm text-green-600 hover:text-green-700 dark:text-green-400">
                                Voir le profil →
                            </a>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">Entreprise</h2>
                        <div class="space-y-2">
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $reservation->entreprise->nom }}</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">{{ $reservation->entreprise->type_activite }}</p>
                            <a href="{{ route('admin.entreprises.show', $reservation->entreprise) }}" class="text-sm text-green-600 hover:text-green-700 dark:text-green-400">
                                Voir l'entreprise →
                            </a>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">Paiement</h2>
                        <div class="space-y-3">
                            @if($reservation->est_paye)
                                <div>
                                    <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded">Payé</span>
                                    @if($reservation->date_paiement)
                                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                                            Le {{ $reservation->date_paiement->format('d/m/Y à H:i') }}
                                        </p>
                                    @endif
                                </div>
                            @else
                                <div>
                                    <span class="px-2 py-1 text-xs bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 rounded">Non payé</span>
                                    <form action="{{ route('admin.reservations.mark-paid', $reservation) }}" method="POST" class="mt-3">
                                        @csrf
                                        <button type="submit" class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                                            Marquer comme payé
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>

