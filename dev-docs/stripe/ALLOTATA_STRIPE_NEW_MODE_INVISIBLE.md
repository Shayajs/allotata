# Allotata – Stripe en mode « paiement invisible »

Ce document décrit le fonctionnement du **service de paiement Stripe** dans son mode « terminal invisible » : aucun redirect Stripe Checkout, tout reste sur le site. Stripe sert uniquement de **terminal de débit** ; la logique métier (prix, échéances, réductions) est gérée dans Laravel.

---

## 1. Principes généraux

### 1.1 Modèle « Terminal invisible »

- **Pas de redirection** vers une page Stripe (Checkout Session classique). L’utilisateur reste sur `/checkout` (Espace Paiement).
- **Stripe Elements** (Payment Element) : capture des données carte **dans nos formulaires**, iframes Stripe intégrées au design.
- **Off-session** : on enregistre un **PaymentMethod** (PM) puis on débite **côté serveur** via l’API Stripe, sans action de l’utilisateur au moment du prélèvement.
- **Pas de Plans/Produits Stripe** pour les abonnements : les montants sont calculés en PHP (Tarif, CustomPrice, promo) ; Stripe reçoit uniquement des ordres de débit pour des **montants variables**.

### 1.2 Flux en deux temps

1. **Enregistrement carte (SetupIntent)**  
   L’utilisateur va sur `/checkout`, remplit le formulaire Stripe, clique « Enregistrer ma carte ». Aucun débit. On stocke le **PaymentMethod ID** (`pm_xxx`) et on le rattache au **Customer** Stripe.
2. **Débit (PaymentIntent)**  
   Plus tard (immédiatement si « Régler » sur une échéance, ou via CRON pour les échéances mensuelles) : le serveur crée un **PaymentIntent** avec `payment_method`, `off_session`, `confirm`, et prélève la carte sans interaction utilisateur (sauf 3D Secure si requis).

### 1.3 Rôles des objets Stripe

| Objet | Rôle |
|-------|------|
| **Customer** | Un par `User`. `user.stripe_id` = `cus_xxx`. Créé à la volée si absent. |
| **SetupIntent** | Créer un PaymentMethod sans débiter. `usage: off_session`. |
| **PaymentMethod** | Carte (ou autre) attachée au Customer. ID stocké dans `user.stripe_payment_method_id`. |
| **PaymentIntent** | Ordre de débit. Créé côté serveur avec `customer`, `payment_method`, `off_session`, `confirm`. |
| **Checkout Session** | Utilisé **uniquement** pour le flux legacy « Paiement test » (redirect). Pas pour le flux invisible. |

---

## 2. Configuration

### 2.1 Variables d’environnement

```env
STRIPE_KEY=pk_test_...       # Clé publique (front)
STRIPE_SECRET=sk_test_...    # Clé secrète (back)
STRIPE_WEBHOOK_SECRET=whsec_...
CRON_SECRET=...              # Token secret pour /cron-run (voir § 5.4)
```

- `config('services.stripe.key')` et `config('services.stripe.secret')` alimentent Stripe PHP et le front.
- Le **webhook** doit pointer vers votre handler (ex. `/stripe/webhook`) et envoyer au moins `payment_intent.succeeded` pour la réconciliation.
- **CRON_SECRET** : valeur aléatoire sécurisée (ex. `openssl rand -hex 32`). Protège la route `/cron-run` qui lance les tâches planifiées. Ne pas utiliser `change-me-in-production`.

### 2.2 Base de données

- **Users** : `stripe_id`, `stripe_payment_method_id`, `pm_type`, `pm_last_four`.
- **Echeances** : `stripe_checkout_session_id`, `stripe_payment_intent_id`, `statut`, `paye_at`, etc.
- **stripe_transactions** : journal des événements Stripe (payment_intent.succeeded, etc.).
- **payment_audit_log** : audit détaillé (setup, save PM, charge, 3DS, IP, user-agent, etc.).

