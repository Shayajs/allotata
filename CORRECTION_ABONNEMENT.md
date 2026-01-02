# Correction du problème d'abonnement

## Problème identifié

L'abonnement `sub_1SlCTxIc1BVXq0Hli8lkyAp7` a été créé avec :
- ❌ `type: NULL` au lieu du type attendu (`entreprise_site_web_X` ou `entreprise_multi_personnes_X`)
- ❌ Pas d'entrée dans la table `entreprise_subscriptions`
- ❌ Redirection vers `/abonnement` au lieu de la route d'entreprise

## Cause

Les métadonnées n'ont pas été correctement passées lors de la création du checkout, ou l'utilisateur a utilisé le mauvais contrôleur (SubscriptionController au lieu de EntrepriseSubscriptionController).

## Corrections apportées

### 1. ✅ Ajout du nom d'abonnement dans les métadonnées
Dans `EntrepriseSubscriptionController::checkout()`, on ajoute maintenant `'name' => $subscriptionName` dans les métadonnées pour que Cashier puisse l'utiliser.

### 2. ✅ Webhook handler amélioré
Le webhook `handleCustomerSubscriptionCreated` crée maintenant automatiquement l'entrée dans `entreprise_subscriptions` si les métadonnées contiennent `entreprise_id` et `type`.

## Pour corriger l'abonnement existant

Si vous avez un abonnement existant avec le mauvais type, vous pouvez :

1. **Option 1 : Le corriger manuellement** (si vous connaissez l'entreprise et le type) :
   ```php
   // Dans tinker
   $sub = \Laravel\Cashier\Subscription::where('stripe_id', 'sub_1SlCTxIc1BVXq0Hli8lkyAp7')->first();
   $entreprise = \App\Models\Entreprise::find(X); // Remplacer X par l'ID de l'entreprise
   $type = 'site_web'; // ou 'multi_personnes'
   
   $subscriptionName = 'entreprise_' . $type . '_' . $entreprise->id;
   $sub->type = $subscriptionName;
   $sub->save();
   
   \App\Models\EntrepriseSubscription::updateOrCreate(
       ['entreprise_id' => $entreprise->id, 'type' => $type],
       [
           'name' => $subscriptionName,
           'stripe_id' => $sub->stripe_id,
           'stripe_status' => $sub->stripe_status,
           'stripe_price' => $sub->stripe_price,
           'est_manuel' => false,
           'trial_ends_at' => $sub->trial_ends_at,
           'ends_at' => $sub->ends_at,
       ]
   );
   ```

2. **Option 2 : Annuler et recréer** l'abonnement via l'interface d'entreprise (recommandé)

## Test

Pour tester que tout fonctionne maintenant :

1. Allez sur le dashboard d'une entreprise
2. Cliquez sur l'onglet "Abonnements"
3. Souscrivez à un nouvel abonnement
4. Vérifiez que :
   - ✅ Vous êtes redirigé vers le dashboard de l'entreprise (pas `/abonnement`)
   - ✅ L'abonnement apparaît dans la liste des abonnements de l'entreprise
   - ✅ L'abonnement est actif

## Vérification

Pour vérifier qu'un abonnement est correctement configuré :

```php
// Dans tinker
$sub = \Laravel\Cashier\Subscription::where('stripe_id', 'sub_XXXXX')->first();
echo "Type: " . ($sub->type ?? 'NULL') . "\n";
echo "Status: " . $sub->stripe_status . "\n";

// Vérifier dans entreprise_subscriptions
$entSub = \App\Models\EntrepriseSubscription::where('stripe_id', 'sub_XXXXX')->first();
if ($entSub) {
    echo "✅ Trouvé dans entreprise_subscriptions\n";
    echo "   Entreprise: " . $entSub->entreprise->nom . "\n";
    echo "   Type: " . $entSub->type . "\n";
} else {
    echo "❌ NON trouvé dans entreprise_subscriptions\n";
}
```
