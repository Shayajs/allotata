{{-- Onglet Sécurité --}}
@php
    // Utiliser $securityStats si disponible (depuis DashboardController), sinon $stats (depuis SecurityController)
    $stats = $securityStats ?? $stats;
@endphp
<div>
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Sécurité</h2>
            <x-course-link-badge page-key="dashboard.securite" :course-links="$courseLinks ?? []" />
        </div>
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

    <!-- Authentification à deux facteurs (A2F) -->
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Authentification à deux facteurs (A2F)</h3>
        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4">
            <form action="{{ route('security.a2f.update') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="a2f_enabled" value="1" 
                               {{ $user->a2f_enabled ? 'checked' : '' }}
                               onchange="document.getElementById('a2f-method-container').classList.toggle('hidden', !this.checked)"
                               class="w-4 h-4 text-green-600 border-slate-300 focus:ring-green-500">
                        <div>
                            <span class="font-medium text-slate-900 dark:text-white">Activer l'A2F</span>
                            <p class="text-sm text-slate-600 dark:text-slate-400">Vous devrez saisir un code à chaque connexion pour plus de sécurité</p>
                        </div>
                    </label>
                    <div id="a2f-method-container" class="{{ $user->a2f_enabled ? '' : 'hidden' }} space-y-3">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Méthode de réception du code :</p>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="a2f_methods[]" value="email" 
                                   {{ ($user->a2f_method_email ?? true) ? 'checked' : '' }}
                                   class="w-4 h-4 text-green-600 border-slate-300 focus:ring-green-500 rounded">
                            <div>
                                <span class="font-medium text-slate-900 dark:text-white">Email</span>
                                <p class="text-sm text-slate-600 dark:text-slate-400">Recevoir le code par email</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="a2f_methods[]" value="sms" 
                                   {{ ($user->a2f_method_sms ?? false) ? 'checked' : '' }}
                                   class="w-4 h-4 text-green-600 border-slate-300 focus:ring-green-500 rounded"
                                   {{ !$user->telephone ? 'disabled' : '' }}>
                            <div>
                                <span class="font-medium text-slate-900 dark:text-white">SMS</span>
                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                    Recevoir le code par SMS
                                    @if(!$user->telephone)
                                        <span class="text-red-600 dark:text-red-400">(Ajoutez un numéro de téléphone dans vos paramètres)</span>
                                    @endif
                                </p>
                            </div>
                        </label>
                        <p class="text-xs text-slate-500 dark:text-slate-400 italic">Vous pouvez sélectionner les deux méthodes pour recevoir le code par email ET par SMS.</p>
                    </div>
                </div>
                <button type="submit" class="mt-4 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition">
                    Enregistrer
                </button>
            </form>
        </div>
    </div>

    <!-- Authentification à deux facteurs TOTP (Google Authenticator) -->
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Authentification TOTP (Google Authenticator)</h3>
        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4">
            @php
                $google2faDisabled = \App\Models\Setting::get('google2fa_disabled', false);
            @endphp
            
            @if($google2faDisabled)
                <div class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                    <p class="text-sm text-yellow-700 dark:text-yellow-400">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        L'authentification TOTP est désactivée par l'administrateur.
                    </p>
                </div>
            @endif

            @if(class_exists('\PragmaRX\Google2FA\Google2FA') && $user->hasGoogle2faEnabled())
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                        <div>
                            <p class="font-medium text-green-900 dark:text-green-300 flex items-center gap-2">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Authentification TOTP activée
                            </p>
                            <p class="text-sm text-green-700 dark:text-green-400 mt-1">
                                Votre compte est protégé par l'authentification à deux facteurs via Google Authenticator.
                            </p>
                        </div>
                        <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full text-sm font-medium">
                            Activé
                        </span>
                    </div>

                    <!-- Codes de récupération -->
                    @php
                        $recoveryCodesWithStatus = $user->getRecoveryCodesWithStatus();
                    @endphp
                    @if(!empty($recoveryCodesWithStatus))
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <p class="font-medium text-blue-900 dark:text-blue-300 flex items-center gap-2">
                                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                        Codes de récupération
                                    </p>
                                    <p class="text-sm text-blue-700 dark:text-blue-400 mt-1">
                                        Enregistrez ces codes dans un endroit sûr. Ils ne peuvent être utilisés qu'une seule fois chacun.
                                    </p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-4">
                                @foreach($recoveryCodesWithStatus as $codeData)
                                    <div class="p-3 bg-white dark:bg-slate-800 rounded-lg border {{ $codeData['used'] ? 'border-red-300 dark:border-red-700 opacity-60' : 'border-slate-200 dark:border-slate-700' }}">
                                        <div class="flex items-center justify-between">
                                            <code class="font-mono text-sm {{ $codeData['used'] ? 'line-through text-slate-400 dark:text-slate-500' : 'text-slate-900 dark:text-white font-bold' }}">
                                                {{ $codeData['code'] }}
                                            </code>
                                            @if($codeData['used'])
                                                <span class="text-xs text-red-600 dark:text-red-400 ml-2" title="Utilisé le {{ $codeData['used_at'] ? $codeData['used_at']->format('d/m/Y à H:i') : 'N/A' }}">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </span>
                                            @else
                                                <span class="text-xs text-green-600 dark:text-green-400 ml-2">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </span>
                                            @endif
                                        </div>
                                        @if($codeData['used'] && $codeData['used_at'])
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                                Utilisé le {{ $codeData['used_at']->format('d/m/Y H:i') }}
                                            </p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            <p class="text-xs text-blue-600 dark:text-blue-400 mt-3">
                                <strong>Note :</strong> Les codes barrés ont déjà été utilisés et ne peuvent plus être utilisés. Les codes valides peuvent encore être utilisés une fois.
                            </p>
                        </div>
                    @endif

                    <div class="flex gap-3">
                        <button type="button" onclick="showGoogle2FAModal('recovery')"
                                class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
                            Régénérer les codes de récupération
                        </button>

                        @if(!$google2faDisabled)
                            <button type="button" onclick="showGoogle2FAModal('disable')"
                                    class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition">
                                Désactiver TOTP
                            </button>
                        @endif
                    </div>
                </div>
            @else
                @if(!$google2faDisabled)
                    <div class="space-y-4">
                        <p class="text-sm text-slate-600 dark:text-slate-400">
                            Utilisez une application d'authentification (comme Google Authenticator, Microsoft Authenticator, ou Authy) pour générer des codes de sécurité à usage unique.
                        </p>

                        <button type="button" onclick="showGoogle2FAModal('enable')"
                                class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition">
                            Activer l'authentification TOTP
                        </button>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- Préférences de récupération -->
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Méthode de récupération de mot de passe</h3>
        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4">
            <form action="{{ route('security.recovery-method.update') }}" method="POST">
                @csrf
                <div class="space-y-3">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="recovery_methods[]" value="email" 
                               {{ ($user->recovery_method_email ?? true) ? 'checked' : '' }}
                               class="w-4 h-4 text-green-600 border-slate-300 focus:ring-green-500 rounded">
                        <div>
                            <span class="font-medium text-slate-900 dark:text-white">Email</span>
                            <p class="text-sm text-slate-600 dark:text-slate-400">Recevoir les codes de réinitialisation par email</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="recovery_methods[]" value="sms" 
                               {{ ($user->recovery_method_sms ?? false) ? 'checked' : '' }}
                               class="w-4 h-4 text-green-600 border-slate-300 focus:ring-green-500 rounded"
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
                    <p class="text-xs text-slate-500 dark:text-slate-400 italic">Vous pouvez sélectionner les deux méthodes pour recevoir les codes par email ET par SMS.</p>
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

    <!-- Accès et actions administrateur -->
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Accès et actions administrateur</h3>
            <a href="{{ route('tickets.create') }}" class="text-xs font-medium text-green-600 hover:text-green-700 dark:text-green-400">
                Contester / support
            </a>
        </div>
        <div class="space-y-3">
            @forelse(($adminAccountLogs ?? collect()) as $log)
                <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2 flex-wrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if($log->severity === 'critical') bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400
                                    @elseif($log->severity === 'high') bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400
                                    @elseif($log->severity === 'medium') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400
                                    @else bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-400
                                    @endif">
                                    {{ ucfirst($log->severity) }}
                                </span>
                                <span class="text-sm font-medium text-slate-900 dark:text-white">
                                    {{ \App\Models\SecurityLog::labelForEvent($log->event_type) }}
                                </span>
                            </div>
                            @if($log->description)
                                <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">{{ $log->description }}</p>
                            @endif
                            @if($log->event_type === 'admin_account_action' && !empty($log->metadata['summary']))
                                <p class="text-xs text-slate-500 dark:text-slate-400 font-mono">{{ $log->metadata['summary'] }}</p>
                            @endif
                            <div class="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-500 mt-2">
                                <span>{{ $log->created_at->format('d/m/Y H:i') }}</span>
                                <span>{{ $log->ip_address }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-600 dark:text-slate-400 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                    Aucun accès ou action administrateur enregistré sur les 30 derniers jours.
                </p>
            @endforelse
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
                                        {{ \App\Models\SecurityLog::labelForEvent($log->event_type) }}
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

    <!-- Modale Google 2FA -->
    <div id="google2fa-modal" class="hidden fixed inset-0 bg-black/50 dark:bg-black/75 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <!-- En-tête de la modale -->
            <div class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-slate-700 flex-shrink-0">
                <h3 id="google2fa-modal-title" class="text-xl font-bold text-slate-900 dark:text-white">Activer l'authentification TOTP</h3>
                <button onclick="closeGoogle2FAModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Contenu de la modale -->
            <div class="overflow-y-auto flex-1 p-6">
                <!-- Contenu pour l'activation -->
                <div id="google2fa-enable-content" class="hidden space-y-6">
                    <div id="google2fa-loading" class="text-center py-8">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
                        <p class="mt-4 text-sm text-slate-600 dark:text-slate-400">Génération du QR code...</p>
                    </div>

                    <div id="google2fa-setup" class="hidden space-y-6">
                        <div>
                            <p class="text-sm font-medium text-slate-900 dark:text-white mb-3">
                                1. Scannez ce QR code avec votre application d'authentification :
                            </p>
                            <div id="qr-code-container" class="flex justify-center mb-4 p-4 bg-white dark:bg-slate-900 rounded-lg">
                                <!-- Le QR code sera injecté ici -->
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 text-center mb-4">
                                Ou entrez manuellement cette clé secrète : 
                                <span id="secret-key" class="font-mono font-bold text-slate-900 dark:text-white bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded"></span>
                            </p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-slate-900 dark:text-white mb-3">
                                2. Entrez le code à 6 chiffres généré par votre application :
                            </p>
                            <form id="enable-google2fa-form">
                                @csrf
                                <div class="flex gap-3">
                                    <input type="text" name="code" id="totp-code" required
                                           pattern="[0-9]{6}" maxlength="6" placeholder="000000"
                                           class="flex-1 px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-center text-3xl font-mono tracking-widest">
                                </div>
                                <div id="google2fa-error" class="hidden mt-2 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                    <p class="text-sm text-red-600 dark:text-red-400"></p>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div id="google2fa-recovery-codes-display" class="hidden">
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <p class="font-medium text-blue-900 dark:text-blue-300 mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                Codes de récupération
                            </p>
                            <p class="text-sm text-blue-700 dark:text-blue-400 mb-3">
                                Enregistrez ces codes dans un endroit sûr. Vous pourrez les utiliser si vous perdez l'accès à votre application d'authentification.
                            </p>
                            <div id="recovery-codes-list" class="bg-white dark:bg-slate-800 p-3 rounded font-mono text-sm space-y-1">
                                <!-- Les codes seront injectés ici -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contenu pour la désactivation -->
                <div id="google2fa-disable-content" class="hidden space-y-4">
                    <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <p class="text-sm text-red-700 dark:text-red-400">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            La désactivation de l'authentification TOTP réduira la sécurité de votre compte.
                        </p>
                    </div>
                    <form action="{{ route('security.google2fa.disable') }}" method="POST" id="disable-google2fa-form">
                        @csrf
                        <div>
                            <label for="password_disable_modal" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Confirmez votre mot de passe pour désactiver :
                            </label>
                            <input type="password" name="password" id="password_disable_modal" required
                                   class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        </div>
                    </form>
                </div>

                <!-- Contenu pour la régénération des codes de récupération -->
                <div id="google2fa-recovery-content" class="hidden space-y-4">
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <p class="text-sm text-blue-700 dark:text-blue-400">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            Les anciens codes de récupération seront invalidés après la régénération.
                        </p>
                    </div>
                    <form action="{{ route('security.google2fa.recovery-codes') }}" method="POST" id="recovery-codes-form">
                        @csrf
                        <div>
                            <label for="password_recovery_modal" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Confirmez votre mot de passe :
                            </label>
                            <input type="password" name="password" id="password_recovery_modal" required
                                   class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        </div>
                    </form>
                </div>
            </div>

            <!-- Pied de la modale -->
            <div class="flex items-center justify-end gap-3 p-6 border-t border-slate-200 dark:border-slate-700 flex-shrink-0">
                <button onclick="closeGoogle2FAModal()" class="px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg text-sm font-medium transition">
                    Annuler
                </button>
                <button id="google2fa-modal-action-btn" onclick="handleGoogle2FAModalAction()" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition">
                    Activer
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let currentGoogle2FAMode = null;

        function showGoogle2FAModal(mode) {
            currentGoogle2FAMode = mode;
            const modal = document.getElementById('google2fa-modal');
            const modalTitle = document.getElementById('google2fa-modal-title');
            const actionBtn = document.getElementById('google2fa-modal-action-btn');
            
            // Masquer tous les contenus
            document.getElementById('google2fa-enable-content').classList.add('hidden');
            document.getElementById('google2fa-disable-content').classList.add('hidden');
            document.getElementById('google2fa-recovery-content').classList.add('hidden');
            document.getElementById('google2fa-setup').classList.add('hidden');
            document.getElementById('google2fa-recovery-codes-display').classList.add('hidden');

            if (mode === 'enable') {
                modalTitle.textContent = 'Activer l\'authentification TOTP';
                actionBtn.textContent = 'Activer';
                actionBtn.className = 'px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition';
                document.getElementById('google2fa-enable-content').classList.remove('hidden');
                document.getElementById('google2fa-loading').classList.remove('hidden');
                setupGoogle2FA();
            } else if (mode === 'disable') {
                modalTitle.textContent = 'Désactiver l\'authentification TOTP';
                actionBtn.textContent = 'Désactiver';
                actionBtn.className = 'px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition';
                document.getElementById('google2fa-disable-content').classList.remove('hidden');
            } else if (mode === 'recovery') {
                modalTitle.textContent = 'Régénérer les codes de récupération';
                actionBtn.textContent = 'Régénérer';
                actionBtn.className = 'px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition';
                document.getElementById('google2fa-recovery-content').classList.remove('hidden');
            }

            modal.classList.remove('hidden');
        }

        function closeGoogle2FAModal() {
            document.getElementById('google2fa-modal').classList.add('hidden');
            currentGoogle2FAMode = null;
        }

        async function handleGoogle2FAModalAction() {
            if (currentGoogle2FAMode === 'enable') {
                await submitEnableGoogle2FA();
            } else if (currentGoogle2FAMode === 'disable') {
                document.getElementById('disable-google2fa-form').submit();
            } else if (currentGoogle2FAMode === 'recovery') {
                document.getElementById('recovery-codes-form').submit();
            }
        }

        async function submitEnableGoogle2FA() {
            const form = document.getElementById('enable-google2fa-form');
            const codeInput = document.getElementById('totp-code');
            const errorDiv = document.getElementById('google2fa-error');
            const errorText = errorDiv.querySelector('p');
            const actionBtn = document.getElementById('google2fa-modal-action-btn');
            
            const code = codeInput.value.trim();
            
            if (!code || code.length !== 6) {
                errorText.textContent = 'Veuillez entrer un code à 6 chiffres.';
                errorDiv.classList.remove('hidden');
                return;
            }

            // Masquer l'erreur précédente
            errorDiv.classList.add('hidden');
            actionBtn.disabled = true;
            actionBtn.textContent = 'Vérification...';

            try {
                const formData = new FormData();
                formData.append('code', code);
                formData.append('_token', '{{ csrf_token() }}');

                const response = await fetch('{{ route("security.google2fa.enable") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    const errorMessage = data.message || data.error || 'Erreur lors de l\'activation.';
                    if (data.errors && data.errors.code) {
                        errorText.textContent = Array.isArray(data.errors.code) ? data.errors.code[0] : data.errors.code;
                    } else {
                        errorText.textContent = errorMessage;
                    }
                    errorDiv.classList.remove('hidden');
                    actionBtn.disabled = false;
                    actionBtn.textContent = 'Activer';
                    return;
                }

                // Afficher les codes de récupération
                if (data.recovery_codes && data.recovery_codes.length > 0) {
                    const recoveryCodesList = document.getElementById('recovery-codes-list');
                    recoveryCodesList.innerHTML = data.recovery_codes.map(code => 
                        '<div class="p-2 bg-slate-50 dark:bg-slate-700 rounded mb-1">' + code + '</div>'
                    ).join('');
                    
                    // Masquer le formulaire d'activation
                    document.getElementById('google2fa-setup').classList.add('hidden');
                    
                    // Afficher les codes de récupération
                    document.getElementById('google2fa-recovery-codes-display').classList.remove('hidden');
                    
                    // Changer le titre et le bouton
                    document.getElementById('google2fa-modal-title').textContent = 'Codes de récupération';
                    actionBtn.textContent = 'Fermer';
                    actionBtn.onclick = closeGoogle2FAModal;
                    actionBtn.disabled = false;
                    actionBtn.className = 'px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition';
                    
                    // Recharger la page après 5 secondes pour afficher le nouveau statut
                    setTimeout(() => {
                        window.location.reload();
                    }, 5000);
                } else {
                    // Si pas de codes, fermer directement et recharger
                    closeGoogle2FAModal();
                    window.location.reload();
                }
            } catch (error) {
                errorText.textContent = 'Erreur : ' + error.message;
                errorDiv.classList.remove('hidden');
                actionBtn.disabled = false;
                actionBtn.textContent = 'Activer';
            }
        }

        async function setupGoogle2FA() {
            try {
                const response = await fetch('{{ route("security.google2fa.generate") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.error || error.message || 'Erreur lors de la génération du QR code');
                }

                const data = await response.json();
                
                // Afficher le QR code (image base64 ou générer côté client)
                const qrContainer = document.getElementById('qr-code-container');
                if (data.qr_code_image) {
                    // Afficher l'image générée par l'API
                    qrContainer.innerHTML = `<img src="${data.qr_code_image}" alt="QR Code" class="mx-auto" />`;
                } else if (data.qr_code_url) {
                    // Fallback: Générer le QR code côté client avec une bibliothèque
                    // ou utiliser une API directement
                    qrContainer.innerHTML = `<img src="https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=${encodeURIComponent(data.qr_code_url)}" alt="QR Code" class="mx-auto" />`;
                }
                
                // Afficher la clé secrète
                document.getElementById('secret-key').textContent = data.secret;
                
                // Afficher le formulaire d'activation
                document.getElementById('google2fa-loading').classList.add('hidden');
                document.getElementById('google2fa-setup').classList.remove('hidden');
                
                // Focus sur l'input du code
                setTimeout(() => {
                    document.getElementById('totp-code')?.focus();
                }, 100);
            } catch (error) {
                alert('Erreur : ' + error.message);
                closeGoogle2FAModal();
            }
        }

        // Validation du code TOTP en temps réel
        document.addEventListener('DOMContentLoaded', function() {
            const codeInput = document.getElementById('totp-code');
            if (codeInput) {
                codeInput.addEventListener('input', function(e) {
                    // Ne garder que les chiffres
                    e.target.value = e.target.value.replace(/[^0-9]/g, '');
                });
            }

            // Afficher les codes de récupération si présents dans la session
            @if(session('recovery_codes'))
                const recoveryCodes = @json(session('recovery_codes'));
                const recoveryCodesList = document.getElementById('recovery-codes-list');
                if (recoveryCodesList) {
                    recoveryCodesList.innerHTML = recoveryCodes.map(code => 
                        '<div class="p-2 bg-slate-50 dark:bg-slate-700 rounded">' + code + '</div>'
                    ).join('');
                    document.getElementById('google2fa-recovery-codes-display').classList.remove('hidden');
                    showGoogle2FAModal('enable');
                }
            @endif

            // Fermer la modale en cliquant en dehors
            document.getElementById('google2fa-modal')?.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeGoogle2FAModal();
                }
            });

            // Fermer avec la touche Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && !document.getElementById('google2fa-modal').classList.contains('hidden')) {
                    closeGoogle2FAModal();
                }
            });

            // Soumission du formulaire avec Enter
            const totpCodeInput = document.getElementById('totp-code');
            if (totpCodeInput) {
                totpCodeInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && currentGoogle2FAMode === 'enable') {
                        e.preventDefault();
                        handleGoogle2FAModalAction();
                    }
                });
            }
        });
    </script>
    @endpush
</div>
