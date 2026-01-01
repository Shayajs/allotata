@extends('admin.layout')

@section('title', 'Exports')
@section('header', '📤 Exports de données')
@section('subheader', 'Téléchargez les données de la plateforme au format CSV')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Export Utilisateurs -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="text-center">
            <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-3xl">👥</span>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Utilisateurs</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                Exportez tous les utilisateurs avec leurs informations de compte et d'abonnement.
            </p>
            <a href="{{ route('admin.exports.users') }}" class="inline-block px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-all">
                📥 Télécharger CSV
            </a>
        </div>
    </div>

    <!-- Export Entreprises -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="text-center">
            <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-3xl">🏢</span>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Entreprises</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                Exportez toutes les entreprises avec leurs coordonnées et statut de vérification.
            </p>
            <a href="{{ route('admin.exports.entreprises') }}" class="inline-block px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-all">
                📥 Télécharger CSV
            </a>
        </div>
    </div>

    <!-- Export Réservations -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="text-center">
            <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-3xl">📅</span>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Réservations</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                Exportez toutes les réservations avec les détails clients et paiements.
            </p>
            <a href="{{ route('admin.exports.reservations') }}" class="inline-block px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition-all">
                📥 Télécharger CSV
            </a>
        </div>
    </div>
</div>

<!-- Export Réservations avec filtres -->
<div class="mt-8 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">📅 Export réservations personnalisé</h3>
    <form action="{{ route('admin.exports.reservations') }}" method="GET" class="flex items-end gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Date début</label>
            <input type="date" name="date_debut" class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Date fin</label>
            <input type="date" name="date_fin" class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
        </div>
        <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition-all">
            📥 Exporter
        </button>
    </form>
</div>

<!-- Info -->
<div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
    <div class="flex items-start gap-3">
        <span class="text-xl">ℹ️</span>
        <div class="text-sm text-blue-800 dark:text-blue-300">
            <p class="font-semibold mb-1">Format des exports</p>
            <p>Les fichiers sont exportés au format CSV avec séparateur point-virgule (;), compatible avec Microsoft Excel et LibreOffice Calc.</p>
        </div>
    </div>
</div>
@endsection
