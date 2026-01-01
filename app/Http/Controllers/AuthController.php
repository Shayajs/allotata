<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        // Créer un membre (par défaut client uniquement)
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'est_client' => true, // Par défaut, tous les membres sont clients
            'est_gerant' => false, // Ils deviendront gérants après avoir ajouté une entreprise
        ]);

        Auth::login($user);

        // Vérifier s'il y a des invitations en attente pour cet email
        $invitationsEnAttente = \App\Models\EntrepriseInvitation::where('email', $validated['email'])
            ->where('statut', 'en_attente_compte')
            ->where(function($query) {
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

        $message = 'Inscription réussie ! Bienvenue sur Allo Tata.';
        if ($invitationsConverties > 0) {
            $message .= " Vous avez {$invitationsConverties} invitation(s) en attente d'acceptation.";
            // Rediriger vers la première invitation si une seule, sinon vers le dashboard
            if ($invitationsConverties === 1 && $invitationsEnAttente->first()) {
                return redirect()->route('invitations.show', $invitationsEnAttente->first()->token)
                    ->with('success', $message);
            }
        }

        return redirect()->route('dashboard')->with('success', $message);
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

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            // Si une invitation est en attente, rediriger vers la page d'invitation
            if ($request->session()->has('invitation_token')) {
                $token = $request->session()->get('invitation_token');
                $request->session()->forget('invitation_token');
                return redirect()->route('invitations.show', $token);
            }
            
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'Les identifiants fournis ne correspondent à aucun compte.',
        ])->onlyInput('email');
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
