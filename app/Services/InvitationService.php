<?php

namespace App\Services;

use App\Models\Entreprise;
use App\Models\EntrepriseInvitation;
use App\Models\EntrepriseMembre;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Mail\Message;

class InvitationService
{
    /**
     * Crée une invitation pour un membre
     */
    public function creerInvitation(Entreprise $entreprise, string $email, string $role, User $invitePar): EntrepriseInvitation
    {
        $token = EntrepriseInvitation::genererToken();
        $expireAt = now()->addDays(30);

        $invitation = EntrepriseInvitation::create([
            'entreprise_id' => $entreprise->id,
            'email' => $email,
            'role' => $role,
            'statut' => 'en_attente_compte',
            'token' => $token,
            'invite_par_user_id' => $invitePar->id,
            'user_id' => null,
            'expire_at' => $expireAt,
        ]);

        return $invitation;
    }

    /**
     * Envoie l'email d'invitation approprié
     */
    public function envoyerEmailInvitation(EntrepriseInvitation $invitation): void
    {
        try {
            if ($invitation->estEnAttenteCompte()) {
                // Email pour créer un compte
                Mail::send('emails.invitation-compte', [
                    'invitation' => $invitation,
                    'entreprise' => $invitation->entreprise,
                    'url' => route('signup', ['invitation' => $invitation->token]),
                ], function (Message $message) use ($invitation) {
                    $message->to($invitation->email)
                            ->subject("Invitation à rejoindre {$invitation->entreprise->nom} sur Allo Tata");
                });
            } elseif ($invitation->estEnAttenteAcceptation() && $invitation->user) {
                // Email pour accepter l'invitation (compte existe déjà)
                Mail::send('emails.invitation-membre', [
                    'invitation' => $invitation,
                    'entreprise' => $invitation->entreprise,
                    'url' => route('invitations.show', $invitation->token),
                ], function (Message $message) use ($invitation) {
                    $message->to($invitation->email)
                            ->subject("Invitation à rejoindre {$invitation->entreprise->nom} en tant que {$invitation->role}");
                });
            }
        } catch (\Exception $e) {
            \Log::error("Erreur lors de l'envoi de l'email d'invitation : " . $e->getMessage());
            // Ne pas bloquer le processus si l'email échoue
        }
    }

    /**
     * Convertit une invitation en attente de compte en invitation de membre
     */
    public function convertirEnInvitationMembre(EntrepriseInvitation $invitation, User $user): void
    {
        $invitation->convertirEnInvitationMembre($user);

        // Créer une notification pour l'utilisateur
        Notification::creer(
            $user->id,
            'invitation_membre',
            'Invitation à rejoindre une entreprise',
            "L'entreprise {$invitation->entreprise->nom} veut vous ajouter en tant que {$invitation->role}",
            route('invitations.show', $invitation->token),
            [
                'invitation_id' => $invitation->id,
                'entreprise_id' => $invitation->entreprise->id,
                'role' => $invitation->role,
            ]
        );
    }

    /**
     * Accepte une invitation et crée le membre
     */
    public function accepterInvitation(EntrepriseInvitation $invitation): EntrepriseMembre
    {
        if (!$invitation->user_id) {
            throw new \Exception('L\'invitation n\'a pas d\'utilisateur associé.');
        }

        // Vérifier que l'utilisateur n'est pas déjà membre
        $membreExistant = EntrepriseMembre::where('entreprise_id', $invitation->entreprise_id)
            ->where('user_id', $invitation->user_id)
            ->first();

        if ($membreExistant) {
            // Réactiver le membre existant
            $membreExistant->update([
                'role' => $invitation->role,
                'est_actif' => true,
                'accepte_at' => now(),
                'invitation_id' => $invitation->id,
            ]);
            $membre = $membreExistant;
        } else {
            // Créer un nouveau membre
            $membre = EntrepriseMembre::create([
                'entreprise_id' => $invitation->entreprise_id,
                'user_id' => $invitation->user_id,
                'role' => $invitation->role,
                'est_actif' => true,
                'invite_at' => $invitation->created_at,
                'accepte_at' => now(),
                'invitation_id' => $invitation->id,
            ]);
        }

        // Marquer l'invitation comme acceptée
        $invitation->marquerAcceptee();

        return $membre;
    }

    /**
     * Refuse une invitation
     */
    public function refuserInvitation(EntrepriseInvitation $invitation): void
    {
        $invitation->marquerRefusee();
    }

    /**
     * Crée une invitation pour un utilisateur existant
     */
    public function creerInvitationPourUtilisateurExistant(Entreprise $entreprise, User $user, string $role, User $invitePar): EntrepriseInvitation
    {
        $token = EntrepriseInvitation::genererToken();
        $expireAt = now()->addDays(30);

        $invitation = EntrepriseInvitation::create([
            'entreprise_id' => $entreprise->id,
            'email' => $user->email,
            'role' => $role,
            'statut' => 'en_attente_acceptation',
            'token' => $token,
            'invite_par_user_id' => $invitePar->id,
            'user_id' => $user->id,
            'expire_at' => $expireAt,
        ]);

        // Créer une notification
        Notification::creer(
            $user->id,
            'invitation_membre',
            'Invitation à rejoindre une entreprise',
            "L'entreprise {$entreprise->nom} veut vous ajouter en tant que {$role}",
            route('invitations.show', $invitation->token),
            [
                'invitation_id' => $invitation->id,
                'entreprise_id' => $entreprise->id,
                'role' => $role,
            ]
        );

        return $invitation;
    }
}
