@php
    // Utiliser le service fiscal pour un calcul complet
    $fiscalService = app(\App\Services\FiscalCalculatorService::class);
    $calculFiscal = $fiscalService->calculerTout($entreprise, $financeStats['totalIncome']);
@endphp

<div class="space-y-8">
    <!-- En-tête avec Totaux -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform">
                <span class="text-6xl">📈</span>
            </div>
            <p class="text-green-100 text-sm font-semibold uppercase tracking-wider mb-2">Recettes (Entrées)</p>
            <h3 class="text-3xl font-bold" id="display-totalIncome">{{ number_format($financeStats['totalIncome'], 2, ',', ' ') }} €</h3>
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
            <h3 class="text-3xl font-bold" id="display-totalCharges">{{ number_format($calculFiscal['total_charges'], 2, ',', ' ') }} €</h3>
            <p class="mt-2 text-xs text-blue-100/80" id="display-tauxGlobal">Taux global : {{ number_format($calculFiscal['taux_global'], 1) }}%</p>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-violet-600 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform">
                <span class="text-6xl">💎</span>
            </div>
            <p class="text-purple-100 text-sm font-semibold uppercase tracking-wider mb-2">Reste à vivre (Net)</p>
            @php
                $net = $financeStats['totalIncome'] - $financeStats['totalExpense'] - $calculFiscal['total_charges'];
            @endphp
            <h3 class="text-3xl font-bold" id="display-net">{{ number_format($net, 2, ',', ' ') }} €</h3>
            <p class="mt-2 text-xs text-purple-100/80">Bénéfice net après charges</p>
        </div>
    </div>

    <!-- Accordéon Paramètres Fiscaux -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
        <button 
            onclick="toggleFiscalAccordion()"
            id="fiscal-accordion-btn"
            class="w-full px-6 py-4 flex items-center justify-between bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 hover:from-indigo-100 hover:to-purple-100 dark:hover:from-indigo-900/30 dark:hover:to-purple-900/30 transition-all"
        >
            <div class="flex items-center gap-3">
                <span class="text-2xl">⚙️</span>
                <div class="text-left">
                    <h3 class="font-bold text-slate-900 dark:text-white">Paramètres fiscaux personnalisés</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Affinez le calcul de votre impôt (situation familiale, enfants, régime fiscal...)</p>
                </div>
            </div>
            <svg id="fiscal-accordion-icon" class="w-6 h-6 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>

        <div id="fiscal-accordion-content" class="hidden border-t border-slate-200 dark:border-slate-700">
            <form id="fiscal-settings-form" class="p-6 space-y-8">
                @csrf
                <!-- Section Principale -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Situation familiale -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">👥 Situation familiale</label>
                        <select name="fiscal_situation_familiale" class="w-full bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-600 rounded-xl px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="celibataire" {{ ($entreprise->fiscal_situation_familiale ?? 'celibataire') == 'celibataire' ? 'selected' : '' }}>Célibataire</option>
                            <option value="marie" {{ ($entreprise->fiscal_situation_familiale ?? '') == 'marie' ? 'selected' : '' }}>Marié(e)</option>
                            <option value="pacse" {{ ($entreprise->fiscal_situation_familiale ?? '') == 'pacse' ? 'selected' : '' }}>Pacsé(e)</option>
                            <option value="divorce" {{ ($entreprise->fiscal_situation_familiale ?? '') == 'divorce' ? 'selected' : '' }}>Divorcé(e)</option>
                            <option value="veuf" {{ ($entreprise->fiscal_situation_familiale ?? '') == 'veuf' ? 'selected' : '' }}>Veuf(ve)</option>
                        </select>
                    </div>

                    <!-- Nombre d'enfants -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">👶 Nombre d'enfants à charge</label>
                        <input type="number" name="fiscal_nombre_enfants" min="0" max="20" value="{{ $entreprise->fiscal_nombre_enfants ?? 0 }}" class="w-full bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-600 rounded-xl px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="text-xs text-slate-500 mt-1">1er et 2ème = 0.5 part, à partir du 3ème = 1 part</p>
                    </div>

                    <!-- Enfants en garde alternée -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">🔄 Garde alternée</label>
                        <input type="number" name="fiscal_enfants_garde_alternee" min="0" max="20" value="{{ $entreprise->fiscal_enfants_garde_alternee ?? 0 }}" class="w-full bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-600 rounded-xl px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="text-xs text-slate-500 mt-1">Enfants en garde alternée (0.25 part chacun)</p>
                    </div>
                </div>

                <!-- Section Régime Fiscal -->
                <div class="p-6 bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-2xl border border-amber-200 dark:border-amber-800">
                    <h4 class="font-bold text-amber-800 dark:text-amber-400 mb-4 flex items-center gap-2">
                        <span class="text-xl">📋</span> Régime d'imposition
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="flex items-center gap-3 p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer hover:border-amber-400 transition-all">
                                <input type="checkbox" name="fiscal_prelevement_liberatoire" value="1" {{ ($entreprise->fiscal_prelevement_liberatoire ?? false) ? 'checked' : '' }} class="w-5 h-5 text-amber-600 focus:ring-amber-500 rounded">
                                <div>
                                    <span class="font-bold text-slate-900 dark:text-white">Prélèvement libératoire</span>
                                    <p class="text-xs text-slate-500">Taux fixe sur le CA ({{ $calculFiscal['type_activite'] == 'vente' ? '1%' : ($calculFiscal['type_activite'] == 'service_bic' ? '1.7%' : '2.2%') }})</p>
                                </div>
                            </label>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Revenu Fiscal de Référence (N-2)</label>
                            <input type="number" step="0.01" name="fiscal_revenu_fiscal_reference" value="{{ $entreprise->fiscal_revenu_fiscal_reference ?? '' }}" placeholder="Pour vérifier l'éligibilité au PL" class="w-full bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-600 rounded-xl px-4 py-3 focus:ring-amber-500 focus:border-amber-500">
                            <p class="text-xs text-slate-500 mt-1">Plafond 2024 : 27 478€ par part</p>
                        </div>
                    </div>
                </div>

                <!-- Revenus du foyer (barème progressif) -->
                <div class="p-6 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl border border-blue-200 dark:border-blue-800">
                    <h4 class="font-bold text-blue-800 dark:text-blue-400 mb-4 flex items-center gap-2">
                        <span class="text-xl">💰</span> Autres revenus du foyer (barème progressif)
                    </h4>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Revenus annuels du conjoint et autres (€)</label>
                        <input type="number" step="0.01" name="fiscal_revenus_autres_foyer" value="{{ $entreprise->fiscal_revenus_autres_foyer ?? 0 }}" class="w-full bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-600 rounded-xl px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-xs text-slate-500 mt-1">Salaires net imposable, pensions, etc. (pour le calcul du barème progressif)</p>
                    </div>
                </div>

                <!-- Section Avancée (repliée par défaut) -->
                <details class="group">
                    <summary class="cursor-pointer text-sm font-bold text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 flex items-center gap-2">
                        <svg class="w-4 h-4 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        Paramètres avancés (situations spécifiques)
                    </summary>
                    <div class="mt-4 p-6 bg-slate-50 dark:bg-slate-900/50 rounded-xl space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="flex items-center gap-3 p-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer">
                                <input type="checkbox" name="fiscal_parent_isole" value="1" {{ ($entreprise->fiscal_parent_isole ?? false) ? 'checked' : '' }} class="w-4 h-4 text-indigo-600 focus:ring-indigo-500 rounded">
                                <span class="text-sm text-slate-700 dark:text-slate-300">Parent isolé (élevant seul les enfants)</span>
                            </label>
                            <label class="flex items-center gap-3 p-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer">
                                <input type="checkbox" name="fiscal_invalidite_contribuable" value="1" {{ ($entreprise->fiscal_invalidite_contribuable ?? false) ? 'checked' : '' }} class="w-4 h-4 text-indigo-600 focus:ring-indigo-500 rounded">
                                <span class="text-sm text-slate-700 dark:text-slate-300">Carte d'invalidité (contribuable)</span>
                            </label>
                            <label class="flex items-center gap-3 p-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer">
                                <input type="checkbox" name="fiscal_invalidite_conjoint" value="1" {{ ($entreprise->fiscal_invalidite_conjoint ?? false) ? 'checked' : '' }} class="w-4 h-4 text-indigo-600 focus:ring-indigo-500 rounded">
                                <span class="text-sm text-slate-700 dark:text-slate-300">Carte d'invalidité (conjoint)</span>
                            </label>
                            <label class="flex items-center gap-3 p-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer">
                                <input type="checkbox" name="fiscal_ancien_combattant" value="1" {{ ($entreprise->fiscal_ancien_combattant ?? false) ? 'checked' : '' }} class="w-4 h-4 text-indigo-600 focus:ring-indigo-500 rounded">
                                <span class="text-sm text-slate-700 dark:text-slate-300">Ancien combattant (>74 ans)</span>
                            </label>
                        </div>
                    </div>
                </details>

                <!-- État de sauvegarde -->
                <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-700">
                    <div id="fiscal-save-status" class="text-sm text-slate-500 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse hidden" id="fiscal-status-saving"></span>
                        <span id="fiscal-status-text">💡 Les modifications sont enregistrées automatiquement</span>
                    </div>
                    <div id="fiscal-parts-display" class="text-sm font-bold text-indigo-600 dark:text-indigo-400">
                        Quotient familial : {{ $calculFiscal['parts']['total'] }} part(s)
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Détail du calcul fiscal -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-slate-800 dark:to-slate-900 border-b border-slate-200 dark:border-slate-700">
            <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span class="text-xl">📊</span> Détail du calcul ({{ $calculFiscal['regime'] == 'prelevement_liberatoire' ? 'Prélèvement Libératoire' : 'Barème Progressif' }})
            </h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Colonne Gauche : URSSAF -->
                <div class="space-y-4">
                    <h4 class="font-bold text-slate-700 dark:text-white border-b border-slate-200 dark:border-slate-600 pb-2">Cotisations sociales (URSSAF)</h4>
                    <div class="flex justify-between py-2">
                        <span class="text-slate-600 dark:text-slate-300">Type d'activité</span>
                        <span class="font-medium text-slate-900 dark:text-white">{{ ucfirst(str_replace('_', ' ', $calculFiscal['type_activite'])) }}</span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="text-slate-600 dark:text-slate-300">Taux URSSAF</span>
                        <span class="font-medium text-slate-900 dark:text-white">{{ number_format($calculFiscal['urssaf']['taux'], 1) }}%</span>
                    </div>
                    <div class="flex justify-between py-2 text-lg font-bold text-red-500 dark:text-red-400">
                        <span>URSSAF à payer</span>
                        <span>{{ number_format($calculFiscal['urssaf']['montant'], 2, ',', ' ') }} €</span>
                    </div>
                </div>

                <!-- Colonne Droite : Impôt -->
                <div class="space-y-4">
                    <h4 class="font-bold text-slate-700 dark:text-white border-b border-slate-200 dark:border-slate-600 pb-2">Impôt sur le revenu</h4>
                    @if($calculFiscal['regime'] == 'prelevement_liberatoire')
                        <div class="p-4 bg-amber-50 dark:bg-amber-900/30 rounded-xl border border-amber-200 dark:border-amber-700">
                            <p class="font-bold text-amber-800 dark:text-amber-300">Prélèvement libératoire activé</p>
                            <p class="text-sm text-amber-600 dark:text-amber-200 mt-1">Taux fixe de {{ number_format($calculFiscal['impot']['taux'], 1) }}% sur le CA</p>
                        </div>
                        <div class="flex justify-between py-2 text-lg font-bold text-orange-500 dark:text-orange-400">
                            <span>Impôt (PL)</span>
                            <span>{{ number_format($calculFiscal['impot']['montant'], 2, ',', ' ') }} €</span>
                        </div>
                    @else
                        <div class="flex justify-between py-2">
                            <span class="text-slate-600 dark:text-slate-300">Abattement forfaitaire</span>
                            <span class="font-medium text-slate-900 dark:text-white">{{ number_format($calculFiscal['abattement']['taux'], 0) }}%</span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span class="text-slate-600 dark:text-slate-300">Revenu imposable (micro)</span>
                            <span class="font-medium text-slate-900 dark:text-white">{{ number_format($calculFiscal['abattement']['revenu_imposable'], 2, ',', ' ') }} €</span>
                        </div>
                        @if($calculFiscal['revenus_autres_foyer'] > 0)
                        <div class="flex justify-between py-2">
                            <span class="text-slate-600 dark:text-slate-300">+ Autres revenus du foyer</span>
                            <span class="font-medium text-slate-900 dark:text-white">{{ number_format($calculFiscal['revenus_autres_foyer'], 2, ',', ' ') }} €</span>
                        </div>
                        @endif
                        <div class="flex justify-between py-2">
                            <span class="text-slate-600 dark:text-slate-300">Nombre de parts</span>
                            <span class="font-medium text-slate-900 dark:text-white">{{ $calculFiscal['parts']['total'] }}</span>
                        </div>
                        @if($calculFiscal['decote']['eligible'])
                        <div class="flex justify-between py-2 text-green-500 dark:text-green-400">
                            <span>Décote appliquée</span>
                            <span>-{{ number_format($calculFiscal['decote']['montant'], 2, ',', ' ') }} €</span>
                        </div>
                        @endif
                        <div class="flex justify-between py-2 text-lg font-bold text-orange-500 dark:text-orange-400">
                            <span>Impôt estimé (part micro)</span>
                            <span>{{ number_format($calculFiscal['impot']['part_micro'], 2, ',', ' ') }} €</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Total -->
            <div class="mt-6 p-4 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl text-white">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-indigo-100 text-sm">TOTAL DES CHARGES À PROVISIONNER</p>
                        <p class="text-xs text-indigo-200 mt-1">URSSAF + Impôt sur le revenu</p>
                    </div>
                    <p class="text-3xl font-bold">{{ number_format($calculFiscal['total_charges'], 2, ',', ' ') }} €</p>
                </div>
            </div>
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
            <span class="text-sm text-slate-500 dark:text-slate-400">{{ $finances->count() }} enregistrements</span>
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
                                <div class="text-xs text-slate-500 dark:text-slate-400">{{ $record->category ?: 'Sans catégorie' }}</div>
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
                            <td class="px-8 py-5 text-right flex items-center justify-end gap-1">
                                <button 
                                    type="button" 
                                    onclick="openEditModal({{ $record->id }}, '{{ $record->type }}', '{{ $record->date_record->format('Y-m-d') }}', {{ $record->amount }}, '{{ addslashes($record->category ?? '') }}', '{{ addslashes($record->description ?? '') }}')"
                                    class="text-blue-400 hover:text-blue-600 transition-colors p-2 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20"
                                    title="Modifier"
                                >
                                    ✏️
                                </button>
                                <form action="{{ route('entreprise.finances.destroy', [$entreprise->slug, $record->id]) }}" method="POST" class="inline-block" onsubmit="return confirm('Confirmer la suppression ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-600 transition-colors p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20" title="Supprimer">
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

    <!-- Section Automatisation (Coming Soon) -->
    <div class="p-8 bg-gradient-to-br from-slate-800 to-slate-900 rounded-3xl text-white relative overflow-hidden shadow-2xl border border-slate-700">
        <div class="absolute top-0 right-0 p-8 opacity-20">
            <span class="text-6xl">⚡</span>
        </div>
        <div class="relative z-10">
            <h3 class="text-xl font-bold mb-2">Synchronisation Bancaire (Bientôt disponible)</h3>
            <p class="text-slate-400 max-w-2xl mb-6">
                Connectez bientôt votre compte bancaire professionnel pour importer automatiquement toutes vos recettes et dépenses. 
                Compatible avec plus de 300 banques européennes via nos partenaires Bridge et Powens.
            </p>
            <div class="inline-flex items-center px-4 py-2 rounded-lg bg-white/10 text-xs font-semibold text-slate-300 border border-white/20">
                🚀 En phase de développement
            </div>
        </div>
    </div>
