<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">
                Factures
            </h2>
            <p class="text-slate-600 dark:text-slate-400">
                Gérez les factures générées automatiquement pour les réservations payées.
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('factures.create-groupee', $entreprise->slug) }}" class="px-6 py-3 bg-gradient-to-r from-purple-600 to-purple-500 hover:from-purple-700 hover:to-purple-600 text-white font-semibold rounded-lg transition-all">
                📋 Facture groupée
            </a>
            <a href="{{ route('factures.comptabilite', $entreprise->slug) }}" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 text-white font-semibold rounded-lg transition-all">
                📊 Comptabilité
            </a>
        </div>
    </div>

            <!-- Barre de recherche et filtres -->
            @if($factures->total() > 0 || request()->hasAny(['search', 'statut', 'date_debut', 'date_fin']))
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
                    <form method="GET" action="{{ route('factures.entreprise', $entreprise->slug) }}" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Rechercher
                                </label>
                                <input 
                                    type="text" 
                                    name="search" 
                                    value="{{ request('search') }}"
                                    placeholder="Numéro, client..."
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Statut
                                </label>
                                <select 
                                    name="statut" 
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                >
                                    <option value="">Tous les statuts</option>
                                    <option value="emise" {{ request('statut') === 'emise' ? 'selected' : '' }}>Émise</option>
                                    <option value="payee" {{ request('statut') === 'payee' ? 'selected' : '' }}>Payée</option>
                                    <option value="annulee" {{ request('statut') === 'annulee' ? 'selected' : '' }}>Annulée</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Date début
                                </label>
                                <input 
                                    type="date" 
                                    name="date_debut" 
                                    value="{{ request('date_debut') }}"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                >
                            </div>
                            <div class="flex items-end gap-2">
                                <button type="submit" class="flex-1 px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                                    🔍 Rechercher
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Date fin
                            </label>
                            <input 
                                type="date" 
                                name="date_fin" 
                                value="{{ request('date_fin') }}"
                                class="w-full md:w-1/3 px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            >
                        </div>
                        @if(request()->hasAny(['search', 'statut', 'date_debut', 'date_fin']))
                            <a href="{{ route('factures.entreprise', $entreprise->slug) }}" class="text-sm text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400">
                                Réinitialiser les filtres
                            </a>
                        @endif
                    </form>
                </div>
            @endif

            @if($factures->count() > 0)
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead class="bg-slate-50 dark:bg-slate-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Numéro</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Client</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Montant TTC</th>
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
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-semibold text-green-600 dark:text-green-400">{{ number_format($facture->montant_ttc, 2, ',', ' ') }} €</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                                @if($facture->statut === 'payee') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                                @elseif($facture->statut === 'annulee') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                                                @else bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                                                @endif">
                                                @if($facture->statut === 'payee') Payée
                                                @elseif($facture->statut === 'annulee') Annulée
                                                @else Émise
                                                @endif
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ route('factures.entreprise.show', [$entreprise->slug, $facture->id]) }}" class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300 mr-3">
                                                Voir
                                            </a>
                                            <a href="{{ route('factures.entreprise.download', [$entreprise->slug, $facture->id]) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">
                                                PDF
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $factures->links() }}
                </div>
            @else
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Aucune facture</h3>
                    <p class="text-slate-600 dark:text-slate-400">
                        Les factures sont générées automatiquement lorsqu'une réservation est marquée comme payée.
                    </p>
                </div>
            @endif
</div>