Migrations à exécuter : `php artisan migrate` (dont `payment_audit_log`, `stripe_transactions`, colonnes Stripe sur `users`).

---

## 3. Adresse de facturation et Payment Element

### 3.1 `address: 'if_required'`

On configure le Payment Element avec :

```js
elements.create('payment', {
  fields: {
    billingDetails: {
      address: 'if_required',
    },
  },
});
```

- Stripe affiche **dans son formulaire** les champs d’adresse requis pour le moyen de paiement (carte : typiquement **code postal**, parfois ville). Pas de formulaire custom côté app.
- L’utilisateur saisit code postal et éventuellement ville **directement dans l’iframe Stripe**. Aucun `payment_method_data.billing_details` n’est envoyé à `confirmSetup` ; Stripe gère la collecte et l’envoi.

### 3.2 Profil utilisateur (Paramètres → Compte)

**Ville** et **code postal** restent stockés dans le profil (Paramètres → Mon compte) pour l’affichage des adresses et la facturation hors Stripe. Ils ne sont **pas** utilisés pour le flux d’enregistrement de carte : la saisie se fait dans le Payment Element.

---

## 4. Parcours utilisateur

### 4.1 Accès au checkout

- **Espace Paiement** : lien dans la nav (desktop) et le burger (mobile) → `/checkout`.
- Depuis l’onglet **Abonnement** (Paramètres / Dashboard) : « Payer maintenant », « Modifier la carte » → `/checkout?change_card=1`, « Ajouter une carte », « Régler », « Voir toutes les échéances et payer » → `/checkout`.

### 4.2 Enregistrement d’une carte (SetupIntent)

1. **Page** `/checkout`, section « Moyen de paiement » (affichée si pas de carte **ou** si `?change_card=1`).
2. **Chargement** : fetch `POST /checkout/setup-intent` → le serveur crée un **SetupIntent** (`usage: off_session`, `payment_method_types: ['card']`), renvoie `client_secret`.
3. **Stripe Elements** : création du Payment Element avec ce `client_secret`, `address: 'if_required'`, et theme night/stripe selon le mode sombre/clair. Stripe affiche les champs nécessaires (carte + code postal, etc.) dans son formulaire.
4. **Clic « Enregistrer ma carte »** (ou « Remplacer la carte » en mode changement) :
   - `stripe.confirmSetup({ elements, confirmParams: { return_url } })`. Aucun `payment_method_data.billing_details` : Stripe collecte l’adresse dans l’Element.
   - Si 3DS : redirect Stripe puis retour sur `/checkout?setup_intent_client_secret=...&redirect_status=succeeded`. Le JS appelle `retrieveSetupIntent`, récupère le `payment_method`, puis `POST /checkout/save-payment-method` avec `{ payment_method: 'pm_xxx' }`.
   - Sinon : pas de redirect ; même appel à `save-payment-method` après `confirmSetup`.
5. **Côté serveur** (`savePaymentMethod`) :
   - Attache le PM au Customer Stripe (`StripeCustomerService::attachPaymentMethod`).
   - Met à jour `user.stripe_payment_method_id`, `pm_type`, `pm_last_four`.
   - Log audit `save_pm_ok` (ou `save_pm_fail` en cas d’erreur).

### 4.3 Changer ou supprimer la carte

- **Changer la carte** : lien « Changer la carte » sur `/checkout` (ou « Modifier la carte » depuis Abonnement) → `/checkout?change_card=1`. Le formulaire d’enregistrement réapparaît ; l’utilisateur saisit une nouvelle carte. Après enregistrement, redirection vers `/checkout` sans paramètre. Boutons « Annuler » / « ← Annuler » pour revenir sans modifier.
- **Supprimer la carte** : bouton « Supprimer la carte » sur `/checkout` → `POST /checkout/remove-payment-method` (avec confirmation). Le serveur détache le PaymentMethod chez Stripe, vide `invoice_settings.default_payment_method` si besoin, met à `null` `stripe_payment_method_id`, `pm_type`, `pm_last_four`, log `remove_pm_ok`, puis redirige vers `/checkout` avec « Carte supprimée. »

