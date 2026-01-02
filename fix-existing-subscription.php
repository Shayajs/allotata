<?php

/**
 * Script pour corriger l'abonnement existant qui a été créé avec le mauvais type
 * 
 * Usage: php artisan tinker < fix-existing-subscription.php
 * ou: ./vendor/bin/sail artisan tinker
 * puis copier-coller le contenu
 */

use App\Models\Entreprise;
use App\Models\StripeTransaction;
use Laravel\Cashier\Subscription;

// Trouver l'abonnement avec le mauvais type
$subscription = Subscription::where('stripe_id', 'sub_1SlCTxIc1BVXq0Hli8lkyAp7')->first();

if (!$subscription) {
    echo "Abonnement non trouvé\n";
    exit;
}

echo "Abonnement trouvé: {$subscription->stripe_id}\n";
echo "Type actuel: " . ($subscription->type ?? 'NULL') . "\n";
echo "User ID: {$subscription->user_id}\n";

// Chercher dans les transactions pour trouver les métadonnées du checkout
$checkoutTransaction = StripeTransaction::where('stripe_subscription_id', $subscription->stripe_id)
    ->where('event_type', 'checkout.session.completed')
    ->first();

if ($checkoutTransaction && $checkoutTransaction->raw_data) {
    $checkoutMetadata = $checkoutTransaction->raw_data['data']['object']['metadata'] ?? [];
    echo "Métadonnées du checkout: " . json_encode($checkoutMetadata) . "\n";
    
    if (isset($checkoutMetadata['entreprise_id']) && isset($checkoutMetadata['type'])) {
        $entreprise = Entreprise::find($checkoutMetadata['entreprise_id']);
        
        if ($entreprise) {
            $subscriptionName = 'entreprise_' . $checkoutMetadata['type'] . '_' . $entreprise->id;
            
            echo "Nom d'abonnement attendu: {$subscriptionName}\n";
            
            // Corriger le type de l'abonnement
            $subscription->type = $subscriptionName;
            $subscription->save();
            
            echo "✅ Type d'abonnement corrigé: {$subscriptionName}\n";
            
            // Créer l'entrée dans entreprise_subscriptions
            $entrepriseSubscription = \App\Models\EntrepriseSubscription::updateOrCreate(
                [
                    'entreprise_id' => $entreprise->id,
                    'type' => $checkoutMetadata['type'],
                ],
                [
                    'name' => $subscriptionName,
                    'stripe_id' => $subscription->stripe_id,
                    'stripe_status' => $subscription->stripe_status,
                    'stripe_price' => $subscription->stripe_price,
                    'est_manuel' => false,
                    'trial_ends_at' => $subscription->trial_ends_at,
                    'ends_at' => $subscription->ends_at,
                ]
            );
            
            echo "✅ Abonnement d'entreprise créé dans entreprise_subscriptions\n";
            echo "   Entreprise: {$entreprise->nom} (ID: {$entreprise->id})\n";
            echo "   Type: {$checkoutMetadata['type']}\n";
        } else {
            echo "❌ Entreprise non trouvée (ID: {$checkoutMetadata['entreprise_id']})\n";
        }
    } else {
        echo "❌ Métadonnées d'entreprise non trouvées dans le checkout\n";
    }
} else {
    echo "❌ Transaction de checkout non trouvée\n";
}
