<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Récupération Système</title>
    <style>
        :root {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --accent: #06b6d4;
            --accent-hover: #22d3ee;
            --danger: #ef4444;
            --danger-hover: #f87171;
            --success: #10b981;
            --warning: #f59e0b;
            --border: #475569;
            --shadow: rgba(0, 0, 0, 0.4);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--bg-primary) 0%, #1a1a2e 100%);
            color: var(--text-primary);
            min-height: 100vh;
            padding: 20px;
            padding-bottom: 120px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        /* Header */
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 30px;
            background: linear-gradient(145deg, var(--bg-secondary), var(--bg-tertiary));
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 10px 40px var(--shadow);
        }
        
        .header-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--accent), #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }
        
        .header-subtitle {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        /* Alert Banners */
        .alert-banner {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid;
        }
        
        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            border-color: rgba(245, 158, 11, 0.3);
            color: #fbbf24;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border-color: rgba(16, 185, 129, 0.3);
            color: #34d399;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
            color: #f87171;
        }
        
        .alert-info {
            background: rgba(6, 182, 212, 0.1);
            border-color: rgba(6, 182, 212, 0.3);
            color: #22d3ee;
        }
        
        .alert-icon {
            font-size: 20px;
            flex-shrink: 0;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            border-color: var(--accent);
            box-shadow: 0 8px 30px var(--shadow);
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Section Cards */
        .section {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 16px;
            margin-bottom: 24px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .section:hover {
            box-shadow: 0 8px 30px var(--shadow);
        }
        
        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 20px 24px;
            background: linear-gradient(90deg, var(--bg-tertiary), transparent);
            border-bottom: 1px solid var(--border);
        }
        
        .section-icon {
            font-size: 24px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .section-content {
            padding: 24px;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
        }
        
        .form-input::placeholder {
            color: var(--text-muted);
        }
        
        /* File Input */
        .file-input-wrapper {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px;
            background: var(--bg-primary);
            border: 2px dashed var(--border);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .file-input-wrapper:hover {
            border-color: var(--accent);
            background: rgba(6, 182, 212, 0.05);
        }
        
        .file-input-wrapper.has-file {
            border-color: var(--success);
            background: rgba(16, 185, 129, 0.05);
        }
        
        .file-input-wrapper input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }
        
        .file-input-icon {
            font-size: 40px;
            margin-bottom: 12px;
        }
        
        .file-input-text {
            font-size: 14px;
            color: var(--text-secondary);
            text-align: center;
        }
        
        .file-input-hint {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 8px;
        }
        
        .file-name {
            margin-top: 12px;
            padding: 8px 16px;
            background: rgba(16, 185, 129, 0.1);
            border-radius: 6px;
            font-size: 13px;
            color: var(--success);
            display: none;
        }
        
        .file-name.visible {
            display: block;
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--accent), #0891b2);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--accent-hover), var(--accent));
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, var(--danger-hover), var(--danger));
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 12px;
        }
        
        .btn-ghost {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }
        
        .btn-ghost:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }
        
        /* Tables */
        .table-wrapper {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid var(--border);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            text-align: left;
            padding: 12px 16px;
            background: var(--bg-tertiary);
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
        }
        
        td {
            padding: 12px 16px;
            font-size: 13px;
            border-bottom: 1px solid var(--border);
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tr:hover td {
            background: rgba(6, 182, 212, 0.05);
        }
        
        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-admin {
            background: linear-gradient(135deg, var(--accent), #0891b2);
            color: white;
        }
        
        .badge-user {
            background: var(--bg-tertiary);
            color: var(--text-secondary);
        }
        
        .badge-size {
            background: var(--bg-tertiary);
            color: var(--text-muted);
            font-family: monospace;
        }
        
        /* Backup List */
        .backup-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px;
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: 10px;
            margin-bottom: 10px;
            transition: all 0.2s ease;
        }
        
        .backup-item:hover {
            border-color: var(--accent);
        }
        
        .backup-info {
            flex: 1;
            min-width: 0;
        }
        
        .backup-name {
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 12px;
            color: var(--text-primary);
            word-break: break-all;
            margin-bottom: 4px;
        }
        
        .backup-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 11px;
            color: var(--text-muted);
        }
        
        .backup-actions {
            flex-shrink: 0;
            margin-left: 16px;
        }
        
        /* Progress Bar Fixed */
        #fixedProgressBar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--bg-secondary);
            border-top: 2px solid var(--accent);
            padding: 20px;
            z-index: 1000;
            box-shadow: 0 -10px 40px var(--shadow);
            display: none;
        }
        
        #fixedProgressBar.active {
            display: block;
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .progress-title {
            font-weight: 600;
            color: var(--accent);
            font-size: 14px;
        }
        
        .progress-bar-container {
            height: 8px;
            background: var(--bg-primary);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent), #a855f7);
            border-radius: 4px;
            transition: width 0.3s ease;
            width: 0%;
        }
        
        .progress-info {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .progress-stats {
            margin-top: 12px;
            padding: 12px;
            background: var(--bg-primary);
            border-radius: 8px;
            font-size: 12px;
            display: none;
        }
        
        .progress-stats.visible {
            display: block;
        }
        
        .progress-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
        }
        
        .progress-stat {
            text-align: center;
        }
        
        .progress-stat-value {
            font-size: 18px;
            font-weight: 700;
            color: var(--accent);
        }
        
        .progress-stat-label {
            font-size: 10px;
            color: var(--text-muted);
            text-transform: uppercase;
        }
        
        /* Verification Steps */
        .verification-steps {
            margin-top: 16px;
        }
        
        .verification-step {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        .verification-step.success {
            color: var(--success);
        }
        
        .verification-step.warning {
            color: var(--warning);
        }
        
        .verification-step.error {
            color: var(--danger);
        }
        
        .verification-step.pending {
            color: var(--text-muted);
        }
        
        .step-icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        /* Responsive */
        @media (max-width: 640px) {
            body {
                padding: 12px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 22px;
            }
            
            .section-content {
                padding: 16px;
            }
            
            .backup-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .backup-actions {
                margin-left: 0;
                width: 100%;
            }
            
            .backup-actions .btn {
                width: 100%;
            }
            
            td, th {
                padding: 10px 12px;
            }
        }
        
        /* Animations */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .animate-pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .animate-spin {
            animation: spin 1s linear infinite;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-icon">🔧</div>
            <h1>Récupération Système</h1>
            <p class="header-subtitle">Accès d'urgence sécurisé • Toutes les actions sont enregistrées</p>
        </div>
        
        <!-- Alerts -->
        @if(session('success'))
            <div class="alert-banner alert-success">
                <span class="alert-icon">✅</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        
        @if(session('info'))
            <div class="alert-banner alert-info">
                <span class="alert-icon">ℹ️</span>
                <span>{{ session('info') }}</span>
            </div>
        @endif
        
        @if(session('error'))
            <div class="alert-banner alert-error">
                <span class="alert-icon">❌</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif
        
        @if($errors->any())
            <div class="alert-banner alert-error">
                <span class="alert-icon">❌</span>
                <div>
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif
        
        <div class="alert-banner alert-warning">
            <span class="alert-icon">⚠️</span>
            <span>Mode urgence uniquement. Utilisez avec précaution.</span>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">{{ $users->count() }}</div>
                <div class="stat-label">Utilisateurs</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $adminCount }}</div>
                <div class="stat-label">Admins</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ count($backups ?? []) }}</div>
                <div class="stat-label">Sauvegardes</div>
            </div>
        </div>

        <!-- Import & Restore Section -->
        <div class="section">
            <div class="section-header">
                <span class="section-icon">📦</span>
                <span class="section-title">Importer et Restaurer une Sauvegarde</span>
            </div>
            <div class="section-content">
                <form id="importBackupForm" method="POST" action="{{ request()->fullUrl() }}" enctype="multipart/form-data" onsubmit="handleImportBackup(event, '{{ $token }}')">
                    @csrf
                    <input type="hidden" name="secret_token" value="{{ $token }}">
                    <input type="hidden" name="action" value="import_backup">
                    
                    <div class="form-group">
                        <div class="file-input-wrapper" id="fileDropZone">
                            <input type="file" name="backup_file" accept=".sql,.gz" required id="backupFileInput" onchange="handleFileSelect(this)">
                            <div class="file-input-icon">📁</div>
                            <div class="file-input-text">Glissez un fichier ici ou cliquez pour sélectionner</div>
                            <div class="file-input-hint">Formats acceptés : .sql, .sql.gz</div>
                            <div class="file-name" id="fileName"></div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-danger" id="importBtn" disabled>
                        <span>🔄</span>
                        <span>Importer et Restaurer</span>
                    </button>
                    
                    <p style="margin-top: 12px; font-size: 12px; color: var(--text-muted);">
                        Cette action téléverse le fichier, vérifie son contenu, puis restaure la base de données.
                    </p>
                </form>

                @if(isset($backups) && count($backups) > 0)
                    <div style="margin-top: 30px;">
                        <h4 style="font-size: 14px; font-weight: 600; color: var(--text-secondary); margin-bottom: 16px;">
                            Sauvegardes disponibles ({{ count($backups) }})
                        </h4>
                        
                        @foreach($backups as $backup)
                            <div class="backup-item">
                                <div class="backup-info">
                                    <div class="backup-name">{{ $backup['filename'] }}</div>
                                    <div class="backup-meta">
                                        <span class="badge badge-size">{{ number_format($backup['size'] / 1024 / 1024, 2) }} MB</span>
                                        <span>{{ \Carbon\Carbon::parse($backup['created_at'])->format('d/m/Y H:i') }}</span>
                                    </div>
                                </div>
                                <div class="backup-actions">
                                    <button 
                                        onclick="restoreBackupWithProgress('{{ $backup['filename'] }}', '{{ $token }}')" 
                                        class="btn btn-danger btn-sm"
                                    >
                                        Restaurer
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state" style="margin-top: 30px;">
                        <div class="empty-state-icon">📭</div>
                        <p>Aucune sauvegarde disponible</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Create Admin Section -->
        <div class="section">
            <div class="section-header">
                <span class="section-icon">👤</span>
                <span class="section-title">Créer un Administrateur</span>
            </div>
            <div class="section-content">
                <form method="POST" action="{{ request()->fullUrl() }}">
                    @csrf
                    <input type="hidden" name="secret_token" value="{{ $token }}">
                    <input type="hidden" name="action" value="create_admin">
                    
                    <div style="display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label">Nom</label>
                            <input type="text" name="name" class="form-input" required placeholder="Jean Dupont">
                        </div>
                        
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-input" required placeholder="admin@exemple.fr">
                        </div>
                    </div>
                    
                    <div style="display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-top: 16px;">
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label">Mot de passe</label>
                            <input type="password" name="password" class="form-input" required minlength="8" placeholder="••••••••">
                        </div>
                        
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label">Confirmer</label>
                            <input type="password" name="password_confirmation" class="form-input" required minlength="8" placeholder="••••••••">
                        </div>
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn btn-primary">
                            <span>➕</span>
                            <span>Créer le compte</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users List Section -->
        <div class="section">
            <div class="section-header">
                <span class="section-icon">👥</span>
                <span class="section-title">Utilisateurs ({{ $users->count() }})</span>
            </div>
            <div class="section-content" style="padding: 0;">
                @if($users->count() > 0)
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Rôle</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                <tr>
                                    <td style="color: var(--text-muted);">#{{ $user->id }}</td>
                                    <td>{{ $user->name }}</td>
                                    <td style="font-family: monospace; font-size: 12px;">{{ $user->email }}</td>
                                    <td>
                                        @if($user->is_admin)
                                            <span class="badge badge-admin">Admin</span>
                                        @else
                                            <span class="badge badge-user">User</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                            @if(!$user->is_admin)
                                            <form method="POST" action="{{ request()->fullUrl() }}" style="display: inline;">
                                                @csrf
                                                <input type="hidden" name="secret_token" value="{{ $token }}">
                                                <input type="hidden" name="action" value="promote">
                                                <input type="hidden" name="user_id" value="{{ $user->id }}">
                                                <button type="submit" class="btn btn-ghost btn-sm">⬆️ Promouvoir</button>
                                            </form>
                                            @endif
                                            
                                            <form method="POST" action="{{ request()->fullUrl() }}" style="display: inline;">
                                                @csrf
                                                <input type="hidden" name="secret_token" value="{{ $token }}">
                                                <input type="hidden" name="action" value="login_as">
                                                <input type="hidden" name="user_id" value="{{ $user->id }}">
                                                <button type="submit" class="btn btn-primary btn-sm">🔑 Connexion</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state">
                        <div class="empty-state-icon">👻</div>
                        <p>Aucun utilisateur dans la base de données</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Fixed Progress Bar -->
    <div id="fixedProgressBar">
        <div class="progress-header">
            <div class="progress-title" id="progressTitle">
                <span class="animate-spin" style="display: inline-block;">⏳</span>
                <span id="progressTitleText">Restauration en cours...</span>
            </div>
            <button class="btn btn-ghost btn-sm" onclick="closeProgressBar()" id="closeProgressBtn" style="display: none;">
                ✕ Fermer
            </button>
        </div>
        
        <div class="progress-bar-container">
            <div class="progress-bar-fill" id="progressBarFill"></div>
        </div>
        
        <div class="progress-info">
            <span id="progressMessage">Initialisation...</span>
            <span id="progressPercent">0%</span>
        </div>
        
        <!-- Verification Steps -->
        <div class="verification-steps" id="verificationSteps">
            <div class="verification-step pending" id="step-upload">
                <span class="step-icon">⏳</span>
                <span>Téléversement du fichier</span>
            </div>
            <div class="verification-step pending" id="step-analyze">
                <span class="step-icon">⏳</span>
                <span>Analyse du contenu</span>
            </div>
            <div class="verification-step pending" id="step-structure">
                <span class="step-icon">⏳</span>
                <span>Vérification de la structure</span>
            </div>
            <div class="verification-step pending" id="step-data">
                <span class="step-icon">⏳</span>
                <span>Vérification des données</span>
            </div>
            <div class="verification-step pending" id="step-users">
                <span class="step-icon">⏳</span>
                <span>Vérification des utilisateurs</span>
            </div>
            <div class="verification-step pending" id="step-restore">
                <span class="step-icon">⏳</span>
                <span>Restauration de la base de données</span>
            </div>
            <div class="verification-step pending" id="step-verify">
                <span class="step-icon">⏳</span>
                <span>Vérification finale</span>
            </div>
        </div>
        
        <!-- Stats -->
        <div class="progress-stats" id="progressStats">
            <div class="progress-stats-grid">
                <div class="progress-stat">
                    <div class="progress-stat-value" id="statTables">-</div>
                    <div class="progress-stat-label">Tables</div>
                </div>
                <div class="progress-stat">
                    <div class="progress-stat-value" id="statRows">-</div>
                    <div class="progress-stat-label">Lignes</div>
                </div>
                <div class="progress-stat">
                    <div class="progress-stat-value" id="statUsers">-</div>
                    <div class="progress-stat-label">Utilisateurs</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let progressInterval = null;
        let currentProgressId = null;

        // File input handling
        function handleFileSelect(input) {
            const wrapper = document.getElementById('fileDropZone');
            const fileName = document.getElementById('fileName');
            const importBtn = document.getElementById('importBtn');
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                wrapper.classList.add('has-file');
                fileName.textContent = '📄 ' + file.name + ' (' + formatBytes(file.size) + ')';
                fileName.classList.add('visible');
                importBtn.disabled = false;
            } else {
                wrapper.classList.remove('has-file');
                fileName.classList.remove('visible');
                importBtn.disabled = true;
            }
        }
        
        function formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function showProgress(message, percent) {
            const bar = document.getElementById('fixedProgressBar');
            bar.classList.add('active');
            
            document.getElementById('progressBarFill').style.width = percent + '%';
            document.getElementById('progressPercent').textContent = percent + '%';
            document.getElementById('progressMessage').textContent = message;
            document.getElementById('closeProgressBtn').style.display = 'none';
            
            // Reset verification steps
            document.querySelectorAll('.verification-step').forEach(step => {
                step.className = 'verification-step pending';
                step.querySelector('.step-icon').textContent = '⏳';
            });
        }
        
        function updateStep(stepId, status, customText) {
            const step = document.getElementById('step-' + stepId);
            if (!step) return;
            
            step.className = 'verification-step ' + status;
            const icon = step.querySelector('.step-icon');
            
            if (status === 'success') {
                icon.textContent = '✅';
            } else if (status === 'warning') {
                icon.textContent = '⚠️';
            } else if (status === 'error') {
                icon.textContent = '❌';
            } else if (status === 'loading') {
                icon.textContent = '🔄';
                icon.classList.add('animate-spin');
            } else {
                icon.textContent = '⏳';
                icon.classList.remove('animate-spin');
            }
            
            if (customText) {
                step.querySelector('span:last-child').textContent = customText;
            }
        }
        
        function updateProgress(data) {
            const progress = data.progress || 0;
            document.getElementById('progressBarFill').style.width = progress + '%';
            document.getElementById('progressPercent').textContent = progress + '%';
            document.getElementById('progressMessage').textContent = data.message || 'En cours...';
            
            // Update verification steps based on status
            if (data.status === 'analyzing' || progress >= 5) {
                updateStep('upload', 'success');
                updateStep('analyze', 'loading');
            }
            
            if (progress >= 10) {
                updateStep('analyze', 'success');
                
                if (data.has_structure !== undefined) {
                    updateStep('structure', data.has_structure ? 'success' : 'warning', 
                        data.has_structure ? 'Structure détectée' : 'Aucune structure détectée');
                }
                
                if (data.has_data !== undefined) {
                    updateStep('data', data.has_data ? 'success' : 'warning',
                        data.has_data ? 'Données détectées' : 'Aucune donnée détectée');
                }
                
                if (data.has_users !== undefined) {
                    updateStep('users', data.has_users ? 'success' : 'warning',
                        data.has_users ? 'Utilisateurs détectés (~' + (data.estimated_user_count || '?') + ')' : 'Aucun utilisateur détecté');
                }
            }
            
            if (progress >= 15) {
                updateStep('restore', 'loading');
            }
            
            if (progress >= 70) {
                updateStep('restore', 'success');
                updateStep('verify', 'loading');
            }
            
            if (data.status === 'completed' || progress >= 100) {
                updateStep('verify', 'success');
                document.getElementById('progressTitleText').textContent = 'Restauration terminée !';
                document.querySelector('#progressTitle .animate-spin').textContent = '✅';
                document.querySelector('#progressTitle .animate-spin').classList.remove('animate-spin');
                document.getElementById('closeProgressBtn').style.display = 'inline-flex';
                
                // Show stats
                if (data.total_tables) {
                    document.getElementById('statTables').textContent = data.total_tables;
                    document.getElementById('statRows').textContent = formatNumber(data.total_rows || 0);
                    document.getElementById('statUsers').textContent = data.users_count !== undefined ? data.users_count : '-';
                    document.getElementById('progressStats').classList.add('visible');
                    
                    // Warning if no users
                    if (data.users_count === 0) {
                        document.getElementById('statUsers').style.color = 'var(--danger)';
                        updateStep('users', 'error', 'Aucun utilisateur trouvé après restauration !');
                    }
                }
            }
            
            if (data.status === 'error') {
                document.getElementById('progressTitleText').textContent = 'Erreur lors de la restauration';
                document.querySelector('#progressTitle .animate-spin').textContent = '❌';
                document.querySelector('#progressTitle .animate-spin').classList.remove('animate-spin');
                document.getElementById('closeProgressBtn').style.display = 'inline-flex';
                updateStep('restore', 'error');
            }
        }
        
        function closeProgressBar() {
            if (progressInterval) {
                clearInterval(progressInterval);
                progressInterval = null;
            }
            document.getElementById('fixedProgressBar').classList.remove('active');
            
            // Reload after a short delay
            setTimeout(() => location.reload(), 500);
        }
        
        function formatNumber(num) {
            return new Intl.NumberFormat('fr-FR').format(num);
        }

        function handleImportBackup(event, token) {
            event.preventDefault();
            
            if (!confirm('⚠️ ATTENTION\n\nCette action va importer et restaurer la sauvegarde.\nToutes les données actuelles seront REMPLACÉES.\n\nContinuer ?')) {
                return;
            }
            
            if (!confirm('🔴 DERNIÈRE CONFIRMATION\n\nVoulez-vous vraiment restaurer cette sauvegarde ?\nCette action est irréversible.')) {
                return;
            }
            
            const form = event.target;
            const formData = new FormData(form);
            const formAction = form.getAttribute('action') || form.action;
            
            // Show progress
            showProgress('Étape 1: Téléversement du fichier...', 0);
            updateStep('upload', 'loading');

            fetch(formAction, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        throw new Error('Réponse non-JSON reçue du serveur.');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.filename) {
                    updateStep('upload', 'success');
                    updateStep('analyze', 'loading');
                    
                    document.getElementById('progressMessage').textContent = 'Étape 2: Analyse et restauration...';
                    document.getElementById('progressBarFill').style.width = '15%';
                    document.getElementById('progressPercent').textContent = '15%';
                    
                    // Update analysis info
                    if (data.has_structure !== undefined) {
                        updateStep('structure', data.has_structure ? 'success' : 'warning',
                            data.has_structure ? 'Structure détectée' : 'Aucune structure');
                    }
                    if (data.has_data !== undefined) {
                        updateStep('data', data.has_data ? 'success' : 'warning',
                            data.has_data ? 'Données détectées' : 'Aucune donnée');
                    }
                    
                    // Start restore
                    return restoreAfterImport(data.filename, token);
                } else {
                    let errorMessage = data.message || 'Erreur lors du téléversement';
                    if (data.errors) {
                        const errorList = Object.values(data.errors).flat().join(', ');
                        errorMessage = errorMessage + (errorList ? ' : ' + errorList : '');
                    }
                    updateStep('upload', 'error');
                    document.getElementById('progressMessage').textContent = errorMessage;
                    document.getElementById('progressTitleText').textContent = 'Erreur';
                    document.querySelector('#progressTitle .animate-spin').textContent = '❌';
                    document.querySelector('#progressTitle .animate-spin').classList.remove('animate-spin');
                    document.getElementById('closeProgressBtn').style.display = 'inline-flex';
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                updateStep('upload', 'error');
                document.getElementById('progressMessage').textContent = 'Erreur: ' + error.message;
                document.getElementById('progressTitleText').textContent = 'Erreur';
                document.querySelector('#progressTitle .animate-spin').textContent = '❌';
                document.querySelector('#progressTitle .animate-spin').classList.remove('animate-spin');
                document.getElementById('closeProgressBtn').style.display = 'inline-flex';
            });
        }
        
        function restoreAfterImport(filename, token) {
            const restoreFormData = new FormData();
            restoreFormData.append('secret_token', token);
            restoreFormData.append('action', 'restore_backup');
            restoreFormData.append('filename', filename);
            restoreFormData.append('_token', '{{ csrf_token() }}');

            updateStep('restore', 'loading');
            document.getElementById('progressMessage').textContent = 'Restauration en cours... (peut prendre plusieurs minutes)';
            document.getElementById('progressBarFill').style.width = '50%';
            document.getElementById('progressPercent').textContent = '50%';

            return fetch('{{ request()->fullUrl() }}', {
                method: 'POST',
                body: restoreFormData
            })
            .then(response => {
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error('Réponse HTML:', text.substring(0, 500));
                        throw new Error('Réponse non-JSON reçue.');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Restauration réussie - afficher les résultats
                    updateStep('restore', 'success');
                    updateStep('verify', 'success');
                    
                    document.getElementById('progressBarFill').style.width = '100%';
                    document.getElementById('progressPercent').textContent = '100%';
                    document.getElementById('progressMessage').textContent = data.message || 'Restauration terminée !';
                    document.getElementById('progressTitleText').textContent = 'Restauration terminée !';
                    document.querySelector('#progressTitle .animate-spin').textContent = '✅';
                    document.querySelector('#progressTitle .animate-spin').classList.remove('animate-spin');
                    document.getElementById('closeProgressBtn').style.display = 'inline-flex';
                    
                    // Afficher les stats
                    if (data.total_tables) {
                        document.getElementById('statTables').textContent = data.total_tables;
                        document.getElementById('statRows').textContent = formatNumber(data.total_rows || 0);
                        document.getElementById('statUsers').textContent = data.users_count !== undefined ? data.users_count : '-';
                        document.getElementById('progressStats').classList.add('visible');
                        
                        if (data.users_count === 0) {
                            document.getElementById('statUsers').style.color = 'var(--danger)';
                            updateStep('users', 'error', 'Aucun utilisateur trouvé !');
                        } else {
                            updateStep('users', 'success', data.users_count + ' utilisateur(s) trouvé(s)');
                        }
                    }
                } else {
                    throw new Error(data.message || 'Erreur lors de la restauration');
                }
            });
        }

        function restoreBackupWithProgress(filename, token) {
            if (!confirm('⚠️ ATTENTION\n\nCette action va restaurer la sauvegarde.\nToutes les données actuelles seront REMPLACÉES.\n\nContinuer ?')) {
                return;
            }
            
            if (!confirm('🔴 DERNIÈRE CONFIRMATION\n\nVoulez-vous vraiment restaurer cette sauvegarde ?\nCette action est irréversible.')) {
                return;
            }

            showProgress('Démarrage de la restauration...', 0);
            updateStep('upload', 'success', 'Fichier déjà présent');
            updateStep('analyze', 'success', 'Analyse terminée');
            updateStep('structure', 'success');
            updateStep('data', 'success');
            updateStep('restore', 'loading');
            
            document.getElementById('progressBarFill').style.width = '40%';
            document.getElementById('progressPercent').textContent = '40%';
            document.getElementById('progressMessage').textContent = 'Restauration en cours... (peut prendre plusieurs minutes)';

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
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error('Réponse HTML:', text.substring(0, 500));
                        throw new Error('Réponse non-JSON reçue.');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Restauration réussie
                    updateStep('restore', 'success');
                    updateStep('verify', 'success');
                    
                    document.getElementById('progressBarFill').style.width = '100%';
                    document.getElementById('progressPercent').textContent = '100%';
                    document.getElementById('progressMessage').textContent = data.message || 'Restauration terminée !';
                    document.getElementById('progressTitleText').textContent = 'Restauration terminée !';
                    document.querySelector('#progressTitle .animate-spin').textContent = '✅';
                    document.querySelector('#progressTitle .animate-spin').classList.remove('animate-spin');
                    document.getElementById('closeProgressBtn').style.display = 'inline-flex';
                    
                    // Afficher les stats
                    if (data.total_tables) {
                        document.getElementById('statTables').textContent = data.total_tables;
                        document.getElementById('statRows').textContent = formatNumber(data.total_rows || 0);
                        document.getElementById('statUsers').textContent = data.users_count !== undefined ? data.users_count : '-';
                        document.getElementById('progressStats').classList.add('visible');
                        
                        if (data.users_count === 0) {
                            document.getElementById('statUsers').style.color = 'var(--danger)';
                            updateStep('users', 'error', 'Aucun utilisateur trouvé !');
                        } else {
                            updateStep('users', 'success', data.users_count + ' utilisateur(s) trouvé(s)');
                        }
                    }
                } else {
                    throw new Error(data.message || 'Erreur lors de la restauration');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                updateStep('restore', 'error');
                document.getElementById('progressMessage').textContent = 'Erreur: ' + error.message;
                document.getElementById('progressTitleText').textContent = 'Erreur';
                document.querySelector('#progressTitle .animate-spin').textContent = '❌';
                document.querySelector('#progressTitle .animate-spin').classList.remove('animate-spin');
                document.getElementById('closeProgressBtn').style.display = 'inline-flex';
            });
        }

        // Drag and drop
        const dropZone = document.getElementById('fileDropZone');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
            });
        });
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.style.borderColor = 'var(--accent)';
                dropZone.style.background = 'rgba(6, 182, 212, 0.1)';
            });
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.style.borderColor = '';
                dropZone.style.background = '';
            });
        });
        
        dropZone.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            if (files.length) {
                document.getElementById('backupFileInput').files = files;
                handleFileSelect(document.getElementById('backupFileInput'));
            }
        });
    </script>
</body>
</html>
