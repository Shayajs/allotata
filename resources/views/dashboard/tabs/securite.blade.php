{{-- Onglet Sécurité --}}
<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Sécurité</h2>
        @if($hasSuspiciousActivity)
            <div class="flex items-center gap-2 px-3 py-1.5 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                <span class="text-sm font-medium text-red-700 dark:text-red-400">Activité suspecte détectée</span>
            </div>
        @endif
    </div>

    @if($isLocked)
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div>
                    <p class="font-semibold text-red-900 dark:text-red-300">Compte temporairement verrouillé</p>
                    <p class="text-sm text-red-700 dark:text-red-400">
                        Votre compte a été verrouillé après plusieurs tentatives de connexion échouées.
                        @if($lockout->locked_until)
                            Vous pourrez réessayer dans {{ now()->diffInMinutes($lockout->locked_until, false) }} minute(s).
                        @endif
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Préférences de récupération -->
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Méthode de récupération de mot de passe</h3>
        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4">
            <form action="{{ route('security.recovery-method.update') }}" method="POST">
                @csrf
                <div class="space-y-3">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="radio" name="preference_recovery_method" value="email" 
                               {{ ($user->preference_recovery_method ?? 'email') === 'email' ? 'checked' : '' }}
                               class="w-4 h-4 text-green-600 border-slate-300 focus:ring-green-500">
                        <div>
                            <span class="font-medium text-slate-900 dark:text-white">Email</span>
                            <p class="text-sm text-slate-600 dark:text-slate-400">Recevoir les codes de réinitialisation par email</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="radio" name="preference_recovery_method" value="sms" 
                               {{ ($user->preference_recovery_method ?? 'email') === 'sms' ? 'checked' : '' }}
                               class="w-4 h-4 text-green-600 border-slate-300 focus:ring-green-500"
                               {{ !$user->telephone ? 'disabled' : '' }}>
                        <div>
                            <span class="font-medium text-slate-900 dark:text-white">SMS</span>
                            <p class="text-sm text-slate-600 dark:text-slate-400">
                                Recevoir les codes de réinitialisation par SMS
                                @if(!$user->telephone)
                                    <span class="text-red-600 dark:text-red-400">(Ajoutez un numéro de téléphone dans vos paramètres)</span>
                                @endif
                            </p>
                        </div>
                    </label>
                </div>
                <button type="submit" class="mt-4 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition">
                    Enregistrer
                </button>
            </form>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4">
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Tentatives de connexion (30j)</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total_login_attempts'] }}</p>
        </div>
        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4">
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Connexions réussies</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['successful_logins'] }}</p>
        </div>
        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4">
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Échecs de connexion</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['failed_attempts'] }}</p>
        </div>
        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4">
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Activités suspectes (30j)</p>
            <p class="text-2xl font-bold {{ $stats['suspicious_logs'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' }}">{{ $stats['suspicious_logs'] }}</p>
        </div>
    </div>

    <!-- Tentatives de connexion récentes -->
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Tentatives de connexion récentes</h3>
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
            @if($loginAttempts->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 dark:bg-slate-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">IP</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Statut</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Raison</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            @foreach($loginAttempts as $attempt)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-slate-900 dark:text-white">
                                        {{ $attempt->attempted_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
                                        {{ $attempt->ip_address }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($attempt->success)
                                            <span class="px-2 py-1 text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full">Réussi</span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full">Échoué</span>
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
                <p class="p-4 text-sm text-slate-600 dark:text-slate-400">Aucune tentative de connexion enregistrée.</p>
            @endif
        </div>
    </div>

    <!-- Historique des IPs -->
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Historique des adresses IP</h3>
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
            @if($ipHistory->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 dark:bg-slate-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Adresse IP</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Localisation</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Première utilisation</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Dernière utilisation</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Connexions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            @foreach($ipHistory as $ip)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-slate-900 dark:text-white font-mono">
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
                <p class="p-4 text-sm text-slate-600 dark:text-slate-400">Aucun historique d'IP disponible.</p>
            @endif
        </div>
    </div>

    <!-- Logs de sécurité -->
    <div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Activités de sécurité récentes</h3>
        <div class="space-y-3">
            @if($securityLogs->count() > 0)
                @foreach($securityLogs->take(20) as $log)
                    <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        @if($log->severity === 'critical') bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400
                                        @elseif($log->severity === 'high') bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400
                                        @elseif($log->severity === 'medium') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400
                                        @else bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-400
                                        @endif">
                                        {{ ucfirst($log->severity) }}
                                    </span>
                                    @if($log->is_suspicious)
                                        <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                        <span class="text-xs text-red-600 dark:text-red-400 font-medium">Suspect</span>
                                    @endif
                                    <span class="text-sm font-medium text-slate-900 dark:text-white">
                                        {{ ucfirst(str_replace('_', ' ', $log->event_type)) }}
                                    </span>
                                </div>
                                @if($log->description)
                                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">{{ $log->description }}</p>
                                @endif
                                <div class="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-500">
                                    <span>{{ $log->created_at->format('d/m/Y H:i') }}</span>
                                    <span>{{ $log->ip_address }}</span>
                                    @if($log->location)
                                        <span>{{ $log->location }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <p class="text-sm text-slate-600 dark:text-slate-400">Aucune activité de sécurité enregistrée.</p>
            @endif
        </div>
    </div>
</div>
