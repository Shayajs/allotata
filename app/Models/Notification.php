<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'titre',
        'message',
        'lien',
        'est_lue',
        'lue_at',
        'donnees',
    ];

    protected $casts = [
        'est_lue' => 'boolean',
        'lue_at' => 'datetime',
        'donnees' => 'array',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Marquer comme lue
     */
    public function marquerCommeLue(): void
    {
        if (!$this->est_lue) {
            $this->update([
                'est_lue' => true,
                'lue_at' => now(),
            ]);
        }
    }

    /**
     * Mapping des types de notification vers les catégories push
     */
    private const TYPE_TO_PUSH_CATEGORY = [
        'reservation' => 'reservation',
        'reservation_confirmee' => 'reservation',
        'reservation_annulee' => 'reservation',
        'nouvelle_reservation' => 'reservation',
        'paiement' => 'paiement',
        'paiement_recu' => 'paiement',
        'rappel_essai' => 'paiement',
        'expiration_essai' => 'paiement',
        'relance_essai' => 'paiement',
        'message' => 'message',
        'nouveau_message' => 'message',
        'rappel' => 'rappel',
        'rappel_rdv' => 'rappel',
        'promotion' => 'promotion',
        'offre' => 'promotion',
        'mise_a_jour' => 'mise_a_jour',
        'devis' => 'paiement',
        'devis_accepte' => 'paiement',
        'devis_refuse' => 'paiement',
        'commande' => 'reservation',
        'invitation_membre' => 'mise_a_jour',
        'admin_push' => 'general',
        'admin_ticket_nouveau' => 'admin',
        'admin_ticket_reponse' => 'admin',
        'admin_contact' => 'admin',
        'admin_message_interne' => 'admin',
        'admin_audit_alerte' => 'admin',
        'admin_audit_termine' => 'admin',
        'admin_audit_echec' => 'admin',
        'admin_erreur' => 'admin',
        'admin_gdpr' => 'admin',
        'admin_entreprise_validation' => 'admin',
        'admin_entreprise_modifiee' => 'admin',
        'audit' => 'admin',
    ];

    public function isUrgent(): bool
    {
        return (bool) ($this->donnees['urgent'] ?? false);
    }

    public function isAdminOps(): bool
    {
        return str_starts_with($this->type, 'admin_') || $this->type === 'audit';
    }

    /**
     * Permet de desactiver le push pour une instance specifique.
     * Utilise par AdminController qui envoie le push separement.
     */
    public bool $skipPush = false;

    protected static function booted(): void
    {
        static::created(function (self $notification) {
            if ($notification->skipPush) {
                return;
            }

            try {
                $user = $notification->user ?? User::find($notification->user_id);
                if (! $user || ! $user->pushSubscriptions()->exists()) {
                    return;
                }

                $prefs = app(\App\Services\NotificationPreferenceService::class);
                $category = $prefs->categoryFromNotificationType($notification->type);
                if (! $prefs->wants($user, $category, \App\Services\NotificationPreferenceService::CHANNEL_PUSH)) {
                    return;
                }

                $pushCategory = self::TYPE_TO_PUSH_CATEGORY[$notification->type] ?? 'general';
                $lien = $notification->lien;
                $pushUrl = $lien ? (str_starts_with($lien, 'http') ? $lien : config('app.url').$lien) : null;

                app(\App\Services\PushNotificationService::class)
                    ->sendToUser($user, $notification->titre, $notification->message, $pushCategory, $pushUrl);
            } catch (\Exception $e) {
                \Log::warning('Erreur envoi push notification : '.$e->getMessage());
            }
        });
    }

    /**
     * Créer une notification en respectant les préférences canal (in-app, push, email optionnel).
     */
    public static function creer(
        int $userId,
        string $type,
        string $titre,
        string $message,
        ?string $lien = null,
        ?array $donnees = null,
        ?callable $emailCallback = null,
    ): ?self {
        $user = User::find($userId);
        if (! $user) {
            return null;
        }

        $prefs = app(\App\Services\NotificationPreferenceService::class);
        $category = $prefs->categoryFromNotificationType($type);

        return app(\App\Services\UserNotificationService::class)->notify(
            $user,
            $category,
            $type,
            $titre,
            $message,
            $lien,
            $donnees,
            $emailCallback,
        );
    }

    /** Libellé lisible du statut paiement (donnees.payment_statut). */
    public function paymentStatutLabel(): ?string
    {
        $statut = $this->donnees['payment_statut'] ?? null;
        if (! $statut) {
            return null;
        }

        return match ($statut) {
            'paye', 'succeeded' => 'Payé',
            'echec', 'failed' => 'Échec',
            'requires_action', '3ds' => 'Action requise',
            'en_attente' => 'En attente',
            'a_payer' => 'À payer',
            default => ucfirst((string) $statut),
        };
    }

    public function paymentStatutBadgeClass(): string
    {
        $statut = $this->donnees['payment_statut'] ?? '';

        return match ($statut) {
            'paye', 'succeeded' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            'echec', 'failed' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
            'requires_action', '3ds' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
            'en_attente', 'a_payer' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
            default => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
        };
    }
}
