<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\EntrepriseMembre;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class EntrepriseMembreController extends Controller
{
    /**
     * Afficher la liste des membres d'une entreprise
     */
    public function index(Entreprise $entreprise)
    {
        $user = Auth::user();
        
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
    public function store(Request $request, Entreprise $entreprise)
    {
        $user = Auth::user();
        
        // Vérifier que l'utilisateur peut gérer cette entreprise
        if (!$entreprise->peutEtreGereePar($user)) {
            return back()->withErrors(['error' => 'Vous n\'avez pas accès à cette entreprise.']);
        }

        // Vérifier que l'entreprise a l'abonnement multi-personnes
        if (!$entreprise->aGestionMultiPersonnes()) {
            return back()->withErrors(['error' => 'Cette fonctionnalité nécessite l\'abonnement Gestion Multi-Personnes.']);
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'role' => ['required', 'in:administrateur,membre'],
        ]);

        // Trouver l'utilisateur par email
        $userInvite = User::where('email', $validated['email'])->first();

        if (!$userInvite) {
            return back()->withErrors(['error' => 'Utilisateur introuvable.']);
        }

        // Vérifier que l'utilisateur n'est pas déjà membre
        if ($entreprise->aMembre($userInvite)) {
            return back()->withErrors(['error' => 'Cet utilisateur est déjà membre de cette entreprise.']);
        }

        // Vérifier que l'utilisateur n'est pas le propriétaire
        if ($entreprise->user_id === $userInvite->id) {
            return back()->withErrors(['error' => 'Le propriétaire de l\'entreprise est automatiquement administrateur.']);
        }

        // Créer l'invitation
        $membre = EntrepriseMembre::create([
            'entreprise_id' => $entreprise->id,
            'user_id' => $userInvite->id,
            'role' => $validated['role'],
            'est_actif' => true,
            'invite_at' => now(),
            'accepte_at' => now(), // On accepte automatiquement pour simplifier
        ]);

        // TODO: Envoyer un email d'invitation

        return redirect()->route('entreprise.membres.index', ['slug' => $entreprise->slug])
            ->with('success', 'L\'utilisateur a été ajouté comme membre de l\'entreprise.');
    }

    /**
     * Mettre à jour le rôle d'un membre
     */
    public function update(Request $request, Entreprise $entreprise, EntrepriseMembre $membre)
    {
        $user = Auth::user();
        
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

        return redirect()->route('entreprise.membres.index', ['entreprise' => $entreprise->id])
            ->with('success', 'Le rôle du membre a été mis à jour.');
    }

    /**
     * Supprimer un membre
     */
    public function destroy(Entreprise $entreprise, EntrepriseMembre $membre)
    {
        $user = Auth::user();
        
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

        return redirect()->route('entreprise.membres.index', ['slug' => $entreprise->slug])
            ->with('success', 'Le membre a été retiré de l\'entreprise.');
    }
}
