<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TwoFactorCode;
use App\Models\SecurityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorController extends Controller
{
    /**
     * Afficher la page de demande du code A2F
     */
    public function show(Request $request)
    {
        $userId = $request->session()->get('two_factor_user_id');
        
        if (!$userId) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Session expirée. Veuillez vous reconnecter.']);
        }

        $user = User::find($userId);
        
        $hasGoogle2fa = class_exists(\PragmaRX\Google2FA\Google2FA::class) && $user->hasGoogle2faEnabled();
        if (!$user || (!$user->a2f_enabled && !$hasGoogle2fa)) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Erreur de session.']);
        }

        // Si l'utilisateur a Google 2FA activé, on ne peut pas envoyer de code email/SMS
        // Il doit utiliser son application d'authentification
        if ($hasGoogle2fa) {
            return view('auth.two-factor.request', [
                'user' => $user,
                'method' => 'totp',
                'hasGoogle2fa' => true,
            ]);
        }

        // Envoyer automatiquement un code à l'arrivée sur la page (si pas déjà envoyé récemment)
        $lastCodeSent = $request->session()->get('last_a2f_code_sent_at');
        $shouldSendCode = !$lastCodeSent || now()->diffInMinutes($lastCodeSent) > 1; // Au moins 1 minute entre chaque envoi

        if ($shouldSendCode) {
            $method = $user->a2f_method ?? 'email';
            
            // Vérifier si SMS est demandé mais pas de téléphone
            if ($method === 'sms' && !$user->telephone) {
                $method = 'email';
            }

            // Générer un code
            $code = TwoFactorCode::generateCode();

            // Supprimer les codes existants non utilisés
            TwoFactorCode::where('user_id', $user->id)
                ->where('is_used', false)
                ->where('expires_at', '>', now())
                ->delete();

            // Créer le code A2F
            $twoFactorCode = TwoFactorCode::create([
                'user_id' => $user->id,
                'code' => $code,
                'method' => $method,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'expires_at' => now()->addMinutes(10),
            ]);

            // Envoyer le code
            try {
                $user->notify(new \App\Notifications\TwoFactorCodeNotification($twoFactorCode));
                $request->session()->put('last_a2f_code_sent_at', now());
                
                SecurityLog::log(
                    $user->id,
                    'a2f_code_sent_auto',
                    $request->ip(),
                    $request->userAgent(),
                    null,
                    ['method' => $method],
                    'medium',
                    false
                );
            } catch (\Exception $e) {
                \Log::error("Erreur lors de l'envoi automatique du code A2F : " . $e->getMessage());
            }
        }

        return view('auth.two-factor.request', [
            'user' => $user,
            'method' => $user->a2f_method ?? 'email',
            'hasGoogle2fa' => false,
        ]);
    }

    /**
     * Demander l'envoi d'un code A2F (bouton pour renvoyer)
     */
    public function requestCode(Request $request)
    {
        $userId = $request->session()->get('two_factor_user_id');
        $method = $request->input('method', 'email');
        
        if (!$userId) {
            return back()->withErrors(['code' => 'Session expirée.']);
        }

        $user = User::find($userId);
        
        if (!$user || !$user->a2f_enabled) {
            return back()->withErrors(['code' => 'Utilisateur invalide.']);
        }

        // Vérifier si SMS est demandé mais pas de téléphone
        if ($method === 'sms' && !$user->telephone) {
            return back()->withErrors(['code' => 'Vous devez avoir un numéro de téléphone configuré pour utiliser le SMS.']);
        }

        // Générer un code à 6 chiffres
        $code = TwoFactorCode::generateCode();

        // Supprimer les codes existants non utilisés pour cet utilisateur
        TwoFactorCode::where('user_id', $user->id)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->delete();

        // Créer le code A2F
        $twoFactorCode = TwoFactorCode::create([
            'user_id' => $user->id,
            'code' => $code,
            'method' => $method,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'expires_at' => now()->addMinutes(10), // Code valide 10 minutes
        ]);

        // Envoyer le code via email ou SMS
        try {
            $user->notify(new \App\Notifications\TwoFactorCodeNotification($twoFactorCode));
            
            SecurityLog::log(
                $user->id,
                'a2f_code_sent',
                $request->ip(),
                $request->userAgent(),
                null,
                ['method' => $method],
                'medium',
                false
            );
        } catch (\Exception $e) {
            \Log::error("Erreur lors de l'envoi du code A2F : " . $e->getMessage());
            return back()->withErrors(['code' => 'Erreur lors de l\'envoi du code. Veuillez réessayer.']);
        }

        return back()->with('status', 'Un code de vérification a été envoyé par ' . ($method === 'sms' ? 'SMS' : 'email') . '.');
    }

    /**
     * Vérifier le code A2F et finaliser la connexion
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'min:6', 'max:8'], // 6 pour TOTP, 8 pour codes de récupération
        ]);

        $userId = $request->session()->get('two_factor_user_id');
        $code = $request->code;

        if (!$userId) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Session expirée. Veuillez vous reconnecter.']);
        }

        $user = User::find($userId);
        
        $hasGoogle2fa = class_exists(\PragmaRX\Google2FA\Google2FA::class) && $user->hasGoogle2faEnabled();
        if (!$user || (!$user->a2f_enabled && !$hasGoogle2fa)) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Erreur de session.']);
        }

        $codeValid = false;
        $twoFactorCode = null;

        // Vérifier d'abord si l'utilisateur a Google 2FA activé (et si le package est installé)
        if ($hasGoogle2fa) {
            // Si le code fait 8 caractères, c'est probablement un code de récupération
            if (strlen($code) === 8) {
                // Essayer avec un code de récupération
                if ($user->verifyRecoveryCode(strtoupper($code), $request->ip(), $request->userAgent())) {
                    $codeValid = true;
                    SecurityLog::log(
                        $user->id,
                        'google2fa_recovery_code_used',
                        $request->ip(),
                        $request->userAgent(),
                        null,
                        [],
                        'medium',
                        false
                    );
                } else {
                    SecurityLog::log(
                        $user->id,
                        'google2fa_recovery_code_invalid',
                        $request->ip(),
                        $request->userAgent(),
                        null,
                        [],
                        'high',
                        true,
                        'Tentative de connexion avec un code de récupération invalide'
                    );

                    return back()->withErrors(['code' => 'Code de récupération invalide.']);
                }
            } else {
                // Vérifier le code TOTP (6 chiffres)
                if ($user->verifyGoogle2faCode($code)) {
                    $codeValid = true;
                    SecurityLog::log(
                        $user->id,
                        'google2fa_verified',
                        $request->ip(),
                        $request->userAgent(),
                        null,
                        [],
                        'low',
                        false
                    );
                } else {
                    // Essayer aussi avec un code de récupération au cas où l'utilisateur a tapé un code de récupération à 6 caractères (peu probable mais possible)
                    if ($user->verifyRecoveryCode(strtoupper($code), $request->ip(), $request->userAgent())) {
                        $codeValid = true;
                        SecurityLog::log(
                            $user->id,
                            'google2fa_recovery_code_used',
                            $request->ip(),
                            $request->userAgent(),
                            null,
                            [],
                            'medium',
                            false
                        );
                    } else {
                        SecurityLog::log(
                            $user->id,
                            'google2fa_code_invalid',
                            $request->ip(),
                            $request->userAgent(),
                            null,
                            [],
                            'high',
                            true,
                            'Tentative de connexion avec un code TOTP invalide'
                        );

                        return back()->withErrors(['code' => 'Code TOTP ou code de récupération invalide.']);
                    }
                }
            }
        } else if ($user->a2f_enabled) {
            // Vérifier le code email/SMS
            $twoFactorCode = TwoFactorCode::where('user_id', $user->id)
                ->where('code', $code)
                ->where('is_used', false)
                ->where('expires_at', '>', now())
                ->latest()
                ->first();

            if ($twoFactorCode && $twoFactorCode->isValid()) {
                $codeValid = true;
                // Marquer le code comme utilisé
                $twoFactorCode->markAsUsed();
            } else {
                SecurityLog::log(
                    $user->id,
                    'a2f_code_invalid',
                    $request->ip(),
                    $request->userAgent(),
                    null,
                    [],
                    'high',
                    true,
                    'Tentative de connexion avec un code A2F invalide'
                );

                return back()->withErrors(['code' => 'Code invalide ou expiré.']);
            }
        } else {
            return redirect()->route('login')
                ->withErrors(['email' => 'Erreur de session.']);
        }

        // Marquer le périphérique/IP comme approuvés (trusted)
        $securityService = app(\App\Services\SecurityService::class);
        $securityService->markDeviceAsTrusted(
            $user,
            $request->ip(),
            $request->userAgent() ?? ''
        );

        // Logger la réussite
        SecurityLog::log(
            $user->id,
            'a2f_verified',
            $request->ip(),
            $request->userAgent(),
            null,
            [],
            'low',
            false
        );

        // Connecter l'utilisateur
        // Note: On vérifie si le remember me est possible (CookieJar disponible)
        $remember = $request->session()->get('two_factor_remember', false);
        try {
            Auth::login($user, $remember);
        } catch (\RuntimeException $e) {
            // Si le CookieJar n'est pas disponible, se connecter sans remember me
            if (str_contains($e->getMessage(), 'Cookie jar has not been set')) {
                Auth::login($user, false);
            } else {
                throw $e;
            }
        }

        // Nettoyer la session A2F
        $redirectUrl = $request->session()->get('a2f_redirect_url');
        $request->session()->forget([
            'two_factor_user_id',
            'two_factor_remember',
            'last_a2f_code_sent_at',
            'a2f_redirect_url'
        ]);

        // Marquer le périphérique comme vérifié pour cette session
        $sessionKey = 'trusted_device_' . md5($request->ip() . ($request->userAgent() ?? ''));
        $request->session()->put($sessionKey, true);

        // Logger la connexion réussie
        $loginAttempt = \App\Models\LoginAttempt::create([
            'email' => $user->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'success' => true,
            'user_id' => $user->id,
            'attempted_at' => now(),
        ]);

        // Réinitialiser les tentatives échouées
        if ($user->accountLockout) {
            $user->accountLockout->update([
                'failed_attempts' => 0,
                'last_failed_attempt' => null,
            ]);
        }

        // Enregistrer la connexion réussie dans les logs de sécurité (déjà fait plus haut avec markDeviceAsTrusted)
        $securityService->recordSuccessfulLogin($user, $request->ip(), $request->userAgent() ?? '');

        $request->session()->regenerate();

        // Si une invitation est en attente, rediriger vers la page d'invitation
        if ($request->session()->has('invitation_token')) {
            $token = $request->session()->get('invitation_token');
            $request->session()->forget('invitation_token');
            return redirect()->route('invitations.show', $token);
        }

        // Rediriger vers l'URL d'origine ou le dashboard
        if ($redirectUrl) {
            return redirect($redirectUrl)
                ->with('status', 'Vérification réussie !');
        }

        return redirect()->intended(route('dashboard'))
            ->with('status', 'Connexion réussie ! Bienvenue.');
    }
}
