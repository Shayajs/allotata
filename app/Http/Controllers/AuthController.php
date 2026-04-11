<?php

namespace App\Http\Controllers;

use App\Models\AccountLockout;
use App\Models\LoginAttempt;
use App\Models\SecurityLog;
use App\Models\User;
use App\Services\SecurityService;
use App\Support\PublicAgendaReturnUrl;
use Illuminate\Http\RedirectResponse;
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
            'public_agenda_return' => PublicAgendaReturnUrl::normalize($request->query('return')),
        ]);
    }

    /**
     * Traiter l'inscription (wizard multi-étapes)
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            // Étape 1 : Informations personnelles
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'date_naissance' => ['required', 'date', 'before:today'],
            'telephone' => ['required', 'string', 'max:20'],
            'adresse' => ['required', 'string', 'max:255'],
            'ville' => ['required', 'string', 'max:255'],
            'code_postal' => ['required', 'string', 'max:10'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'invitation_token' => ['nullable', 'string'],

            // Étape 2 : Profil
            'genre' => ['nullable', 'in:homme,femme,non_precise'],
            'source_inscription' => ['nullable', 'in:google,bouche_a_oreille,reseaux_sociaux,publicite,parrainage,autre'],
            'code_parrainage' => ['nullable', 'string', 'max:10'],

            // Étape 3 : Préférences de notifications
            'notifications_reservations' => ['nullable'],
            'notifications_paiements' => ['nullable'],
            'notifications_messages' => ['nullable'],
            'notifications_rappels' => ['nullable'],
            'notifications_promotions' => ['nullable'],
            'notifications_mises_a_jour' => ['nullable'],

            // Étape 4 : CGU / CGV / Confidentialité
            'cgu_accepted' => ['required', 'accepted'],
            'cgv_accepted' => ['required', 'accepted'],
            'confidentialite_accepted' => ['required', 'accepted'],
            'return' => ['nullable', 'string', 'max:2048'],
        ]);

        // Si une invitation est fournie, vérifier qu'elle correspond à l'email
        if ($request->filled('invitation_token')) {
            $invitation = \App\Models\EntrepriseInvitation::where('token', $request->invitation_token)
                ->where('email', $validated['email'])
                ->where('statut', 'en_attente_compte')
                ->first();

            if (! $invitation) {
                return back()->withErrors(['email' => 'Cette invitation n\'est pas valide pour cet email.'])
                    ->withInput();
            }
        }

        // Construire le nom complet pour la compatibilité (name = prénom + nom de famille)
        $fullName = trim($validated['name']).' '.trim($validated['surname']);

        // Résoudre le parrain si un code de parrainage est fourni
        $parrainId = null;
        if (! empty($validated['code_parrainage'])) {
            $parrain = User::where('code_parrain', strtoupper($validated['code_parrainage']))->first();
            if ($parrain) {
                $parrainId = $parrain->id;
            }
        }

        // Créer un membre (par défaut client uniquement)
        $user = User::create([
            'name' => $fullName,
            'surname' => $validated['surname'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'est_client' => true,
            'est_gerant' => false,
            'email_verified_at' => null,
            // Informations personnelles
            'date_naissance' => $validated['date_naissance'],
            'telephone' => $validated['telephone'],
            'adresse' => $validated['adresse'],
            'ville' => $validated['ville'],
            'code_postal' => $validated['code_postal'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            // Profil
            'genre' => $validated['genre'] ?? 'non_precise',
            'source_inscription' => $validated['source_inscription'] ?? null,
            'code_parrain' => User::generateCodeParrain(),
            'parrain_id' => $parrainId,
            // Acceptation CGU / CGV / Confidentialité
            'cgu_accepted_at' => now(),
            'cgv_accepted_at' => now(),
            'confidentialite_accepted_at' => now(),
            // Préférences de notifications (checkbox non cochée = absent de la requête = false)
            'notifications_reservations' => $request->has('notifications_reservations'),
            'notifications_paiements' => $request->has('notifications_paiements'),
            'notifications_messages' => $request->has('notifications_messages'),
            'notifications_rappels' => $request->has('notifications_rappels'),
            'notifications_promotions' => $request->has('notifications_promotions'),
            'notifications_mises_a_jour' => $request->has('notifications_mises_a_jour'),
        ]);

        // Rattacher la push subscription stockée en session (si l'utilisateur a accepté les push)
        $pendingPush = $request->session()->get('pending_push_subscription');
        if ($pendingPush) {
            try {
                \App\Models\PushSubscription::create([
                    'user_id' => $user->id,
                    'endpoint' => $pendingPush['endpoint'],
                    'p256dh_key' => $pendingPush['p256dh_key'],
                    'auth_token' => $pendingPush['auth_token'],
                    'content_encoding' => $pendingPush['content_encoding'] ?? 'aesgcm',
                ]);
            } catch (\Exception $e) {
                \Log::warning('Erreur lors du rattachement de la push subscription : '.$e->getMessage());
            }
            $request->session()->forget('pending_push_subscription');
        }

        // Générer un hash de vérification
        $emailVerification = \App\Models\EmailVerification::generateHashForUser($user->id);

        // Envoyer l'email de vérification
        try {
            $user->notify(new \App\Notifications\EmailVerificationNotification($emailVerification));
        } catch (\Exception $e) {
            \Log::error("Erreur lors de l'envoi de l'email de vérification : ".$e->getMessage());
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
        $redirectResponse = redirect()->route('verification.required')
            ->with('status', 'Votre compte a été créé avec succès ! Veuillez vérifier votre email pour accéder à votre compte.');

        return $this->withAgendaPostVerifyCookie($redirectResponse, $request->input('return'));
    }

    /**
     * Afficher le formulaire de connexion
     */
    public function showSignin(Request $request)
    {
        if ($request->filled('return')) {
            $normalized = PublicAgendaReturnUrl::normalize($request->query('return'));
            if ($normalized) {
                $request->session()->put('url.intended', $normalized);
            }
        }

        $invitationToken = $request->session()->get('invitation_token');

        return view('auth.signin', [
            'invitation_token' => $invitationToken,
            'public_agenda_return' => PublicAgendaReturnUrl::normalize($request->query('return')),
        ]);
    }

    /**
     * Traiter la connexion
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'return' => ['nullable', 'string', 'max:2048'],
        ]);

        if (! empty($validated['return'])) {
            $normalizedReturn = PublicAgendaReturnUrl::normalize($validated['return']);
            if ($normalizedReturn) {
                $request->session()->put('url.intended', $normalizedReturn);
            }
        }

        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

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
                    'Tentative de connexion sur un compte interdit'
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
                    'Tentative de connexion sur un compte supprimé'
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
                    'Tentative de connexion alors que le compte est verrouillé'
                );

                return back()->withErrors([
                    'email' => "Votre compte est temporairement verrouillé après plusieurs tentatives échouées. Veuillez réessayer dans {$remainingMinutes} minute(s) ou réinitialiser votre mot de passe.",
                ])->onlyInput('email');
            }
        }

        // Tentative de connexion
        // Note: On gère le cas où le CookieJar n'est pas disponible
        $remember = $request->boolean('remember');
        $attemptSucceeded = false;
        try {
            $attemptSucceeded = $user && Auth::attempt($credentials, $remember);
        } catch (\RuntimeException $e) {
            // Si le CookieJar n'est pas disponible, réessayer sans remember me
            if (str_contains($e->getMessage(), 'Cookie jar has not been set')) {
                $attemptSucceeded = $user && Auth::attempt($credentials, false);
            } else {
                throw $e;
            }
        }

        if ($attemptSucceeded) {
            // Vérifier si l'email est vérifié
            if (! $user->hasVerifiedEmail()) {
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
                    'Tentative de connexion avec email non vérifié'
                );

                // Stocker l'email en session pour le sas (AVANT de déconnecter)
                // Le sas enverra automatiquement l'email de vérification
                $request->session()->put('pending_verification_email', $user->email);
                $agendaReturnCandidate = $request->session()->get('url.intended');

                // Déconnecter et rediriger vers le sas
                $this->safeLogout($request);
                $request->session()->regenerateToken(); // Régénérer le token CSRF mais garder les données de session

                return $this->withAgendaPostVerifyCookie(
                    redirect()->route('verification.required')
                        ->with('status', 'Votre email n\'a pas encore été vérifié. Un email de vérification va vous être envoyé.'),
                    $agendaReturnCandidate
                );
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
                    $this->safeLogout($request);
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
                        'Google 2FA (TOTP) requis pour la connexion'
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
                    $this->safeLogout($request);
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
                        'A2F requis pour la connexion - nouvelle IP ou périphérique détecté'
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

            // Mettre à jour la date de dernière connexion
            $user->update(['derniere_connexion_at' => now()]);

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
                    'Tentative de connexion avec un ancien mot de passe'
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
            $errorMessage = 'Vous avez utilisé un ancien mot de passe. Veuillez utiliser votre mot de passe actuel. Si vous avez oublié votre mot de passe, utilisez la fonction de réinitialisation.';
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

        $this->safeLogout($request);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Déconnexion robuste qui gère le cas où le CookieJar n'est pas disponible
     */
    private function safeLogout(Request $request): void
    {
        try {
            Auth::logout();
        } catch (\RuntimeException $e) {
            // Si le CookieJar n'est pas disponible, forcer la déconnexion via session
            if (str_contains($e->getMessage(), 'Cookie jar has not been set')) {
                // Vider manuellement l'utilisateur de la session
                $request->session()->forget('login_web_'.sha1(Auth::getDefaultDriver()));
            } else {
                throw $e;
            }
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  Popup Auth (IdP style Google)
    // ═══════════════════════════════════════════════════════════

    /**
     * Afficher le popup de connexion (layout minimal).
     */
    public function showPopup(Request $request)
    {
        $mode = $request->query('mode', 'login'); // login | register

        return view('auth.popup', ['mode' => $mode]);
    }

    /**
     * Traiter le login depuis le popup.
     * Retourne du JSON pour que le popup puisse communiquer avec le parent via postMessage.
     */
    public function loginPopup(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();
        $user = User::where('email', $credentials['email'])->first();

        // Logger la tentative
        $loginAttempt = LoginAttempt::create([
            'email' => $credentials['email'],
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'success' => false,
            'user_id' => $user?->id,
            'attempted_at' => now(),
        ]);

        // Vérifications : compte interdit/supprimé/verrouillé
        if ($user) {
            if ($user->isInterdit() || $user->isSupprime()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Ce compte n\'est pas accessible.',
                ], 403);
            }

            $lockout = AccountLockout::firstOrCreate(
                ['user_id' => $user->id],
                ['failed_attempts' => 0, 'is_locked' => false]
            );

            if ($lockout->isCurrentlyLocked()) {
                $mins = now()->diffInMinutes($lockout->locked_until, false);

                return response()->json([
                    'success' => false,
                    'error' => "Compte temporairement verrouillé. Réessayez dans {$mins} minute(s).",
                ], 429);
            }
        }

        // Tentative d'auth
        $remember = $request->boolean('remember');
        $attemptSucceeded = false;
        try {
            $attemptSucceeded = $user && Auth::attempt($credentials, $remember);
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'Cookie jar has not been set')) {
                $attemptSucceeded = $user && Auth::attempt($credentials, false);
            } else {
                throw $e;
            }
        }

        if (! $attemptSucceeded) {
            // Incrémenter les tentatives échouées
            if ($user) {
                $lockout = $user->accountLockout;
                if ($lockout) {
                    $lockout->increment('failed_attempts');
                    $lockout->update(['last_failed_attempt' => now()]);
                    if ($lockout->failed_attempts >= 5) {
                        $lockout->update(['is_locked' => true, 'locked_until' => now()->addMinutes(15)]);
                    }
                }
            }

            $loginAttempt->update(['failure_reason' => $user ? 'invalid_credentials' : 'user_not_found']);

            return response()->json([
                'success' => false,
                'error' => 'Identifiants incorrects.',
            ], 401);
        }

        // Email non vérifié → signaler au popup
        if (! $user->hasVerifiedEmail()) {
            $request->session()->put('pending_verification_email', $user->email);
            $this->safeLogout($request);
            $request->session()->regenerateToken();

            return response()->json([
                'success' => false,
                'needs_verification' => true,
                'error' => 'Votre email n\'est pas encore vérifié. Un email vous a été envoyé.',
            ], 403);
        }

        // 2FA requis → signaler au popup (le popup redirigera vers /two-factor)
        $hasGoogle2fa = class_exists(\PragmaRX\Google2FA\Google2FA::class) && $user->hasGoogle2faEnabled();
        if ($user->a2f_enabled || $hasGoogle2fa) {
            $securityService = app(SecurityService::class);
            if ($hasGoogle2fa || ($user->a2f_enabled && $securityService->shouldRequireA2F($user, $ipAddress, $userAgent))) {
                $request->session()->put('two_factor_user_id', $user->id);
                $request->session()->put('two_factor_remember', $remember);
                $this->safeLogout($request);
                $request->session()->regenerateToken();

                return response()->json([
                    'success' => false,
                    'needs_2fa' => true,
                    'redirect' => route('two-factor.show'),
                ], 403);
            }
            $securityService->markDeviceAsTrusted($user, $ipAddress, $userAgent);
        }

        // Connexion réussie
        $loginAttempt->update(['success' => true, 'user_id' => $user->id]);
        if ($user->accountLockout) {
            $user->accountLockout->update(['failed_attempts' => 0, 'last_failed_attempt' => null]);
        }

        $securityService = app(SecurityService::class);
        $securityService->recordSuccessfulLogin($user, $ipAddress, $userAgent);
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'telephone' => $user->telephone ?? '',
            ],
        ]);
    }

    /**
     * Traiter l'inscription depuis le popup.
     */
    public function registerPopup(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'est_client' => true,
            'est_gerant' => false,
            'email_verified_at' => null,
        ]);

        // Vérification email
        $emailVerification = \App\Models\EmailVerification::generateHashForUser($user->id);
        try {
            $user->notify(new \App\Notifications\EmailVerificationNotification($emailVerification));
        } catch (\Exception $e) {
            \Log::error('Erreur envoi email vérification popup : '.$e->getMessage());
        }

        SecurityLog::log($user->id, 'account_created', $request->ip(), $request->userAgent(), null, ['source' => 'popup'], 'low', false);

        return response()->json([
            'success' => true,
            'needs_verification' => true,
            'message' => 'Compte créé ! Vérifiez votre email pour vous connecter.',
        ]);
    }

    /**
     * Pose un cookie pour retrouver l'agenda public après vérification de l'e-mail (nouvelle session).
     */
    protected function withAgendaPostVerifyCookie(RedirectResponse $response, ?string $urlCandidate): RedirectResponse
    {
        $normalized = PublicAgendaReturnUrl::normalize($urlCandidate);
        if ($normalized === null) {
            return $response;
        }

        return $response->withCookie(cookie(
            PublicAgendaReturnUrl::POST_VERIFY_COOKIE,
            $normalized,
            PublicAgendaReturnUrl::POST_VERIFY_COOKIE_MINUTES,
            '/',
            null,
            (bool) config('session.secure'),
            true,
            false,
            config('session.same_site', 'lax')
        ));
    }
}
