<?php

namespace App\Console\Commands;

use App\Services\MembresManageService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class MembresManageCommand extends Command
{
    protected $signature = 'membres:manage
        {action : help|list-entreprises|list|view|add|create|invite|archive|restore|role|admin|password|invitations|cancel-invite}
        {--entreprise= : Slug ou ID de l\'entreprise}
        {--membre= : ID entreprise_membres}
        {--user= : ID utilisateur}
        {--email= : Adresse email}
        {--role=membre : administrateur ou membre}
        {--password= : Mot de passe (create / password)}
        {--name= : Nom affiché (create)}
        {--surname= : Prénom (create, optionnel)}
        {--invitation= : ID invitation (cancel-invite)}
        {--inactive : Inclure les membres archivés (list)}
        {--all : Lister les utilisateurs inscrits (site entier) au lieu d\'une entreprise}
        {--query= : Recherche nom / email (avec --all)}
        {--limit=50 : Nombre max de résultats (avec --all)}
        {--all-invitations : Toutes les invitations, pas seulement en attente}
        {--force : Ajout direct sans invitation / sans confirmation}
        {--no-email : Ne pas envoyer l\'email d\'invitation}
        {--no-verify : Ne pas auto-vérifier l\'email (create)}';

    protected $description = 'Gestion CLI des membres d\'entreprise (secours si le GUI est indisponible)';

    public function __construct(
        private readonly MembresManageService $service,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            return match ($this->argument('action')) {
                'help' => $this->showHelp(),
                'list-entreprises' => $this->handleListEntreprises(),
                'list' => $this->handleList(),
                'view' => $this->handleView(),
                'add' => $this->handleAdd(),
                'create' => $this->handleCreate(),
                'invite' => $this->handleInvite(),
                'archive' => $this->handleArchive(),
                'restore' => $this->handleRestore(),
                'role' => $this->handleRole(),
                'admin' => $this->handleAdmin(),
                'password' => $this->handlePassword(),
                'invitations' => $this->handleInvitations(),
                'cancel-invite' => $this->handleCancelInvite(),
                default => $this->showHelp(),
            };
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return 1;
        }
    }

    private function showHelp(): int
    {
        $this->newLine();
        $this->line('<fg=white;options=bold>  ╔══════════════════════════════════════════════════════════════╗</>');
        $this->line('<fg=white;options=bold>  ║         membres:manage — Gestion CLI des membres            ║</>');
        $this->line('<fg=white;options=bold>  ╚══════════════════════════════════════════════════════════════╝</>');
        $this->newLine();

        $this->line('<fg=cyan;options=bold>  CONSULTATION</>');
        $this->line('  <fg=green>help</>              Afficher cette aide');
        $this->line('  <fg=green>list-entreprises</>  Lister les entreprises (slug / ID)');
        $this->line('  <fg=green>list</>              Membres d\'une entreprise           <fg=gray>--entreprise= [--inactive]</>');
        $this->line('  <fg=green>list --all</>        Utilisateurs inscrits (site entier) <fg=gray>[--query=] [--email=] [--limit=]</>');
        $this->line('  <fg=green>view</>              Détail membre ou utilisateur        <fg=gray>--email=|--user= [--entreprise=]</>');
        $this->line('  <fg=green>invitations</>       Invitations en attente              <fg=gray>--entreprise= [--all-invitations]</>');
        $this->newLine();

        $this->line('<fg=cyan;options=bold>  AJOUT</>');
        $this->line('  <fg=green>invite</>            Envoyer une invitation              <fg=gray>--entreprise= --email= --role= [--no-email]</>');
        $this->line('  <fg=green>add</>               Ajouter (invitation par défaut)     <fg=gray>--force = ajout direct sans invitation</>');
        $this->line('  <fg=green>create</>            Créer compte + membre direct        <fg=gray>--entreprise= --email= --password= --name=</>');
        $this->newLine();

        $this->line('<fg=cyan;options=bold>  MODIFICATION</>');
        $this->line('  <fg=green>role</>              Changer le rôle                     <fg=gray>--role=administrateur|membre</>');
        $this->line('  <fg=green>admin</>             Raccourci → rôle administrateur');
        $this->line('  <fg=green>password</>          Changer le mot de passe utilisateur <fg=gray>--password=</>');
        $this->line('  <fg=green>archive</>           Archiver (désactiver) un membre');
        $this->line('  <fg=green>restore</>           Réactiver un membre archivé');
        $this->line('  <fg=green>cancel-invite</>     Annuler une invitation              <fg=gray>--invitation=ID</>');
        $this->newLine();

        $this->line('<fg=yellow;options=bold>  EXEMPLES</>');
        $this->line('  <fg=gray>php artisan membres:manage list-entreprises</>');
        $this->line('  <fg=gray>php artisan membres:manage list --entreprise=mon-salon</>');
        $this->line('  <fg=gray>php artisan membres:manage list --all --query=dupont</>');
        $this->line('  <fg=gray>php artisan membres:manage list --all --email=jean@ex.fr</>');
        $this->line('  <fg=gray>php artisan membres:manage view --email=jean@ex.fr</>');
        $this->line('  <fg=gray>php artisan membres:manage view --entreprise=1 --membre=12</>');
        $this->line('  <fg=gray>php artisan membres:manage invite --entreprise=mon-salon --email=jean@ex.fr --role=membre</>');
        $this->line('  <fg=gray>php artisan membres:manage add --entreprise=mon-salon --email=jean@ex.fr --role=administrateur --force</>');
        $this->line('  <fg=gray>php artisan membres:manage create --entreprise=1 --email=nouveau@ex.fr --password=secret123 --name="Jean Dupont"</>');
        $this->line('  <fg=gray>php artisan membres:manage admin --entreprise=mon-salon --email=jean@ex.fr</>');
        $this->line('  <fg=gray>php artisan membres:manage archive --entreprise=mon-salon --membre=5 --force</>');
        $this->line('  <fg=gray>php artisan membres:manage password --email=jean@ex.fr --password=nouveau123 --force</>');
        $this->newLine();

        return 0;
    }

    private function handleListEntreprises(): int
    {
        $entreprises = $this->service->listEntreprises();

        if ($entreprises->isEmpty()) {
            $this->warn('Aucune entreprise trouvée.');

            return 0;
        }

        $rows = $entreprises->map(fn ($e) => [
            $e->id,
            $e->slug,
            $e->nom,
            $e->user?->email ?? $e->email ?? '—',
        ])->all();

        $this->table(['ID', 'Slug', 'Nom', 'Propriétaire'], $rows);
        $this->info("Total : {$entreprises->count()} entreprise(s)");

        return 0;
    }

    private function handleList(): int
    {
        if ($this->option('all')) {
            return $this->handleListAllUsers();
        }

        $entreprise = $this->requireEntreprise();
        $membres = $this->service->listMembres($entreprise, $this->option('inactive'));

        $this->info("Membres — {$entreprise->nom} ({$entreprise->slug})");

        if ($membres->isEmpty()) {
            $this->warn('Aucun membre trouvé.');

            return 0;
        }

        $rows = $membres->map(function ($m) use ($entreprise) {
            $data = $this->service->memberToArray($m, $entreprise);

            return [
                $data['membre_id'],
                $data['user_id'],
                $data['email'],
                $data['name'],
                $data['role'],
                $data['est_actif'] ? 'actif' : 'archivé',
                $data['est_proprietaire'] ? 'oui' : 'non',
            ];
        })->all();

        $this->table(['Membre', 'User', 'Email', 'Nom', 'Rôle', 'Statut', 'Proprio.'], $rows);
        $this->info("Total : {$membres->count()} membre(s)");

        return 0;
    }

    private function handleListAllUsers(): int
    {
        $limit = (int) ($this->option('limit') ?: 50);
        $query = $this->option('query');
        $email = $this->option('email');
        $userId = $this->option('user') ? (int) $this->option('user') : null;
        $includeInactive = $this->option('inactive');

        if (!$query && !$email && !$userId) {
            $this->comment('Astuce : sans filtre, les '.$limit.' derniers inscrits sont affichés. Utilisez --query= ou --email= pour cibler.');
        }

        $users = $this->service->searchUsers($query, $email, $userId, $limit);

        $this->info('Utilisateurs inscrits sur Allotata');

        if ($users->isEmpty()) {
            $this->warn('Aucun utilisateur trouvé.');

            return 0;
        }

        $rows = $users->map(function ($user) use ($includeInactive) {
            $memberships = $this->service->getUserMemberships($user, $includeInactive);
            $activeCount = collect($memberships)->where('est_actif', true)->count();

            return [
                $user->id,
                $user->email,
                trim($user->name.' '.($user->surname ?? '')),
                $activeCount,
                count($memberships),
                $this->service->formatMembershipsForList($user, $includeInactive),
            ];
        })->all();

        $this->table(['User', 'Email', 'Nom', 'Actifs', 'Total', 'Entreprises (slug, rôle)'], $rows);
        $this->info("Affichés : {$users->count()} utilisateur(s) (limite {$limit})");
        $this->comment('Détail complet : php artisan membres:manage view --email=... ou --user=ID');

        return 0;
    }

    private function handleView(): int
    {
        if (!$this->option('entreprise')) {
            return $this->handleViewUser();
        }

        $entreprise = $this->requireEntreprise();
        $membre = $this->resolveMembreForEntreprise($entreprise);
        $data = $this->service->memberToArray($membre, $entreprise);
        $user = $membre->user;

        $this->info("Membre #{$membre->id} — {$entreprise->nom}");
        $this->newLine();

        foreach ($data as $key => $value) {
            $display = is_bool($value) ? ($value ? 'true' : 'false') : ($value ?? '—');
            $this->line("  <fg=cyan>{$key}</> : {$display}");
        }

        if ($user) {
            $this->newLine();
            $this->line('  <fg=yellow>Compte utilisateur</>');
            $this->line("  email_verified : ".($user->email_verified_at ? 'oui' : 'non'));
            $this->line("  est_gerant     : ".($user->est_gerant ? 'oui' : 'non'));
            $this->line("  statut_compte  : ".($user->statut_compte ?? '—'));
            $this->line("  telephone      : ".($user->telephone ?? '—'));
        }

        $reservations = $membre->reservations()->count();
        $this->newLine();
        $this->line("  Réservations liées : {$reservations}");

        return 0;
    }

    private function handleViewUser(): int
    {
        $user = $this->service->resolveUser(
            $this->option('user') ? (int) $this->option('user') : null,
            $this->option('email'),
        );

        $data = $this->service->userToArray($user);

        $this->info("Utilisateur #{$user->id} — {$user->email}");
        $this->newLine();

        foreach ($data as $key => $value) {
            $display = is_bool($value) ? ($value ? 'oui' : 'non') : ($value ?? '—');
            $this->line("  <fg=cyan>{$key}</> : {$display}");
        }

        $memberships = $this->service->getUserMemberships($user, $this->option('inactive'));

        $this->newLine();
        $this->line('  <fg=yellow>Appartenance(s) entreprise</>');

        if ($memberships === []) {
            $this->line('  <fg=gray>Aucune — cet utilisateur n\'est membre d\'aucune entreprise.</>');

            $owned = \App\Models\Entreprise::where('user_id', $user->id)->get(['id', 'slug', 'nom']);
            if ($owned->isNotEmpty()) {
                $this->newLine();
                $this->line('  <fg=yellow>Entreprise(s) dont il est propriétaire</>');
                foreach ($owned as $e) {
                    $this->line("    [{$e->id}] {$e->nom} ({$e->slug}) — propriétaire");
                }
            }
        } else {
            $this->table(
                ['Membre', 'Entreprise', 'Slug', 'Rôle', 'Statut', 'Proprio.'],
                collect($memberships)->map(fn ($m) => [
                    $m['membre_id'],
                    $m['nom'],
                    $m['slug'],
                    $m['role'],
                    $m['est_actif'] ? 'actif' : 'archivé',
                    $m['est_proprietaire'] ? 'oui' : 'non',
                ])->all(),
            );
        }

        $pendingInvites = \App\Models\EntrepriseInvitation::where('email', $user->email)
            ->whereIn('statut', ['en_attente_compte', 'en_attente_acceptation'])
            ->with('entreprise')
            ->get();

        if ($pendingInvites->isNotEmpty()) {
            $this->newLine();
            $this->line('  <fg=yellow>Invitations en attente</>');
            foreach ($pendingInvites as $inv) {
                $this->line("    #{$inv->id} → {$inv->entreprise?->nom} ({$inv->entreprise?->slug}) — {$inv->role} [{$inv->statut}]");
            }
        }

        return 0;
    }

    private function handleAdd(): int
    {
        if ($this->option('force')) {
            return $this->handleAddDirect();
        }

        return $this->handleInvite();
    }

    private function handleAddDirect(): int
    {
        $entreprise = $this->requireEntreprise();
        $email = $this->requireEmail();
        $role = $this->option('role') ?? 'membre';

        $user = $this->service->resolveUser(null, $email);
        $membre = $this->service->addMemberDirect($entreprise, $user, $role);

        $this->info("Membre ajouté directement : #{$membre->id} ({$user->email}) — rôle {$role}");

        return 0;
    }

    private function handleInvite(): int
    {
        $entreprise = $this->requireEntreprise();
        $email = $this->requireEmail();
        $role = $this->option('role') ?? 'membre';
        $sendEmail = !$this->option('no-email');

        $invitation = $this->service->inviteMember($entreprise, $email, $role, $sendEmail);

        $this->info("Invitation créée #{$invitation->id} pour {$email} (rôle : {$role}, statut : {$invitation->statut})");

        if (!$sendEmail) {
            $this->comment('Email non envoyé (--no-email).');
        }

        return 0;
    }

    private function handleCreate(): int
    {
        $entreprise = $this->requireEntreprise();
        $email = $this->requireEmail();
        $password = $this->option('password');

        if (!$password) {
            throw new InvalidArgumentException('Précisez --password= pour créer un compte.');
        }

        $name = $this->option('name') ?: explode('@', $email)[0];

        if (!$this->option('force') && !$this->confirm("Créer le compte {$email} et l'ajouter à « {$entreprise->nom} » ?", true)) {
            $this->comment('Annulé.');

            return 0;
        }

        $role = $this->option('role') ?? 'membre';
        $result = $this->service->createUserAndAdd(
            $entreprise,
            $email,
            $password,
            $name,
            $role,
            $this->option('surname'),
            !$this->option('no-verify'),
        );

        $this->info("Compte créé (user #{$result['user']->id}) + membre #{$result['membre']->id} — rôle {$role}");

        return 0;
    }

    private function handleArchive(): int
    {
        $entreprise = $this->requireEntreprise();
        $membre = $this->resolveMembreForEntreprise($entreprise);

        if (!$this->option('force') && !$this->confirm("Archiver le membre #{$membre->id} ({$membre->user?->email}) ?", false)) {
            $this->comment('Annulé.');

            return 0;
        }

        $this->service->archiveMember($entreprise, $membre);
        $this->info("Membre #{$membre->id} archivé.");

        return 0;
    }

    private function handleRestore(): int
    {
        $entreprise = $this->requireEntreprise();
        $membre = $this->resolveMembreForEntreprise($entreprise);

        $this->service->restoreMember($entreprise, $membre);
        $this->info("Membre #{$membre->id} réactivé.");

        return 0;
    }

    private function handleRole(): int
    {
        $entreprise = $this->requireEntreprise();
        $membre = $this->resolveMembreForEntreprise($entreprise);
        $role = $this->option('role') ?? 'membre';

        $this->service->setRole($entreprise, $membre, $role);
        $this->info("Rôle du membre #{$membre->id} mis à jour : {$role}");

        return 0;
    }

    private function handleAdmin(): int
    {
        $this->input->setOption('role', 'administrateur');

        return $this->handleRole();
    }

    private function handlePassword(): int
    {
        $password = $this->option('password');

        if (!$password) {
            throw new InvalidArgumentException('Précisez --password=.');
        }

        $user = $this->service->resolveUser(
            $this->option('user') ? (int) $this->option('user') : null,
            $this->option('email'),
        );

        if (!$this->option('force') && !$this->confirm("Changer le mot de passe de {$user->email} ?", false)) {
            $this->comment('Annulé.');

            return 0;
        }

        $this->service->changePassword($user, $password);
        $this->info("Mot de passe mis à jour pour {$user->email} (user #{$user->id}).");

        return 0;
    }

    private function handleInvitations(): int
    {
        $entreprise = $this->requireEntreprise();
        $pendingOnly = !$this->option('all-invitations');
        $invitations = $this->service->listInvitations($entreprise, $pendingOnly);

        $this->info("Invitations — {$entreprise->nom}");

        if ($invitations->isEmpty()) {
            $this->warn('Aucune invitation.');

            return 0;
        }

        $rows = $invitations->map(fn ($i) => [
            $i->id,
            $i->email,
            $i->role,
            $i->statut,
            $i->expire_at?->format('Y-m-d'),
            $i->user_id ?? '—',
        ])->all();

        $this->table(['ID', 'Email', 'Rôle', 'Statut', 'Expire', 'User'], $rows);

        return 0;
    }

    private function handleCancelInvite(): int
    {
        $entreprise = $this->requireEntreprise();
        $invitationId = $this->option('invitation');

        if (!$invitationId) {
            throw new InvalidArgumentException('Précisez --invitation=ID.');
        }

        if (!$this->option('force') && !$this->confirm("Annuler l'invitation #{$invitationId} ?", false)) {
            $this->comment('Annulé.');

            return 0;
        }

        $invitation = $this->service->cancelInvitation($entreprise, (int) $invitationId);
        $this->info("Invitation #{$invitation->id} annulée ({$invitation->email}).");

        return 0;
    }

    private function requireEntreprise(): \App\Models\Entreprise
    {
        $ref = $this->option('entreprise');

        if (!$ref) {
            throw new InvalidArgumentException('Précisez --entreprise= (slug ou ID). Utilisez list-entreprises pour la liste.');
        }

        return $this->service->resolveEntreprise($ref);
    }

    private function requireEmail(): string
    {
        $email = $this->option('email');

        if (!$email) {
            throw new InvalidArgumentException('Précisez --email=.');
        }

        return strtolower(trim($email));
    }

    private function resolveMembreForEntreprise(\App\Models\Entreprise $entreprise): \App\Models\EntrepriseMembre
    {
        return $this->service->resolveMembre(
            $entreprise,
            $this->option('membre') ? (int) $this->option('membre') : null,
            $this->option('email'),
            $this->option('user') ? (int) $this->option('user') : null,
        );
    }
}
