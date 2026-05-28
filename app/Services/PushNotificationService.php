<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushNotificationService
{
    private WebPush $webPush;

    /** Clé push → catégorie préférences */
    private const PUSH_KEY_TO_CATEGORY = [
        'reservation' => NotificationPreferenceService::CATEGORY_RESERVATION,
        'paiement' => NotificationPreferenceService::CATEGORY_PAYMENT,
        'message' => NotificationPreferenceService::CATEGORY_MESSAGE,
        'rappel' => NotificationPreferenceService::CATEGORY_REMINDER,
        'promotion' => NotificationPreferenceService::CATEGORY_PROMOTION,
        'mise_a_jour' => NotificationPreferenceService::CATEGORY_PRODUCT_UPDATE,
        'admin' => NotificationPreferenceService::CATEGORY_ADMIN_OPS,
    ];

    /** @deprecated Conservé pour sendToAllSubscribers (requête SQL legacy) */
    private const CATEGORY_MAP = [
        'reservation' => 'notifications_reservations',
        'paiement' => 'notifications_paiements',
        'message' => 'notifications_messages',
        'rappel' => 'notifications_rappels',
        'promotion' => 'notifications_promotions',
        'mise_a_jour' => 'notifications_mises_a_jour',
    ];

    public function __construct()
    {
        $auth = [
            'VAPID' => [
                'subject' => config('webpush.vapid.subject'),
                'publicKey' => config('webpush.vapid.public_key'),
                'privateKey' => config('webpush.vapid.private_key'),
            ],
        ];

        $this->webPush = new WebPush($auth);
    }

    /**
     * Envoyer une notification push à un utilisateur
     */
    public function sendToUser(
        User $user,
        string $title,
        string $body,
        string $category = 'general',
        ?string $url = null,
        ?string $icon = null
    ): void {
        // Vérifier la préférence utilisateur pour cette catégorie
        if (!$this->userWantsCategory($user, $category)) {
            return;
        }

        $subscriptions = $user->pushSubscriptions;

        if ($subscriptions->isEmpty()) {
            return;
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url ?? config('app.url'),
            'icon' => $icon ?? '/icons/icon-192x192.png',
            'badge' => '/icons/icon-192x192.png',
            'category' => $category,
        ]);

        foreach ($subscriptions as $pushSubscription) {
            $subscription = Subscription::create([
                'endpoint' => $pushSubscription->endpoint,
                'publicKey' => $pushSubscription->p256dh_key,
                'authToken' => $pushSubscription->auth_token,
                'contentEncoding' => $pushSubscription->content_encoding,
            ]);

            $this->webPush->queueNotification($subscription, $payload);
        }

        // Envoyer toutes les notifications en file d'attente
        $this->processQueue($user);
    }

    /**
     * Envoyer une notification à tous les abonnés d'une catégorie
     */
    public function sendToAllSubscribers(
        string $title,
        string $body,
        string $category = 'general',
        ?string $url = null
    ): void {
        $preferenceField = self::CATEGORY_MAP[$category] ?? null;

        $query = User::whereHas('pushSubscriptions');

        if ($preferenceField) {
            $query->where($preferenceField, true);
        }

        $query->with('pushSubscriptions')->chunk(100, function ($users) use ($title, $body, $category, $url) {
            foreach ($users as $user) {
                $this->sendToUser($user, $title, $body, $category, $url);
            }
        });
    }

    /**
     * Vérifier si l'utilisateur souhaite recevoir cette catégorie de notification
     */
    private function userWantsCategory(User $user, string $pushKey): bool
    {
        $prefCategory = self::PUSH_KEY_TO_CATEGORY[$pushKey] ?? null;

        if ($prefCategory !== null) {
            return app(NotificationPreferenceService::class)
                ->wants($user, $prefCategory, NotificationPreferenceService::CHANNEL_PUSH);
        }

        return true;
    }

    /**
     * Traiter la file d'attente et nettoyer les souscriptions invalides
     */
    private function processQueue(User $user): void
    {
        try {
            foreach ($this->webPush->flush() as $report) {
                $endpoint = $report->getRequest()->getUri()->__toString();

                if ($report->isSuccess()) {
                    continue;
                }

                // Si l'endpoint est expiré ou invalide (410 Gone, 404 Not Found)
                $statusCode = $report->getResponse()?->getStatusCode();
                if (in_array($statusCode, [404, 410])) {
                    PushSubscription::where('user_id', $user->id)
                        ->where('endpoint', $endpoint)
                        ->delete();

                    \Log::info("Push subscription supprimée (endpoint expiré {$statusCode})", [
                        'user_id' => $user->id,
                        'endpoint' => substr($endpoint, 0, 80),
                    ]);
                } else {
                    \Log::warning("Échec envoi push notification", [
                        'user_id' => $user->id,
                        'status' => $statusCode,
                        'reason' => $report->getReason(),
                        'endpoint' => substr($endpoint, 0, 80),
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Log::error("Erreur lors de l'envoi des push notifications : " . $e->getMessage());
        }
    }

    /**
     * Obtenir le mapping des catégories
     */
    public static function getCategoryMap(): array
    {
        return self::CATEGORY_MAP;
    }
}
