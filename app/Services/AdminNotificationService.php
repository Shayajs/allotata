<?php

namespace App\Services;

use App\Models\AdminConversation;
use App\Models\AdminMessage;
use App\Models\Contact;
use App\Models\Entreprise;
use App\Models\ErrorLog;
use App\Models\GdprRequest;
use App\Models\Notification;
use App\Models\SiteAudit;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Notifications in-app / push / email pour les administrateurs plateforme.
 */
class AdminNotificationService
{
    public function __construct(
        private UserNotificationService $userNotifications,
        private NotificationPreferenceService $preferences,
    ) {}

    /**
     * @param  callable(User): bool|null  $adminFilter
     */
    public function notifyAllAdmins(
        string $type,
        string $titre,
        string $message,
        ?string $lien = null,
        ?array $donnees = null,
        ?callable $adminFilter = null,
        ?int $excludeUserId = null,
        bool $alwaysInApp = false,
    ): void {
        foreach ($this->getAdmins() as $admin) {
            if ($excludeUserId && $admin->id === $excludeUserId) {
                continue;
            }
            if ($adminFilter && ! $adminFilter($admin)) {
                continue;
            }

            if ($alwaysInApp && ! $this->preferences->wants($admin, NotificationPreferenceService::CATEGORY_ADMIN_OPS, NotificationPreferenceService::CHANNEL_IN_APP)) {
                Notification::create([
                    'user_id' => $admin->id,
                    'type' => $type,
                    'titre' => $titre,
                    'message' => $message,
                    'lien' => $lien,
                    'donnees' => $donnees,
                ]);
                if ($this->preferences->wants($admin, NotificationPreferenceService::CATEGORY_ADMIN_OPS, NotificationPreferenceService::CHANNEL_PUSH)) {
                    $this->sendPushToAdmin($admin, $type, $titre, $message, $lien);
                }
                continue;
            }

            $this->userNotifications->notify(
                $admin,
                NotificationPreferenceService::CATEGORY_ADMIN_OPS,
                $type,
                $titre,
                $message,
                $lien,
                $donnees,
            );
        }
    }

    public function notifyNewTicket(Ticket $ticket): void
    {
        $ticket->loadMissing('user');
        $userName = $ticket->user?->name ?? 'Utilisateur';
        $urgent = $ticket->priorite === 'urgente';

        $this->notifyAllAdmins(
            'admin_ticket_nouveau',
            $urgent ? 'Ticket urgent' : 'Nouveau ticket support',
            "{$userName} — {$ticket->sujet} ({$ticket->numero_ticket})",
            route('admin.tickets.show', $ticket),
            [
                'ticket_id' => $ticket->id,
                'numero_ticket' => $ticket->numero_ticket,
                'priorite' => $ticket->priorite,
                'urgent' => $urgent,
            ],
        );
    }

    public function notifyTicketUserReply(Ticket $ticket, TicketMessage $ticketMessage): void
    {
        $author = Auth::user();
        if (! $author || $author->is_admin || $ticketMessage->est_interne) {
            return;
        }

        $ticket->loadMissing('user');
        $preview = Str::limit(strip_tags($ticketMessage->message), 120);

        $this->notifyAllAdmins(
            'admin_ticket_reponse',
            'Réponse sur un ticket',
            ($ticket->user?->name ?? 'Client')." — {$ticket->numero_ticket} : {$preview}",
            route('admin.tickets.show', $ticket),
            [
                'ticket_id' => $ticket->id,
                'message_id' => $ticketMessage->id,
                'urgent' => $ticket->priorite === 'urgente',
            ],
            excludeUserId: $ticketMessage->user_id,
        );
    }

    public function notifyNewContact(Contact $contact): void
    {
        $this->notifyAllAdmins(
            'admin_contact',
            'Nouvelle demande de contact',
            "{$contact->nom} — {$contact->sujet}",
            route('admin.contacts.show', $contact),
            [
                'contact_id' => $contact->id,
                'email' => $contact->email,
            ],
        );
    }

    public function notifyInternalMessage(AdminMessage $message, AdminConversation $conversation): void
    {
        $message->loadMissing('user');
        $sender = $message->user;
        if (! $sender) {
            return;
        }

        $preview = $message->contenu
            ? Str::limit(strip_tags($message->contenu), 100)
            : ($message->type === 'image' ? 'Image' : 'Fichier');

        $conversation->loadMissing('members');
        $memberIds = $conversation->members->pluck('id');

        foreach ($this->getAdmins() as $admin) {
            if ($admin->id === $sender->id || ! $memberIds->contains($admin->id)) {
                continue;
            }

            $this->userNotifications->notify(
                $admin,
                NotificationPreferenceService::CATEGORY_ADMIN_OPS,
                'admin_message_interne',
                'Messagerie interne',
                "{$sender->name} : {$preview}",
                route('admin.messagerie-interne.show', $conversation),
                [
                    'conversation_id' => $conversation->id,
                    'message_id' => $message->id,
                ],
            );
        }
    }

