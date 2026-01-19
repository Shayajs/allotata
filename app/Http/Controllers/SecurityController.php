<?php

namespace App\Http\Controllers;

use App\Models\LoginAttempt;
use App\Models\SecurityLog;
use App\Models\UserIpHistory;
use App\Models\AccountLockout;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
// Imports conditionnels - vérifiés au runtime

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

    /**
     * Générer le secret Google 2FA et afficher le QR code
     */
    public function generateGoogle2fa(Request $request)
    {
        // Vérifier que le package Google2FA est installé
        if (!class_exists(\PragmaRX\Google2FA\Google2FA::class)) {
            return response()->json([
                'error' => 'Le package Google2FA n\'est pas installé. Veuillez exécuter: composer require pragmarx/google2fa-laravel bacon/bacon-qr-code'
            ], 500);
        }

        $user = Auth::user();

        // Vérifier si l'A2F TOTP est désactivé globalement par l'admin
        if (Setting::get('google2fa_disabled', false)) {
            return back()->withErrors([
                'google2fa' => 'L\'authentification à deux facteurs TOTP est désactivée par l\'administrateur.',
            ]);
        }

        // Générer un nouveau secret si nécessaire
        if (!$user->google2fa_secret) {
            $user->generateGoogle2faSecret();
        }

        // Utiliser le service container pour résoudre Google2FA
        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
        $secret = decrypt($user->google2fa_secret);
        
        // Créer l'URL du QR code
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name', 'Allotata'),
            $user->email,
            $secret
        );

        // Générer le QR code via une API externe (compatible avec toutes les versions PHP)
        // On utilise une API publique pour générer le QR code
        $qrCodeDataUri = $this->generateQRCodeDataUri($qrCodeUrl);

        SecurityLog::log(
            $user->id,
            'google2fa_secret_generated',
            $request->ip(),
            $request->userAgent(),
            null,
            [],
            'medium',
            false
        );

        return response()->json([
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
            'qr_code_image' => $qrCodeDataUri, // Image en base64 data URI
        ]);
    }

    /**
     * Activer Google 2FA après vérification du code
     */
    public function enableGoogle2fa(Request $request)
    {
        // Vérifier que le package Google2FA est installé
        if (!class_exists(\PragmaRX\Google2FA\Google2FA::class)) {
            return back()->withErrors([
                'google2fa' => 'Le package Google2FA n\'est pas installé. Veuillez exécuter: composer require pragmarx/google2fa-laravel bacon/bacon-qr-code'
            ]);
        }

        $user = Auth::user();

        // Vérifier si l'A2F TOTP est désactivé globalement par l'admin
        if (Setting::get('google2fa_disabled', false)) {
            return back()->withErrors([
                'google2fa' => 'L\'authentification à deux facteurs TOTP est désactivée par l\'administrateur.',
            ]);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        // Vérifier le code
        if (!$user->verifyGoogle2faCode($validated['code'])) {
            SecurityLog::log(
                $user->id,
                'google2fa_activation_failed',
                $request->ip(),
                $request->userAgent(),
                null,
                ['reason' => 'invalid_code'],
                'high',
                false
            );

            return back()->withErrors([
                'code' => 'Le code fourni est invalide.',
            ]);
        }

        // Générer les codes de récupération
        $recoveryCodes = $user->generateRecoveryCodes(8);
        
        // Activer Google 2FA
        $user->enableGoogle2fa();

        SecurityLog::log(
            $user->id,
            'google2fa_enabled',
            $request->ip(),
            $request->userAgent(),
            null,
            [],
            'medium',
            false
        );

        return back()->with([
            'success' => 'L\'authentification à deux facteurs TOTP a été activée avec succès.',
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    /**
     * Désactiver Google 2FA
     */
    public function disableGoogle2fa(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        // Vérifier le mot de passe
        if (!Auth::guard()->validate(['email' => $user->email, 'password' => $validated['password']])) {
            SecurityLog::log(
                $user->id,
                'google2fa_disable_failed',
                $request->ip(),
                $request->userAgent(),
                null,
                ['reason' => 'invalid_password'],
                'high',
                false
            );

            return back()->withErrors([
                'password' => 'Le mot de passe est incorrect.',
            ]);
        }

        // Désactiver Google 2FA
        $user->disableGoogle2fa();

        SecurityLog::log(
            $user->id,
            'google2fa_disabled',
            $request->ip(),
            $request->userAgent(),
            null,
            [],
            'medium',
            false
        );

        return back()->with('success', 'L\'authentification à deux facteurs TOTP a été désactivée.');
    }

    /**
     * Régénérer les codes de récupération
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasGoogle2faEnabled()) {
            return back()->withErrors([
                'google2fa' => 'L\'authentification à deux facteurs TOTP n\'est pas activée.',
            ]);
        }

        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        // Vérifier le mot de passe
        if (!Auth::guard()->validate(['email' => $user->email, 'password' => $validated['password']])) {
            return back()->withErrors([
                'password' => 'Le mot de passe est incorrect.',
            ]);
        }

        // Générer de nouveaux codes de récupération
        $recoveryCodes = $user->generateRecoveryCodes(8);

        SecurityLog::log(
            $user->id,
            'google2fa_recovery_codes_regenerated',
            $request->ip(),
            $request->userAgent(),
            null,
            [],
            'medium',
            false
        );

        return back()->with([
            'success' => 'De nouveaux codes de récupération ont été générés.',
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    /**
     * Générer un QR code via une API externe et retourner en data URI
     * Compatible avec PHP 8.5+
     */
    private function generateQRCodeDataUri(string $url): string
    {
        // Utiliser une API publique pour générer le QR code
        // Option 1: API QR Server (gratuite, pas de clé API requise)
        $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=' . urlencode($url);
        
        try {
            // Récupérer l'image du QR code
            $imageData = @file_get_contents($qrApiUrl);
            
            if ($imageData === false) {
                // Fallback: Utiliser Google Charts API (dépréciée mais toujours fonctionnelle)
                $qrApiUrl = 'https://chart.googleapis.com/chart?chs=400x400&cht=qr&chl=' . urlencode($url);
                $imageData = @file_get_contents($qrApiUrl);
            }
            
            if ($imageData !== false) {
                // Convertir en data URI (base64)
                $base64 = base64_encode($imageData);
                return 'data:image/png;base64,' . $base64;
            }
        } catch (\Exception $e) {
            \Log::warning('Erreur lors de la génération du QR code via API: ' . $e->getMessage());
        }
        
        // Si l'API échoue, retourner une image SVG basique générée côté client
        // Le JavaScript pourra générer le QR code via une bibliothèque côté client
        return '';
    }
}
