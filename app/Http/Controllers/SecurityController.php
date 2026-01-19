<?php

namespace App\Http\Controllers;

use App\Models\LoginAttempt;
use App\Models\SecurityLog;
use App\Models\UserIpHistory;
use App\Models\AccountLockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SecurityController extends Controller
{
    /**
     * Afficher la page de sécurité
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Récupérer les tentatives de connexion récentes (30 derniers jours)
        $loginAttempts = LoginAttempt::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->orderBy('attempted_at', 'desc')
            ->limit(50)
            ->get();

        // Récupérer les logs de sécurité récents
        $securityLogs = SecurityLog::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        // Récupérer l'historique des IPs
        $ipHistory = UserIpHistory::where('user_id', $user->id)
            ->orderBy('last_seen_at', 'desc')
            ->get();

        // Récupérer le statut de blocage
        $lockout = $user->accountLockout;
        $isLocked = $lockout && $lockout->isCurrentlyLocked();

        // Vérifier s'il y a des activités suspectes récentes
        $hasSuspiciousActivity = SecurityLog::where('user_id', $user->id)
            ->where('is_suspicious', true)
            ->where('created_at', '>=', now()->subDays(7))
            ->exists();

        // Statistiques
        $stats = [
            'total_login_attempts' => $loginAttempts->count(),
            'failed_attempts' => $loginAttempts->where('success', false)->count(),
            'successful_logins' => $loginAttempts->where('success', true)->count(),
            'suspicious_logs' => SecurityLog::where('user_id', $user->id)
                ->where('is_suspicious', true)
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
            'unique_ips' => $ipHistory->count(),
        ];

        return view('dashboard.tabs.securite', [
            'user' => $user,
            'loginAttempts' => $loginAttempts,
            'securityLogs' => $securityLogs,
            'ipHistory' => $ipHistory,
            'lockout' => $lockout,
            'isLocked' => $isLocked,
            'hasSuspiciousActivity' => $hasSuspiciousActivity,
            'stats' => $stats,
        ]);
    }

    /**
     * Mettre à jour la préférence de méthode de récupération
     */
    public function updateRecoveryMethod(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'preference_recovery_method' => ['required', 'in:email,sms'],
        ]);

        // Vérifier si SMS est choisi mais pas de téléphone
        if ($validated['preference_recovery_method'] === 'sms' && !$user->telephone) {
            return back()->withErrors([
                'preference_recovery_method' => 'Vous devez d\'abord ajouter un numéro de téléphone dans vos paramètres pour utiliser la récupération par SMS.',
            ]);
        }

        $user->update($validated);

        SecurityLog::log(
            $user->id,
            'recovery_method_updated',
            $request->ip(),
            $request->userAgent(),
            null,
            ['method' => $validated['preference_recovery_method']],
            'low',
            false
        );

        return back()->with('success', 'Votre préférence de méthode de récupération a été mise à jour.');
    }

    /**
     * Activer ou désactiver l'A2F
     */
    public function updateA2F(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'a2f_enabled' => ['required', 'boolean'],
            'a2f_method' => ['required_if:a2f_enabled,1', 'in:email,sms'],
        ]);

        // Vérifier si SMS est choisi mais pas de téléphone
        if ($validated['a2f_enabled'] && ($validated['a2f_method'] ?? 'email') === 'sms' && !$user->telephone) {
            return back()->withErrors([
                'a2f_method' => 'Vous devez d\'abord ajouter un numéro de téléphone dans vos paramètres pour utiliser l\'A2F par SMS.',
            ]);
        }

        $user->update([
            'a2f_enabled' => $validated['a2f_enabled'],
            'a2f_method' => $validated['a2f_enabled'] ? ($validated['a2f_method'] ?? 'email') : 'email',
        ]);

        SecurityLog::log(
            $user->id,
            $validated['a2f_enabled'] ? 'a2f_enabled' : 'a2f_disabled',
            $request->ip(),
            $request->userAgent(),
            null,
            ['method' => $validated['a2f_method'] ?? 'email'],
            'medium',
            false
        );

        $message = $validated['a2f_enabled'] 
            ? 'L\'authentification à deux facteurs a été activée. Vous devrez saisir un code à chaque connexion.'
            : 'L\'authentification à deux facteurs a été désactivée.';

        return back()->with('success', $message);
    }
}
