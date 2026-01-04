<div class="space-y-8">
    <!-- En-tête avec Totaux -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform">
                <span class="text-6xl">📈</span>
            </div>
            <p class="text-green-100 text-sm font-semibold uppercase tracking-wider mb-2">Recettes (Entrées)</p>
            <h3 class="text-3xl font-bold">{{ number_format($financeStats['totalIncome'], 2, ',', ' ') }} €</h3>
            <p class="mt-2 text-xs text-green-100/80">Pour la période sélectionnée</p>
        </div>

        <div class="bg-gradient-to-br from-red-500 to-orange-600 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform">
                <span class="text-6xl">📉</span>
            </div>
            <p class="text-red-100 text-sm font-semibold uppercase tracking-wider mb-2">Dépenses (Sorties)</p>
            <h3 class="text-3xl font-bold">{{ number_format($financeStats['totalExpense'], 2, ',', ' ') }} €</h3>
            <p class="mt-2 text-xs text-red-100/80">Achats, loyers, matériel...</p>
        </div>

        <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform">
                <span class="text-6xl">🏛️</span>
            </div>
            <p class="text-blue-100 text-sm font-semibold uppercase tracking-wider mb-2">Charges & Impôts (Est.)</p>
            <h3 class="text-3xl font-bold">{{ number_format($financeStats['chargesEstimées']['total'], 2, ',', ' ') }} €</h3>
            <p class="mt-2 text-xs text-blue-100/80">Estimation URSSAF ({{ $financeStats['chargesEstimées']['taux_combine'] }}%)</p>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-violet-600 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform">
                <span class="text-6xl">💎</span>
            </div>
            <p class="text-purple-100 text-sm font-semibold uppercase tracking-wider mb-2">Reste à vivre (Net)</p>
            @php
                $net = $financeStats['totalIncome'] - $financeStats['totalExpense'] - $financeStats['chargesEstimées']['total'];
            @endphp
            <h3 class="text-3xl font-bold">{{ number_format($net, 2, ',', ' ') }} €</h3>
            <p class="mt-2 text-xs text-purple-100/80">Bénéfice net après charges</p>
        </div>
    </div>

    <!-- Filtres et Actions -->
    <div class="flex flex-col md:flex-row items-center justify-between gap-4 py-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl px-6 border border-slate-200 dark:border-slate-700">
        <form action="{{ route('entreprise.dashboard', $entreprise->slug) }}" method="GET" class="flex flex-wrap items-center gap-4">
            <input type="hidden" name="tab" value="finances">
            
            <select name="finance_month" class="bg-white dark:bg-slate-700 border-slate-300 dark:border-slate-600 rounded-xl px-4 py-2 text-sm focus:ring-green-500 focus:border-green-500 transition-all">
                @foreach(range(1, 12) as $m)
                    <option value="{{ $m }}" {{ $financeStats['selectedMonth'] == $m ? 'selected' : '' }}>
                        {{ Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
                    </option>
                @endforeach
            </select>

            <select name="finance_year" class="bg-white dark:bg-slate-700 border-slate-300 dark:border-slate-600 rounded-xl px-4 py-2 text-sm focus:ring-green-500 focus:border-green-500 transition-all">
                @foreach(range(now()->year - 2, now()->year + 1) as $y)
                    <option value="{{ $y }}" {{ $financeStats['selectedYear'] == $y ? 'selected' : '' }}>
                        {{ $y }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="p-2 bg-slate-200 dark:bg-slate-600 hover:bg-slate-300 dark:hover:bg-slate-500 rounded-xl transition-all">
                🔄
            </button>
        </form>

        <div class="flex items-center gap-3">
            <button 
                onclick="document.getElementById('modal-add-record').classList.remove('hidden')"
                class="px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold rounded-xl shadow-lg transition-all transform hover:-translate-y-1"
            >
                + Ajouter une entrée/sortie
            </button>
        </div>
    </div>

    <!-- Liste des transactions -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
        <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">Détail des transactions</h2>
            <span class="text-sm text-slate-500">{{ $finances->count() }} enregistrements</span>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-100 dark:bg-slate-700">
                        <th class="px-8 py-4 text-xs font-bold text-slate-500 dark:text-slate-300 uppercase tracking-wider">Date</th>
                        <th class="px-8 py-4 text-xs font-bold text-slate-500 dark:text-slate-300 uppercase tracking-wider">Description / Catégorie</th>
                        <th class="px-8 py-4 text-xs font-bold text-slate-500 dark:text-slate-300 uppercase tracking-wider">Type</th>
                        <th class="px-8 py-4 text-xs font-bold text-slate-500 dark:text-slate-300 uppercase tracking-wider text-right">Montant</th>
                        <th class="px-8 py-4 text-xs font-bold text-slate-500 dark:text-slate-300 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse($finances->sortByDesc('date_record') as $record)
                        <tr class="bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <td class="px-8 py-5 text-sm text-slate-700 dark:text-slate-300">
                                {{ $record->date_record->translatedFormat('d F Y') }}
                            </td>
                            <td class="px-8 py-5">
                                <div class="text-sm font-semibold text-slate-900 dark:text-white">{{ $record->description ?: 'Sans description' }}</div>
                                <div class="text-xs text-slate-500">{{ $record->category ?: 'Sans catégorie' }}</div>
                            </td>
                            <td class="px-8 py-5">
                                @if($record->type === 'income')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                        Entrée
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                        Sortie
                                    </span>
                                @endif
                            </td>
                            <td class="px-8 py-5 text-right font-bold {{ $record->type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $record->type === 'income' ? '+' : '-' }} {{ number_format($record->amount, 2, ',', ' ') }} €
                            </td>
                            <td class="px-8 py-5 text-right">
                                <form action="{{ route('entreprise.finances.destroy', [$entreprise->slug, $record->id]) }}" method="POST" class="inline-block" onsubmit="return confirm('Confirmer la suppression ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-600 transition-colors p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20">
                                        🗑️
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr class="bg-white dark:bg-slate-800">
                            <td colspan="5" class="px-8 py-12 text-center text-slate-500 dark:text-slate-400">
                                <div class="text-4xl mb-4">📂</div>
                                Aucun enregistrement pour cette période.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Ajout Record -->
<div id="modal-add-record" class="hidden fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="document.getElementById('modal-add-record').classList.add('hidden')"></div>

        <div class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 dark:border-slate-700">
            <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-4">
                <h3 class="text-lg font-bold text-white">Nouveau mouvement financier</h3>
            </div>
            
            <form action="{{ route('entreprise.finances.store', $entreprise->slug) }}" method="POST" class="px-6 py-6 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Type</label>
                        <select name="type" required class="w-full bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 focus:ring-green-500">
                            <option value="income">Recette (Entrée)</option>
                            <option value="expense">Dépense (Sortie)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Date</label>
                        <input type="date" name="date_record" required value="{{ date('Y-m-d') }}" class="w-full bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 focus:ring-green-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Montant (€)</label>
                    <input type="number" step="0.01" name="amount" required placeholder="0.00" class="w-full bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-2xl font-bold focus:ring-green-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Catégorie</label>
                    <input type="text" name="category" placeholder="Ex: Vente matériel, Loyer, Maintenance..." class="w-full bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 focus:ring-green-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Description (Optionnel)</label>
                    <textarea name="description" rows="2" class="w-full bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 focus:ring-green-500"></textarea>
                </div>

                <div class="flex items-center gap-3 pt-4">
                    <button type="button" onclick="document.getElementById('modal-add-record').classList.add('hidden')" class="flex-1 px-4 py-3 text-slate-600 dark:text-slate-400 font-semibold rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 transition">
                        Annuler
                    </button>
                    <button type="submit" class="flex-1 px-4 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl shadow-lg transition-all">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
