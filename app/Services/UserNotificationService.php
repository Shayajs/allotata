<?php

namespace App\Services;

use App\Models\Echeance;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserNotificationService
{
    public function __construct(
        private NotificationPreferenceService $preferences,
        private PushNotificationService $push,
    ) {}

    /**
     * Envoie une notification sur les canaux activés par l'utilisateur.
     *
     * @param  callable|null  $emailCallback  Appelé si le canal email est activé
     */
    public function notify(
        User $user,
        string $category,
        string $type,
        string $titre,
        string $message,
        ?string $lien = null,
        ?array $donnees = null,
        ?callable $emailCallback = null,
    ): ?Notification {
        $prefs = $this->preferences->forUser($user, $category);
        $notification = null;

        if ($prefs[NotificationPreferenceService::CHANNEL_IN_APP]) {
            $notification = Notification::create([
                'user_id' => $user->id,
                'type' => $type,
                'titre' => $titre,
                'message' => $message,
                'lien' => $lien,
                'donnees' => $donnees,
            ]);
            $notification->skipPush = ! $prefs[NotificationPreferenceService::CHANNEL_PUSH];
        } elseif ($prefs[NotificationPreferenceService::CHANNEL_PUSH] && $user->pushSubscriptions()->exists()) {
            $this->sendPushOnly($user, $category, $titre, $message, $lien);
        }

        if ($prefs[NotificationPreferenceService::CHANNEL_EMAIL] && $emailCallback) {
            try {
                $emailCallback();
            } catch (\Throwable $e) {
                Log::error('UserNotificationService: email failed', [
                    'user_id' => $user->id,
                    'category' => $category,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $notification;
    }

    public function notifyNewMessage(User $recipient, string $titre, string $message, string $lien, array $donnees, ?callable $emailCallback): ?Notification
    {
        return $this->notify(
            $recipient,
            NotificationPreferenceService::CATEGORY_MESSAGE,
            'nouveau_message',
            $titre,
            $message,
            $lien,
            $donnees,
            $emailCallback,
        );
    }

    public function notifyPaymentStatus(User $user, Echeance $echeance, string $statut, ?string $detail = null, ?callable $emailCallback = null): ?Notification
    {
        $montant = number_format((float) ($echeance->montant_final ?? $echeance->montant_du ?? 0), 2, ',', ' ');
        $libelle = $echeance->libelle();

        [$type, $titre, $message] = match ($statut) {
            'paye', 'succeeded' => [
                'paiement_recu',
                'Paiement confirmé',
                "Votre paiement de {$montant} € pour « {$libelle} » a bien été enregistré.",
            ],
            'echec', 'failed' => [
                'paiement_echec',
                'Échec de paiement',
                "Le prélèvement de {$montant} € pour « {$libelle} » a échoué.".($detail ? " {$detail}" : ' Mettez à jour votre moyen de paiement.'),
            ],
            'requires_action', '3ds' => [
                'paiement_3ds',
                'Action requise — paiement',
                "Votre banque demande une validation pour « {$libelle} » ({$montant} €). Finalisez le paiement depuis vos paramètres.",
            ],
            'en_attente' => [
                'paiement_en_attente',
                'Paiement en attente',
                "Le paiement de {$montant} € pour « {$libelle} » est en cours de traitement.",
            ],
            default => [
                'paiement',
                'Mise à jour paiement',
                "Statut du paiement pour « {$libelle} » : {$statut}.",
            ],
        };

        $lien = route('settings.index', ['tab' => 'subscription']);

        return $this->notify(
            $user,
            NotificationPreferenceService::CATEGORY_PAYMENT,
            $type,
            $titre,
            $message,
            $lien,
            [
                'payment_statut' => $statut,
                'echeance_id' => $echeance->id,
                'montant' => (float) ($echeance->montant_final ?? $echeance->montant_du ?? 0),
                'libelle' => $libelle,
            ],
            $emailCallback,
        );
    }

    private function sendPushOnly(User $user, string $category, string $titre, string $message, ?string $lien): void
    {
        $pushKey = $this->preferences->pushKeyForCategory($category);
        $pushUrl = $lien ? (str_starts_with($lien, 'http') ? $lien : config('app.url').$lien) : null;
        $this->push->sendToUser($user, $titre, $message, $pushKey, $pushUrl);
    }
}