### 4.4 Paiement d’une échéance (PaymentIntent)

1. **Prérequis** : carte déjà enregistrée (`user.stripe_payment_method_id` présent).
2. Sur `/checkout`, l’utilisateur clique **« Régler »** sur une échéance (ou « Régler cette échéance »).
3. **Front** : `POST /checkout/charge` avec `{ echeance_id, code_promo? }`.
4. **Back** (`charge`) :
   - Vérifie l’échéance (statut `a_payer` ou `en_attente`, appartenance au user).
   - Calcule le montant (`CalculMontantDuService::calculerPourEcheance`).
   - Si montant ≤ 0 ou pas de PM → 422/409 et log `charge_fail`.
   - Sinon : `PaymentIntent::create` avec `customer`, `payment_method`, `off_session`, `confirm: true`, `metadata: { user_id, echeance_id }`.
5. **Réponses** :
   - **`succeeded`** : on met à jour l’échéance (statut `paye`, `paye_at`, etc.), on appelle `PaymentVerificationService::markEcheancePaidFromPaymentIntent`, on crée une `StripeTransaction`, on log `charge_ok`. Réponse `{ success: true }`.
   - **`requires_action`** (3DS) : on met l’échéance en `en_attente`, on stocke `stripe_payment_intent_id`, on log `charge_3ds`. Réponse `{ requires_action: true, client_secret, payment_intent_id }`.
6. **Si 3DS** : le front appelle `stripe.handleCardAction(client_secret)`. Après succès, `POST /checkout/confirm-status` avec `payment_intent_id`. Le serveur appelle à nouveau `markEcheancePaidFromPaymentIntent`, met à jour l’échéance, log `confirm_status_ok`.

### 4.5 Codes promo

- **Application** : formulaire sur `/checkout` ou paramètre de requête. Le code est stocké en session (`checkout_promo_code`) et/ou envoyé dans `charge`.
- **Calcul** : `CalculMontantDuService::calculerPourEcheance` utilise `PromoCode::validateCode` et applique la réduction. Le montant final peut être 0 (refusé en charge avec un message dédié).

---

## 5. Vérification et réconciliation des paiements

### 5.1 Triple niveau de robustesse

1. **Webhook** `payment_intent.succeeded` (et éventuellement `checkout.session.completed` pour le legacy) : mise à jour des échéances et création de `StripeTransaction`. Peut échouer (réseau, erreur 5xx).
2. **Vérification directe Stripe** : après 3DS, le front appelle `confirm-status` ; le serveur fait un `PaymentIntent::retrieve` et marque l’échéance payée si `status === 'succeeded'`.
3. **CRON** `subscriptions:reconcile-echeances` : pour les échéances `en_attente` avec `stripe_checkout_session_id`, on interroge Stripe (Session ou PI selon le flux) et on met à jour le statut. Si le paiement n’est pas confirmé, on repasse en `a_payer` pour retry.

### 5.2 PaymentVerificationService

- **`verifyAndMarkPaid(sessionId)`** : flux Checkout Session. Récupère la session, vérifie `payment_status === 'paid'`, extrait `user_id` et `echeance_id` des metadata, met à jour l’échéance, crée une `StripeTransaction` si besoin, gère les abonnements entreprise.
- **`markEcheancePaidFromPaymentIntent(piId)`** : flux PaymentIntent (charge Elements). Récupère le PI, vérifie `status === 'succeeded'`, idem metadata et mise à jour. Idempotent : si l’échéance est déjà payée, ne fait rien.
- **`ensureStripeTransactionFromPaymentIntent`** : crée une entrée `stripe_transactions` à partir du PI (event_type `payment_intent.succeeded`, `processed: true`).

### 5.3 Commandes CRON

