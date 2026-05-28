<?php

namespace App\Services;

use App\Models\User;

/**
 * Préférences de notification par catégorie et canal (in-app, push, email).
 * Un seul jeu de réglages par compte — valable client et professionnel.
 */
class NotificationPreferenceService
{
    public const CHANNEL_IN_APP = 'in_app';

    public const CHANNEL_PUSH = 'push';

    public const CHANNEL_EMAIL = 'email';

    public const CATEGORY_RESERVATION = 'reservation';

    public const CATEGORY_PAYMENT = 'payment';

    public const CATEGORY_MESSAGE = 'message';

    public const CATEGORY_REMINDER = 'reminder';

    public const CATEGORY_PROMOTION = 'promotion';

    public const CATEGORY_PRODUCT_UPDATE = 'product_update';

    /** Alertes plateforme (admins uniquement) */
    public const CATEGORY_ADMIN_OPS = 'admin_ops';

    /** Mapping catégorie → colonne legacy (push uniquement) */
    private const LEGACY_PUSH_COLUMN = [
        self::CATEGORY_RESERVATION => 'notifications_reservations',
        self::CATEGORY_PAYMENT => 'notifications_paiements',
        self::CATEGORY_MESSAGE => 'notifications_messages',
        self::CATEGORY_REMINDER => 'notifications_rappels',
        self::CATEGORY_PROMOTION => 'notifications_promotions',
        self::CATEGORY_PRODUCT_UPDATE => 'notifications_mises_a_jour',
    ];

    /** Types in-app → catégorie */
    public const TYPE_TO_CATEGORY = [
        'reservation' => self::CATEGORY_RESERVATION,
        'reservation_confirmee' => self::CATEGORY_RESERVATION,
        'reservation_annulee' => self::CATEGORY_RESERVATION,
        'nouvelle_reservation' => self::CATEGORY_RESERVATION,
        'commande' => self::CATEGORY_RESERVATION,
        'paiement' => self::CATEGORY_PAYMENT,
        'paiement_recu' => self::CATEGORY_PAYMENT,
        'paiement_echec' => self::CATEGORY_PAYMENT,
        'paiement_3ds' => self::CATEGORY_PAYMENT,
        'paiement_en_attente' => self::CATEGORY_PAYMENT,
        'devis' => self::CATEGORY_PAYMENT,
        'devis_accepte' => self::CATEGORY_PAYMENT,
        'devis_refuse' => self::CATEGORY_PAYMENT,
        'message' => self::CATEGORY_MESSAGE,
        'nouveau_message' => self::CATEGORY_MESSAGE,
        'rappel' => self::CATEGORY_REMINDER,
        'rappel_rdv' => self::CATEGORY_REMINDER,
        'promotion' => self::CATEGORY_PROMOTION,
        'offre' => self::CATEGORY_PROMOTION,
        'mise_a_jour' => self::CATEGORY_PRODUCT_UPDATE,
        'invitation_membre' => self::CATEGORY_PRODUCT_UPDATE,
        'admin_push' => 'general',
        'admin_ticket_nouveau' => self::CATEGORY_ADMIN_OPS,
        'admin_ticket_reponse' => self::CATEGORY_ADMIN_OPS,
        'admin_contact' => self::CATEGORY_ADMIN_OPS,
        'admin_message_interne' => self::CATEGORY_ADMIN_OPS,
        'admin_audit_alerte' => self::CATEGORY_ADMIN_OPS,
        'admin_audit_termine' => self::CATEGORY_ADMIN_OPS,
        'admin_audit_echec' => self::CATEGORY_ADMIN_OPS,
        'admin_erreur' => self::CATEGORY_ADMIN_OPS,
        'admin_gdpr' => self::CATEGORY_ADMIN_OPS,
        'admin_entreprise_validation' => self::CATEGORY_ADMIN_OPS,
        'audit' => self::CATEGORY_ADMIN_OPS,
    ];

    /** Catégorie push (PushNotificationService) */
    public const CATEGORY_TO_PUSH_KEY = [
        self::CATEGORY_RESERVATION => 'reservation',
        self::CATEGORY_PAYMENT => 'paiement',
        self::CATEGORY_MESSAGE => 'message',
        self::CATEGORY_REMINDER => 'rappel',
        self::CATEGORY_PROMOTION => 'promotion',
        self::CATEGORY_PRODUCT_UPDATE => 'mise_a_jour',
        self::CATEGORY_ADMIN_OPS => 'admin',
    ];

    public static function adminCategories(): array
    {
        return [self::CATEGORY_ADMIN_OPS];
    }

    public static function categories(): array
    {
        return [
            self::CATEGORY_RESERVATION,
            self::CATEGORY_PAYMENT,
            self::CATEGORY_MESSAGE,
            self::CATEGORY_REMINDER,
            self::CATEGORY_PROMOTION,
            self::CATEGORY_PRODUCT_UPDATE,
        ];
    }

    public static function channels(): array
    {
        return [self::CHANNEL_IN_APP, self::CHANNEL_PUSH, self::CHANNEL_EMAIL];
    }

