<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Comptabilité - {{ $entreprise->nom }} - Allo Tata</title>
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
                        <a href="{{ route('factures.entreprise', $entreprise->slug) }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                            ← Retour aux factures
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">
                    📊 Comptabilité - {{ $entreprise->nom }}
                </h1>
                <p class="text-slate-600 dark:text-slate-400">
                    Vue d'ensemble financière de votre entreprise
                </p>
            </div>

            <!-- Filtres de période -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
                <form method="GET" action="{{ route('factures.comptabilite', $entreprise->slug) }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Date de début
                        </label>
                        <input 
                            type="date" 
                            name="date_debut" 
                            value="{{ $dateDebut }}"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Date de fin
                        </label>
                        <input 
                            type="date" 
                            name="date_fin" 
                            value="{{ $dateFin }}"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                    </div>
                    <button type="submit" class="px-6 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                        🔍 Filtrer
                    </button>
                </form>
            </div>

            <!-- Statistiques globales -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Total HT</p>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($totalHT, 2, ',', ' ') }} €</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <span class="text-2xl">💰</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Total TVA</p>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($totalTVA, 2, ',', ' ') }} €</p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                            <span class="text-2xl">📋</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Total TTC</p>
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($totalTTC, 2, ',', ' ') }} €</p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                            <span class="text-2xl">✅</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques par statut -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4">Factures émises</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-slate-600 dark:text-slate-400">Nombre</span>
                            <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ $facturesEmises->count() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-slate-600 dark:text-slate-400">HT</span>
                            <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ number_format($totalHTEmises, 2, ',', ' ') }} €</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-slate-600 dark:text-slate-400">TTC</span>
                            <span class="text-sm font-semibold text-blue-600 dark:text-blue-400">{{ number_format($totalTTCEmises, 2, ',', ' ') }} €</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4">Factures payées</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-slate-600 dark:text-slate-400">Nombre</span>
                            <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ $facturesPayees->count() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-slate-600 dark:text-slate-400">HT</span>
                            <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ number_format($totalHTPayees, 2, ',', ' ') }} €</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-slate-600 dark:text-slate-400">TTC</span>
                            <span class="text-sm font-semibold text-green-600 dark:text-green-400">{{ number_format($totalTTCPayees, 2, ',', ' ') }} €</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4">Factures annulées</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-slate-600 dark:text-slate-400">Nombre</span>
                            <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ $facturesAnnulees->count() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-slate-600 dark:text-slate-400">HT</span>
                            <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ number_format($totalHTAnnulees, 2, ',', ' ') }} €</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-slate-600 dark:text-slate-400">TTC</span>
                            <span class="text-sm font-semibold text-red-600 dark:text-red-400">{{ number_format($totalTTCAnnulees, 2, ',', ' ') }} €</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Évolution par mois -->
            @if($facturesParMois->count() > 0)
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">Évolution mensuelle</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead class="bg-slate-50 dark:bg-slate-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Mois</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nombre</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total HT</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total TTC</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                                @foreach($facturesParMois as $mois => $stats)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white">
                                            {{ \Carbon\Carbon::createFromFormat('Y-m', $mois)->locale('fr')->translatedFormat('F Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-slate-600 dark:text-slate-400">
                                            {{ $stats['count'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-slate-900 dark:text-white">
                                            {{ number_format($stats['ht'], 2, ',', ' ') }} €
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-green-600 dark:text-green-400">
                                            {{ number_format($stats['ttc'], 2, ',', ' ') }} €
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Liste des factures -->
            @if($factures->count() > 0)
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Liste des factures ({{ $factures->count() }})</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead class="bg-slate-50 dark:bg-slate-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Numéro</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Client</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">HT</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">TVA</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">TTC</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Statut</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                                @foreach($factures as $facture)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-slate-900 dark:text-white">{{ $facture->numero_facture }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-slate-900 dark:text-white">{{ $facture->user->name }}</div>
                                            <div class="text-sm text-slate-500 dark:text-slate-400">{{ $facture->user->email }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-slate-900 dark:text-white">{{ $facture->date_facture->format('d/m/Y') }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-slate-900 dark:text-white">
                                            {{ number_format($facture->montant_ht, 2, ',', ' ') }} €
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-slate-600 dark:text-slate-400">
                                            {{ number_format($facture->montant_tva, 2, ',', ' ') }} €
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-right text-slate-900 dark:text-white">
                                            {{ number_format($facture->montant_ttc, 2, ',', ' ') }} €
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-medium rounded
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
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center justify-end gap-3">
                                                <a href="{{ route('factures.entreprise.show', [$entreprise->slug, $facture->id]) }}" class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 hover:bg-blue-200 dark:hover:bg-blue-900/50 text-blue-700 dark:text-blue-400 rounded-lg transition text-sm font-medium">
                                                    👁️ Voir
                                                </a>
                                                <a href="{{ route('factures.entreprise.download', [$entreprise->slug, $facture->id]) }}" class="px-3 py-1 bg-green-100 dark:bg-green-900/30 hover:bg-green-200 dark:hover:bg-green-900/50 text-green-700 dark:text-green-400 rounded-lg transition text-sm font-medium">
                                                    📄 PDF
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="text-center py-12 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-slate-900 dark:text-white">Aucune facture</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Aucune facture trouvée pour cette période.
                    </p>
                </div>
            @endif
        </div>
    </body>
</html>

