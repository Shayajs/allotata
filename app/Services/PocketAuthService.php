<?php

namespace App\Services;

use App\Http\Controllers\NativeDeviceController;
use App\Models\AccountLockout;
use App\Models\ApiToken;
use App\Models\LoginAttempt;
use App\Models\SecurityLog;
use App\Models\TwoFactorCode;
use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PocketAuthService
{
    public function login(Request $request): array
    {
        $valide = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $ip = $request->ip();
        $ua = (string) $request->userAgent();
        $user = User::where('email', $valide['email'])->first();

        LoginAttempt::create([
            'email' => $valide['email'],
            'ip_address' => $ip,
            'user_agent' => $ua,
            'success' => false,
            'user_id' => $user?->id,
            'attempted_at' => now(),
        ]);

        if ($user && $user->isInterdit()) {
            return $this->echec('Ce compte a été désactivé.', 'compte_interdit', 403);
        }
        if ($user && $user->isSupprime()) {
            return $this->echec('Ce compte n’existe plus.', 'compte_supprime', 403);
        }

        if ($user) {
            $lockout = AccountLockout::firstOrCreate(
                ['user_id' => $user->id],
                ['failed_attempts' => 0, 'is_locked' => false]
            );
            if ($lockout->isCurrentlyLocked()) {
                $mins = max(1, (int) now()->diffInMinutes($lockout->locked_until, false));

                return $this->echec("Compte verrouillé. Réessayez dans {$mins} min.", 'compte_verrouille', 429);
            }
        }

        if (! $user || ! Hash::check($valide['password'], $user->password)) {
            if ($user) {
                $lockout = $user->accountLockout
                    ?? AccountLockout::firstOrCreate(
                        ['user_id' => $user->id],
                        ['failed_attempts' => 0, 'is_locked' => false]
                    );
                $lockout->incrementFailedAttempts($ip);
            }

            return $this->echec('Identifiants incorrects.', 'identifiants', 401);
        }

        if (! $user->hasVerifiedEmail()) {
            return $this->echec('Vérifiez votre e-mail avant de vous connecter.', 'email_non_verifie', 403);
        }

        $a2f = $this->besoinA2f($user, $ip, $ua);
        if ($a2f) {
            return $this->ouvrirA2f($user, $request, $a2f);
        }

        return $this->reussir($user, $request);
    }

    public function verifierA2f(Request $request): array
    {
        $valide = $request->validate([
            'challenge' => ['required', 'string', 'max:80'],
            'code' => ['required', 'string', 'max:20'],
        ]);

        $pending = Cache::get('pocket_2fa:'.$valide['challenge']);
        if (! is_array($pending) || empty($pending['user_id'])) {
            return $this->echec('Session expirée. Reconnectez-vous.', 'challenge_expire', 401);
        }

        $user = User::find($pending['user_id']);
        if (! $user) {
            return $this->echec('Session expirée. Reconnectez-vous.', 'challenge_expire', 401);
        }

        $code = trim($valide['code']);
        $ok = false;
        $hasGoogle = class_exists(\PragmaRX\Google2FA\Google2FA::class) && $user->hasGoogle2faEnabled();

        if ($hasGoogle) {
            $ok = $user->verifyGoogle2faCode($code)
                || $user->verifyRecoveryCode(strtoupper($code), $request->ip(), $request->userAgent());
        } elseif ($user->a2f_enabled) {
            $two = TwoFactorCode::where('user_id', $user->id)
                ->where('code', $code)
                ->where('is_used', false)
                ->where('expires_at', '>', now())
                ->latest()
                ->first();
            if ($two && $two->isValid()) {
                $two->markAsUsed();
                $ok = true;
            }
        }

        if (! $ok) {
            return $this->echec('Code invalide ou expiré.', 'code_invalide', 401);
        }

        Cache::forget('pocket_2fa:'.$valide['challenge']);
        app(SecurityService::class)->markDeviceAsTrusted($user, $request->ip(), (string) $request->userAgent());

        return $this->reussir($user, $request);
    }

    public function renvoyerA2f(Request $request): array
    {
        $valide = $request->validate([
            'challenge' => ['required', 'string', 'max:80'],
        ]);

        $pending = Cache::get('pocket_2fa:'.$valide['challenge']);
        if (! is_array($pending) || empty($pending['user_id'])) {
            return $this->echec('Session expirée. Reconnectez-vous.', 'challenge_expire', 401);
        }

        $user = User::find($pending['user_id']);
        if (! $user || ! $user->a2f_enabled) {
            return $this->echec('Impossible de renvoyer un code.', 'renvoi_impossible', 422);
        }

        $this->envoyerCodeA2f($user, $request);

        return [
            'ok' => true,
            'status' => 200,
            'code' => 'code_envoye',
            'message' => 'Un nouveau code a été envoyé.',
        ];
    }

    private function besoinA2f(User $user, string $ip, string $ua): ?string
    {
        $hasGoogle = class_exists(\PragmaRX\Google2FA\Google2FA::class) && $user->hasGoogle2faEnabled();
        if ($hasGoogle) {
            return 'totp';
        }
        if ($user->a2f_enabled && app(SecurityService::class)->shouldRequireA2F($user, $ip, $ua)) {
            $methode = $user->a2f_method ?? 'email';
            if ($methode === 'sms' && ! $user->telephone) {
                $methode = 'email';
            }

            return $methode;
        }

        return null;
    }

    private function ouvrirA2f(User $user, Request $request, string $methode): array
    {
        $challenge = Str::random(40);
        Cache::put('pocket_2fa:'.$challenge, ['user_id' => $user->id], now()->addMinutes(10));

        if ($methode !== 'totp') {
            $this->envoyerCodeA2f($user, $request);
        }

        return [
            'ok' => false,
            'status' => 403,
            'code' => 'a2f_requis',
            'message' => $methode === 'totp'
                ? 'Entrez le code de votre application d’authentification.'
                : 'Un code vient de vous être envoyé.',
            'challenge' => $challenge,
            'methode' => $methode,
        ];
    }

    private function envoyerCodeA2f(User $user, Request $request): void
    {
        $methode = $user->a2f_method ?? 'email';
        if ($methode === 'sms' && ! $user->telephone) {
            $methode = 'email';
        }

        $code = TwoFactorCode::generateCode();
        TwoFactorCode::where('user_id', $user->id)->where('is_used', false)->where('expires_at', '>', now())->delete();
        $modele = TwoFactorCode::create([
            'user_id' => $user->id,
            'code' => $code,
            'method' => $methode,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'expires_at' => now()->addMinutes(10),
        ]);

        try {
            $user->notify(new TwoFactorCodeNotification($modele));
        } catch (\Throwable $e) {
            \Log::error('Pocket A2F : '.$e->getMessage());
        }
    }

    private function reussir(User $user, Request $request): array
    {
        LoginAttempt::where('email', $user->email)->latest('id')->first()?->update([
            'success' => true,
            'user_id' => $user->id,
        ]);

        if ($user->accountLockout) {
            $user->accountLockout->update([
                'failed_attempts' => 0,
                'last_failed_attempt' => null,
            ]);
        }

        $user->update(['derniere_connexion_at' => now()]);
        app(SecurityService::class)->recordSuccessfulLogin($user, $request->ip(), (string) $request->userAgent());

        $user->apiTokens()->where('nom', NativeDeviceController::TOKEN_NAME)->delete();
        $jeton = ApiToken::creerPour($user, NativeDeviceController::TOKEN_NAME)['jeton'];

        SecurityLog::log(
            $user->id,
            'pocket_login',
            $request->ip(),
            $request->userAgent(),
            null,
            [],
            'low',
            false,
            'Connexion Pocket Android'
        );

        return [
            'ok' => true,
            'status' => 200,
            'jeton' => $jeton,
            'compte' => [
                'id' => $user->id,
                'nom' => $user->name,
                'email' => $user->email,
                'est_gerant' => (bool) $user->est_gerant,
                'est_client' => (bool) $user->est_client,
            ],
        ];
    }

    /**
     * @return array{ok: false, status: int, code: string, message: string}
     */
    private function echec(string $message, string $code, int $status): array
    {
        return [
            'ok' => false,
            'status' => $status,
            'code' => $code,
            'message' => $message,
        ];
    }
}
