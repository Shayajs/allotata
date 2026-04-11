<?php

namespace App\Http\Controllers;

use App\Models\EmailVerification;
use App\Models\SecurityLog;
use App\Support\PublicAgendaReturnUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class EmailVerificationController extends Controller
{
    /**
     * Afficher la page de vérification requise (SAS)
     */
    public function show(Request $request)
    {
        // Si l'utilisateur est connecté et a vérifié son email, rediriger vers le dashboard
        if (Auth::check() && Auth::user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $user = Auth::user();
        $email = null;

        // Si l'utilisateur n'est pas connecté mais qu'on a un email en session (depuis tentative de connexion)
        if (! $user && $request->session()->has('pending_verification_email')) {
            $email = $request->session()->get('pending_verification_email');
            $user = \App\Models\User::where('email', $email)->first();

            // Si l'utilisateur existe et n'a pas son email vérifié, envoyer automatiquement un email
            // Mais seulement si on n'a pas déjà envoyé un email récemment (éviter les spams sur refresh)
            if ($user && ! $user->hasVerifiedEmail()) {
                $lastEmailSent = $request->session()->get('last_verification_email_sent_at');
                $shouldSendEmail = ! $lastEmailSent || now()->diffInMinutes($lastEmailSent) > 2; // Au moins 2 minutes entre chaque envoi

                if ($shouldSendEmail) {
                    // Générer un nouveau hash de vérification si nécessaire
                    $emailVerification = EmailVerification::where('user_id', $user->id)
                        ->where('is_used', false)
                        ->where('expires_at', '>', now())
                        ->latest()
                        ->first();

                    if (! $emailVerification) {
                        $emailVerification = EmailVerification::generateHashForUser($user->id);
                    }

                    // Envoyer l'email de vérification
                    try {
                        $user->notify(new \App\Notifications\EmailVerificationNotification($emailVerification));

                        // Marquer qu'on a envoyé un email maintenant (pour éviter les spams)
                        $request->session()->put('last_verification_email_sent_at', now());

                        // Logger l'événement
                        SecurityLog::log(
                            $user->id,
                            'verification_email_sent_from_sas',
                            $request->ip(),
                            $request->userAgent(),
                            null,
                            [],
                            'low',
                            false
                        );
                    } catch (\Exception $e) {
                        \Log::error("Erreur lors de l'envoi de l'email de vérification depuis le sas : ".$e->getMessage());
                    }
                }
            }
        } elseif ($user) {
            $email = $user->email;

            // Si l'utilisateur est connecté mais n'a pas vérifié, envoyer aussi un email si nécessaire
            if (! $user->hasVerifiedEmail()) {
                $lastEmailSent = $request->session()->get('last_verification_email_sent_at');
                $shouldSendEmail = ! $lastEmailSent || now()->diffInMinutes($lastEmailSent) > 2;

                if ($shouldSendEmail) {
                    $emailVerification = EmailVerification::where('user_id', $user->id)
                        ->where('is_used', false)
                        ->where('expires_at', '>', now())
                        ->latest()
                        ->first();

                    if (! $emailVerification) {
                        $emailVerification = EmailVerification::generateHashForUser($user->id);
                    }

                    try {
                        $user->notify(new \App\Notifications\EmailVerificationNotification($emailVerification));
                        $request->session()->put('last_verification_email_sent_at', now());
                    } catch (\Exception $e) {
                        \Log::error("Erreur lors de l'envoi de l'email de vérification : ".$e->getMessage());
                    }
                }
            }
        }

        return view('auth.verification.required', [
            'user' => $user,
            'email' => $email,
        ]);
    }

    /**
     * Renvoyer l'email de vérification
     */
    public function resend(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return back()->withErrors(['email' => 'Vous devez être connecté pour recevoir un email de vérification.']);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard')
                ->with('status', 'Votre email est déjà vérifié.');
        }

        // Générer un nouveau hash de vérification
        $emailVerification = EmailVerification::generateHashForUser($user->id);

        // Envoyer l'email
        try {
            $user->notify(new \App\Notifications\EmailVerificationNotification($emailVerification));
        } catch (\Exception $e) {
            \Log::error("Erreur lors de l'envoi de l'email de vérification : ".$e->getMessage());

            return back()->withErrors(['email' => 'Erreur lors de l\'envoi de l\'email. Veuillez réessayer.']);
        }

        SecurityLog::log(
            $user->id,
            'verification_email_resent',
            $request->ip(),
            $request->userAgent(),
            null,
            [],
            'low',
            false
        );

        return back()->with('status', 'Un nouvel email de vérification a été envoyé à '.$user->email.'.');
    }

    /**
     * Vérifier l'email avec le hash
     */
    public function verify(Request $request, string $hash)
    {
        $emailVerification = EmailVerification::where('hash', $hash)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (! $emailVerification || ! $emailVerification->isValid()) {
            return redirect()->route('verification.required')
                ->withErrors(['email' => 'Ce lien de vérification est invalide ou a expiré. Veuillez demander un nouveau lien.']);
        }

        $user = $emailVerification->user;

        if (! $user) {
            return redirect()->route('verification.required')
                ->withErrors(['email' => 'Utilisateur introuvable.']);
        }

        // Marquer l'email comme vérifié
        $user->markEmailAsVerified();

        // Marquer le hash comme utilisé
        $emailVerification->markAsUsed($request->ip());

        // Nettoyer tous les autres hash non utilisés pour cet utilisateur
        EmailVerification::where('user_id', $user->id)
            ->where('id', '!=', $emailVerification->id)
            ->where('is_used', false)
            ->update(['is_used' => true]);

        // Logger l'événement
        SecurityLog::log(
            $user->id,
            'email_verified',
            $request->ip(),
            $request->userAgent(),
            null,
            [],
            'low',
            false
        );

        // Connecter l'utilisateur s'il ne l'est pas déjà
        if (! Auth::check() || Auth::id() !== $user->id) {
            Auth::login($user);
        }

        // Envoyer l'email de bienvenue maintenant que l'email est vérifié
        try {
            \App\Helpers\EmailHelper::sendWelcome($user);
        } catch (\Exception $e) {
            \Log::error("Erreur lors de l'envoi de l'email de bienvenue : ".$e->getMessage());
        }

        // Vérifier s'il y a des invitations en attente pour cet email
        $invitationsEnAttente = \App\Models\EntrepriseInvitation::where('email', $user->email)
            ->where('statut', 'en_attente_compte')
            ->where(function ($query) {
                $query->whereNull('expire_at')
                    ->orWhere('expire_at', '>', now());
            })
            ->get();

        $invitationService = app(\App\Services\InvitationService::class);
        $invitationsConverties = 0;

        foreach ($invitationsEnAttente as $invitation) {
            // Convertir l'invitation en invitation de membre
            $invitationService->convertirEnInvitationMembre($invitation, $user);
            $invitationsConverties++;
        }

        $message = 'Votre email a été vérifié avec succès ! Bienvenue sur Allo Tata.';
        if ($invitationsConverties > 0) {
            $message .= " Vous avez {$invitationsConverties} invitation(s) en attente d'acceptation.";
            // Rediriger vers la première invitation si une seule, sinon vers le dashboard
            if ($invitationsConverties === 1 && $invitationsEnAttente->first()) {
                return redirect()->route('invitations.show', $invitationsEnAttente->first()->token)
                    ->with('success', $message)
                    ->withCookie(Cookie::forget(PublicAgendaReturnUrl::POST_VERIFY_COOKIE));
            }
        }

        $agendaReturn = PublicAgendaReturnUrl::normalize($request->cookie(PublicAgendaReturnUrl::POST_VERIFY_COOKIE));
        if ($agendaReturn) {
            return redirect($agendaReturn)
                ->with('status', 'Bienvenue ! Reprenez votre réservation : vos choix sur cet appareil ont été conservés.')
                ->withCookie(Cookie::forget(PublicAgendaReturnUrl::POST_VERIFY_COOKIE));
        }

        return redirect()->route('dashboard')
            ->with('status', $message)
            ->withCookie(Cookie::forget(PublicAgendaReturnUrl::POST_VERIFY_COOKIE));
    }
}
