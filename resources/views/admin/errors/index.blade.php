@extends('admin.layout')

@section('title', 'Logs d\'erreurs')
@section('header', 'Logs d\'erreurs')
@section('subheader', 'Surveillance des erreurs système en temps réel')

@section('content')
    <!-- Filtres -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <label for="filter-level" class="text-sm font-medium text-slate-700 dark:text-slate-300">Niveau :</label>
                    <select id="filter-level" class="px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-green-500">
                        <option value="" {{ request('level') == '' ? 'selected' : '' }}>Tous</option>
                        <option value="error" {{ request('level') == 'error' ? 'selected' : '' }}>Erreur</option>
                        <option value="warning" {{ request('level') == 'warning' ? 'selected' : '' }}>Avertissement</option>
                        <option value="critical" {{ request('level') == 'critical' ? 'selected' : '' }}>Critique</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <label for="filter-status" class="text-sm font-medium text-slate-700 dark:text-slate-300">Statut :</label>
                    <select id="filter-status" class="px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-green-500">
                        <option value="unread" {{ request('status', 'unread') == 'unread' ? 'selected' : '' }}>Non lues</option>
                        <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>Toutes</option>
                        <option value="read" {{ request('status') == 'read' ? 'selected' : '' }}>Lues</option>
                    </select>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button id="mark-all-read-btn" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition text-sm">
                    ✓ Tout marquer comme lu
                </button>
                <button id="refresh-btn" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 font-medium rounded-lg transition text-sm">
                    🔄 Rafraîchir
                </button>
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                    <span class="text-xl">🔴</span>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total'] }}</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Total erreurs</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                    <span class="text-xl">⚠️</span>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['unread'] }}</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Non lues</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                    <span class="text-xl">📅</span>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['today'] }}</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Aujourd'hui</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <span class="text-xl">✓</span>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['read'] }}</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Résolues</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des erreurs -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        @if($errors->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Niveau</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Message</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Fichier</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">URL</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @foreach($errors as $error)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition {{ !$error->est_vue ? 'bg-red-50/50 dark:bg-red-900/10' : '' }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $levelColors = [
                                            'error' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                            'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                            'critical' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                                            'info' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                        ];
                                        $color = $levelColors[$error->level] ?? 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-400';
                                    @endphp
                                    <span class="px-2.5 py-1 text-xs font-medium rounded-full {{ $color }}">
                                        {{ strtoupper($error->level) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-slate-900 dark:text-white font-medium max-w-xs truncate" title="{{ $error->message }}">
                                        {{ Str::limit($error->message, 50) }}
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-slate-600 dark:text-slate-400 max-w-xs truncate" title="{{ $error->file }}">
                                        {{ $error->file ? basename($error->file) . ':' . $error->line : '-' }}
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-slate-600 dark:text-slate-400 max-w-xs truncate" title="{{ $error->url }}">
                                        {{ $error->url ? Str::limit($error->url, 30) : '-' }}
                                    </p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ $error->created_at->format('d/m/Y H:i') }}</p>
                                    <p class="text-xs text-slate-500">{{ $error->created_at->diffForHumans() }}</p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <button 
                                            onclick="showErrorDetail({{ $error->id }})"
                                            class="px-3 py-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition"
                                        >
                                            Détails
                                        </button>
                                        @if(!$error->est_vue)
                                            <button 
                                                onclick="markAsRead({{ $error->id }})"
                                                class="px-3 py-1.5 text-xs font-medium text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition"
                                            >
                                                ✓ Lu
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
                {{ $errors->links() }}
            </div>
        @else
            <div class="p-12 text-center">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-3xl">✓</span>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Aucune erreur</h3>
                <p class="text-slate-600 dark:text-slate-400">Le système fonctionne correctement. Aucune erreur n'a été enregistrée.</p>
            </div>
        @endif
    </div>

    <!-- Modal détails erreur -->
    <div id="error-detail-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-3xl w-full max-h-[80vh] overflow-hidden">
            <div class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Détails de l'erreur</h3>
                <button onclick="closeErrorModal()" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div id="error-detail-content" class="p-6 overflow-y-auto max-h-[60vh]">
                <!-- Contenu chargé dynamiquement -->
            </div>
        </div>
    </div>

    <!-- Données des erreurs pour JavaScript -->
    <script>
        const errorsData = @json($errors->items());
        
        function showErrorDetail(errorId) {
            const error = errorsData.find(e => e.id === errorId);
            if (!error) return;
            
            const content = document.getElementById('error-detail-content');
            content.innerHTML = `
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Niveau</label>
                        <span class="px-3 py-1 text-sm font-medium rounded-full ${getLevelClass(error.level)}">${error.level.toUpperCase()}</span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Message</label>
                        <p class="text-slate-900 dark:text-white bg-slate-100 dark:bg-slate-700 p-3 rounded-lg">${escapeHtml(error.message)}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Fichier</label>
                            <p class="text-sm text-slate-600 dark:text-slate-400">${error.file || '-'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Ligne</label>
                            <p class="text-sm text-slate-600 dark:text-slate-400">${error.line || '-'}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">URL</label>
                            <p class="text-sm text-slate-600 dark:text-slate-400 break-all">${error.url || '-'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Méthode</label>
                            <p class="text-sm text-slate-600 dark:text-slate-400">${error.method || '-'}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">IP</label>
                            <p class="text-sm text-slate-600 dark:text-slate-400">${error.ip || '-'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Date</label>
                            <p class="text-sm text-slate-600 dark:text-slate-400">${formatDate(error.created_at)}</p>
                        </div>
                    </div>
                    ${error.trace ? `
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Stack Trace</label>
                            <pre class="text-xs text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-700 p-3 rounded-lg overflow-x-auto max-h-48">${escapeHtml(error.trace)}</pre>
                        </div>
                    ` : ''}
                </div>
            `;
            
            document.getElementById('error-detail-modal').classList.remove('hidden');
        }
        
        function closeErrorModal() {
            document.getElementById('error-detail-modal').classList.add('hidden');
        }
        
        function getLevelClass(level) {
            const classes = {
                'error': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                'warning': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                'critical': 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                'info': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
            };
            return classes[level] || 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-400';
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('fr-FR') + ' ' + date.toLocaleTimeString('fr-FR');
        }
        
        function markAsRead(errorId) {
            fetch(`/admin/errors/${errorId}/read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }).then(response => {
                if (response.ok) {
                    location.reload();
                }
            });
        }
        
        document.getElementById('mark-all-read-btn').addEventListener('click', function() {
            fetch('/admin/errors/mark-all-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }).then(response => {
                if (response.ok) {
                    location.reload();
                }
            });
        });
        
        document.getElementById('refresh-btn').addEventListener('click', function() {
            location.reload();
        });

        // Gestion des filtres
        function updateFilters() {
            const level = document.getElementById('filter-level').value;
            const status = document.getElementById('filter-status').value;
            
            const url = new URL(window.location.href);
            
            if (level) {
                url.searchParams.set('level', level);
            } else {
                url.searchParams.delete('level');
            }
            
            if (status) {
                url.searchParams.set('status', status);
            } else {
                url.searchParams.delete('status');
            }
            
            // Revenir à la première page lors du changement de filtre
            url.searchParams.delete('page');
            
            window.location.href = url.toString();
        }

        document.getElementById('filter-level').addEventListener('change', updateFilters);
        document.getElementById('filter-status').addEventListener('change', updateFilters);
        
        // Fermer modal avec Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeErrorModal();
            }
        });
    </script>
@endsection