- **`subscriptions:check-echeances`** : génère les échéances à venir (Premium, options entreprise) selon les dates de facturation. Planifié quotidiennement.
- **`subscriptions:reconcile-echeances`** : réconcilie les échéances `en_attente`. **Note** : la commande actuelle cible celles avec `stripe_checkout_session_id`. Les échéances mises en attente par le flux **PaymentIntent** (3DS) n’ont que `stripe_payment_intent_id` ; une évolution pourrait étendre la réconciliation à ces cas (vérification du PI sur Stripe).
- **`essais:check-expiration`** : vérifie les essais gratuits, envoie rappels et notifications d’expiration.

### 5.4 Déclenchement HTTP : `/cron-run`

Pour éviter d’utiliser `docker exec` ou un cron Laravel dans un conteneur, une **route HTTP** permet de lancer les trois commandes ci‑dessus.

**Configuration**

- `.env` : `CRON_SECRET=<token-secret-long>` (ex. généré avec `openssl rand -hex 32`).

**Utilisation**

| Usage | Méthode |
|-------|---------|
| **Manuel** | Ouvrir dans le navigateur : `https://votre-domaine.fr/cron-run?token=VOTRE_CRON_SECRET` |
| **Cron externe** | `curl -s "https://votre-domaine.fr/cron-run?token=VOTRE_CRON_SECRET"` |
| **Header** | `X-Cron-Token: VOTRE_CRON_SECRET` (GET ou POST) |

**Comportement**

- Vérification du token (query `token` ou header `X-Cron-Token`). Si invalide ou absent → 403. Si `CRON_SECRET` manquant ou égal à `change-me-in-production` → 500.
- Exécution séquentielle de : `subscriptions:check-echeances`, `subscriptions:reconcile-echeances`, `essais:check-expiration`.
- Réponse JSON : `success`, `message`, `results` (sortie de chaque commande), `at` (ISO 8601). En cas d’échec d’une commande → `success: false` et HTTP 500.

**Exemple de crontab (hébergeur, sans Docker)**

```cron
0 6 * * * curl -s "https://allotata.fr/cron-run?token=VOTRE_CRON_SECRET"
```

Aucun `php artisan`, ni `docker exec` : un simple `curl` sur l’URL suffit.

---

## 6. Échéances, tarifs et calcul des montants

### 6.1 Modèle Echeance

- **Statuts** : `a_payer`, `en_attente`, `paye`, `echec`, `annule`, `arrete`.
- **Types** : `default` (Premium), `site_web`, `multi_personnes` (options entreprise).
- **Champs clés** : `user_id`, `entreprise_id`, `subscription_type`, `periode_debut` / `periode_fin`, `montant_du`, `montant_final`, `reduction_promo`, `reduction_manuel`, `promo_code_id`, `stripe_payment_intent_id`, `stripe_checkout_session_id`, `paye_at`, `statut`.

### 6.2 Calcul du montant

- **`CalculMontantDuService::calculerPourEcheance`** :
  - Base = tarifs (Tarif, CustomPrice selon user/entreprise).
  - Applique les codes promo (`PromoCode::validateCode`).
  - Applique les réductions manuelles (gestes commerciaux) sur l’échéance.
- Retour : `montant_du`, `montant_final`, `reduction_promo`, `lignes`, `promo_code_id`. Le **montant affiché et débité** est `montant_final`.

### 6.3 Tarifs

- **Tarif** : modèle en BDD (type, amount, currency, label). Les prix sont **éditables** dans l’admin (ex. onglet Prix Stripe). Au moment du paiement, le tarif est « figé » pour l’échéance ; les changements futurs de tarif n’impactent pas les échéances déjà créées.

---

## 7. Audit et traçabilité

### 7.1 payment_audit_log

Chaque action sensible est enregistrée : `user_id`, `action`, IDs Stripe (customer, PI, PM, etc.), `amount`, `currency`, `status`, `ip_address`, `user_agent`, `request_id`, `context` (JSON), `message`.

**Actions loguées** :

- `setup_intent_created`, `save_pm_ok`, `save_pm_fail`, `remove_pm_ok`
- `charge_ok`, `charge_fail`, `charge_3ds`
- `confirm_status_ok`, `confirm_status_fail`

