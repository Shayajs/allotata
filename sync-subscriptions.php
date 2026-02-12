<?php

/**
 * Script pour synchroniser manuellement les abonnements depuis Stripe
 * 
 * Usage: ./vendor/bin/sail artisan tinker
 * Puis copier-coller le contenu
 */

use Laravel\Cashier\Subscription;
use App\Models\EntrepriseSubscription;

echo "Synchronisation des abonnements depuis Stripe...\n\n";

$subscriptions = Subscription::where('stripe_status', 'active')->get();

foreach ($subscriptions as $subscription) {
    try {
        $stripeSubscription = $subscription->asStripeSubscription();
        
        $needsUpdate = false;
        $updates = [];
        
        // Vérifier cancel_at_period_end
        if ($stripeSubscription->cancel_at_period_end) {
            $expectedEndsAt = null;
            
            if (isset($stripeSubscription->cancel_at)) {
                $expectedEndsAt = \Carbon\Carbon::createFromTimestamp($stripeSubscription->cancel_at);
            } elseif (isset($stripeSubscription->current_period_end)) {
                $expectedEndsAt = \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end);
            }
            
            if ($expectedEndsAt) {
                if (!$subscription->ends_at || !$subscription->ends_at->equalTo($expectedEndsAt)) {
                    $updates['ends_at'] = $expectedEndsAt;
                    $needsUpdate = true;
                }
            }
        } else {
            // Si cancel_at_period_end est false, supprimer ends_at
            if ($subscription->ends_at) {
                $updates['ends_at'] = null;
                $needsUpdate = true;
            }
        }
        
        // Vérifier le statut
        if ($subscription->stripe_status !== $stripeSubscription->status) {
            $updates['stripe_status'] = $stripeSubscription->status;
            $needsUpdate = true;
        }
        
        if ($needsUpdate) {
            $subscription->update($updates);
            
            echo "✅ Abonnement {$subscription->stripe_id} mis à jour:\n";
            foreach ($updates as $key => $value) {
                if ($value instanceof \Carbon\Carbon) {
                    echo "   {$key}: {$value->format('Y-m-d H:i:s')}\n";
                } else {
                    echo "   {$key}: " . ($value ?? 'NULL') . "\n";
                }
            }
            
            // Mettre à jour aussi dans entreprise_subscriptions si c'est un abonnement d'entreprise
            $entrepriseSubscription = EntrepriseSubscription::where('stripe_id', $subscription->stripe_id)->first();
            if ($entrepriseSubscription) {
                $entrepriseSubscription->update([
                    'stripe_status' => $subscription->stripe_status,
                    'ends_at' => $subscription->ends_at,
                ]);
                echo "   → Abonnement d'entreprise également mis à jour\n";
            }
        } else {
            echo "✓ Abonnement {$subscription->stripe_id} déjà à jour\n";
        }
        
    } catch (\Exception $e) {
        echo "❌ Erreur pour l'abonnement {$subscription->stripe_id}: {$e->getMessage()}\n";
    }
}

echo "\nSynchronisation terminée.\n";
