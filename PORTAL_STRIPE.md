# Portail Client Stripe pour la Gestion des Abonnements

## Fonctionnement

Lorsqu'un utilisateur clique sur "Annuler l'abonnement", il est maintenant redirigé vers le **Customer Portal de Stripe** au lieu d'annuler directement depuis l'application.

## Avantages

✅ **Gestion complète par Stripe** : Les utilisateurs gèrent leurs abonnements directement depuis Stripe
✅ **Sécurité** : Stripe gère toute la logique de paiement et d'annulation
✅ **Fonctionnalités supplémentaires** :
   - Annuler l'abonnement
   - Reprendre un abonnement annulé
   - Mettre à jour la méthode de paiement
   - Voir l'historique des factures
   - Télécharger les factures
   - Mettre à jour les informations de facturation

## Configuration requise

Le Customer Portal de Stripe doit être configuré dans votre tableau de bord Stripe :

1. Allez sur https://dashboard.stripe.com/settings/billing/portal
2. Activez le Customer Portal
3. Configurez les fonctionnalités disponibles :
   - ✅ Annulation d'abonnement
   - ✅ Reprise d'abonnement
   - ✅ Mise à jour de la méthode de paiement
   - ✅ Téléchargement de factures

## Routes concernées

- **Abonnement utilisateur** : `POST /abonnement/cancel` → Redirige vers le portail Stripe
- **Abonnement entreprise** : `POST /m/{slug}/abonnements/{type}/cancel` → Redirige vers le portail Stripe

## Retour après gestion

Après avoir géré leur abonnement dans le portail Stripe, les utilisateurs sont redirigés vers :
- **Abonnement utilisateur** : `/abonnement`
- **Abonnement entreprise** : `/m/{slug}?tab=abonnements`

## Webhooks

Les changements effectués dans le portail Stripe déclenchent automatiquement les webhooks :
- `customer.subscription.updated` : Quand l'abonnement est modifié
- `customer.subscription.deleted` : Quand l'abonnement est supprimé
- `invoice.payment_succeeded` : Quand un paiement réussit
- etc.

Ces webhooks sont déjà gérés par `StripeWebhookController` et mettent à jour automatiquement la base de données.

## Note pour les administrateurs

Les administrateurs peuvent toujours annuler les abonnements directement depuis l'interface admin (méthode `cancelStripeSubscription` dans `AdminController`), car ils ont besoin de cette fonctionnalité pour la gestion manuelle.
