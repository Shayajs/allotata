@extends('admin.layout')

@section('title', 'Statistiques Détaillées')

@section('content')
<div class="space-y-6">
    <!-- En-tête avec filtres et actions -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Statistiques Détaillées</h1>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Vue complète de toutes les données de la plateforme</p>
        </div>
        
        <div class="flex items-center gap-3">
            <!-- Filtre période -->
            <form method="GET" action="{{ route('admin.statistiques.index') }}" class="flex items-center gap-2">
                <select name="period" id="period-select" onchange="this.form.submit()" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white">
                    <option value="7" {{ $periodDays == 7 ? 'selected' : '' }}>7 derniers jours</option>
                    <option value="30" {{ $periodDays == 30 ? 'selected' : '' }}>30 derniers jours</option>
                    <option value="90" {{ $periodDays == 90 ? 'selected' : '' }}>90 derniers jours</option>
                    <option value="365" {{ $periodDays == 365 ? 'selected' : '' }}>12 derniers mois</option>
                </select>
            </form>
            
            <!-- Boutons d'export -->
            <div class="flex gap-2">
                <a href="{{ route('admin.statistiques.export', ['type' => 'visites', 'period' => $periodDays]) }}" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition">
                    📥 Export Visites
                </a>
                <a href="{{ route('admin.statistiques.export', ['type' => 'entreprises', 'period' => $periodDays]) }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
                    📥 Export Entreprises
                </a>
            </div>
        </div>
    </div>

    <!-- Note de mise à jour en temps réel -->
    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg flex items-center justify-between">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
            <span class="text-sm text-blue-800 dark:text-blue-400">Données actualisées en temps réel</span>
        </div>
        <span id="last-update" class="text-xs text-blue-600 dark:text-blue-500">Dernière mise à jour : {{ now()->setTimezone('Europe/Paris')->format('H:i:s') }}</span>
    </div>

    <!-- Contenu principal - Je vais créer la suite dans un autre fichier -->
    <p class="text-center text-slate-500 dark:text-slate-400 py-8">Chargement des statistiques...</p>
</div>
@endsection
