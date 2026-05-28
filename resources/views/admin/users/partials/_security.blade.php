<div class="space-y-6 lg:space-y-8">
    <!-- Actions Administrateur -->
    <div class="bg-gradient-to-r from-slate-900 to-slate-800 dark:from-slate-800 dark:to-slate-900 rounded-3xl p-6 lg:p-8 border border-slate-700 shadow-lg">
        <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-3">
            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            Actions Administrateur
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Générer un mot de passe -->
            <form action="{{ route('admin.users.generate-password', $user) }}" method="POST" class="flex flex-col">
                @csrf
                <label class="flex items-center gap-2 text-white text-sm mb-3 h-6">
                    <input type="checkbox" name="send_email" value="1" checked class="w-4 h-4 rounded border-slate-600">
                    <span>Envoyer par email</span>
                </label>
                <button type="submit" class="ui-btn-simple flex-1 w-full px-4 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl flex items-center justify-center gap-2 min-h-[48px]">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    <span class="text-center">Générer un mot de passe</span>
                </button>
            </form>

            <!-- Modifier l'email -->
            <div class="flex flex-col">
                <div class="h-6 mb-3"></div>
                <button onclick="showEmailModal()" class="flex-1 w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl flex items-center justify-center gap-2 min-h-[48px]">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <span class="text-center">Modifier l'email</span>
                </button>
            </div>

            <!-- Bloquer/Débloquer -->
            <div class="flex flex-col">
                <div class="h-6 mb-3"></div>
                @if($user->statut_compte === 'interdit' || $isLocked)
                    <form action="{{ route('admin.users.unblock', $user) }}" method="POST" class="flex-1 flex">
                        @csrf
                        <button type="submit" class="ui-btn-simple flex-1 w-full px-4 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl flex items-center justify-center gap-2 min-h-[48px]">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-center">Débloquer</span>
                        </button>
                    </form>
                @else
                    <button onclick="showBlockModal()" class="flex-1 w-full px-4 py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl flex items-center justify-center gap-2 min-h-[48px]">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                        </svg>
                        <span class="text-center">Bloquer</span>
                    </button>
                @endif
            </div>

            <!-- Archiver -->
            <div class="flex flex-col">
                <div class="h-6 mb-3"></div>
                <button onclick="showArchiveModal()" class="flex-1 w-full px-4 py-3 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl flex items-center justify-center gap-2 min-h-[48px]">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                    </svg>
                    <span class="text-center">Archiver</span>
                </button>
            </div>
        </div>
    </div>

    @if($isLocked)
        <div class="bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-800 rounded-3xl p-6 lg:p-8">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-red-900 dark:text-red-300 mb-2">Compte temporairement verrouillé</h3>
                    <p class="text-sm text-red-700 dark:text-red-400">
                        Le compte a été verrouillé après plusieurs tentatives de connexion échouées.
                        @if($lockout && $lockout->locked_until)
                            <br><strong>Déverrouillage prévu :</strong> {{ $lockout->locked_until->format('d/m/Y à H:i') }}
                            <br><strong>Temps restant :</strong> {{ now()->diffInMinutes($lockout->locked_until, false) }} minute(s)
                        @endif
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if($hasSuspiciousActivity)
        <div class="bg-orange-50 dark:bg-orange-900/20 border-2 border-orange-200 dark:border-orange-800 rounded-3xl p-6 lg:p-8">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-orange-900 dark:text-orange-300 mb-2">Activité suspecte détectée</h3>
                    <p class="text-sm text-orange-700 dark:text-orange-400">
                        Des activités suspectes ont été détectées sur ce compte au cours des 7 derniers jours.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Statistiques de sécurité -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Tentatives de connexion</p>
            <p class="text-3xl font-extrabold text-slate-900 dark:text-white">{{ $securityStats['total_login_attempts'] }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Connexions réussies</p>
            <p class="text-3xl font-extrabold text-green-600 dark:text-green-400">{{ $securityStats['successful_logins'] }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Échecs de connexion</p>
            <p class="text-3xl font-extrabold text-red-600 dark:text-red-400">{{ $securityStats['failed_attempts'] }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Activités suspectes (30j)</p>
            <p class="text-3xl font-extrabold {{ $securityStats['suspicious_logs'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' }}">{{ $securityStats['suspicious_logs'] }}</p>
        </div>
    </div>

    <!-- Authentification à deux facteurs -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 lg:p-8 border border-slate-100 dark:border-slate-700 shadow-sm">
        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-3">
            <span>🔐</span>
            Authentification à deux facteurs (A2F)
        </h3>
        <div class="space-y-4">
            <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-900/30 rounded-2xl">
                <div>
                    <p class="font-bold text-slate-900 dark:text-white">Statut A2F</p>
                    <p class="text-sm text-slate-500 mt-1">
                        @if($user->a2f_enabled)
                            Activé via {{ $user->a2f_method === 'sms' ? 'SMS' : 'Email' }}
                        @else
                            Désactivé
                        @endif
                    </p>
                </div>
                <span class="px-4 py-2 rounded-xl text-sm font-bold {{ $user->a2f_enabled ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400' }}">
                    {{ $user->a2f_enabled ? 'Activé' : 'Désactivé' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Tentatives de connexion récentes -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 lg:p-8 border border-slate-100 dark:border-slate-700 shadow-sm">
        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-3">
            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Tentatives de connexion récentes
        </h3>
        @if($loginAttempts->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-900/30">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">IP</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Statut</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Raison</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @foreach($loginAttempts->take(20) as $attempt)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/30 transition-colors">
                                <td class="px-4 py-3 text-sm text-slate-900 dark:text-white">
                                    {{ $attempt->attempted_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3 text-sm font-mono text-slate-600 dark:text-slate-400">
                                    {{ $attempt->ip_address }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($attempt->success)
                                        <span class="px-3 py-1 text-xs font-bold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full">Réussi</span>
                                    @else
                                        <span class="px-3 py-1 text-xs font-bold bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full">Échoué</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
                                    {{ $attempt->failure_reason ? ucfirst(str_replace('_', ' ', $attempt->failure_reason)) : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-8">Aucune tentative de connexion enregistrée.</p>
        @endif
    </div>

    <!-- Historique des IPs -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 lg:p-8 border border-slate-100 dark:border-slate-700 shadow-sm">
        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-3">
            <span>🌐</span>
            Historique des adresses IP
        </h3>
        @if($ipHistory->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-900/30">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Adresse IP</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Localisation</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Première utilisation</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Dernière utilisation</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Connexions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @foreach($ipHistory->take(20) as $ip)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/30 transition-colors">
                                <td class="px-4 py-3 text-sm font-mono text-slate-900 dark:text-white">
                                    {{ $ip->ip_address }}
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
                                    {{ $ip->location ?? 'Inconnue' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
                                    {{ $ip->first_seen_at->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
                                    {{ $ip->last_seen_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
                                    {{ $ip->login_count }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-8">Aucun historique d'IP disponible.</p>
        @endif
    </div>

    <!-- Logs de sécurité -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 lg:p-8 border border-slate-100 dark:border-slate-700 shadow-sm">
        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-3">
            <span>🔍</span>
            Activités de sécurité récentes
        </h3>
        <div class="space-y-3">
            @if($securityLogs->count() > 0)
                @foreach($securityLogs->take(20) as $log)
                    <div class="bg-slate-50 dark:bg-slate-900/30 rounded-2xl p-4 border border-slate-100 dark:border-slate-700">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="px-3 py-1 text-xs font-bold rounded-full
                                        @if($log->severity === 'critical') bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400
                                        @elseif($log->severity === 'high') bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400
                                        @elseif($log->severity === 'medium') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400
                                        @else bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-400
                                        @endif">
                                        {{ ucfirst($log->severity) }}
                                    </span>
                                    @if($log->is_suspicious)
                                        <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                        <span class="text-xs text-red-600 dark:text-red-400 font-bold">Suspect</span>
                                    @endif
                                    <span class="text-sm font-bold text-slate-900 dark:text-white">
                                        {{ ucfirst(str_replace('_', ' ', $log->event_type)) }}
                                    </span>
                                </div>
                                @if($log->description)
                                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">{{ $log->description }}</p>
                                @endif
                                <div class="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-500">
                                    <span>{{ $log->created_at->format('d/m/Y H:i') }}</span>
                                    <span class="font-mono">{{ $log->ip_address }}</span>
                                    @if($log->location)
                                        <span>{{ $log->location }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-8">Aucune activité de sécurité enregistrée.</p>
            @endif
        </div>
    </div>

    <!-- Historique de sécurité -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 lg:p-8 border border-slate-100 dark:border-slate-700 shadow-sm">
        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-3">
            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Historique des changements (Mots de passe & Emails)
        </h3>
        @if(isset($securityHistory) && $securityHistory->count() > 0)
            <div class="space-y-3">
                @foreach($securityHistory as $history)
                    <div class="bg-slate-50 dark:bg-slate-900/30 rounded-2xl p-4 border border-slate-100 dark:border-slate-700">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="px-3 py-1 text-xs font-bold rounded-full
                                        @if($history->type === 'password') bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400
                                        @else bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400
                                        @endif">
                                        @if($history->type === 'password')
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                            </svg>
                                            Mot de passe
                                        @else
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                            Email
                                        @endif
                                    </span>
                                    @if($history->changed_by)
                                        <span class="text-xs text-slate-500">Par admin #{{ $history->changed_by }}</span>
                                    @else
                                        <span class="text-xs text-slate-500">Par l'utilisateur</span>
                                    @endif
                                </div>
                                @if($history->type === 'email')
                                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">
                                        <strong>Ancien :</strong> {{ $history->old_email ?? 'N/A' }}
                                    </p>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">
                                        <strong>Nouveau :</strong> {{ $history->new_email ?? 'N/A' }}
                                    </p>
                                @else
                                    <p class="text-sm text-slate-600 dark:text-slate-400">
                                        Mot de passe modifié
                                    </p>
                                @endif
                                @if($history->reason)
                                    <p class="text-xs text-slate-500 mt-1 italic">{{ $history->reason }}</p>
                                @endif
                                <div class="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-500 mt-2">
                                    <span>{{ $history->created_at->format('d/m/Y H:i') }}</span>
                                    @if($history->ip_address)
                                        <span class="font-mono">{{ $history->ip_address }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-8">Aucun historique disponible.</p>
        @endif
    </div>
</div>

<!-- Modals -->
<!-- Modal Modifier Email -->
<div id="emailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-3xl p-8 max-w-md w-full shadow-2xl">
        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6">Modifier l'email</h3>
        <form action="{{ route('admin.users.update-email', $user) }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Nouvel email</label>
                    <input type="email" name="email" value="{{ $user->email }}" required class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-slate-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Raison (optionnel)</label>
                    <textarea name="reason" rows="3" class="ui-textarea w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-slate-700 dark:text-white"></textarea>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="hideEmailModal()" class="flex-1 px-4 py-3 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-xl hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                    Annuler
                </button>
                <button type="submit" class="ui-btn-simple flex-1 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition">
                    Modifier
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Bloquer -->
<div id="blockModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-3xl p-8 max-w-md w-full shadow-2xl">
        <h3 class="text-xl font-bold text-red-600 dark:text-red-400 mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
            </svg>
            Bloquer l'utilisateur
        </h3>
        <p class="text-sm text-slate-600 dark:text-slate-400 mb-6">Cette action bloquera définitivement l'accès au compte.</p>
        <form action="{{ route('admin.users.block', $user) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Raison (optionnel)</label>
                <textarea name="reason" rows="3" class="ui-textarea w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent dark:bg-slate-700 dark:text-white"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="hideBlockModal()" class="flex-1 px-4 py-3 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-xl hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                    Annuler
                </button>
                <button type="submit" class="ui-btn-simple flex-1 px-4 py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition">
                    Bloquer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Archiver -->
<div id="archiveModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-3xl p-8 max-w-md w-full shadow-2xl">
        <h3 class="text-xl font-bold text-orange-600 dark:text-orange-400 mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
            </svg>
            Archiver l'utilisateur
        </h3>
        <p class="text-sm text-slate-600 dark:text-slate-400 mb-6">Cette action archivera le compte (soft delete). L'utilisateur pourra être restauré ultérieurement.</p>
        <form action="{{ route('admin.users.archive', $user) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Raison (optionnel)</label>
                <textarea name="reason" rows="3" class="ui-textarea w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent dark:bg-slate-700 dark:text-white"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="hideArchiveModal()" class="flex-1 px-4 py-3 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-xl hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                    Annuler
                </button>
                <button type="submit" class="ui-btn-simple flex-1 px-4 py-3 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-xl transition">
                    Archiver
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showEmailModal() {
    document.getElementById('emailModal').classList.remove('hidden');
}
function hideEmailModal() {
    document.getElementById('emailModal').classList.add('hidden');
}
function showBlockModal() {
    document.getElementById('blockModal').classList.remove('hidden');
}
function hideBlockModal() {
    document.getElementById('blockModal').classList.add('hidden');
}
function showArchiveModal() {
    document.getElementById('archiveModal').classList.remove('hidden');
}
function hideArchiveModal() {
    document.getElementById('archiveModal').classList.add('hidden');
}

// Fermer les modals en cliquant à l'extérieur
document.getElementById('emailModal')?.addEventListener('click', function(e) {
    if (e.target === this) hideEmailModal();
});
document.getElementById('blockModal')?.addEventListener('click', function(e) {
    if (e.target === this) hideBlockModal();
});
document.getElementById('archiveModal')?.addEventListener('click', function(e) {
    if (e.target === this) hideArchiveModal();
});
</script>