Consultation : **Admin → Paiements → « Journal d’audit paiements (verbose) »** (`/admin/payment-audit-log`). Filtres par utilisateur, action, Payment Intent, dates.

### 7.2 stripe_transactions

- Entrées créées depuis les webhooks ou depuis `ensureStripeTransactionFromPaymentIntent` / `ensureStripeTransaction`.
- Utilisées pour l’historique « Derniers paiements », les remboursements, etc. On exclut les transactions de montant ≤ 0 et on déduplique avec les échéances payées.

---

## 8. Affichage côté utilisateur (Abonnement, Checkout)

### 8.1 Cartes enregistrées

- Affichage `•••• XXXX` et type (Visa, etc.) à partir de `pm_last_four` et `pm_type`.
- **Changer la carte** → `/checkout?change_card=1` (ou « Modifier la carte » depuis Abonnement). **Supprimer la carte** → `POST /checkout/remove-payment-method` (confirmations utilisateur).
- « Ajouter une carte » → `/checkout` (formulaire affiché lorsqu’aucune carte n’est enregistrée).

### 8.2 Factures

- Uniquement les **factures Stripe** `status === 'paid'` et `amount_paid > 0`. Pas de factures à 0 € ou brouillons.

### 8.3 Derniers paiements

- Combinaison d’**échéances payées** et de **StripeTransaction** (hors doublons).  
- **Exclusion** : montant ≤ 0 (pas d’entrées « 0,00 € »).

### 8.4 Prochains paiements

- Échéances `a_payer` ou `en_attente`.  
- Actions : « Régler » → `/checkout`, « Annuler » → `POST /abonnement/echeance/{id}/annuler` (statut → `annule`, pas de débit).

---

## 9. Gestion des erreurs et UX Checkout

### 9.1 Setup Intent / chargement du formulaire

- Si `POST /checkout/setup-intent` échoue (500, pas de `client_secret`) : message d’erreur explicite + **bouton « Réessayer »** sans recharger la page.
- **Chargement** : spinner « Chargement du formulaire… » pendant le fetch et le mount du Payment Element.
- **Double init** : `form.dataset.saveCardInit` évite d’attacher plusieurs fois les listeners.

### 9.2 Erreurs Stripe (carte refusée, 3DS, etc.)

- Affichage dans `#checkout-card-error` ou via toasts. Pas de redirect Stripe ; tout reste sur le site.
- En cas de **CardException** (fonds insuffisants, refus) : message utilisateur clair et log `charge_fail` dans l’audit.

### 9.3 3D Secure

- Si `PaymentIntent` retourne `requires_action` : le front utilise `stripe.handleCardAction(client_secret)`.  
- Après succès 3DS : appel à `confirm-status` puis rechargement de la page. L’échéance est marquée payée et une `StripeTransaction` est créée.

---

## 10. Routes et endpoints

| Méthode | Route | Contrôleur | Rôle |
|--------|-------|------------|------|
| GET | `/checkout` | `CheckoutController@index` | Page Espace Paiement |
| POST | `/checkout/setup-intent` | `createSetupIntent` | Créer un SetupIntent, retourner `client_secret` |
| POST | `/checkout/save-payment-method` | `savePaymentMethod` | Attacher le PM au Customer, mettre à jour le user |
| POST | `/checkout/remove-payment-method` | `removePaymentMethod` | Détacher le PM, vider user (stripe_payment_method_id, etc.) |
| POST | `/checkout/charge` | `charge` | Créer un PaymentIntent, débiter (ou retourner 3DS) |
| POST | `/checkout/confirm-status` | `confirmStatus` | Après 3DS : vérifier le PI et marquer l’échéance payée |
| POST | `/checkout/appliquer-promo` | `appliquerPromo` | Appliquer un code promo (session) |
| POST | `/checkout/retirer-promo` | `retirerPromo` | Retirer le code promo |
| POST | `/abonnement/echeance/{echeance}/annuler` | `SubscriptionController@annulerEcheance` | Annuler une échéance à venir |
| GET / POST | `/cron-run` | `CronRunController@run` | Lancer les tâches planifiées (échéances, réconciliation, essais). Protégé par `?token=CRON_SECRET` ou `X-Cron-Token`. |