</div>


<!-- Modal Ajout Record -->
<div id="modal-add-record" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm" aria-hidden="true" onclick="document.getElementById('modal-add-record').classList.add('hidden')"></div>
    
    <!-- Modal Content -->
    <div class="modal-content relative z-10 w-full max-w-lg rounded-2xl text-left overflow-hidden shadow-2xl">
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

<!-- Modal Edit Record -->
<div id="modal-edit-record" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4" aria-labelledby="modal-edit-title" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm" aria-hidden="true" onclick="document.getElementById('modal-edit-record').classList.add('hidden')"></div>
    
    <!-- Modal Content -->
    <div class="modal-content relative z-10 w-full max-w-lg rounded-2xl text-left overflow-hidden shadow-2xl">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
            <h3 class="text-lg font-bold text-white">Modifier la transaction</h3>
        </div>
        
        <form id="edit-record-form" method="POST" class="px-6 py-6 space-y-4">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Type</label>
                    <select name="type" id="edit-type" required class="w-full bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 focus:ring-blue-500">
                        <option value="income">Recette (Entrée)</option>
                        <option value="expense">Dépense (Sortie)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Date</label>
                    <input type="date" name="date_record" id="edit-date" required class="w-full bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Montant (€)</label>
                <input type="number" step="0.01" name="amount" id="edit-amount" required placeholder="0.00" class="w-full bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-2xl font-bold focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Catégorie</label>
                <input type="text" name="category" id="edit-category" placeholder="Ex: Vente matériel, Loyer, Maintenance..." class="w-full bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Description (Optionnel)</label>
                <textarea name="description" id="edit-description" rows="2" class="w-full bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 focus:ring-blue-500"></textarea>
            </div>

            <div class="flex items-center gap-3 pt-4">
                <button type="button" onclick="document.getElementById('modal-edit-record').classList.add('hidden')" class="flex-1 px-4 py-3 text-slate-600 dark:text-slate-400 font-semibold rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 transition">
                    Annuler
                </button>
                <button type="submit" class="flex-1 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg transition-all">
                    Mettre à jour
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Script pour la sauvegarde automatique -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('fiscal-settings-form');
    if (!form) return;

    let debounceTimer;
    const statusText = document.getElementById('fiscal-status-text');
    const statusSaving = document.getElementById('fiscal-status-saving');

    form.addEventListener('change', function(e) {
        clearTimeout(debounceTimer);
        
        // Afficher le statut "sauvegarde en cours"
        if (statusSaving) statusSaving.classList.remove('hidden');
        if (statusText) statusText.textContent = 'Sauvegarde en cours...';

        debounceTimer = setTimeout(() => {
            const formData = new FormData(form);
            
            // Gérer les checkboxes non cochées
            ['fiscal_parent_isole', 'fiscal_prelevement_liberatoire', 'fiscal_invalidite_contribuable', 'fiscal_invalidite_conjoint', 'fiscal_ancien_combattant'].forEach(name => {
                if (!formData.has(name)) {
                    formData.append(name, '0');
                }
            });

            fetch('{{ route("entreprise.fiscal-settings.save", $entreprise->slug) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (statusSaving) statusSaving.classList.add('hidden');
                if (statusText) statusText.textContent = '✅ Paramètres enregistrés';
                
                // Mettre à jour les affichages si disponibles
                if (data.calcul) {
                    const totalCharges = document.getElementById('display-totalCharges');
                    const tauxGlobal = document.getElementById('display-tauxGlobal');
                    const netDisplay = document.getElementById('display-net');
                    const partsDisplay = document.getElementById('fiscal-parts-display');
                    
                    if (totalCharges) {
                        totalCharges.textContent = new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 2 }).format(data.calcul.total_charges) + ' €';
                    }
                    if (tauxGlobal) {
                        tauxGlobal.textContent = 'Taux global : ' + data.calcul.taux_global.toFixed(1) + '%';
                    }
                    if (partsDisplay) {
                        partsDisplay.textContent = 'Quotient familial : ' + data.calcul.parts.total + ' part(s)';
                    }
                }
                
                setTimeout(() => {
                    if (statusText) statusText.textContent = '💡 Les modifications sont enregistrées automatiquement';
                }, 2000);
            })
            .catch(error => {
                if (statusSaving) statusSaving.classList.add('hidden');
                if (statusText) statusText.textContent = '❌ Erreur de sauvegarde';
                console.error('Erreur:', error);
            });
        }, 500);
    });
});

// Fonction pour toggle l'accordéon fiscal
function toggleFiscalAccordion() {
    const content = document.getElementById('fiscal-accordion-content');
    const icon = document.getElementById('fiscal-accordion-icon');
    
    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        icon.classList.add('rotate-180');
    } else {
        content.classList.add('hidden');
        icon.classList.remove('rotate-180');
    }
}

// Fonction pour ouvrir la modale d'édition
function openEditModal(id, type, date, amount, category, description) {
    const form = document.getElementById('edit-record-form');
    form.action = '{{ url("/m/" . $entreprise->slug . "/finances") }}/' + id;
    
    document.getElementById('edit-type').value = type;
    document.getElementById('edit-date').value = date;
    document.getElementById('edit-amount').value = amount;
    document.getElementById('edit-category').value = category;
    document.getElementById('edit-description').value = description;
    
    document.getElementById('modal-edit-record').classList.remove('hidden');
}
</script>