    public static function categoryLabels(): array
    {
        return [
            self::CATEGORY_RESERVATION => 'Réservations',
            self::CATEGORY_PAYMENT => 'Paiements & abonnements',
            self::CATEGORY_MESSAGE => 'Messages',
            self::CATEGORY_REMINDER => 'Rappels de RDV',
            self::CATEGORY_PROMOTION => 'Promotions & offres',
            self::CATEGORY_PRODUCT_UPDATE => 'Mises à jour',
            self::CATEGORY_ADMIN_OPS => 'Administration plateforme',
        ];
    }

    public static function channelLabels(): array
    {
        return [
            self::CHANNEL_IN_APP => 'Centre de notifications',
            self::CHANNEL_PUSH => 'Push navigateur',
            self::CHANNEL_EMAIL => 'Email',
        ];
    }

    public static function categoryDescriptions(): array
    {
        return [
            self::CATEGORY_RESERVATION => 'Confirmations, modifications et annulations de réservations',
            self::CATEGORY_PAYMENT => 'Prélèvements, échecs, validation 3D Secure et factures',
            self::CATEGORY_MESSAGE => 'Nouveaux messages dans la messagerie (push recommandé, email optionnel)',
            self::CATEGORY_REMINDER => 'Rappels avant vos rendez-vous',
            self::CATEGORY_PROMOTION => 'Offres spéciales et promotions',
            self::CATEGORY_PRODUCT_UPDATE => 'Nouvelles fonctionnalités et invitations équipe',
            self::CATEGORY_ADMIN_OPS => 'Tickets, contacts, messagerie interne, audits, erreurs et validations',
        ];
    }

    public function defaults(): array
    {
        $defaults = [];
        foreach (array_merge(self::categories(), self::adminCategories()) as $category) {
            $defaults[$category] = [
                self::CHANNEL_IN_APP => true,
                self::CHANNEL_PUSH => true,
                self::CHANNEL_EMAIL => ! in_array($category, [self::CATEGORY_MESSAGE, self::CATEGORY_ADMIN_OPS], true),
            ];
        }

        return $defaults;
    }

    /** @return list<string> */
    public function categoriesForUser(User $user): array
    {
        $cats = self::categories();
        if ($user->isAdmin()) {
            $cats = array_merge($cats, self::adminCategories());
        }

        return $cats;
    }

    public function forUser(User $user, string $category): array
    {
        if ($category === 'general') {
            return [
                self::CHANNEL_IN_APP => true,
                self::CHANNEL_PUSH => true,
                self::CHANNEL_EMAIL => true,
            ];
        }

        $stored = $user->notification_channel_prefs;
        if (is_string($stored)) {
            $stored = json_decode($stored, true);
        }

        $merged = $this->defaults();
        if (is_array($stored)) {
            foreach (array_keys($this->defaults()) as $cat) {
                if (! isset($stored[$cat]) || ! is_array($stored[$cat])) {
                    continue;
                }
                foreach (self::channels() as $channel) {
                    if (array_key_exists($channel, $stored[$cat])) {
                        $merged[$cat][$channel] = (bool) $stored[$cat][$channel];
                    }
                }
            }
        } else {
            $merged = $this->mergeFromLegacyColumns($user, $merged);
        }

        return $merged[$category] ?? $this->defaults()[$category] ?? [
            self::CHANNEL_IN_APP => true,
            self::CHANNEL_PUSH => true,
            self::CHANNEL_EMAIL => true,
        ];
    }

    public function allForUser(User $user): array
    {
        $result = [];
        foreach ($this->categoriesForUser($user) as $category) {
            $result[$category] = $this->forUser($user, $category);
        }

        return $result;
    }

    public function wants(User $user, string $category, string $channel): bool
    {
        $prefs = $this->forUser($user, $category);

        return (bool) ($prefs[$channel] ?? false);
    }

    public function categoryFromNotificationType(string $type): string
    {
        return self::TYPE_TO_CATEGORY[$type] ?? 'general';
    }

    public function pushKeyForCategory(string $category): string
    {
        return self::CATEGORY_TO_PUSH_KEY[$category] ?? 'general';
    }

    /**
     * @param  array<string, array<string, bool>>  $matrix
     */
    public function saveForUser(User $user, array $matrix): void
    {
        $normalized = $this->defaults();
        foreach ($this->categoriesForUser($user) as $category) {
            if (! isset($matrix[$category]) || ! is_array($matrix[$category])) {
                continue;
            }
            foreach (self::channels() as $channel) {
                $normalized[$category][$channel] = ! empty($matrix[$category][$channel]);
            }
        }

        $legacy = [];
        foreach (self::LEGACY_PUSH_COLUMN as $category => $column) {
            if (isset($normalized[$category])) {
                $legacy[$column] = $normalized[$category][self::CHANNEL_PUSH] ?? true;
            }
        }

        $user->update(array_merge(
            ['notification_channel_prefs' => $normalized],
            $legacy
        ));
    }

    private function mergeFromLegacyColumns(User $user, array $merged): array
    {
        foreach (self::LEGACY_PUSH_COLUMN as $category => $column) {
            $pushOn = (bool) ($user->{$column} ?? true);
            $merged[$category][self::CHANNEL_PUSH] = $pushOn;
        }

        return $merged;
    }
}
