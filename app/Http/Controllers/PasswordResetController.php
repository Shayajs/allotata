<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PasswordResetCode;
use App\Models\SecurityLog;
use App\Services\SecurityService;
use App\Notifications\PasswordResetCodeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    /**
     * Afficher le formulaire de demande de réinitialisation
     */
    public function showRequestForm()
    {
        return view('auth.password.request');
    }

    /**
     * Envoyer le code de réinitialisation (email ou SMS)
     */
    public function sendCode(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = $request->email;
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        $user = User::where('email', $email)->first();

        // Pour des raisons de sécurité, on ne révèle pas si l'email existe ou non
        // Mais on log quand même pour le monitoring
        if ($user) {
            SecurityLog::log(
                $user->id,
                'password_reset_requested',
                $ipAddress,
                $userAgent,
                null,
                [],
                'medium',
                false
            );
        }

        // Si l'utilisateur n'existe pas, retourner quand même un succès (sécurité)
        if (!$user) {
            return back()->with('status', 'Si cette adresse email existe dans notre système, un code de réinitialisation a été envoyé.');
        }

        // Vérifier si on doit envoyer une notification (selon l'IP)
        $securityService = app(SecurityService::class);
        $shouldSend = $securityService->shouldSendRecoveryNotification($user, $ipAddress);

        if (!$shouldSend) {
            return back()->with('status', 'Si cette adresse email existe dans notre système, un code de réinitialisation a été envoyé.');
        }

        // Générer un code à 6 chiffres
        $code = PasswordResetCode::generateCode();

        // Déterminer la méthode (email ou SMS)
        $method = $user->preference_recovery_method ?? 'email';

        // Vérifier si le numéro de téléphone est disponible pour SMS
        if ($method === 'sms' && !$user->telephone) {
            $method = 'email';
        }

        // Supprimer les codes existants non utilisés pour cet utilisateur
        PasswordResetCode::where('user_id', $user->id)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->delete();

        // Créer le code de réinitialisation
        $resetCode = PasswordResetCode::create([
            'user_id' => $user->id,
            'code' => $code,
            'method' => $method,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'expires_at' => now()->addMinutes(15), // Code valide 15 minutes
        ]);

        // Envoyer le code via email ou SMS
        try {
            $user->notify(new PasswordResetCodeNotification($resetCode));
            
            SecurityLog::log(
                $user->id,
                'password_reset_code_sent',
                $ipAddress,
                $userAgent,
                null,
                ['method' => $method],
                'medium',
                false
            );
        } catch (\Exception $e) {
            \Log::error("Erreur lors de l'envoi du code de réinitialisation : " . $e->getMessage());
            
            return back()->withErrors([
                'email' => 'Une erreur est survenue lors de l\'envoi du code. Veuillez réessayer.',
            ]);
        }

        return redirect()->route('password.reset.verify', ['email' => $email])
            ->with('status', 'Un code de réinitialisation a été envoyé par ' . ($method === 'sms' ? 'SMS' : 'email') . '.');
    }

    /**
     * Afficher le formulaire de vérification du code
     */
    public function showVerifyForm(Request $request)
    {
        $email = $request->get('email');
        
        if (!$email) {
            return redirect()->route('password.request');
        }

        return view('auth.password.verify', ['email' => $email]);
    }

    /**
     * Vérifier le code et afficher le formulaire de nouveau mot de passe
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $email = $request->email;
        $code = $request->code;
        $ipAddress = $request->ip();

        $user = User::where('email', $email)->first();

        if (!$user) {
            return back()->withErrors(['code' => 'Code invalide.']);
        }

        // Chercher le code valide
        $resetCode = PasswordResetCode::where('user_id', $user->id)
            ->where('code', $code)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$resetCode || !$resetCode->isValid()) {
            SecurityLog::log(
                $user->id,
                'password_reset_code_invalid',
                $ipAddress,
                $request->userAgent(),
                null,
                [],
                'medium',
                true,
                'Tentative de réinitialisation avec un code invalide'
            );

            return back()->withErrors(['code' => 'Code invalide ou expiré.']);
        }

        // Créer un token temporaire pour la session
        $token = bin2hex(random_bytes(32));
        $request->session()->put('password_reset_token', $token);
        $request->session()->put('password_reset_user_id', $user->id);
        $request->session()->put('password_reset_code_id', $resetCode->id);

        SecurityLog::log(
            $user->id,
            'password_reset_code_verified',
            $ipAddress,
            $request->userAgent(),
            null,
            [],
            'medium',
            false
        );

        return redirect()->route('password.reset.form', ['token' => $token]);
    }

    /**
     * Afficher le formulaire de nouveau mot de passe
     */
    public function showResetForm(Request $request, $token)
    {
        $sessionToken = $request->session()->get('password_reset_token');
        $userId = $request->session()->get('password_reset_user_id');

        if ($sessionToken !== $token || !$userId) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Le lien de réinitialisation est invalide ou a expiré.']);
        }

        $user = User::find($userId);
        
        if (!$user) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Utilisateur introuvable.']);
        }

        return view('auth.password.reset', ['token' => $token, 'email' => $user->email]);
    }

    /**
     * Réinitialiser le mot de passe
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $sessionToken = $request->session()->get('password_reset_token');
        $userId = $request->session()->get('password_reset_user_id');
        $codeId = $request->session()->get('password_reset_code_id');

        if ($sessionToken !== $request->token || !$userId || !$codeId) {
            return back()->withErrors(['password' => 'Le lien de réinitialisation est invalide ou a expiré.']);
        }

        $user = User::find($userId);
        
        if (!$user || $user->email !== $request->email) {
            return back()->withErrors(['password' => 'Les informations fournies sont invalides.']);
        }

        $resetCode = PasswordResetCode::find($codeId);
        
        if (!$resetCode || $resetCode->is_used || !$resetCode->isValid()) {
            return back()->withErrors(['password' => 'Le code de réinitialisation a expiré. Veuillez en demander un nouveau.']);
        }

        // Mettre à jour le mot de passe
        DB::transaction(function () use ($user, $resetCode, $request) {
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            // Marquer le code comme utilisé
            $resetCode->markAsUsed();

            // Déverrouiller le compte si il était verrouillé
            if ($user->accountLockout) {
                $user->accountLockout->unlock();
            }

            // Logger l'événement
            SecurityLog::log(
                $user->id,
                'password_reset_completed',
                $request->ip(),
                $request->userAgent(),
                null,
                [],
                'medium',
                false
            );
        });

        // Nettoyer la session
        $request->session()->forget(['password_reset_token', 'password_reset_user_id', 'password_reset_code_id']);

        return redirect()->route('login')
            ->with('status', 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.');
    }
}