---

## 11. Fichiers principaux

| Rôle | Fichiers |
|------|----------|
| Checkout (page, formulaire) | `resources/views/checkout/index.blade.php`, `resources/js/checkout.js` |
| Contrôleur checkout | `app/Http/Controllers/CheckoutController.php` |
| Customer Stripe, PM | `app/Services/StripeCustomerService.php` |
| Vérification / réconciliation | `app/Services/PaymentVerificationService.php` |
| Calcul des montants | `app/Services/CalculMontantDuService.php` |
| Échéances | `app/Models/Echeance.php` |
| Audit | `app/Models/PaymentAuditLog.php`, `app/Http/Controllers/Admin/PaymentAuditLogController.php` |
| Abonnement (onglet) | `resources/views/partials/settings/subscription-tab.blade.php`, `SettingsController`, `DashboardController` |
| CRON | `app/Console/Commands/CheckEcheancesCommand.php`, `ReconcileEcheancesCommand.php`, `CheckEssaisExpiration.php` |
| Cron HTTP | `app/Http/Controllers/CronRunController.php` (route `/cron-run`) |

---

## 12. Subtilités à garder en tête

1. **Code postal** : avec `address: 'if_required'`, Stripe affiche le champ dans son formulaire. L’utilisateur le saisit directement ; il doit correspondre à celui enregistré auprès de la banque (sinon erreur « Votre numéro de carte et votre code postal ne correspondent pas »).
2. **Réconciliation** : le CRON actuel ne réconcilie que les échéances avec `stripe_checkout_session_id`. Les échéances 3DS purement PaymentIntent n’ont que `stripe_payment_intent_id` ; une extension du CRON pourrait les traiter aussi.
3. **Factures** : uniquement `paid` et `amount_paid > 0`. Les factures à 0 € ou « open » ne sont pas affichées.
4. **Derniers paiements** : exclusion des montants ≤ 0 (échéances et transactions) pour éviter les lignes « 0,00 € ».
5. **CSP / Google Pay** : le SetupIntent est créé avec `payment_method_types: ['card']` pour n’afficher que le formulaire carte. Cela évite le chargement de l’iframe `pay.google.com` (Google Pay) et les violations CSP « frame-ancestors » qui peuvent bloquer la saisie carte. Pas de Google Pay côté checkout.
6. **Audit** : en cas d’absence ou d’erreur sur la table `payment_audit_log`, le log audit est ignoré (try/catch) pour ne pas bloquer `createSetupIntent` ni le flux de paiement.

---

## 13. Checklist déploiement

- [ ] `.env` : `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` corrects.
- [ ] `.env` : `CRON_SECRET` défini (token long, ex. `openssl rand -hex 32`) pour `/cron-run`.
- [ ] `php artisan migrate` (dont `payment_audit_log`, `stripe_transactions`, colonnes Stripe sur `users`).
- [ ] Webhook Stripe configuré vers l’URL de production, avec `payment_intent.succeeded` (et `checkout.session.completed` si flux legacy utilisé).
- [ ] **CRON** : soit `php artisan schedule:run` (Laravel Scheduler), soit **curl sur `/cron-run`** (recommandé en Docker) :  
  `0 6 * * * curl -s "https://votre-domaine.fr/cron-run?token=VOTRE_CRON_SECRET"`. Aucun `docker exec` requis.
- [ ] Vérifier que les utilisateurs peuvent renseigner **ville** et **code postal** dans Paramètres → Compte.
- [ ] Rebuild des assets : `npm run build` (ou `sail npm run build`) pour `checkout.js` et les vues.

---

*Document généré pour le projet Allotata – mode Stripe « paiement invisible ».*
