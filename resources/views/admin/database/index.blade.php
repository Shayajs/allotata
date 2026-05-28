@extends('admin.layout')

@section('title', 'Gestion de la base de données')
@section('header', 'Gestion de la base de données')
@section('subheader', 'Sauvegardes et restauration complète')

@section('content')
<div class="space-y-6">
    @if($error ?? null)
    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-700/50 rounded-lg shadow-sm">
        <p class="text-red-800 dark:text-red-300 font-medium flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            {{ $error }}
        </p>
    </div>
    @endif
    
    @if(session('error'))
    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-700/50 rounded-lg shadow-sm">
        <p class="text-red-800 dark:text-red-300 font-medium flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            {{ session('error') }}
        </p>
    </div>
    @endif
    
    @if(session('success'))
    <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/50 border border-green-200 dark:border-green-700/50 rounded-lg shadow-sm">
        <p class="text-green-800 dark:text-green-300 font-medium flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            {{ session('success') }}
        </p>
    </div>
    @endif
    
    @if(session('info'))
    <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/50 border border-blue-200 dark:border-blue-700/50 rounded-lg shadow-sm">
        <p class="text-blue-800 dark:text-blue-300 font-medium flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            {{ session('info') }}
        </p>
    </div>
    @endif

    <!-- Informations sur la base de données -->
    @if($dbInfo)
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
            </svg>
            Informations sur la base de données
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4">
                <div class="text-sm text-slate-600 dark:text-slate-400">Base de données</div>
                <div class="text-lg font-semibold text-slate-900 dark:text-white mt-1">{{ $dbInfo['database'] }}</div>
            </div>
            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4">
                <div class="text-sm text-slate-600 dark:text-slate-400">Nombre de tables</div>
                <div class="text-lg font-semibold text-slate-900 dark:text-white mt-1">{{ $dbInfo['total_tables'] }}</div>
            </div>
            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4">
                <div class="text-sm text-slate-600 dark:text-slate-400">Taille totale</div>
                <div class="text-lg font-semibold text-slate-900 dark:text-white mt-1">{{ number_format($dbInfo['total_size_mb'], 2) }} MB</div>
            </div>
        </div>
        
        <details class="mt-4" open>
            <summary class="cursor-pointer text-sm text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium">
                📊 Détail des tables et données
            </summary>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Table</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">📝 Lignes (données)</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">💾 Taille</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @foreach($dbInfo['tables'] as $table)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-4 py-3 text-sm font-mono text-slate-900 dark:text-white">{{ $table['name'] }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if($table['rows'] > 0)
                                    <span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400 font-medium">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        {{ number_format($table['rows']) }} ligne(s)
                                    </span>
                                @else
                                    <span class="text-slate-400 dark:text-slate-500">Vide</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">{{ number_format($table['size_mb'], 2) }} MB</td>
                            <td class="px-4 py-3 text-sm">
                                <button 
                                    onclick="viewTableData('{{ $table['name'] }}')" 
                                    class="px-2 py-1 text-xs bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded transition-colors"
                                    title="Voir les données"
                                >
                                    Voir données
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    </div>
    @endif

    <!-- Actions rapides -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Actions rapides
        </h2>
        
        <div class="space-y-4">
            <!-- Formulaire de création de sauvegarde -->
            <div class="bg-slate-50 dark:bg-slate-700/30 rounded-lg p-4 border border-slate-200 dark:border-slate-600">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Créer une sauvegarde</h3>
                
                <div class="space-y-3">
                    <!-- Type de sauvegarde -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Type de sauvegarde
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            @php
                                $availableTypes = $availableTypes ?? ['all' => true, 'structure' => true, 'data' => true];
                            @endphp
                            
                            <label class="flex items-center p-3 border-2 rounded-lg transition-colors {{ $availableTypes['all'] ?? true ? 'border-slate-300 dark:border-slate-600 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700/50' : 'border-slate-200 dark:border-slate-700 opacity-50 cursor-not-allowed' }}">
                                <input 
                                    type="radio" 
                                    name="backup_type" 
                                    value="all" 
                                    {{ ($availableTypes['all'] ?? true) ? 'checked' : 'disabled' }}
                                    class="mr-3 text-green-600 focus:ring-green-500 {{ ($availableTypes['all'] ?? true) ? '' : 'cursor-not-allowed' }}"
                                >
                                <div>
                                    <div class="font-medium {{ ($availableTypes['all'] ?? true) ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500' }}">📦 Tout</div>
                                    <div class="text-xs {{ ($availableTypes['all'] ?? true) ? 'text-slate-600 dark:text-slate-400' : 'text-slate-400 dark:text-slate-500' }}">Structure + Données</div>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-3 border-2 rounded-lg transition-colors {{ $availableTypes['structure'] ?? true ? 'border-slate-300 dark:border-slate-600 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700/50' : 'border-slate-200 dark:border-slate-700 opacity-50 cursor-not-allowed' }}">
                                <input 
                                    type="radio" 
                                    name="backup_type" 
                                    value="structure" 
                                    {{ ($availableTypes['structure'] ?? true) ? '' : 'disabled' }}
                                    class="mr-3 text-blue-600 focus:ring-blue-500 {{ ($availableTypes['structure'] ?? true) ? '' : 'cursor-not-allowed' }}"
                                >
                                <div>
                                    <div class="font-medium {{ ($availableTypes['structure'] ?? true) ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500' }}">🏗️ Structure</div>
                                    <div class="text-xs {{ ($availableTypes['structure'] ?? true) ? 'text-slate-600 dark:text-slate-400' : 'text-slate-400 dark:text-slate-500' }}">Tables uniquement</div>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-3 border-2 rounded-lg transition-colors {{ $availableTypes['data'] ?? true ? 'border-slate-300 dark:border-slate-600 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700/50' : 'border-slate-200 dark:border-slate-700 opacity-50 cursor-not-allowed' }}">
                                <input 
                                    type="radio" 
                                    name="backup_type" 
                                    value="data" 
                                    {{ ($availableTypes['data'] ?? true) ? '' : 'disabled' }}
                                    class="mr-3 text-purple-600 focus:ring-purple-500 {{ ($availableTypes['data'] ?? true) ? '' : 'cursor-not-allowed' }}"
                                >
                                <div>
                                    <div class="font-medium {{ ($availableTypes['data'] ?? true) ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500' }}">💾 Données</div>
                                    <div class="text-xs {{ ($availableTypes['data'] ?? true) ? 'text-slate-600 dark:text-slate-400' : 'text-slate-400 dark:text-slate-500' }}">Données uniquement</div>
                                </div>
                            </label>
                        </div>
                        
                        @if(isset($availableTypes) && (!($availableTypes['all'] ?? true) || !($availableTypes['structure'] ?? true) || !($availableTypes['data'] ?? true)))
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                            ℹ️ Certaines options sont désactivées car vos anciennes sauvegardes n'ont qu'un seul type. Créez de nouvelles sauvegardes pour activer toutes les options.
                        </p>
                        @endif
                    </div>
                    
                    <!-- Description -->
                    <div>
                        <label for="backup_description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Description (optionnelle)
                        </label>
                        <input 
                            type="text" 
                            id="backup_description" 
                            placeholder="Ex: Sauvegarde avant migration"
                            class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent"
                        >
                    </div>
                    
                    <!-- Bouton de création -->
                    <button 
                        onclick="createBackup()" 
                        class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Créer la sauvegarde
                    </button>
                </div>
            </div>
            
            <!-- Autres actions -->
            <div class="flex flex-wrap gap-3">
            
            <label class="px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg font-medium transition-colors flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                Importer une sauvegarde
                <input type="file" id="importFile" accept=".sql,.gz" class="hidden" onchange="importBackup(this)">
            </label>
            
            <button 
                onclick="cleanBackups()" 
                class="px-4 py-2 bg-orange-600 hover:bg-orange-700 dark:bg-orange-500 dark:hover:bg-orange-600 text-white rounded-lg font-medium transition-colors flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                Nettoyer les anciennes sauvegardes
            </button>
        </div>
    </div>

    <!-- Liste des sauvegardes -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Sauvegardes disponibles ({{ count($backups) }})
        </h2>
        
        @if(count($backups) === 0)
        <div class="text-center py-12 text-slate-500 dark:text-slate-400">
            <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p class="text-lg font-medium">Aucune sauvegarde disponible</p>
            <p class="text-sm mt-2">Créez votre première sauvegarde pour commencer</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Fichier</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Taille</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Date de création</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @foreach($backups as $backup)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="px-4 py-3 text-sm font-mono text-slate-900 dark:text-white">
                            {{ $backup['filename'] }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @php
                                // Déterminer le type depuis le nom du fichier ou les métadonnées
                                $type = $backup['type'] ?? 'all';
                                if (strpos($backup['filename'], 'backup_full_') !== false) {
                                    $type = 'all';
                                } elseif (strpos($backup['filename'], 'backup_structure_') !== false) {
                                    $type = 'structure';
                                } elseif (strpos($backup['filename'], 'backup_data_') !== false) {
                                    $type = 'data';
                                }
                                $typeLabels = [
                                    'all' => ['label' => '📦 Tout', 'color' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'],
                                    'structure' => ['label' => '🏗️ Structure', 'color' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'],
                                    'data' => ['label' => '💾 Données', 'color' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400'],
                                ];
                                $typeInfo = $typeLabels[$type] ?? $typeLabels['all'];
                            @endphp
                            <span class="px-2 py-1 text-xs font-medium rounded {{ $typeInfo['color'] }}">
                                {{ $typeInfo['label'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
                            {{ $backup['description'] ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
                            {{ number_format($backup['size'] / 1024 / 1024, 2) }} MB
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
                            {{ \Carbon\Carbon::parse($backup['created_at'])->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a 
                                    href="{{ route('admin.database.download', $backup['filename']) }}" 
                                    class="px-3 py-1 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded text-xs font-medium transition-colors"
                                    title="Télécharger"
                                >
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                </a>
                                <button 
                                    onclick="restoreBackup('{{ $backup['filename'] }}')" 
                                    class="px-3 py-1 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white rounded text-xs font-medium transition-colors"
                                    title="Restaurer"
                                >
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                </button>
                                <button 
                                    onclick="deleteBackup('{{ $backup['filename'] }}')" 
                                    class="px-3 py-1 bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white rounded text-xs font-medium transition-colors"
                                    title="Supprimer"
                                >
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

<!-- Modal pour créer une sauvegarde -->
<div id="backupModal" class="hidden fixed inset-0 bg-black/50 dark:bg-black/70 z-50 flex items-center justify-center">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl p-6 max-w-md w-full mx-4 border border-slate-200 dark:border-slate-700">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Créer une sauvegarde</h3>
        <form id="backupForm" onsubmit="submitBackup(event)">
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Description (optionnelle)
                </label>
                <input 
                    type="text" 
                    id="backupDescription" 
                    name="description"
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    placeholder="Ex: Sauvegarde avant mise à jour"
                >
            </div>
            <div class="flex gap-3 justify-end">
                <button 
                    type="button" 
                    onclick="closeBackupModal()" 
                    class="px-4 py-2 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white"
                >
                    Annuler
                </button>
                <button 
                    type="submit" 
                    class="ui-btn-simple px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors"
                >
                    Créer la sauvegarde
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function viewTableData(tableName) {
        // Ouvrir une nouvelle fenêtre avec les données de la table
        const url = `{{ route('admin.database.info') }}?table=${encodeURIComponent(tableName)}`;
        window.open(url, '_blank', 'width=1200,height=800');
    }

    function createBackup() {
        // Récupérer le type de sauvegarde sélectionné
        const backupType = document.querySelector('input[name="backup_type"]:checked')?.value || 'all';
        const description = document.getElementById('backup_description')?.value || '';
        
        // Afficher un indicateur de chargement
        const button = event?.target || document.querySelector('button[onclick="createBackup()"]');
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<svg class="animate-spin w-4 h-4 inline-block mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Création en cours...';
        
        const typeNames = {
            'all': 'complète (structure + données)',
            'structure': 'structure seule',
            'data': 'données seules'
        };
        
        fetch('{{ route("admin.database.backup") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                type: backupType,
                description: description || null
            })
        })
        .then(response => response.json())
        .then(data => {
            button.disabled = false;
            button.innerHTML = originalText;
            
            if (data.success) {
                alert('✅ Sauvegarde ' + typeNames[backupType] + ' créée avec succès !');
                location.reload();
            } else {
                alert('❌ Erreur: ' + data.message);
            }
        })
        .catch(error => {
            button.disabled = false;
            button.innerHTML = originalText;
            alert('❌ Erreur lors de la création de la sauvegarde: ' + error.message);
        });
    }

    async function restoreBackup(filename) {
        if (!confirm('⚠️ ATTENTION: Cette action va remplacer TOUTE la base de données actuelle par cette sauvegarde. Cette action est IRRÉVERSIBLE. Êtes-vous sûr ?')) {
            return;
        }
        
        if (!confirm('⚠️ DERNIÈRE CONFIRMATION: Voulez-vous vraiment restaurer cette sauvegarde ? Toutes les données actuelles seront PERDUES.')) {
            return;
        }
        
        try {
            // Construire l'URL manuellement pour éviter l'erreur de génération de route
            const url = `{{ url('admin/database/backup') }}/${encodeURIComponent(filename)}/restore`;
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ confirm: true })
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Base de données restaurée avec succès ! La page va se recharger.');
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        } catch (error) {
            alert('Erreur lors de la restauration: ' + error.message);
        }
    }

    async function deleteBackup(filename) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette sauvegarde ?')) {
            return;
        }
        
        try {
            // Construire l'URL manuellement pour éviter l'erreur de génération de route
            const url = `{{ url('admin/database/backup') }}/${encodeURIComponent(filename)}`;
            const response = await fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Sauvegarde supprimée avec succès !');
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        } catch (error) {
            alert('Erreur lors de la suppression: ' + error.message);
        }
    }

    async function importBackup(input) {
        if (!input.files || !input.files[0]) {
            return;
        }
        
        const file = input.files[0];
        const formData = new FormData();
        formData.append('backup_file', file);
        
        try {
            const response = await fetch('{{ route("admin.database.import") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Fichier importé avec succès !');
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        } catch (error) {
            alert('Erreur lors de l\'import: ' + error.message);
        }
        
        input.value = '';
    }

    async function cleanBackups() {
        const keep = prompt('Combien de sauvegardes voulez-vous conserver ?', '10');
        
        if (!keep || isNaN(keep) || keep < 1) {
            return;
        }
        
        if (!confirm(`Voulez-vous supprimer toutes les sauvegardes sauf les ${keep} plus récentes ?`)) {
            return;
        }
        
        try {
            const response = await fetch('{{ route("admin.database.clean") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ keep: parseInt(keep) })
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        } catch (error) {
            alert('Erreur lors du nettoyage: ' + error.message);
        }
    }
</script>
@endpush
@endsection
