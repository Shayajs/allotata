@extends('admin.layout')

@section('title', 'Modifier code promo')
@section('header', '✏️ Modifier le code promo')
@section('subheader', $promoCode->code)

@section('content')
<div class="max-w-3xl">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <form method="POST" action="{{ route('admin.promo-codes.update', $promoCode) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="code" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Code <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="code" 
                        id="code"
                        value="{{ old('code', $promoCode->code) }}"
                        required
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white font-mono uppercase"
                    >
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Description</label>
                    <input 
                        type="text" 
                        name="description" 
                        id="description"
                        value="{{ old('description', $promoCode->description) }}"
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="type" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Type de réduction</label>
                    <select name="type" id="type" class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <option value="pourcentage" {{ old('type', $promoCode->type) === 'pourcentage' ? 'selected' : '' }}>Pourcentage (%)</option>
                        <option value="montant_fixe" {{ old('type', $promoCode->type) === 'montant_fixe' ? 'selected' : '' }}>Montant fixe (€)</option>
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
                        value="{{ old('valeur', $promoCode->valeur) }}"
                        required
                        min="0"
                        step="0.01"
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>

                <div>
                    <label for="duree_mois" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Durée (mois)</label>
                    <input 
                        type="number" 
                        name="duree_mois" 
                        id="duree_mois"
                        value="{{ old('duree_mois', $promoCode->duree_mois) }}"
                        min="1"
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="usages_max" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Usages max</label>
                    <input 
                        type="number" 
                        name="usages_max" 
                        id="usages_max"
                        value="{{ old('usages_max', $promoCode->usages_max) }}"
                        min="1"
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>

                <div>
                    <label for="date_debut" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Date de début</label>
                    <input type="datetime-local" name="date_debut" id="date_debut" value="{{ old('date_debut', $promoCode->date_debut?->format('Y-m-d\TH:i')) }}" class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                </div>

                <div>
                    <label for="date_fin" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Date de fin</label>
                    <input type="datetime-local" name="date_fin" id="date_fin" value="{{ old('date_fin', $promoCode->date_fin?->format('Y-m-d\TH:i')) }}" class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                </div>
            </div>

            <!-- Statistiques d'usage -->
            <div class="p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                <h3 class="font-medium text-slate-900 dark:text-white mb-2">📊 Statistiques</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-slate-600 dark:text-slate-400">Usages actuels:</span>
                        <span class="font-semibold text-slate-900 dark:text-white ml-2">{{ $promoCode->usages_actuels }}</span>
                    </div>
                    <div>
                        <span class="text-slate-600 dark:text-slate-400">Restants:</span>
                        <span class="font-semibold text-slate-900 dark:text-white ml-2">{{ $promoCode->usages_restants ?? '∞' }}</span>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="est_actif" value="1" {{ old('est_actif', $promoCode->est_actif) ? 'checked' : '' }} class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-green-600 focus:ring-green-500">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Code actif</span>
                </label>

                <label class="flex items-center gap-3">
                    <input type="checkbox" name="premier_abonnement_uniquement" value="1" {{ old('premier_abonnement_uniquement', $promoCode->premier_abonnement_uniquement) ? 'checked' : '' }} class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-green-600 focus:ring-green-500">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Premier abonnement uniquement</span>
                </label>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-700">
                <form method="POST" action="{{ route('admin.promo-codes.destroy', $promoCode) }}" onsubmit="return confirm('Êtes-vous sûr ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-600 hover:text-red-700 font-medium">🗑️ Supprimer</button>
                </form>
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.promo-codes.index') }}" class="px-6 py-2 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition">
                        Annuler
                    </a>
                    <button type="submit" class="px-6 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                        ✅ Enregistrer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
