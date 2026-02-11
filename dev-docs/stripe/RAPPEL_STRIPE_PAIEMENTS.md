# Récapitulatif – Stripe & Paiements (Checkout, Abonnements)

## Ce qui a été fait

### 1. **Terminal de paiement invisible**
- **Checkout** (`/checkout`) : pas de redirection Stripe Checkout. Tout reste sur le site.
- **Stripe Elements** : Payment Element pour enregistrer la carte (SetupIntent), puis débit via PaymentIntent `off_session` côté serveur.
- **Adresse / code postal** : `fields.billingDetails.address: 'never'` sur le Payment Element → plus de collecte ni de vérification postale (évite l’erreur "code postal ne correspond pas").
- **Prix** : gérés en PHP/Laravel (Tarif, CustomPrice, CalculMontantDuService). Stripe exécute des débits pour des montants variables.

### 2. **Accès au Checkout**
- **Liens visibles** :
  - Nav dashboard (desktop) : « Espace Paiement » → `/checkout`
  - Nav paramètres (desktop) : « Espace Paiement » → `/checkout`
  - Burger mobile (dashboard / paramètres) : « Espace Paiement » → `/checkout`
- Depuis l’onglet **Abonnement** : « Payer maintenant », « Modifier la carte », « Ajouter une carte », « Régler », « Voir toutes les échéances et payer » → `/checkout`.

### 3. **Onglet Abonnement (Paramètres / Dashboard)**
- **Cartes** : affichage •••• XXXX, « Modifier » / « Ajouter une carte » → checkout.
- **Factures** : uniquement factures Stripe **payées** et **montant > 0** (`status === 'paid'` et `amount_paid > 0`). Plus de factures « fantômes » ou $0 quand aucun paiement.
- **Derniers paiements** : échéances payées + `StripeTransaction`, dédupliqués.
- **Prochains paiements** : échéances à payer, avec « Régler » et « Annuler » (annulation = `statut → annulé`, pas de débit).

### 4. **Audit verbose (protection légale)**
- **Table** `payment_audit_log` : `action`, `user_id`, `stripe_*`, `amount`, `currency`, `status`, `ip_address`, `user_agent`, `request_id`, `context` (JSON), `message`.
- **Actions loguées** : `setup_intent_created`, `save_pm_ok`, `save_pm_fail`, `charge_ok`, `charge_fail`, `charge_3ds`, `confirm_status_ok`, `confirm_status_fail`.
- **Consultation** : Admin → Paiements → « Journal d’audit paiements (verbose) », ou `/admin/payment-audit-log`. Filtres : membre, action, Payment Intent, dates.

### 5. **Mobile**
- Barre d’onglets scrollable (Settings + Dashboard) pour naviguer entre les onglets sur mobile.
- Cartes, factures, derniers / prochains paiements, checkout : padding, boutons `min-h-[44px]`, `touch-manipulation`, mise en page responsive.

---

## Ce qu’il faut pour que ça marche à 100 %

1. **`.env`** : `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` corrects.
2. **Migrations** : `php artisan migrate` (dont `payment_audit_log`, `stripe_transactions`, `echeances`, etc.).
3. **Webhook** : `payment_intent.succeeded` configuré et pointant vers votre handler. Réconciliation via webhook + vérification API + CRON.
4. **CRON** : `subscriptions:check-echeances` et `subscriptions:reconcile-echeances` planifiés (ex. daily).
5. **Checkout** : utilisateur authentifié, avec droit d’accès au checkout (même règles que l’onglet abonnement).

---

## Probabilités de bug / à surveiller

| Risque | Probabilité | Mitigation |
|--------|-------------|------------|
| Webhook `payment_intent.succeeded` non reçu ou en erreur | Moyenne | Vérification directe Stripe après 3DS + CRON de réconciliation. |
| 3DS non terminée (fermeture avant redirect) | Faible | Échéance reste `en_attente`, CRON peut réconcilier si paiement finalisé côté Stripe. |
| Carte refusée (fonds, 3DS échouée) | Normal | Erreur renvoyée au client, `charge_fail` dans l’audit. |
| Doublon charge (double clic « Régler ») | Faible | Bouton désactivé pendant le traitement ; idempotence côté Stripe (même `echeance_id` dans metadata). |
| Factures Stripe sans paiement réel | Réduit | Filtre `paid` + `amount_paid > 0` ; plus d’affichage de factures $0 ou brouillons. |
| Erreur « code postal ne correspond pas » | Réduit | `address: 'never'` sur le Payment Element. |
| Impossible de trouver le Checkout | Réduit | Liens « Espace Paiement » dans nav, burger et onglet abonnement. |

---

## Vérifier qu’il n’y a pas de bug

1. **Audit** : aller sur `/admin/payment-audit-log`, filtrer par utilisateur ou par action. Vérifier que chaque `charge_ok` / `confirm_status_ok` a un `charge_fail` ou `confirm_status_fail` cohérent en cas d’échec.
2. **Échéances** : Admin → Paiements → voir les statuts (à payer, en attente, payé, échec, annulé, arrêté).
3. **Stripe Dashboard** : Logs des webhooks, paiements (Payment Intents), clients.
4. **Logs Laravel** : `storage/logs/laravel.log` pour erreurs Stripe, vérification, réconciliation.

---

## Fichiers principaux

- **Checkout** : `CheckoutController`, `resources/views/checkout/index.blade.php`, `resources/js/checkout.js`
- **SetupIntent / save PM / charge** : `CheckoutController` (+ `StripeCustomerService`), `PaymentVerificationService`
- **Abonnement** : `resources/views/partials/settings/subscription-tab.blade.php`, `SettingsController`, `DashboardController`
- **Audit** : `PaymentAuditLog`, `app/Http/Controllers/Admin/PaymentAuditLogController`, `resources/views/admin/payment-audit-log/index.blade.php`
- **Factures (filtre)** : `SettingsController`, `DashboardController` (filter `paid` + `amount_paid > 0`)

---

## À faire éventuellement (tâches restantes)

- **p6** : Désactiver reçus Stripe + envoi d’email de confirmation d’échéance.
- **p7** : Webhook `payment_intent.succeeded` + réconciliation dédiée.
- **p8** : Nettoyage Checkout Session, tests admin, CRON.
