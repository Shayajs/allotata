<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Facture {{ $facture->numero_facture }} - Allo Tata</title>
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
                        <a href="{{ isset($isGerant) && $isGerant ? route('factures.entreprise', $facture->entreprise->slug) : route('factures.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                            ← Retour
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Facture -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-8">
                <!-- En-tête -->
                <div class="flex items-start justify-between mb-8 pb-8 border-b border-slate-200 dark:border-slate-700">
                    <div class="flex items-start gap-4">
                        @if($facture->entreprise->logo)
                            <img 
                                src="{{ asset('storage/' . $facture->entreprise->logo) }}" 
                                alt="Logo {{ $facture->entreprise->nom }}"
                                class="w-20 h-20 rounded-lg object-cover border-2 border-slate-200 dark:border-slate-700"
                            >
                        @endif
                        <div>
                            <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">FACTURE</h1>
                            <p class="text-sm text-slate-600 dark:text-slate-400">Numéro : <span class="font-semibold">{{ $facture->numero_facture }}</span></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-slate-600 dark:text-slate-400">Date d'émission</p>
                        <p class="text-lg font-semibold text-slate-900 dark:text-white">{{ $facture->date_facture->format('d/m/Y') }}</p>
                        @if($facture->date_echeance)
                            <p class="text-sm text-slate-600 dark:text-slate-400 mt-2">Échéance</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $facture->date_echeance->format('d/m/Y') }}</p>
                        @endif
                    </div>
                </div>

                <!-- Informations entreprise et client -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3 uppercase">Facturé par</h3>
                        <div class="space-y-1">
                            <p class="font-semibold text-slate-900 dark:text-white">{{ $facture->entreprise->nom }}</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">{{ $facture->entreprise->type_activite }}</p>
                            @if($facture->entreprise->siren)
                                <p class="text-sm text-slate-600 dark:text-slate-400">SIREN : {{ $facture->entreprise->siren }}</p>
                            @endif
                            <p class="text-sm text-slate-600 dark:text-slate-400">{{ $facture->entreprise->email }}</p>
                            @if($facture->entreprise->telephone)
                                <p class="text-sm text-slate-600 dark:text-slate-400">{{ $facture->entreprise->telephone }}</p>
                            @endif
                            @if($facture->entreprise->ville)
                                <p class="text-sm text-slate-600 dark:text-slate-400">{{ $facture->entreprise->ville }}</p>
                            @endif
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3 uppercase">Facturé à</h3>
                        <div class="space-y-1">
                            <p class="font-semibold text-slate-900 dark:text-white">{{ $facture->user->name }}</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">{{ $facture->user->email }}</p>
                        </div>
                    </div>
                </div>

                <!-- Détails de la réservation (facture simple) -->
                @if($facture->reservation && !$facture->estGroupee())
                    <div class="mb-8 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Détails de la réservation</h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-slate-600 dark:text-slate-400">Service</p>
                                <p class="font-medium text-slate-900 dark:text-white">{{ $facture->reservation->typeService ? $facture->reservation->typeService->nom : ($facture->reservation->type_service ?? 'Service') }}</p>
                            </div>
                            <div>
                                <p class="text-slate-600 dark:text-slate-400">Date</p>
                                <p class="font-medium text-slate-900 dark:text-white">{{ $facture->reservation->date_reservation->format('d/m/Y à H:i') }}</p>
                            </div>
                            @if($facture->reservation->lieu)
                                <div>
                                    <p class="text-slate-600 dark:text-slate-400">Lieu</p>
                                    <p class="font-medium text-slate-900 dark:text-white">{{ $facture->reservation->lieu }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                @if($facture->estGroupee())
                    <div class="mb-8 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                        <h3 class="text-sm font-semibold text-purple-700 dark:text-purple-300 mb-2">📋 Facture groupée</h3>
                        <p class="text-sm text-purple-600 dark:text-purple-400">Cette facture regroupe {{ $facture->reservations->count() }} réservation(s).</p>
                    </div>
                @endif

                <!-- Tableau des lignes -->
                <div class="mb-8">
                    <table class="w-full">
                        <thead class="bg-slate-50 dark:bg-slate-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700 dark:text-slate-300">Description</th>
                                @if($facture->estGroupee())
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700 dark:text-slate-300">Date</th>
                                @endif
                                <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700 dark:text-slate-300">Montant HT</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700 dark:text-slate-300">TVA</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700 dark:text-slate-300">Montant TTC</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            @if($facture->estGroupee())
                                @foreach($facture->reservations as $reservation)
                                    <tr>
                                        <td class="px-4 py-4 text-sm text-slate-900 dark:text-white">
                                            {{ $reservation->typeService ? $reservation->typeService->nom : ($reservation->type_service ?? 'Service') }}
                                            @if($reservation->duree_minutes)
                                                <span class="text-slate-500 dark:text-slate-400">({{ $reservation->duree_minutes }} min)</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-sm text-slate-900 dark:text-white">{{ $reservation->date_reservation->format('d/m/Y H:i') }}</td>
                                        <td class="px-4 py-4 text-sm text-right text-slate-900 dark:text-white">{{ number_format($reservation->prix, 2, ',', ' ') }} €</td>
                                        <td class="px-4 py-4 text-sm text-right text-slate-900 dark:text-white">
                                            @if($facture->taux_tva > 0)
                                                {{ $facture->taux_tva }}% ({{ number_format($reservation->prix * ($facture->taux_tva / 100), 2, ',', ' ') }} €)
                                            @else
                                                Exonéré
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-sm font-semibold text-right text-slate-900 dark:text-white">{{ number_format($reservation->prix * (1 + $facture->taux_tva / 100), 2, ',', ' ') }} €</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td class="px-4 py-4 text-sm text-slate-900 dark:text-white">
                                        {{ $facture->reservation->typeService ? $facture->reservation->typeService->nom : ($facture->reservation->type_service ?? 'Service') }}
                                        @if($facture->reservation && $facture->reservation->duree_minutes)
                                            <span class="text-slate-500 dark:text-slate-400">({{ $facture->reservation->duree_minutes }} min)</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-sm text-right text-slate-900 dark:text-white">{{ number_format($facture->montant_ht, 2, ',', ' ') }} €</td>
                                    <td class="px-4 py-4 text-sm text-right text-slate-900 dark:text-white">
                                        @if($facture->taux_tva > 0)
                                            {{ $facture->taux_tva }}% ({{ number_format($facture->montant_tva, 2, ',', ' ') }} €)
                                        @else
                                            Exonéré
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-sm font-semibold text-right text-slate-900 dark:text-white">{{ number_format($facture->montant_ttc, 2, ',', ' ') }} €</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- Total -->
                <div class="flex justify-end mb-8">
                    <div class="w-64 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-600 dark:text-slate-400">Total HT</span>
                            <span class="font-medium text-slate-900 dark:text-white">{{ number_format($facture->montant_ht, 2, ',', ' ') }} €</span>
                        </div>
                        @if($facture->taux_tva > 0)
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-600 dark:text-slate-400">TVA ({{ $facture->taux_tva }}%)</span>
                                <span class="font-medium text-slate-900 dark:text-white">{{ number_format($facture->montant_tva, 2, ',', ' ') }} €</span>
                            </div>
                        @endif
                        <div class="flex justify-between pt-2 border-t border-slate-200 dark:border-slate-700">
                            <span class="text-lg font-bold text-slate-900 dark:text-white">Total TTC</span>
                            <span class="text-lg font-bold text-slate-900 dark:text-white">{{ number_format($facture->montant_ttc, 2, ',', ' ') }} €</span>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                @if($facture->notes)
                    <div class="mb-8 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Notes</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ $facture->notes }}</p>
                    </div>
                @endif

                <!-- Statut -->
                <div class="flex items-center justify-between pt-8 border-t border-slate-200 dark:border-slate-700">
                    <div>
                        <p class="text-sm text-slate-600 dark:text-slate-400">Statut</p>
                        <span class="inline-block mt-1 px-3 py-1 text-sm font-medium rounded
                            @if($facture->statut === 'payee') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                            @elseif($facture->statut === 'annulee') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                            @else bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                            @endif">
                            @if($facture->statut === 'payee') Payée
                            @elseif($facture->statut === 'annulee') Annulée
                            @elseif($facture->statut === 'brouillon') Brouillon
                            @else Émise
                            @endif
                        </span>
                    </div>
                    <div class="flex gap-3">
                        <a href="{{ isset($isGerant) && $isGerant ? route('factures.entreprise.download', [$facture->entreprise->slug, $facture->id]) : route('factures.download', $facture->id) }}" class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                            📄 Télécharger PDF
                        </a>
                        <button onclick="window.print()" class="px-6 py-3 bg-slate-600 hover:bg-slate-700 text-white font-semibold rounded-lg transition-all">
                            🖨️ Imprimer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>

