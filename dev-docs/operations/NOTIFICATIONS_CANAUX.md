# Notifications — canaux et préférences

## Réglages utilisateur

**Paramètres → Notifications** : matrice **catégorie × canal** (même compte client et professionnel).

| Canal | Description |
|-------|-------------|
| Centre de notifications | Enregistrement in-app (`notifications` table) |
| Push navigateur | Web Push (VAPID), si abonnement actif |
| Email | Templates `EmailHelper` (si callback fourni) |

### Défauts

- **Messages** : in-app + push **ON**, email **OFF** (push recommandé pour les nouveaux messages).
- Autres catégories : les trois canaux **ON**.

Les anciennes colonnes `notifications_*` sur `users` restent synchronisées avec la colonne **push** (rétrocompatibilité).

## Services

- `NotificationPreferenceService` — lecture / écriture des prefs (`notification_channel_prefs` JSON).
- `UserNotificationService` — point d’entrée `notify()`, `notifyNewMessage()`, `notifyPaymentStatus()`.
- `Notification::creer()` — délègue à `UserNotificationService` (tous les appels existants respectent les prefs).

## Paiements

Notifications automatiques (in-app + push + email optionnel) :

- CRON `subscriptions:process-payments` : succès, échec, 3DS (`requires_action`).
- Checkout : 3DS lors du paiement manuel.

Badge **statut paiement** dans le centre de notifications (`donnees.payment_statut`).

## Messages

`MessagerieController` : in-app + push ; email uniquement si activé dans les réglages.

## Notifications administrateurs

Service : `AdminNotificationService` — alerte **tous les comptes `is_admin`**.

| Événement | Type | Lien |
|-----------|------|------|
| Nouveau ticket | `admin_ticket_nouveau` | Fiche ticket admin |
| Réponse client sur ticket | `admin_ticket_reponse` | Fiche ticket admin |
| Formulaire contact | `admin_contact` | Fiche contact admin |
| Message messagerie interne | `admin_message_interne` | Conversation admin |
| Audit terminé (note basse / critiques) | `admin_audit_alerte` | Rapport audit |
| Audit terminé (OK) | `admin_audit_termine` | Rapport audit |
| Audit échoué | `admin_audit_echec` | Rapport audit |
| Erreur application (`error_logs`) | `admin_erreur` | `/admin/errors` |
| Demande suppression RGPD | `admin_gdpr` | `/admin/gdpr` |
| Entreprise à valider | `admin_entreprise_validation` | Fiche entreprise admin |

**Préférences** : catégorie `admin_ops` dans Paramètres → Notifications (visible si `is_admin`).

**Erreurs** : in-app pour tous les admins ; push si `notifications_erreurs_actives` **et** push `admin_ops` activé.

## Déploiement

```bash
php artisan migrate
```

Migration : `2026_05_28_100000_add_notification_channel_prefs_to_users_table.php`
