# ✅ Vérification de la Configuration Stripe

## Checklist de vérification

### 1. Configuration CSRF ✅
- [x] Exception CSRF configurée dans `bootstrap/app.php` pour `/stripe/*`
- [x] Route webhook définie dans `routes/web.php`
- [x] Route webhook utilise le handler personnalisé `StripeWebhookController`

### 2. Configuration Stripe ✅
- [ ] `STRIPE_KEY` présent dans `.env`
- [ ] `STRIPE_SECRET` présent dans `.env`
- [ ] `STRIPE_WEBHOOK_SECRET` présent dans `.env`
- [ ] Configuration dans `config/services.php` correcte

### 3. Base de données ✅
- [x] Migration `create_stripe_transactions_table` créée
- [ ] Migration exécutée : `php artisan migrate`
- [x] Modèle `StripeTransaction` créé avec toutes les méthodes nécessaires

### 4. Webhook Handler ✅
- [x] `StripeWebhookController` créé et étend `CashierController`
- [x] Tous les événements sont loggés
- [x] Toutes les transactions sont stockées dans la base de données
- [x] Gestion des erreurs sans bloquer le traitement

### 5. Stockage des IDs de paiement ✅
- [x] Table `stripe_transactions` avec colonnes :
  - `stripe_payment_intent_id` (pour remboursements)
  - `stripe_charge_id` (pour remboursements)
  - `stripe_invoice_id`
  - `stripe_subscription_id`
  - `stripe_customer_id`
  - `stripe_checkout_session_id`
- [x] Méthodes de recherche dans le modèle :
  - `findByPaymentIntent()`
  - `findByCharge()`
  - `findByUser()`

## Commandes de vérification

### 1. Vérifier la configuration
```bash
./vendor/bin/sail artisan tinker
>>> config('services.stripe.webhook.secret')
>>> config('services.stripe.key')
```

### 2. Vérifier les migrations
```bash
./vendor/bin/sail artisan migrate:status
```

### 3. Vérifier les transactions stockées
```bash
./vendor/bin/sail artisan tinker
>>> App\Models\StripeTransaction::count()
>>> App\Models\StripeTransaction::latest(5)->get()
```

### 4. Tester le webhook localement
```bash
# Dans un terminal, lancer Stripe CLI
stripe listen --forward-to localhost/stripe/webhook

# Dans un autre terminal, déclencher un événement de test
stripe trigger payment_intent.succeeded
```

## Utilisation pour les remboursements

### Exemple : Rembourser un paiement
```php
use App\Models\StripeTransaction;
use Stripe\StripeClient;

// Trouver la transaction
$transaction = StripeTransaction::findByPaymentIntent('pi_xxxxx');

if ($transaction && $transaction->stripe_charge_id) {
    $stripe = new StripeClient(config('services.stripe.secret'));
    
    // Créer un remboursement
    $refund = $stripe->refunds->create([
        'charge' => $transaction->stripe_charge_id,
        'amount' => $transaction->amount * 100, // Convertir en centimes
    ]);
    
    // Logger le remboursement
    Log::info('Remboursement effectué', [
        'transaction_id' => $transaction->id,
        'refund_id' => $refund->id,
        'amount' => $refund->amount / 100,
    ]);
}
```

## Logs et monitoring

Tous les événements Stripe sont loggés dans :
- **Laravel logs** : `storage/logs/laravel.log`
- **Base de données** : Table `stripe_transactions`
- **Stripe CLI** : Terminal où tourne `stripe listen`

### Vérifier les logs
```bash
# Logs Laravel
./vendor/bin/sail artisan log:tail

# Ou directement
tail -f storage/logs/laravel.log
```

## Prochaines étapes recommandées

1. **Exécuter la migration** :
   ```bash
   ./vendor/bin/sail artisan migrate
   ```

2. **Tester le webhook** :
   ```bash
   stripe listen --forward-to localhost/stripe/webhook
   stripe trigger payment_intent.succeeded
   ```

3. **Vérifier que les transactions sont stockées** :
   ```bash
   ./vendor/bin/sail artisan tinker
   >>> App\Models\StripeTransaction::count()
   ```

4. **Créer une interface admin** (optionnel) :
   - Liste des transactions
   - Détails d'une transaction
   - Bouton de remboursement
