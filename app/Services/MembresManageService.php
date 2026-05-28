<?php

namespace App\Services;

use App\Models\Entreprise;
use App\Models\EntrepriseInvitation;
use App\Models\EntrepriseMembre;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class MembresManageService
{
    public function __construct(
        private readonly InvitationService $invitationService,
    ) {}

    public function resolveEntreprise(string|int $ref): Entreprise
    {
        $ref = is_string($ref) ? trim($ref) : $ref;

        $entreprise = is_numeric($ref)
            ? Entreprise::find((int) $ref)
            : Entreprise::where('slug', $ref)->first();

        if (!$entreprise) {
            throw new InvalidArgumentException("Entreprise introuvable : {$ref}");
        }

        return $entreprise;
    }

    public function resolveMembre(Entreprise $entreprise, ?int $membreId, ?string $email, ?int $userId): EntrepriseMembre
    {
        $query = EntrepriseMembre::where('entreprise_id', $entreprise->id)->with('user');

        if ($membreId) {
            $membre = $query->where('id', $membreId)->first();
        } elseif ($userId) {
            $membre = $query->where('user_id', $userId)->first();
        } elseif ($email) {
            $membre = $query->whereHas('user', fn ($q) => $q->where('email', $email))->first();
        } else {
            throw new InvalidArgumentException('Précisez --membre=, --user= ou --email=.');
        }

        if (!$membre) {
            throw new InvalidArgumentException('Membre introuvable pour cette entreprise.');
        }

        return $membre;
    }

    public function resolveUser(?int $userId, ?string $email): User
    {
        if ($userId) {
            $user = User::find($userId);
        } elseif ($email) {
            $user = User::where('email', $email)->first();
        } else {
            throw new InvalidArgumentException('Précisez --user= ou --email=.');
        }

        if (!$user) {
            throw new InvalidArgumentException('Utilisateur introuvable.');
        }

        return $user;
    }

    public function listEntreprises(): Collection
    {
        return Entreprise::with('user')
            ->orderBy('nom')
            ->get(['id', 'slug', 'nom', 'user_id', 'email']);
    }

    public function listMembres(Entreprise $entreprise, bool $includeInactive = false): Collection
    {
        $query = $entreprise->tousMembres()->with('user')->orderBy('id');

        if (!$includeInactive) {
            $query->where('est_actif', true);
        }

        return $query->get();
    }

    /**
     * Recherche d'utilisateurs inscrits sur le site (toutes entreprises confondues).
     */
    public function searchUsers(?string $query, ?string $email, ?int $userId, int $limit = 50): Collection
    {
        $builder = User::query()->orderByDesc('id');

        if ($userId) {
            $builder->where('id', $userId);
        } elseif ($email) {
            $email = strtolower(trim($email));
            $builder->where('email', 'like', "%{$email}%");
        } elseif ($query) {
            $like = '%'.$query.'%';
            $builder->where(function ($q) use ($like) {
                $q->where('email', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('surname', 'like', $like);
            });
        }

        return $builder
            ->limit(max(1, min($limit, 500)))
            ->with(['entreprisesMembres.entreprise'])
            ->get();
    }

    /**
     * Résumé des appartenances entreprise d'un utilisateur.
     *
     * @return list<array{membre_id: int, entreprise_id: int, slug: string, nom: string, role: string, est_actif: bool, est_proprietaire: bool}>
     */
    public function getUserMemberships(User $user, bool $includeInactive = true): array
    {
        $query = $user->entreprisesMembres()->with('entreprise')->orderBy('entreprise_id');

        if (!$includeInactive) {
            $query->where('est_actif', true);
        }

        return $query->get()->map(function (EntrepriseMembre $membre) {
            $entreprise = $membre->entreprise;

            return [
                'membre_id' => $membre->id,
                'entreprise_id' => $entreprise?->id,
                'slug' => $entreprise?->slug ?? '—',
                'nom' => $entreprise?->nom ?? '—',
                'role' => $membre->role,
                'est_actif' => $membre->est_actif,
                'est_proprietaire' => $entreprise && (int) $entreprise->user_id === (int) $membre->user_id,
                'accepte_at' => $membre->accepte_at?->toDateTimeString(),
            ];
        })->all();
    }

    public function formatMembershipsForList(User $user, bool $includeInactive = true): string
    {
        $memberships = $this->getUserMemberships($user, $includeInactive);

        if ($memberships === []) {
            return '—';
        }

        return collect($memberships)->map(function (array $m) {
            $status = $m['est_actif'] ? '' : ' [archivé]';
            $owner = $m['est_proprietaire'] ? ' (proprio.)' : '';

            return "{$m['slug']} ({$m['role']}){$owner}{$status}";
        })->implode(', ');
    }

    public function userToArray(User $user): array
    {
        return [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'surname' => $user->surname,
            'email_verified' => $user->email_verified_at !== null,
            'est_gerant' => (bool) $user->est_gerant,
            'est_client' => (bool) $user->est_client,
            'is_admin' => (bool) $user->is_admin,
            'statut_compte' => $user->statut_compte,
            'telephone' => $user->telephone,
            'created_at' => $user->created_at?->toDateTimeString(),
        ];
    }

    public function memberToArray(EntrepriseMembre $membre, Entreprise $entreprise): array
    {
        $user = $membre->user;
        $isOwner = (int) $membre->user_id === (int) $entreprise->user_id;

        return [
            'membre_id' => $membre->id,
            'user_id' => $membre->user_id,
            'email' => $user?->email,
            'name' => $user?->name,
            'role' => $membre->role,
            'est_actif' => $membre->est_actif,
            'est_proprietaire' => $isOwner,
            'accepte_at' => $membre->accepte_at?->toDateTimeString(),
            'invite_at' => $membre->invite_at?->toDateTimeString(),
            'created_at' => $membre->created_at?->toDateTimeString(),
        ];
    }

    public function inviteMember(
        Entreprise $entreprise,
        string $email,
        string $role,
        bool $sendEmail = true,
    ): EntrepriseInvitation {
        $this->assertValidRole($role);
        $email = strtolower(trim($email));

        $this->assertNotOwnerEmail($entreprise, $email);

        $pending = EntrepriseInvitation::where('entreprise_id', $entreprise->id)
            ->where('email', $email)
            ->whereIn('statut', ['en_attente_compte', 'en_attente_acceptation'])
            ->exists();

        if ($pending) {
            throw new InvalidArgumentException("Une invitation est déjà en cours pour {$email}.");
        }

        $invitePar = $entreprise->user ?? User::where('is_admin', true)->first();

        if (!$invitePar) {
            throw new InvalidArgumentException('Impossible de déterminer l\'utilisateur inviteur (propriétaire absent).');
        }

        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            $active = EntrepriseMembre::where('entreprise_id', $entreprise->id)
                ->where('user_id', $existingUser->id)
                ->where('est_actif', true)
                ->exists();

            if ($active) {
                throw new InvalidArgumentException("{$email} est déjà membre actif de cette entreprise.");
            }

            $invitation = $this->invitationService->creerInvitationPourUtilisateurExistant(
                $entreprise,
                $existingUser,
                $role,
                $invitePar,
            );
        } else {
            $invitation = $this->invitationService->creerInvitation(
                $entreprise,
                $email,
                $role,
                $invitePar,
            );
        }

        if ($sendEmail) {
            $this->invitationService->envoyerEmailInvitation($invitation);
        }

        Log::info('membres:manage invite', [
            'entreprise_id' => $entreprise->id,
            'email' => $email,
            'role' => $role,
            'invitation_id' => $invitation->id,
        ]);

        return $invitation->fresh();
    }

    /**
     * Ajoute ou réactive un membre directement (sans flux d'invitation).
     */
    public function addMemberDirect(Entreprise $entreprise, User $user, string $role): EntrepriseMembre
    {
        $this->assertValidRole($role);
        $this->assertNotOwnerUser($entreprise, $user);

        $membre = EntrepriseMembre::where('entreprise_id', $entreprise->id)
            ->where('user_id', $user->id)
            ->first();

        if ($membre) {
            $membre->update([
                'role' => $role,
                'est_actif' => true,
                'accepte_at' => $membre->accepte_at ?? now(),
            ]);
        } else {
            $membre = EntrepriseMembre::create([
                'entreprise_id' => $entreprise->id,
                'user_id' => $user->id,
                'role' => $role,
                'est_actif' => true,
                'invite_at' => now(),
                'accepte_at' => now(),
            ]);
        }

        Log::info('membres:manage add-direct', [
            'entreprise_id' => $entreprise->id,
            'user_id' => $user->id,
            'membre_id' => $membre->id,
            'role' => $role,
        ]);

        return $membre->fresh(['user']);
    }

    public function createUserAndAdd(
        Entreprise $entreprise,
        string $email,
        string $password,
        string $name,
        string $role,
        ?string $surname = null,
        bool $verifyEmail = true,
    ): array {
        $this->assertValidRole($role);
        $email = strtolower(trim($email));

        if (User::where('email', $email)->exists()) {
            throw new InvalidArgumentException("Un compte existe déjà pour {$email}. Utilisez « add » ou « invite ».");
        }

        $this->assertNotOwnerEmail($entreprise, $email);

        $user = User::create([
            'name' => trim($name),
            'surname' => $surname ? trim($surname) : null,
            'email' => $email,
            'password' => Hash::make($password),
            'est_client' => true,
            'est_gerant' => false,
            'email_verified_at' => $verifyEmail ? now() : null,
            'code_parrain' => User::generateCodeParrain(),
            'cgu_accepted_at' => now(),
            'cgv_accepted_at' => now(),
            'confidentialite_accepted_at' => now(),
        ]);

        $membre = $this->addMemberDirect($entreprise, $user, $role);

        Log::info('membres:manage create-user', [
            'entreprise_id' => $entreprise->id,
            'user_id' => $user->id,
            'membre_id' => $membre->id,
        ]);

        return ['user' => $user, 'membre' => $membre];
    }

    public function archiveMember(Entreprise $entreprise, EntrepriseMembre $membre): void
    {
        $this->assertMembreBelongsToEntreprise($entreprise, $membre);
        $this->assertNotOwnerMembre($entreprise, $membre);

        if (!$membre->est_actif) {
            throw new InvalidArgumentException('Ce membre est déjà archivé.');
        }

        $membre->update(['est_actif' => false]);

        Log::info('membres:manage archive', [
            'entreprise_id' => $entreprise->id,
            'membre_id' => $membre->id,
        ]);
    }

    public function restoreMember(Entreprise $entreprise, EntrepriseMembre $membre): void
    {
        $this->assertMembreBelongsToEntreprise($entreprise, $membre);

        if ($membre->est_actif) {
            throw new InvalidArgumentException('Ce membre est déjà actif.');
        }

        $membre->update([
            'est_actif' => true,
            'accepte_at' => $membre->accepte_at ?? now(),
        ]);

        Log::info('membres:manage restore', [
            'entreprise_id' => $entreprise->id,
            'membre_id' => $membre->id,
        ]);
    }

    public function setRole(Entreprise $entreprise, EntrepriseMembre $membre, string $role): void
    {
        $this->assertValidRole($role);
        $this->assertMembreBelongsToEntreprise($entreprise, $membre);
        $this->assertNotOwnerMembre($entreprise, $membre);

        $membre->update(['role' => $role]);

        Log::info('membres:manage role', [
            'entreprise_id' => $entreprise->id,
            'membre_id' => $membre->id,
            'role' => $role,
        ]);
    }

    public function changePassword(User $user, string $password): void
    {
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Le mot de passe doit contenir au moins 8 caractères.');
        }

        $user->password = Hash::make($password);
        $user->save();

        Log::info('membres:manage password', ['user_id' => $user->id]);
    }

    public function listInvitations(Entreprise $entreprise, bool $pendingOnly = true): Collection
    {
        $query = $entreprise->invitations()->orderByDesc('id');

        if ($pendingOnly) {
            $query->whereIn('statut', ['en_attente_compte', 'en_attente_acceptation']);
        }

        return $query->get();
    }

    public function cancelInvitation(Entreprise $entreprise, int $invitationId): EntrepriseInvitation
    {
        $invitation = EntrepriseInvitation::where('entreprise_id', $entreprise->id)
            ->where('id', $invitationId)
            ->first();

        if (!$invitation) {
            throw new InvalidArgumentException("Invitation #{$invitationId} introuvable.");
        }

        if (!in_array($invitation->statut, ['en_attente_compte', 'en_attente_acceptation'], true)) {
            throw new InvalidArgumentException("L'invitation #{$invitationId} n'est pas annulable (statut : {$invitation->statut}).");
        }

        $invitation->update(['statut' => 'refusee', 'refuse_at' => now()]);

        Log::info('membres:manage cancel-invite', [
            'entreprise_id' => $entreprise->id,
            'invitation_id' => $invitation->id,
        ]);

        return $invitation->fresh();
    }

    public function assertValidRole(string $role): void
    {
        if (!in_array($role, ['administrateur', 'membre'], true)) {
            throw new InvalidArgumentException("Rôle invalide « {$role} ». Valeurs : administrateur, membre.");
        }
    }

    private function assertMembreBelongsToEntreprise(Entreprise $entreprise, EntrepriseMembre $membre): void
    {
        if ((int) $membre->entreprise_id !== (int) $entreprise->id) {
            throw new InvalidArgumentException('Ce membre n\'appartient pas à cette entreprise.');
        }
    }

    private function assertNotOwnerMembre(Entreprise $entreprise, EntrepriseMembre $membre): void
    {
        if ((int) $membre->user_id === (int) $entreprise->user_id) {
            throw new InvalidArgumentException('Le propriétaire de l\'entreprise ne peut pas être modifié.');
        }
    }

    private function assertNotOwnerUser(Entreprise $entreprise, User $user): void
    {
        if ((int) $user->id === (int) $entreprise->user_id) {
            throw new InvalidArgumentException('Le propriétaire est déjà membre (administrateur implicite).');
        }
    }

    private function assertNotOwnerEmail(Entreprise $entreprise, string $email): void
    {
        $ownerEmail = $entreprise->user?->email ?? $entreprise->email;

        if ($ownerEmail && strtolower($ownerEmail) === strtolower($email)) {
            throw new InvalidArgumentException('Impossible d\'inviter le propriétaire de l\'entreprise.');
        }
    }
}