    public function notifyAuditCompleted(SiteAudit $audit): void
    {
        $audit->refresh();
        $criticalCount = $this->countCriticalItems($audit->resultats ?? []);
        $note = (int) ($audit->note_globale ?? 0);
        $alert = $note < 50 || $criticalCount > 0;

        $titre = $alert ? 'Audit : problèmes détectés' : 'Audit du site terminé';
        $message = $alert
            ? "Note {$note}/100 — {$criticalCount} point(s) critique(s). Consultez le rapport."
            : "Note globale : {$note}/100. Aucun point critique majeur.";

        $this->notifyAllAdmins(
            $alert ? 'admin_audit_alerte' : 'admin_audit_termine',
            $titre,
            $message,
            '/admin/audits/'.$audit->id,
            [
                'audit_id' => $audit->id,
                'note' => $note,
                'critical_count' => $criticalCount,
                'urgent' => $alert,
            ],
            excludeUserId: $audit->user_id,
        );

        // Le lanceur reçoit aussi une notif personnelle si exclu ci-dessus
        if ($audit->user_id) {
            $launcher = User::find($audit->user_id);
            if ($launcher?->is_admin) {
                Notification::creer(
                    $audit->user_id,
                    $alert ? 'admin_audit_alerte' : 'audit',
                    $titre,
                    $message,
                    '/admin/audits/'.$audit->id,
                    ['audit_id' => $audit->id, 'note' => $note],
                );
            }
        }
    }

    public function notifyAuditFailed(SiteAudit $audit, string $errorMessage): void
    {
        $this->notifyAllAdmins(
            'admin_audit_echec',
            'Audit du site échoué',
            'Erreur fatale lors de l\'audit. '.$errorMessage,
            '/admin/audits/'.$audit->id,
            ['audit_id' => $audit->id, 'urgent' => true],
        );
    }

    public function notifyErrorLog(ErrorLog $log): void
    {
        if ($this->wasErrorAlreadyNotified($log->id)) {
            return;
        }

        $level = strtolower((string) $log->level);
        $ignoredLevels = ['debug', 'info', 'notice'];

        // Ne pas notifier les logs purement informatifs.
        if (in_array($level, $ignoredLevels, true)) {
            return;
        }

        $urgentLevels = ['error', 'critical', 'alert', 'emergency', 'exception'];
        $isUrgent = in_array($level, $urgentLevels, true);
        $titleByLevel = [
            'warning' => 'Avertissement application',
            'error' => 'Erreur application',
            'critical' => 'Erreur critique application',
            'alert' => 'Alerte application',
            'emergency' => 'Urgence application',
            'exception' => 'Exception application',
        ];

        $preview = Str::limit($log->message, 150);
        $lien = route('admin.errors.index');
        $donnees = [
            'error_log_id' => $log->id,
            'level' => $level,
            'url' => $log->url,
            'urgent' => $isUrgent,
        ];

        foreach ($this->getAdmins() as $admin) {
            $shouldPush = (bool) $admin->notifications_erreurs_actives
                && $this->preferences->wants($admin, NotificationPreferenceService::CATEGORY_ADMIN_OPS, NotificationPreferenceService::CHANNEL_PUSH);

            $notification = new Notification([
                'user_id' => $admin->id,
                'type' => 'admin_erreur',
                'titre' => $titleByLevel[$level] ?? 'Erreur application',
                'message' => $preview,
                'lien' => $lien,
                'donnees' => $donnees,
            ]);
            $notification->skipPush = ! $shouldPush;
            $notification->save();
        }
    }

    public function notifyGdprDeletionRequest(GdprRequest $request): void
    {
        $request->loadMissing('user');
        $userName = $request->user?->name ?? 'Utilisateur #'.$request->user_id;

        $this->notifyAllAdmins(
            'admin_gdpr',
            'Demande RGPD — suppression',
            "{$userName} a demandé la suppression de son compte.",
            route('admin.gdpr.index'),
            [
                'gdpr_request_id' => $request->id,
                'user_id' => $request->user_id,
                'urgent' => true,
            ],
        );
    }

    public function notifyEntreprisePendingValidation(Entreprise $entreprise): void
    {
        $entreprise->loadMissing('user');

        $this->notifyAllAdmins(
            'admin_entreprise_validation',
            'Entreprise à valider',
            "« {$entreprise->nom} » ({$entreprise->user?->name}) attend votre validation.",
            route('admin.entreprises.show', $entreprise),
            [
                'entreprise_id' => $entreprise->id,
                'slug' => $entreprise->slug,
            ],
        );
    }

    public function notifyEntrepriseModified(Entreprise $entreprise): void
    {
        $entreprise->loadMissing('user');

        $this->notifyAllAdmins(
            'admin_entreprise_modifiee',
            'Modification d\'entreprise à valider',
            "« {$entreprise->nom} » a soumis un changement sensible (SIREN, média, vidéo ou site). Le reste de la fiche est déjà à jour.",
            route('admin.entreprises.show', $entreprise),
            [
                'entreprise_id' => $entreprise->id,
                'slug' => $entreprise->slug,
            ],
        );
    }

    /** @return \Illuminate\Support\Collection<int, User> */
    private function getAdmins()
    {
        return User::query()->where('is_admin', true)->get();
    }

    private function sendPushToAdmin(User $admin, string $type, string $titre, string $message, ?string $lien): void
    {
        $pushKey = $this->preferences->pushKeyForCategory(NotificationPreferenceService::CATEGORY_ADMIN_OPS);
        $url = $lien ? (str_starts_with($lien, 'http') ? $lien : config('app.url').$lien) : null;
        app(PushNotificationService::class)->sendToUser($admin, $titre, $message, $pushKey, $url);
    }

    private function countCriticalItems(array $resultats): int
    {
        $count = 0;
        foreach ($resultats as $checker) {
            if (! is_array($checker)) {
                continue;
            }
            foreach ($checker['items'] ?? [] as $item) {
                if (is_array($item) && ($item['severity'] ?? '') === 'critical') {
                    $count++;
                }
            }
        }

        return $count;
    }

    private function wasErrorAlreadyNotified(int $errorLogId): bool
    {
        return Notification::query()
            ->where('type', 'admin_erreur')
            ->where('created_at', '>=', now()->subHour())
            ->where('donnees->error_log_id', $errorLogId)
            ->exists();
    }
}
