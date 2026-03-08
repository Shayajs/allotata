# Paiement Auto Robuste - Phase 1 (Admin Only)

## Contexte

Cette refonte corrige un point critique:

- les abonnements **manuels** ne doivent **jamais** être auto-preleves;
- les abonnements **carte/Stripe** doivent suivre une logique d'echeance et de retry robuste;
- l'admin doit pouvoir gerer les dettes dues/non dues au cas par cas.

## Objectifs appliques

- Separation stricte des flux `manual` vs `auto_card` vs `provider_subscription`.
- Exclusion explicite des echeances manuelles du scheduler de prelevement.
- Gestion admin complete des dettes:
  - creation de dette manuelle,
  - marquage paye manuel,
  - conversion dette Stripe -> manuel,
  - marquage regle hors ligne,
  - filtres origin/provider/auto-charge.
- Preparation multi-provider sans activer de nouveau provider en production.

## Schema de donnees ajoute

### `echeances`

- `payment_origin` (`manual`, `auto_card`, `provider_subscription`)
- `payment_provider` (ex: `stripe`)
- `auto_charge_eligible` (bool)

### `users`

- `payment_provider`
- `provider_customer_id`
- `provider_payment_method_id`
- `provider_payload` (json)

### `entreprise_subscriptions`

- `payment_provider`
- `provider_subscription_id`
- `provider_payload` (json)

## Algorithme d'echeances (scheduler)

### Commande `subscriptions:check-echeances`

- Cree les echeances auto pour les abonnements non manuels.
- Cree les echeances mensuelles manuelles (visibles membre + admin) avec:
  - `payment_origin=manual`
  - `auto_charge_eligible=false`
- Idempotence renforcee:
  - pas de doublons meme scope/periode/source
  - filtres de statut pour eviter les recreations incoherentes.

### Commande `subscriptions:process-payments`

- Ne traite que les echeances:
  - `statut in (a_payer, echec)`
  - `auto_charge_eligible=true`
  - `payment_origin != manual`
- Ajoute un garde-fou supplementaire:
  - si user/entreprise en manuel actif, echeance ignoree.
- Retry conserve (3 tentatives / 7 jours), annulation selon regles existantes.

### Commande `subscriptions:reconcile-echeances`

- Reconciliation des `en_attente` via provider resolver.
- Exclut les echeances manuelles/non auto-charge.

## Architecture provider (preparation)

- `PaymentProviderInterface`
- `ProviderResolver`
- `StripeProvider`

Benefice:

- le scheduler et les verifications n'appellent plus Stripe en direct partout;
- ajout futur de PayPal/Google Pay/Apple Pay/Samsung Pay simplifie (nouveau provider + resolver).

## Module admin dettes/paiements

### Nouvelles actions

- `POST /admin/echeances/manual`
- `POST /admin/echeances/{echeance}/manual-pay`
- `POST /admin/echeances/{echeance}/convert-to-manual`
- `POST /admin/echeances/{echeance}/offline-settled`

### Cas "bascule vers manuel"

Choix metier applique:

- les anciennes dettes Stripe ne sont pas auto-annulees;
- elles sont traitees au cas par cas via actions admin.

## Correctifs UX membres manuels

Dans dashboard/settings/checkout:

- si abonnement manuel actif, l'affichage des echeances dues est filtre sur l'origine manuelle;
- evite les faux messages "vous devez de l'argent" issus d'anciennes dettes Stripe non arbitrees.

## Cohérence d'execution CRON

`/cron-run` execute maintenant aussi:

- `subscriptions:process-payments`

pour rester coherent avec `check` + `reconcile`.

## Validation et tests

- Lints: OK sur fichiers modifies.
- Suite tests Laravel:
  - correction du setup DB tests (base MySQL `testing`),
  - ajout `EcheanceFactory`,
  - ajustements tests (`Tarif::updateOrCreate`, mocking verification Stripe),
  - resultat final: tests passes.

## Checklist de deploiement

1. `php artisan migrate`
2. verifier scheduler:
   - `subscriptions:check-echeances`
   - `subscriptions:process-payments`
   - `subscriptions:reconcile-echeances`
3. verifier admin:
   - creation dette manuelle
   - marquage paye manuel
   - conversion Stripe -> manuel
4. verifier un compte manuel actif:
   - aucune tentative auto-charge
   - affichage coherent des dettes.
