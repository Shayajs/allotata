<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Réservation - {{ $entreprise->nom }}</title>
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
                        <a href="{{ route('reservations.index', $entreprise->slug) }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                            ← Retour
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <p class="text-green-800 dark:text-green-400">{{ session('success') }}</p>
                </div>
            @endif

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">
                            Réservation #{{ $reservation->id }}
                        </h1>
                        <span class="px-3 py-1 text-sm font-medium rounded-full
                            @if($reservation->statut === 'confirmee') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                            @elseif($reservation->statut === 'annulee') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                            @elseif($reservation->statut === 'terminee') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                            @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400
                            @endif">
                            @if($reservation->statut === 'confirmee') Confirmée
                            @elseif($reservation->statut === 'annulee') Annulée
                            @elseif($reservation->statut === 'terminee') Terminée
                            @else En attente
                            @endif
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Client</h3>
                        <p class="text-slate-900 dark:text-white">{{ $reservation->user->name }}</p>
                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ $reservation->user->email }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Service</h3>
                        <p class="text-slate-900 dark:text-white">{{ $reservation->type_service ?? 'Service' }}</p>
                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ $reservation->duree_minutes }} minutes</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Date et heure</h3>
                        <p class="text-slate-900 dark:text-white">{{ $reservation->date_reservation->format('d/m/Y à H:i') }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Prix</h3>
                        <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ number_format($reservation->prix, 2, ',', ' ') }} €</p>
                        @if($reservation->est_paye)
                            <span class="inline-block mt-2 px-3 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full">
                                ✓ Payé le {{ $reservation->date_paiement->format('d/m/Y') }}
                            </span>
                        @else
                            <span class="inline-block mt-2 px-3 py-1 text-xs bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 rounded-full">
                                ⏳ Non payé
                            </span>
                        @endif
                    </div>
                    @if($reservation->lieu)
                        <div>
                            <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Lieu</h3>
                            <p class="text-slate-900 dark:text-white">{{ $reservation->lieu }}</p>
                        </div>
                    @endif
                    <div>
                        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Téléphone</h3>
                        <p class="text-slate-900 dark:text-white">
                            {{ $reservation->telephone_client }}
                            @if($reservation->telephone_cache)
                                <span class="text-xs text-slate-500 dark:text-slate-400">(masqué)</span>
                            @endif
                        </p>
                    </div>
                </div>

                @if($reservation->notes)
                    <div class="mb-6 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Notes</h3>
                        <p class="text-slate-900 dark:text-white whitespace-pre-line">{{ $reservation->notes }}</p>
                    </div>
                @endif

                <!-- Actions pour les réservations en attente -->
                @if($reservation->statut === 'en_attente')
                    <div class="border-t border-slate-200 dark:border-slate-700 pt-6">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Actions</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Accepter -->
                            <form action="{{ route('reservations.accept', [$entreprise->slug, $reservation->id]) }}" method="POST">
                                @csrf
                                <div class="space-y-3">
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Note (optionnel)
                                    </label>
                                    <textarea 
                                        name="notes_gerant" 
                                        rows="3"
                                        placeholder="Ajouter une note pour cette réservation..."
                                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                    ></textarea>
                                    <button 
                                        type="submit" 
                                        class="w-full px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all"
                                    >
                                        ✓ Accepter la réservation
                                    </button>
                                </div>
                            </form>

                            <!-- Refuser -->
                            <form action="{{ route('reservations.reject', [$entreprise->slug, $reservation->id]) }}" method="POST">
                                @csrf
                                <div class="space-y-3">
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Raison du refus (optionnel)
                                    </label>
                                    <textarea 
                                        name="raison_refus" 
                                        rows="3"
                                        placeholder="Expliquez pourquoi vous refusez..."
                                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                    ></textarea>
                                    <button 
                                        type="submit" 
                                        onclick="return confirm('Êtes-vous sûr de vouloir refuser cette réservation ?');"
                                        class="w-full px-6 py-3 bg-gradient-to-r from-red-600 to-red-500 hover:from-red-700 hover:to-red-600 text-white font-semibold rounded-lg transition-all"
                                    >
                                        ✗ Refuser la réservation
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="border-t border-slate-200 dark:border-slate-700 pt-6 space-y-6">
                        <!-- Marquer comme payé -->
                        @if(!$reservation->est_paye)
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">💳 Marquer le paiement</h3>
                                <form action="{{ route('reservations.marquer-payee', [$entreprise->slug, $reservation->id]) }}" method="POST">
                                    @csrf
                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                Date du paiement
                                            </label>
                                            <input 
                                                type="date" 
                                                name="date_paiement" 
                                                value="{{ now()->format('Y-m-d') }}"
                                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                            >
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                Note (optionnel)
                                            </label>
                                            <textarea 
                                                name="notes_paiement" 
                                                rows="2"
                                                placeholder="Ex: Paiement en espèces, virement reçu..."
                                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                            ></textarea>
                                        </div>
                                        <button 
                                            type="submit" 
                                            class="w-full px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all"
                                        >
                                            ✓ Marquer comme payé
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endif

                        <!-- Ajouter des notes pour les réservations confirmées -->
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Ajouter une note</h3>
                            <form action="{{ route('reservations.notes', [$entreprise->slug, $reservation->id]) }}" method="POST">
                                @csrf
                                <div class="space-y-3">
                                    <textarea 
                                        name="notes_gerant" 
                                        rows="3"
                                        placeholder="Ajouter une note pour cette réservation..."
                                        required
                                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                    ></textarea>
                                    <button 
                                        type="submit" 
                                        class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all"
                                    >
                                        Ajouter la note
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </body>
</html>

