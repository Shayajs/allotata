<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\LoginAttempt;
use App\Models\AccountLockout;
use App\Models\SecurityLog;
use App\Services\SecurityService;
use App\Mail\WelcomeEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Afficher le formulaire d'inscription
     */
    public function showSignup(Request $request)
    {
        $invitationToken = $request->get('invitation');
        $invitation = null;
        
        if ($invitationToken) {
            $invitation = \App\Models\EntrepriseInvitation::where('token', $invitationToken)
                ->where('statut', 'en_attente_compte')
                ->with('entreprise')
                ->first();
        }

        return view('auth.signup', [
            'invitation' => $invitation,
        ]);
    }

    /**
     * Traiter l'inscription
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'invitation_token' => ['nullable', 'string'],
        ]);

        // Si une invitation est fournie, vérifier qu'elle correspond à l'email
        if ($request->filled('invitation_token')) {
            $invitation = \App\Models\EntrepriseInvitation::where('token', $request->invitation_token)
                ->where('email', $validated['email'])
                ->where('statut', 'en_attente_compte')
                ->first();

            if (!$invitation) {
                return back()->withErrors(['email' => 'Cette invitation n\'est pas valide pour cet email.'])
                    ->withInput();
            }
        }

        // Construire le nom complet pour la compatibilité (name = prénom + nom de famille)
        $fullName = trim($validated['name']);
        if (!empty($validated['surname'])) {
            $fullName = trim($validated['name']) . ' ' . trim($validated['surname']);
        }

        // Créer un membre (par défaut client uniquement)
        // email_verified_at reste null jusqu'à vérification
        $user = User::create([
            'name' => $fullName, // Nom complet pour la compatibilité
            'surname' => $validated['surname'] ?? null, // Nom de famille séparé
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'est_client' => true, // Par défaut, tous les membres sont clients
            'est_gerant' => false, // Ils deviendront gérants après avoir ajouté une entreprise
            'email_verified_at' => null, // Pas vérifié à la création
        ]);

        // Générer un hash de vérification
        $emailVerification = \App\Models\EmailVerification::generateHashForUser($user->id);

        // Envoyer l'email de vérification
        try {
            $user->notify(new \App\Notifications\EmailVerificationNotification($emailVerification));
        } catch (\Exception $e) {
            \Log::error("Erreur lors de l'envoi de l'email de vérification : " . $e->getMessage());
        }

        // Logger l'inscription
        \App\Models\SecurityLog::log(
            $user->id,
            'account_created',
            $request->ip(),
            $request->userAgent(),
            null,
            [],
            'low',
            false
        );

        // NE PAS connecter automatiquement - rediriger vers le sas de vérification
        // Les invitations seront gérées après la vérification de l'email (dans EmailVerificationController après vérification)

        // Rediriger vers le sas de vérification d'email
        return redirect()->route('verification.required')
            ->with('status', 'Votre compte a été créé avec succès ! Veuillez vérifier votre email pour accéder à votre compte.');
    }

    /**
     * Afficher le formulaire de connexion
     */
    public function showSignin(Request $request)
    {
        $invitationToken = $request->session()->get('invitation_token');
        
        return view('auth.signin', [
            'invitation_token' => $invitationToken,
        ]);
    }

    /**
     * Traiter la connexion
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();
        $email = $credentials['email'];

        // Chercher l'utilisateur
        $user = User::where('email', $email)->first();

        // Logger la tentative de connexion
        $loginAttempt = LoginAttempt::create([
            'email' => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'success' => false,
            'user_id' => $user?->id,
            'attempted_at' => now(),
        ]);

        // Si l'utilisateur existe, vérifier le blocage
        if ($user) {
            // Vérifier si le compte est interdit ou supprimé
            if ($user->isInterdit()) {
                $loginAttempt->update([
                    'failure_reason' => 'account_forbidden',
                ]);

                SecurityLog::log(
                    $user->id,
                    'login_blocked',
                    $ipAddress,
                    $userAgent,
                    null,
                    ['reason' => 'account_forbidden'],
                    'high',
                    true,
                    "Tentative de connexion sur un compte interdit"
                );

                return back()->withErrors([
                    'email' => "Votre compte a été désactivé. Veuillez contacter l'administrateur pour plus d'informations.",
                ])->onlyInput('email');
            }

            if ($user->isSupprime()) {
                $loginAttempt->update([
                    'failure_reason' => 'account_deleted',
                ]);

                SecurityLog::log(
                    $user->id,
                    'login_blocked',
                    $ipAddress,
                    $userAgent,
                    null,
                    ['reason' => 'account_deleted'],
                    'high',
                    true,
                    "Tentative de connexion sur un compte supprimé"
                );

                return back()->withErrors([
                    'email' => "Ce compte n'existe plus ou a été archivé.",
                ])->onlyInput('email');
            }

            $lockout = AccountLockout::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'failed_attempts' => 0,
                    'is_locked' => false,
                ]
            );

            // Vérifier si le compte est verrouillé
            if ($lockout->isCurrentlyLocked()) {
                $remainingMinutes = now()->diffInMinutes($lockout->locked_until, false);
                
                $loginAttempt->update([
                    'failure_reason' => 'account_locked',
                ]);

                SecurityLog::log(
                    $user->id,
                    'login_blocked',
                    $ipAddress,
                    $userAgent,
                    null,
                    ['locked_until' => $lockout->locked_until],
                    'high',
                    true,
                    "Tentative de connexion alors que le compte est verrouillé"
                );

                return back()->withErrors([
                    'email' => "Votre compte est temporairement verrouillé après plusieurs tentatives échouées. Veuillez réessayer dans {$remainingMinutes} minute(s) ou réinitialiser votre mot de passe.",
                ])->onlyInput('email');
            }
        }

        // Tentative de connexion
        if ($user && Auth::attempt($credentials, $request->boolean('remember'))) {
            // Vérifier si l'email est vérifié
            if (!$user->hasVerifiedEmail()) {
                // Logger la tentative
                $loginAttempt->update([
                    'success' => false,
                    'failure_reason' => 'email_not_verified',
                ]);

                SecurityLog::log(
                    $user->id,
                    'login_blocked_unverified',
                    $ipAddress,
                    $userAgent,
                    null,
                    [],
                    'medium',
                    false,
                    "Tentative de connexion avec email non vérifié"
                );

                // Stocker l'email en session pour le sas (AVANT de déconnecter)
                // Le sas enverra automatiquement l'email de vérification
                $request->session()->put('pending_verification_email', $user->email);
                
                // Déconnecter et rediriger vers le sas
                Auth::logout();
                $request->session()->regenerateToken(); // Régénérer le token CSRF mais garder les données de session

                return redirect()->route('verification.required')
                    ->with('status', 'Votre email n\'a pas encore été vérifié. Un email de vérification va vous être envoyé.');
            }

            // Vérifier si l'utilisateur a l'A2F activé (email/SMS) ou Google 2FA (TOTP)
            $hasGoogle2fa = class_exists(\PragmaRX\Google2FA\Google2FA::class) && $user->hasGoogle2faEnabled();
            if ($user->a2f_enabled || $hasGoogle2fa) {
                $securityService = app(SecurityService::class);
                
                // Si Google 2FA est activé, toujours demander le code TOTP
                if ($hasGoogle2fa) {
                    // Stocker l'ID utilisateur et le "remember" en session pour l'A2F
                    $request->session()->put('two_factor_user_id', $user->id);
                    $request->session()->put('two_factor_remember', $request->boolean('remember'));
                    
                    // Déconnecter temporairement (sera reconnecté après vérification A2F)
                    Auth::logout();
                    $request->session()->regenerateToken();
                    
                    // Logger la tentative
                    $loginAttempt->update([
                        'success' => false,
                        'failure_reason' => 'google2fa_required',
                    ]);

                    SecurityLog::log(
                        $user->id,
                        'google2fa_required',
                        $ipAddress,
                        $userAgent,
                        null,
                        [],
                        'medium',
                        false,
                        "Google 2FA (TOTP) requis pour la connexion"
                    );

                    // Rediriger vers la page A2F (qui affichera le formulaire TOTP)
                    return redirect()->route('two-factor.show');
                }
                
                // Vérifier si l'A2F email/SMS est nécessaire (nouvelle IP, nouveau périphérique, ou changement de pays)
                if ($user->a2f_enabled && $securityService->shouldRequireA2F($user, $ipAddress, $userAgent)) {
                    // Stocker l'ID utilisateur et le "remember" en session pour l'A2F
                    $request->session()->put('two_factor_user_id', $user->id);
                    $request->session()->put('two_factor_remember', $request->boolean('remember'));
                    
                    // Déconnecter temporairement (sera reconnecté après vérification A2F)
                    Auth::logout();
                    $request->session()->regenerateToken();
                    
                    // Logger la tentative
                    $loginAttempt->update([
                        'success' => false,
                        'failure_reason' => 'a2f_required',
                    ]);

                    SecurityLog::log(
                        $user->id,
                        'a2f_required',
                        $ipAddress,
                        $userAgent,
                        null,
                        [],
                        'medium',
                        false,
                        "A2F requis pour la connexion - nouvelle IP ou périphérique détecté"
                    );

                    // Rediriger vers la page A2F
                    return redirect()->route('two-factor.show');
                }
                
                // A2F activé mais IP/périphérique connus → Pas besoin de code
                // Marquer le périphérique comme approuvé pour cette connexion
                $securityService->markDeviceAsTrusted($user, $ipAddress, $userAgent);
            }

            // Connexion réussie et email vérifié (sans A2F)
            $loginAttempt->update([
                'success' => true,
                'user_id' => $user->id,
            ]);

            // Réinitialiser les tentatives échouées
            if ($user->accountLockout) {
                $user->accountLockout->update([
                    'failed_attempts' => 0,
                    'last_failed_attempt' => null,
                ]);
            }

            // Enregistrer la connexion réussie dans les logs de sécurité
            $securityService = app(SecurityService::class);
            $securityService->recordSuccessfulLogin($user, $ipAddress, $userAgent);

            $request->session()->regenerate();
            
            // Si une invitation est en attente, rediriger vers la page d'invitation
            if ($request->session()->has('invitation_token')) {
                $token = $request->session()->get('invitation_token');
                $request->session()->forget('invitation_token');
                return redirect()->route('invitations.show', $token);
            }
            
            return redirect()->intended(route('dashboard'));
        }

        // Connexion échouée - Vérifier si c'est un ancien mot de passe
        $failureReason = $user ? 'invalid_credentials' : 'user_not_found';
        $isOldPassword = false;
        
        if ($user && $request->filled('password')) {
            // Vérifier si le mot de passe correspond à un ancien mot de passe
            $isOldPassword = \App\Models\UserSecurityHistory::checkOldPassword($user, $request->password);
            
            if ($isOldPassword) {
                $failureReason = 'old_password_used';
                
                // Logger l'événement de sécurité suspect
                SecurityLog::log(
                    $user->id,
                    'old_password_attempt',
                    $ipAddress,
                    $userAgent,
                    null,
                    [],
                    'high',
                    true,
                    "Tentative de connexion avec un ancien mot de passe"
                );
            }
        }
        
        $loginAttempt->update([
            'failure_reason' => $failureReason,
        ]);

        // Si l'utilisateur existe, incrémenter les tentatives échouées
        if ($user) {
            $lockout = AccountLockout::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'failed_attempts' => 0,
                    'is_locked' => false,
                ]
            );

            $lockout->incrementFailedAttempts($ipAddress);

            // Logger l'événement de sécurité
            SecurityLog::log(
                $user->id,
                'login_failed',
                $ipAddress,
                $userAgent,
                null,
                ['failed_attempts' => $lockout->failed_attempts],
                $lockout->failed_attempts >= 5 ? 'high' : 'medium',
                $lockout->failed_attempts >= 5,
                $lockout->failed_attempts >= 5 ? 'Compte verrouillé après 5 tentatives échouées' : 'Tentative de connexion échouée'
            );
        }

        $errorMessage = 'Les identifiants fournis ne correspondent à aucun compte.';
        
        // Message spécifique pour les anciens mots de passe
        if ($isOldPassword) {
            $errorMessage = "Vous avez utilisé un ancien mot de passe. Veuillez utiliser votre mot de passe actuel. Si vous avez oublié votre mot de passe, utilisez la fonction de réinitialisation.";
        } elseif ($user && $lockout && $lockout->isCurrentlyLocked()) {
            $remainingMinutes = now()->diffInMinutes($lockout->locked_until, false);
            $errorMessage = "Votre compte est temporairement verrouillé après plusieurs tentatives échouées. Veuillez réessayer dans {$remainingMinutes} minute(s) ou réinitialiser votre mot de passe.";
        }

        return back()->withErrors([
            'email' => $errorMessage,
        ])->onlyInput('email');
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        
        if ($user) {
            // Logger la déconnexion
            SecurityLog::log(
                $user->id,
                'logout',
                $request->ip(),
                $request->userAgent(),
                null,
                [],
                'low',
                false
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
