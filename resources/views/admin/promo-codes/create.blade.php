@extends('admin.layout')

@section('title', 'Nouveau code promo')
@section('header', '➕ Nouveau code promo')
@section('subheader', 'Créez un code promotionnel')

@section('content')
<div class="max-w-3xl">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <form method="POST" action="{{ route('admin.promo-codes.store') }}" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="code" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Code <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="code" 
                        id="code"
                        value="{{ old('code', $suggestedCode) }}"
                        required
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white font-mono uppercase"
                        placeholder="EX: PROMO2024"
                    >
                    <p class="mt-1 text-xs text-slate-500">Le code sera automatiquement en majuscules</p>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Description</label>
                    <input 
                        type="text" 
                        name="description" 
                        id="description"
                        value="{{ old('description') }}"
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        placeholder="Ex: Offre de lancement"
                    >
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="type" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Type de réduction</label>
                    <select name="type" id="type" class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <option value="pourcentage" {{ old('type') === 'pourcentage' ? 'selected' : '' }}>Pourcentage (%)</option>
                        <option value="montant_fixe" {{ old('type') === 'montant_fixe' ? 'selected' : '' }}>Montant fixe (€)</option>
                    </select>
                </div>

                <div>
                    <label for="valeur" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Valeur <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        name="valeur" 
                        id="valeur"
                        value="{{ old('valeur') }}"
                        required
                        min="0"
                        step="0.01"
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        placeholder="Ex: 20"
                    >
                </div>

                <div>
                    <label for="duree_mois" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Durée (mois)</label>
                    <input 
                        type="number" 
                        name="duree_mois" 
                        id="duree_mois"
                        value="{{ old('duree_mois') }}"
                        min="1"
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        placeholder="Optionnel"
                    >
                    <p class="mt-1 text-xs text-slate-500">Nombre de mois où la réduction s'applique</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="usages_max" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Usages max</label>
                    <input 
                        type="number" 
                        name="usages_max" 
                        id="usages_max"
                        value="{{ old('usages_max') }}"
                        min="1"
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        placeholder="Illimité"
                    >
                </div>

                <div>
                    <label for="date_debut" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Date de début</label>
                    <input type="datetime-local" name="date_debut" id="date_debut" value="{{ old('date_debut') }}" class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                </div>

                <div>
                    <label for="date_fin" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Date de fin</label>
                    <input type="datetime-local" name="date_fin" id="date_fin" value="{{ old('date_fin') }}" class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                </div>
            </div>

            <div class="space-y-3">
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="est_actif" value="1" checked class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-green-600 focus:ring-green-500">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Code actif</span>
                </label>

                <label class="flex items-center gap-3">
                    <input type="checkbox" name="premier_abonnement_uniquement" value="1" checked class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-green-600 focus:ring-green-500">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Premier abonnement uniquement</span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                <a href="{{ route('admin.promo-codes.index') }}" class="px-6 py-2 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition">
                    Annuler
                </a>
                <button type="submit" class="px-6 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                    ✅ Créer le code
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
