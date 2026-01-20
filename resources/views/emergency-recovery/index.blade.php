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
            
            <form method="POST" action="{{ request()->fullUrl() }}" enctype="multipart/form-data" style="margin-bottom: 20px;">
                @csrf
                <input type="hidden" name="secret_token" value="{{ $token }}">
                <input type="hidden" name="action" value="import_backup">
                
                <div class="form-group">
                    <label>Fichier de sauvegarde (.sql ou .sql.gz)</label>
                    <input type="file" name="backup_file" accept=".sql,.gz" required>
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
                            <form method="POST" action="{{ request()->fullUrl() }}" style="display: inline;" onsubmit="return confirm('⚠️ ATTENTION: Cette action va remplacer TOUTE la base de données. Êtes-vous sûr ?');">
                                @csrf
                                <input type="hidden" name="secret_token" value="{{ $token }}">
                                <input type="hidden" name="action" value="restore_backup">
                                <input type="hidden" name="filename" value="{{ $backup['filename'] }}">
                                <button type="submit" class="btn-small btn-danger">Restaurer</button>
                            </form>
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
</body>
</html>
