<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\EntrepriseMembre;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class EntrepriseMembreController extends Controller
{
    /**
     * Afficher la liste des membres d'une entreprise
     */
    public function index($slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        
        // Vérifier que l'utilisateur peut gérer cette entreprise
        if (!$entreprise->peutEtreGereePar($user)) {
            return redirect()->route('dashboard')
                ->with('error', 'Vous n\'avez pas accès à cette entreprise.');
        }

        // Vérifier que l'entreprise a l'abonnement multi-personnes
        if (!$entreprise->aGestionMultiPersonnes()) {
            return redirect()->route('dashboard')
                ->with('error', 'Cette fonctionnalité nécessite l\'abonnement Gestion Multi-Personnes.');
        }

        $membres = $entreprise->tousMembres()->with('user')->get();

        return view('entreprise.membres.index', [
            'entreprise' => $entreprise,
            'membres' => $membres,
        ]);
    }

    /**
     * Inviter un utilisateur à rejoindre l'entreprise
     */
    public function store(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        
        // Vérifier que l'utilisateur peut gérer cette entreprise
        if (!$entreprise->peutEtreGereePar($user)) {
            return back()->withErrors(['error' => 'Vous n\'avez pas accès à cette entreprise.']);
        }

        // Vérifier que l'entreprise a l'abonnement multi-personnes
        if (!$entreprise->aGestionMultiPersonnes()) {
            return back()->withErrors(['error' => 'Cette fonctionnalité nécessite l\'abonnement Gestion Multi-Personnes.']);
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', 'in:administrateur,membre'],
        ]);

        // Vérifier que l'email n'est pas celui du propriétaire
        if ($entreprise->email === $validated['email'] || $entreprise->user->email === $validated['email']) {
            return back()->withErrors(['error' => 'Le propriétaire de l\'entreprise est automatiquement administrateur.']);
        }

        // Vérifier qu'il n'y a pas déjà une invitation en attente pour cet email
        $invitationExistante = \App\Models\EntrepriseInvitation::where('entreprise_id', $entreprise->id)
            ->where('email', $validated['email'])
            ->whereIn('statut', ['en_attente_compte', 'en_attente_acceptation'])
            ->first();

        if ($invitationExistante) {
            return back()->withErrors(['error' => 'Une invitation est déjà en cours pour cet email.']);
        }

        $invitationService = app(InvitationService::class);

        // Chercher l'utilisateur par email
        $userInvite = User::where('email', $validated['email'])->first();

        if ($userInvite) {
            // Utilisateur existe déjà
            // Vérifier qu'il n'est pas déjà membre actif
            $membreExistant = EntrepriseMembre::where('entreprise_id', $entreprise->id)
                ->where('user_id', $userInvite->id)
                ->where('est_actif', true)
                ->first();

            if ($membreExistant) {
                return back()->withErrors(['error' => 'Cet utilisateur est déjà membre de cette entreprise.']);
            }

            // Créer une invitation pour utilisateur existant
            $invitation = $invitationService->creerInvitationPourUtilisateurExistant(
                $entreprise,
                $userInvite,
                $validated['role'],
                $user
            );

            // Envoyer l'email d'invitation
            $invitationService->envoyerEmailInvitation($invitation);

            return redirect()->route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'equipe'])
                ->with('success', 'Une invitation a été envoyée à ' . $validated['email'] . '.');
        } else {
            // Utilisateur n'existe pas, créer une invitation en attente de compte
            $invitation = $invitationService->creerInvitation(
                $entreprise,
                $validated['email'],
                $validated['role'],
                $user
            );

            // Envoyer l'email d'invitation pour créer un compte
            $invitationService->envoyerEmailInvitation($invitation);

            return redirect()->route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'equipe'])
                ->with('success', 'Une invitation a été envoyée à ' . $validated['email'] . '. L\'utilisateur devra créer un compte pour accepter.');
        }
    }

    /**
     * Mettre à jour le rôle d'un membre
     */
    public function update(Request $request, $slug, EntrepriseMembre $membre)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        
        // Vérifier que l'utilisateur peut gérer cette entreprise
        if (!$entreprise->peutEtreGereePar($user)) {
            return back()->withErrors(['error' => 'Vous n\'avez pas accès à cette entreprise.']);
        }

        // Vérifier que le membre appartient à cette entreprise
        if ($membre->entreprise_id !== $entreprise->id) {
            return back()->withErrors(['error' => 'Membre introuvable.']);
        }

        // Ne pas permettre de modifier le propriétaire
        if ($membre->user_id === $entreprise->user_id) {
            return back()->withErrors(['error' => 'Le propriétaire de l\'entreprise ne peut pas être modifié.']);
        }

        $validated = $request->validate([
            'role' => ['required', 'in:administrateur,membre'],
        ]);

        $membre->update([
            'role' => $validated['role'],
        ]);

        return redirect()->route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'equipe'])
            ->with('success', 'Le rôle du membre a été mis à jour.');
    }

    /**
     * Supprimer un membre
     */
    public function destroy($slug, EntrepriseMembre $membre)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        
        // Vérifier que l'utilisateur peut gérer cette entreprise
        if (!$entreprise->peutEtreGereePar($user)) {
            return back()->withErrors(['error' => 'Vous n\'avez pas accès à cette entreprise.']);
        }

        // Vérifier que le membre appartient à cette entreprise
        if ($membre->entreprise_id !== $entreprise->id) {
            return back()->withErrors(['error' => 'Membre introuvable.']);
        }

        // Ne pas permettre de supprimer le propriétaire
        if ($membre->user_id === $entreprise->user_id) {
            return back()->withErrors(['error' => 'Le propriétaire de l\'entreprise ne peut pas être supprimé.']);
        }

        // Désactiver le membre au lieu de le supprimer
        $membre->update([
            'est_actif' => false,
        ]);

        return redirect()->route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'equipe'])
            ->with('success', 'Le membre a été retiré de l\'entreprise.');
    }
}
