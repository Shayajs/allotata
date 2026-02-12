<?php

namespace App\Http\Controllers;

use App\Models\EntrepriseInvitation;
use App\Services\InvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvitationController extends Controller
{
    /**
     * Afficher les détails d'une invitation
     */
    public function show($token)
    {
        $invitation = EntrepriseInvitation::where('token', $token)
            ->with(['entreprise', 'invitePar', 'user'])
            ->firstOrFail();

        // Vérifier si l'invitation est expirée
        if ($invitation->estExpiree()) {
            return view('invitations.expiree', [
                'invitation' => $invitation,
            ]);
        }

        // Vérifier si l'invitation est déjà traitée
        if ($invitation->estAcceptee()) {
            return view('invitations.acceptee', [
                'invitation' => $invitation,
            ]);
        }

        if ($invitation->estRefusee()) {
            return view('invitations.refusee', [
                'invitation' => $invitation,
            ]);
        }

        // Si l'invitation est en attente d'acceptation et l'utilisateur n'est pas connecté
        if ($invitation->estEnAttenteAcceptation() && !Auth::check()) {
            return redirect()->route('login')
                ->with('invitation_token', $token)
                ->with('info', 'Veuillez vous connecter pour accepter cette invitation.');
        }

        // Si l'invitation est en attente d'acceptation, vérifier que c'est le bon utilisateur
        if ($invitation->estEnAttenteAcceptation() && Auth::check()) {
            if ($invitation->user_id !== Auth::id()) {
                abort(403, 'Cette invitation ne vous est pas destinée.');
            }
        }

        return view('invitations.show', [
            'invitation' => $invitation,
        ]);
    }

    /**
     * Accepter une invitation
     */
    public function accepter(Request $request, $token)
    {
        $invitation = EntrepriseInvitation::where('token', $token)
            ->with(['entreprise', 'user'])
            ->firstOrFail();

        // Vérifier si l'invitation est expirée
        if ($invitation->estExpiree()) {
            return back()->withErrors(['error' => 'Cette invitation a expiré.']);
        }

        // Vérifier si l'invitation est déjà traitée
        if ($invitation->estAcceptee()) {
            return back()->withErrors(['error' => 'Cette invitation a déjà été acceptée.']);
        }

        if ($invitation->estRefusee()) {
            return back()->withErrors(['error' => 'Cette invitation a été refusée.']);
        }

        // Si l'invitation est en attente de compte, rediriger vers l'inscription
        if ($invitation->estEnAttenteCompte()) {
            return redirect()->route('signup', ['invitation' => $token])
                ->with('info', 'Créez un compte avec l\'email ' . $invitation->email . ' pour accepter cette invitation.');
        }

        // Si l'invitation est en attente d'acceptation, vérifier l'authentification
        if ($invitation->estEnAttenteAcceptation()) {
            if (!Auth::check()) {
                return redirect()->route('login')
                    ->with('invitation_token', $token)
                    ->with('info', 'Veuillez vous connecter pour accepter cette invitation.');
            }

            if ($invitation->user_id !== Auth::id()) {
                abort(403, 'Cette invitation ne vous est pas destinée.');
            }

            // Accepter l'invitation
            $invitationService = app(InvitationService::class);
            $membre = $invitationService->accepterInvitation($invitation);

            return redirect()->route('entreprise.dashboard', ['slug' => $invitation->entreprise->slug])
                ->with('success', 'Vous avez rejoint l\'entreprise ' . $invitation->entreprise->nom . ' en tant que ' . $invitation->role . '.');
        }

        return back()->withErrors(['error' => 'Statut d\'invitation invalide.']);
    }

    /**
     * Refuser une invitation
     */
    public function refuser(Request $request, $token)
    {
        $invitation = EntrepriseInvitation::where('token', $token)
            ->with(['entreprise', 'user'])
            ->firstOrFail();

        // Vérifier si l'invitation est expirée
        if ($invitation->estExpiree()) {
            return back()->withErrors(['error' => 'Cette invitation a expiré.']);
        }

        // Vérifier si l'invitation est déjà traitée
        if ($invitation->estAcceptee()) {
            return back()->withErrors(['error' => 'Cette invitation a déjà été acceptée.']);
        }

        if ($invitation->estRefusee()) {
            return back()->withErrors(['error' => 'Cette invitation a déjà été refusée.']);
        }

        // Si l'invitation est en attente d'acceptation, vérifier l'authentification
        if ($invitation->estEnAttenteAcceptation()) {
            if (!Auth::check()) {
                return redirect()->route('login')
                    ->with('invitation_token', $token)
                    ->with('info', 'Veuillez vous connecter pour refuser cette invitation.');
            }

            if ($invitation->user_id !== Auth::id()) {
                abort(403, 'Cette invitation ne vous est pas destinée.');
            }

            // Refuser l'invitation
            $invitationService = app(InvitationService::class);
            $invitationService->refuserInvitation($invitation);

            return redirect()->route('dashboard')
                ->with('info', 'Vous avez refusé l\'invitation de ' . $invitation->entreprise->nom . '.');
        }

        // Pour les invitations en attente de compte, on peut aussi les refuser (supprimer)
        if ($invitation->estEnAttenteCompte()) {
            $invitationService = app(InvitationService::class);
            $invitationService->refuserInvitation($invitation);

            return redirect('/')
                ->with('info', 'L\'invitation a été annulée.');
        }

        return back()->withErrors(['error' => 'Statut d\'invitation invalide.']);
    }
}
