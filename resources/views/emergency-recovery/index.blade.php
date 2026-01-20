<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Récupération système</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #1a1a1a;
            color: #e0e0e0;
            padding: 20px;
            padding-bottom: 100px; /* Espace pour la barre de progression fixe */
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #2a2a2a;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }
        h1 {
            color: #ff6b6b;
            margin-bottom: 10px;
            font-size: 24px;
        }
        .warning {
            background: #3a2a2a;
            border-left: 4px solid #ff6b6b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .section {
            margin: 30px 0;
            padding: 20px;
            background: #1f1f1f;
            border-radius: 6px;
        }
        .section h2 {
            color: #4ecdc4;
            margin-bottom: 15px;
            font-size: 18px;
        }
        form {
            margin: 15px 0;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #b0b0b0;
            font-size: 14px;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            background: #1a1a1a;
            border: 1px solid #444;
            border-radius: 4px;
            color: #e0e0e0;
            font-size: 14px;
        }
        input:focus {
            outline: none;
            border-color: #4ecdc4;
        }
        button {
            background: #4ecdc4;
            color: #1a1a1a;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: background 0.2s;
        }
        button:hover {
            background: #45b8b0;
        }
        .btn-danger {
            background: #ff6b6b;
        }
        .btn-danger:hover {
            background: #ff5252;
        }
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        /* Barre de progression fixe en bas */
        #fixedProgressBar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #2a2a2a;
            border-top: 2px solid #4ecdc4;
            padding: 15px 20px;
            z-index: 1000;
            box-shadow: 0 -4px 12px rgba(0,0,0,0.5);
            display: none;
        }
        
        #fixedProgressBar.active {
            display: block;
        }
        
        .fixed-progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .fixed-progress-title {
            font-weight: bold;
            color: #4ecdc4;
            font-size: 14px;
        }
        
        .fixed-progress-close {
            background: #ff6b6b;
            border: none;
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .fixed-progress-close:hover {
            background: #ff5252;
        }
        
        .fixed-progress-bar-container {
            width: 100%;
            height: 8px;
            background: #1f1f1f;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 8px;
        }
        
        .fixed-progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #4ecdc4, #44a08d);
            transition: width 0.3s ease;
            width: 0%;
        }
        
        .fixed-progress-info {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #aaa;
        }
        
        @media (max-width: 768px) {
            #fixedProgressBar {
                padding: 12px 15px;
            }
            body {
                padding-bottom: 120px;
            }
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #444;
        }
        th {
            color: #4ecdc4;
            font-size: 12px;
            text-transform: uppercase;
        }
        td {
            font-size: 13px;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-admin {
            background: #4ecdc4;
            color: #1a1a1a;
        }
        .badge-user {
            background: #666;
            color: #e0e0e0;
        }
        .alert {
            padding: 12px;
            border-radius: 4px;
            margin: 15px 0;
        }
        .alert-success {
            background: #2a4a2a;
            border-left: 4px solid #4ecdc4;
            color: #a0e0a0;
        }
        .alert-info {
            background: #2a3a4a;
            border-left: 4px solid #4ecdc4;
            color: #a0c0e0;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-box {
            background: #1f1f1f;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #4ecdc4;
        }
        .stat-label {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Récupération Système</h1>
        
        <div class="warning">
            <strong>⚠️ Accès d'urgence uniquement</strong><br>
            Toutes les actions sont enregistrées dans les logs système.
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        
        @if(session('info'))
            <div class="alert alert-info">{{ session('info') }}</div>
        @endif
        
        @if(session('error'))
            <div class="alert" style="background: #4a2a2a; border-left: 4px solid #ff6b6b; color: #ffaaaa;">
                {{ session('error') }}
            </div>
        @endif
        
        @if($errors->any())
            <div class="alert" style="background: #4a2a2a; border-left: 4px solid #ff6b6b; color: #ffaaaa;">
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="stats">
            <div class="stat-box">
                <div class="stat-value">{{ $users->count() }}</div>
                <div class="stat-label">Utilisateurs</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">{{ $adminCount }}</div>
                <div class="stat-label">Administrateurs</div>
            </div>
        </div>

        <!-- Importer et restaurer une sauvegarde -->
        <div class="section">
            <h2>📦 Importer et restaurer une sauvegarde</h2>
            
            <form id="importBackupForm" method="POST" action="{{ request()->fullUrl() }}" enctype="multipart/form-data" style="margin-bottom: 20px;" onsubmit="handleImportBackup(event, '{{ $token }}')">
                @csrf
                <input type="hidden" name="secret_token" value="{{ $token }}">
                <input type="hidden" name="action" value="import_backup">
                
                <div class="form-group">
                    <label>Fichier de sauvegarde (.sql ou .sql.gz)</label>
                    <input type="file" name="backup_file" accept=".sql,.gz" required id="backupFileInput">
                </div>
                
                <button type="submit">Importer la sauvegarde</button>
            </form>

            @if(isset($backups) && count($backups) > 0)
            <h3 style="color: #4ecdc4; margin-top: 30px; margin-bottom: 15px;">Sauvegardes disponibles</h3>
            <table>
                <thead>
                    <tr>
                        <th>Fichier</th>
                        <th>Taille</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($backups as $backup)
                    <tr>
                        <td style="font-family: monospace; font-size: 12px;">{{ $backup['filename'] }}</td>
                        <td>{{ number_format($backup['size'] / 1024 / 1024, 2) }} MB</td>
                        <td>{{ \Carbon\Carbon::parse($backup['created_at'])->format('d/m/Y H:i') }}</td>
                        <td>
                            <button 
                                onclick="restoreBackupWithProgress('{{ $backup['filename'] }}', '{{ $token }}')" 
                                class="btn-small btn-danger"
                            >
                                Restaurer
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>

        <!-- Créer un nouvel admin -->
        <div class="section">
            <h2>Créer un nouveau compte administrateur</h2>
            <form method="POST" action="{{ request()->fullUrl() }}">
                @csrf
                <input type="hidden" name="secret_token" value="{{ $token }}">
                <input type="hidden" name="action" value="create_admin">
                
                <div class="form-group">
                    <label>Nom</label>
                    <input type="text" name="name" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>Mot de passe</label>
                    <input type="password" name="password" required minlength="8">
                </div>
                
                <div class="form-group">
                    <label>Confirmer le mot de passe</label>
                    <input type="password" name="password_confirmation" required minlength="8">
                </div>
                
                <button type="submit">Créer le compte admin</button>
            </form>
        </div>

        <!-- Liste des utilisateurs -->
        <div class="section">
            <h2>Utilisateurs existants</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @if($user->is_admin)
                                <span class="badge badge-admin">Admin</span>
                            @else
                                <span class="badge badge-user">User</span>
                            @endif
                        </td>
                        <td>
                            @if(!$user->is_admin)
                            <form method="POST" action="{{ request()->fullUrl() }}" style="display: inline;">
                                @csrf
                                <input type="hidden" name="secret_token" value="{{ $token }}">
                                <input type="hidden" name="action" value="promote">
                                <input type="hidden" name="user_id" value="{{ $user->id }}">
                                <button type="submit" class="btn-small">Promouvoir admin</button>
                            </form>
                            @endif
                            
                            <form method="POST" action="{{ request()->fullUrl() }}" style="display: inline;">
                                @csrf
                                <input type="hidden" name="secret_token" value="{{ $token }}">
                                <input type="hidden" name="action" value="login_as">
                                <input type="hidden" name="user_id" value="{{ $user->id }}">
                                <button type="submit" class="btn-small">Se connecter</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Barre de progression fixe en bas de l'écran -->
    <div id="fixedProgressBar" class="hidden">
        <div class="fixed-progress-header">
            <div class="fixed-progress-title">
                <span id="fixedProgressTitle">🔄 Restauration en cours...</span>
            </div>
            <button class="fixed-progress-close" onclick="closeFixedProgressBar()" id="fixedProgressCloseBtn" style="display: none;">
                ✕ Fermer
            </button>
        </div>
        <div class="fixed-progress-bar-container">
            <div class="fixed-progress-bar-fill" id="fixedProgressBarFill"></div>
        </div>
        <div class="fixed-progress-info">
            <span id="fixedProgressMessage">Initialisation...</span>
            <span id="fixedProgressPercent">0%</span>
        </div>
        <div id="fixedProgressDetails" style="margin-top: 8px; font-size: 11px; color: #888; display: none;">
            <div id="fixedProgressStats"></div>
        </div>
    </div>

    <!-- Modale de progression de restauration -->
    <div id="restoreProgressModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl p-6 max-w-2xl w-full mx-4">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">🔄 Restauration en cours...</h3>
            
            <div id="restoreProgressContent">
                <div class="mb-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-slate-600 dark:text-slate-400" id="progressMessage">Initialisation...</span>
                        <span class="text-sm font-medium text-slate-900 dark:text-white" id="progressPercent">0%</span>
                    </div>
                    <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-3">
                        <div id="progressBar" class="bg-green-600 h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                </div>
                
                <div id="progressDetails" class="mt-4 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg text-sm">
                    <div class="space-y-2">
                        <div id="progressStatus" class="text-slate-600 dark:text-slate-400">En attente...</div>
                        <div id="progressStats" class="hidden space-y-1">
                            <div><strong>Tables totales:</strong> <span id="totalTables">-</span></div>
                            <div><strong>Tables avec données:</strong> <span id="tablesWithData">-</span></div>
                            <div><strong>Lignes totales:</strong> <span id="totalRows">-</span></div>
                            <div id="tableDetailsList" class="mt-2 max-h-40 overflow-y-auto"></div>
                        </div>
                        <div id="progressInfo" class="text-xs text-slate-500 dark:text-slate-500 mt-2">
                            <div id="hasDataInfo"></div>
                            <div id="hasStructureInfo"></div>
                        </div>
                    </div>
                </div>
                
                <div id="progressError" class="hidden mt-4 p-4 bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-700 rounded-lg">
                    <p class="text-red-800 dark:text-red-300 font-medium" id="errorMessage"></p>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end">
                <button 
                    id="closeProgressModal" 
                    onclick="closeRestoreProgressModal()" 
                    class="px-4 py-2 bg-slate-600 hover:bg-slate-700 text-white rounded-lg font-medium transition-colors hidden"
                >
                    Fermer
                </button>
            </div>
        </div>
    </div>

    <script>
        let restoreProgressInterval = null;
        let currentProgressId = null;

        function restoreBackupWithProgress(filename, token) {
            if (!confirm('⚠️ ATTENTION: Cette action va remplacer TOUTE la base de données. Êtes-vous sûr ?')) {
                return;
            }
            
            if (!confirm('⚠️ DERNIÈRE CONFIRMATION: Voulez-vous vraiment restaurer cette sauvegarde ? Toutes les données actuelles seront PERDUES.')) {
                return;
            }

            // Afficher la modale ET la barre fixe
            showProgress('Démarrage de la restauration...', 0);

            // Démarrer la restauration
            const formData = new FormData();
            formData.append('secret_token', token);
            formData.append('action', 'restore_backup');
            formData.append('filename', filename);
            formData.append('_token', '{{ csrf_token() }}');

            fetch('{{ request()->fullUrl() }}', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Vérifier si la réponse est du JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error('Réponse HTML reçue au lieu de JSON:', text.substring(0, 500));
                        throw new Error('Réponse non-JSON reçue. Le serveur a peut-être renvoyé une page d\'erreur HTML.');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.progress_id) {
                    currentProgressId = data.progress_id;
                    startProgressPolling(data.progress_id, token);
                } else {
                    showError(data.message || 'Erreur lors du démarrage de la restauration');
                }
            })
            .catch(error => {
                console.error('Erreur lors de la restauration:', error);
                showError('Erreur: ' + error.message);
            });
        }

        function showProgress(message, percent) {
            // Afficher la modale
            document.getElementById('restoreProgressModal').classList.remove('hidden');
            document.getElementById('progressBar').style.width = percent + '%';
            document.getElementById('progressPercent').textContent = percent + '%';
            document.getElementById('progressMessage').textContent = message;
            document.getElementById('progressError').classList.add('hidden');
            document.getElementById('closeProgressModal').classList.add('hidden');
            document.getElementById('progressStats').classList.add('hidden');
            
            // Afficher la barre fixe
            const fixedBar = document.getElementById('fixedProgressBar');
            fixedBar.classList.remove('hidden');
            fixedBar.classList.add('active');
            document.getElementById('fixedProgressBarFill').style.width = percent + '%';
            document.getElementById('fixedProgressPercent').textContent = percent + '%';
            document.getElementById('fixedProgressMessage').textContent = message;
            document.getElementById('fixedProgressCloseBtn').style.display = 'none';
        }
        
        function updateFixedProgress(data) {
            const progress = data.progress || 0;
            const message = data.message || 'En cours...';
            
            // Mettre à jour la barre fixe
            document.getElementById('fixedProgressBarFill').style.width = progress + '%';
            document.getElementById('fixedProgressPercent').textContent = progress + '%';
            document.getElementById('fixedProgressMessage').textContent = message;
            
            // Mettre à jour le titre si nécessaire
            if (data.status === 'completed') {
                document.getElementById('fixedProgressTitle').textContent = '✅ Restauration terminée';
                document.getElementById('fixedProgressCloseBtn').style.display = 'block';
            } else if (data.status === 'error') {
                document.getElementById('fixedProgressTitle').textContent = '❌ Erreur';
                document.getElementById('fixedProgressCloseBtn').style.display = 'block';
            }
            
            // Afficher les stats si disponibles
            if (data.total_tables) {
                let statsHtml = `Tables: ${data.total_tables} | Lignes: ${number_format(data.total_rows || 0)}`;
                if (data.users_count !== undefined) {
                    statsHtml += ` | Utilisateurs: ${data.users_count}`;
                    if (data.users_count === 0) {
                        statsHtml += ' ⚠️';
                    }
                }
                document.getElementById('fixedProgressStats').textContent = statsHtml;
                document.getElementById('fixedProgressDetails').style.display = 'block';
            }
        }
        
        function closeFixedProgressBar() {
            const fixedBar = document.getElementById('fixedProgressBar');
            fixedBar.classList.add('hidden');
            fixedBar.classList.remove('active');
            
            // Recharger la page après un court délai
            setTimeout(() => {
                location.reload();
            }, 500);
        }
        
        function startProgressPolling(progressId, token) {
            const progressUrl = `{{ request()->fullUrl() }}/progress/${progressId}?token=${encodeURIComponent(token)}`;
            
            restoreProgressInterval = setInterval(() => {
                fetch(progressUrl)
                    .then(response => {
                        // Vérifier si la réponse est du JSON
                        const contentType = response.headers.get('content-type');
                        if (!contentType || !contentType.includes('application/json')) {
                            return response.text().then(text => {
                                throw new Error('Réponse non-JSON lors du polling');
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        updateProgress(data);
                        updateFixedProgress(data);
                        
                        if (data.status === 'completed' || data.status === 'error') {
                            clearInterval(restoreProgressInterval);
                            
                            if (data.status === 'completed') {
                                document.getElementById('progressBar').style.width = '100%';
                                document.getElementById('progressPercent').textContent = '100%';
                                
                                let message = '✅ ' + (data.message || 'Restauration terminée !');
                                if (data.users_count !== undefined && data.users_count === 0) {
                                    message = '⚠️ ' + message + ' Aucun utilisateur trouvé !';
                                }
                                document.getElementById('progressMessage').textContent = message;
                                
                                if (data.total_tables) {
                                    document.getElementById('totalTables').textContent = data.total_tables;
                                    document.getElementById('totalRows').textContent = number_format(data.total_rows || 0);
                                    if (data.tables_with_data) {
                                        document.getElementById('tablesWithData').textContent = data.tables_with_data;
                                    }
                                    if (data.users_count !== undefined) {
                                        // Afficher le nombre d'utilisateurs dans les stats
                                        const statsEl = document.getElementById('progressStats');
                                        if (statsEl && !statsEl.querySelector('#usersCountInfo')) {
                                            const usersInfo = document.createElement('div');
                                            usersInfo.id = 'usersCountInfo';
                                            usersInfo.innerHTML = '<strong>Utilisateurs:</strong> <span id="usersCount">' + data.users_count + '</span>';
                                            if (data.users_count === 0) {
                                                usersInfo.className = 'text-red-600 dark:text-red-400 font-bold';
                                            }
                                            statsEl.appendChild(usersInfo);
                                        } else if (statsEl) {
                                            const usersCountEl = document.getElementById('usersCount');
                                            if (usersCountEl) {
                                                usersCountEl.textContent = data.users_count;
                                                if (data.users_count === 0) {
                                                    usersCountEl.parentElement.className = 'text-red-600 dark:text-red-400 font-bold';
                                                }
                                            }
                                        }
                                    }
                                    document.getElementById('progressStats').classList.remove('hidden');
                                }
                                
                                document.getElementById('closeProgressModal').classList.remove('hidden');
                            } else {
                                showError(data.error || data.message || 'Erreur lors de la restauration');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Erreur lors du polling:', error);
                        // Ne pas arrêter le polling pour une erreur temporaire
                    });
            }, 500); // Polling toutes les 500ms
        }

        function updateProgress(data) {
            const progress = data.progress || 0;
            document.getElementById('progressBar').style.width = progress + '%';
            document.getElementById('progressPercent').textContent = progress + '%';
            document.getElementById('progressMessage').textContent = data.message || 'En cours...';
            document.getElementById('progressStatus').textContent = data.message || 'En cours...';
            
            // Afficher les informations sur les données
            if (data.has_data !== undefined) {
                const hasDataEl = document.getElementById('hasDataInfo');
                if (hasDataEl) {
                    hasDataEl.textContent = data.has_data ? '✅ Données détectées dans le fichier' : '⚠️ Aucune donnée détectée';
                    hasDataEl.className = data.has_data ? 'text-green-600 dark:text-green-400' : 'text-orange-600 dark:text-orange-400';
                }
            }
            
            if (data.has_structure !== undefined) {
                const hasStructEl = document.getElementById('hasStructureInfo');
                if (hasStructEl) {
                    hasStructEl.textContent = data.has_structure ? '✅ Structure détectée' : '⚠️ Aucune structure détectée';
                }
            }
            
            // Afficher les statistiques finales
            if (data.total_tables) {
                document.getElementById('totalTables').textContent = data.total_tables;
                document.getElementById('totalRows').textContent = number_format(data.total_rows || 0);
                if (data.tables_with_data) {
                    document.getElementById('tablesWithData').textContent = data.tables_with_data;
                }
                
                // Afficher les détails des tables
                if (data.table_details && data.table_details.length > 0) {
                    const detailsList = document.getElementById('tableDetailsList');
                    if (detailsList) {
                        detailsList.innerHTML = '<div class="font-medium mb-1">Exemples de tables avec données:</div>' +
                            data.table_details.map(t => 
                                `<div class="text-xs">• ${t.name}: ${number_format(t.rows)} ligne(s)</div>`
                            ).join('');
                    }
                }
                
                document.getElementById('progressStats').classList.remove('hidden');
            }
        }

        function showError(message) {
            document.getElementById('progressError').classList.remove('hidden');
            document.getElementById('errorMessage').textContent = message;
            document.getElementById('closeProgressModal').classList.remove('hidden');
        }

        function closeRestoreProgressModal() {
            if (restoreProgressInterval) {
                clearInterval(restoreProgressInterval);
                restoreProgressInterval = null;
            }
            document.getElementById('restoreProgressModal').classList.add('hidden');
            closeFixedProgressBar();
            currentProgressId = null;
        }

        function handleImportBackup(event, token) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            
            // Obtenir l'URL de manière sûre
            const formAction = form.getAttribute('action') || form.action || '{{ request()->fullUrl() }}';
            
            // Afficher la modale ET la barre fixe
            showProgress('Import du fichier...', 0);

            fetch(formAction, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Vérifier si la réponse est du JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        throw new Error('Réponse non-JSON reçue. Le serveur a peut-être renvoyé une page d\'erreur HTML.');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Mettre à jour la modale
                    document.getElementById('progressBar').style.width = '100%';
                    document.getElementById('progressPercent').textContent = '100%';
                    document.getElementById('progressMessage').textContent = '✅ ' + (data.message || 'Fichier importé avec succès');
                    document.getElementById('closeProgressModal').classList.remove('hidden');
                    
                    // Mettre à jour la barre fixe
                    updateFixedProgress({
                        progress: 100,
                        message: '✅ ' + (data.message || 'Fichier importé avec succès'),
                        status: 'completed'
                    });
                    
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    // Afficher les erreurs de validation si présentes
                    let errorMessage = data.message || 'Erreur lors de l\'import';
                    if (data.errors) {
                        const errorList = Object.values(data.errors).flat().join(', ');
                        errorMessage = errorMessage + (errorList ? ' : ' + errorList : '');
                    }
                    showError(errorMessage);
                    updateFixedProgress({
                        progress: 0,
                        message: errorMessage,
                        status: 'error'
                    });
                }
            })
            .catch(error => {
                console.error('Erreur lors de l\'import:', error);
                showError('Erreur: ' + error.message);
            });
        }

        function number_format(number) {
            return new Intl.NumberFormat('fr-FR').format(number);
        }
    </script>
</body>
</html>
