<?php

namespace App\Services\Audit;

use App\Models\LoginAttempt;
use App\Models\SecurityLog;
use App\Models\User;
use App\Models\AccountLockout;

class SecurityChecker extends BaseChecker
{
    public function key(): string
    {
        return 'security';
    }

    public function label(): string
    {
        return 'Sécurité';
    }

    public function run(): array
    {
        $items = [];
        $recommendations = [];
        $score = 100;

        // Debug mode
        $debugMode = config('app.debug');
        $items[] = ['label' => 'Mode debug', 'value' => $debugMode ? 'ACTIVÉ' : 'Désactivé', 'severity' => $debugMode ? 'critical' : 'ok'];
        if ($debugMode) {
            $score -= 25;
            $recommendations[] = 'Le mode debug est activé en production — désactiver APP_DEBUG.';
        }

        // HTTPS
        $appUrl = config('app.url', '');
        $isHttps = str_starts_with($appUrl, 'https://');
        $items[] = ['label' => 'HTTPS', 'value' => $isHttps ? 'Oui' : 'Non', 'severity' => $isHttps ? 'ok' : 'critical'];
        if (!$isHttps) {
            $score -= 20;
            $recommendations[] = 'Le site n\'utilise pas HTTPS — configurer un certificat SSL.';
        }

        // Logins suspects (7 derniers jours)
        $suspiciousLogins = SecurityLog::where('is_suspicious', true)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        $items[] = ['label' => 'Connexions suspectes (7j)', 'value' => $suspiciousLogins, 'severity' => $suspiciousLogins > 10 ? 'critical' : ($suspiciousLogins > 3 ? 'warning' : 'ok')];
        $score -= min(15, $suspiciousLogins * 2);

        // Brute force (tentatives échouées)
        $failedLogins = LoginAttempt::where('success', false)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        $items[] = ['label' => 'Tentatives de connexion échouées (7j)', 'value' => $failedLogins, 'severity' => $failedLogins > 50 ? 'critical' : ($failedLogins > 20 ? 'warning' : 'ok')];
        if ($failedLogins > 50) {
            $score -= 15;
            $recommendations[] = 'Nombre élevé de tentatives échouées — possible attaque brute force.';
        }

        // Comptes verrouillés
        $lockedAccounts = AccountLockout::where('locked_until', '>', now())->count();
        $items[] = ['label' => 'Comptes actuellement verrouillés', 'value' => $lockedAccounts, 'severity' => $lockedAccounts > 5 ? 'warning' : 'ok'];

        // Adoption 2FA
        $totalUsers = User::where('is_admin', false)->count();
        $users2fa = User::where('is_admin', false)
            ->where(function ($q) {
                $q->where('google2fa_enabled', true);
            })
            ->count();
        $twoFaRate = $totalUsers > 0 ? round(($users2fa / $totalUsers) * 100) : 0;
        $items[] = ['label' => 'Adoption 2FA', 'value' => $twoFaRate . '%', 'severity' => $twoFaRate >= 50 ? 'ok' : ($twoFaRate >= 20 ? 'warning' : 'critical')];

        // Admin 2FA
        $adminTotal = User::where('is_admin', true)->count();
        $admin2fa = User::where('is_admin', true)
            ->where(function ($q) {
                $q->where('google2fa_enabled', true);
            })
            ->count();
        $admin2faRate = $adminTotal > 0 ? round(($admin2fa / $adminTotal) * 100) : 0;
        $items[] = ['label' => '2FA admins', 'value' => $admin2faRate . '% (' . $admin2fa . '/' . $adminTotal . ')', 'severity' => $admin2faRate === 100 ? 'ok' : 'critical'];
        if ($admin2faRate < 100) {
            $score -= 10;
            $recommendations[] = 'Tous les administrateurs devraient avoir la 2FA activée.';
        }

        // Permissions .env
        $envPath = base_path('.env');
        $envPerms = null;
        if (file_exists($envPath)) {
            $envPerms = substr(sprintf('%o', fileperms($envPath)), -3);
        }
        $envSecure = $envPerms && (int) $envPerms <= 640;
        $items[] = ['label' => 'Permissions .env', 'value' => $envPerms ?? 'N/A', 'severity' => $envSecure ? 'ok' : 'warning'];
        if (!$envSecure && $envPerms) {
            $score -= 5;
            $recommendations[] = "Permissions .env trop permissives ({$envPerms}) — recommandé : 600.";
        }

        // Vérifier APP_KEY
        $appKey = config('app.key');
        $hasKey = !empty($appKey) && strlen($appKey) > 20;
        $items[] = ['label' => 'APP_KEY configurée', 'value' => $hasKey ? 'Oui' : 'Non', 'severity' => $hasKey ? 'ok' : 'critical'];
        if (!$hasKey) {
            $score -= 20;
        }

        // Session securisée
        $sessionSecure = config('session.secure', false);
        $sessionHttpOnly = config('session.http_only', true);
        $items[] = ['label' => 'Session sécurisée (secure cookie)', 'value' => $sessionSecure ? 'Oui' : 'Non', 'severity' => $sessionSecure ? 'ok' : 'warning'];
        $items[] = ['label' => 'Session HTTP-only', 'value' => $sessionHttpOnly ? 'Oui' : 'Non', 'severity' => $sessionHttpOnly ? 'ok' : 'warning'];

        return $this->result($score, $items, $recommendations);
    }
}
