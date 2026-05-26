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
    ];

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
                if (!$user || !$user->pushSubscriptions()->exists()) {
                    return;
                }

                $pushCategory = self::TYPE_TO_PUSH_CATEGORY[$notification->type] ?? 'general';
                $lien = $notification->lien;
                $pushUrl = $lien ? (str_starts_with($lien, 'http') ? $lien : config('app.url') . $lien) : null;

                $pushService = new \App\Services\PushNotificationService();
                $pushService->sendToUser($user, $notification->titre, $notification->message, $pushCategory, $pushUrl);
            } catch (\Exception $e) {
                \Log::warning("Erreur envoi push notification : " . $e->getMessage());
            }
        });
    }

    /**
     * Créer une notification (in-app + push automatique via le hook created)
     */
    public static function creer(
        int $userId,
        string $type,
        string $titre,
        string $message,
        ?string $lien = null,
        ?array $donnees = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'type' => $type,
            'titre' => $titre,
            'message' => $message,
            'lien' => $lien,
            'donnees' => $donnees,
        ]);
    }
}
