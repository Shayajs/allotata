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
        
        <details class="mt-4">
            <summary class="cursor-pointer text-sm text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">
                Voir le détail des tables
            </summary>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Table</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Lignes</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Taille</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @foreach($dbInfo['tables'] as $table)
                        <tr>
                            <td class="px-4 py-3 text-sm text-slate-900 dark:text-white">{{ $table['name'] }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">{{ number_format($table['rows']) }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">{{ number_format($table['size_mb'], 2) }} MB</td>
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
        
        <div class="flex flex-wrap gap-3">
            <button 
                onclick="createBackup()" 
                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Créer une sauvegarde
            </button>
            
            <label class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                Importer une sauvegarde
                <input type="file" id="importFile" accept=".sql,.gz" class="hidden" onchange="importBackup(this)">
            </label>
            
            <button 
                onclick="cleanBackups()" 
                class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2"
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
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
                            {{ $backup['description'] ?? 'Aucune description' }}
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
                                    class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-medium transition-colors"
                                    title="Télécharger"
                                >
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                </a>
                                <button 
                                    onclick="restoreBackup('{{ $backup['filename'] }}')" 
                                    class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white rounded text-xs font-medium transition-colors"
                                    title="Restaurer"
                                >
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                </button>
                                <button 
                                    onclick="deleteBackup('{{ $backup['filename'] }}')" 
                                    class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-medium transition-colors"
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
<div id="backupModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl p-6 max-w-md w-full mx-4">
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
                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors"
                >
                    Créer la sauvegarde
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function createBackup() {
        document.getElementById('backupModal').classList.remove('hidden');
    }

    function closeBackupModal() {
        document.getElementById('backupModal').classList.add('hidden');
        document.getElementById('backupDescription').value = '';
    }

    async function submitBackup(event) {
        event.preventDefault();
        
        const description = document.getElementById('backupDescription').value;
        const button = event.target.querySelector('button[type="submit"]');
        const originalText = button.textContent;
        
        button.disabled = true;
        button.textContent = 'Création en cours...';
        
        try {
            const response = await fetch('{{ route("admin.database.backup") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ description })
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Sauvegarde créée avec succès !');
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
                button.disabled = false;
                button.textContent = originalText;
            }
        } catch (error) {
            alert('Erreur lors de la création de la sauvegarde: ' + error.message);
            button.disabled = false;
            button.textContent = originalText;
        }
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
