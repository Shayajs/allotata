<?php

/**
 * Script de test pour vérifier la mise à jour des abonnements
 * 
 * Usage: ./vendor/bin/sail artisan tinker < test-webhook-update.php
 */

use App\Models\StripeTransaction;
use Laravel\Cashier\Subscription;

// Trouver le dernier webhook customer.subscription.updated
$transaction = StripeTransaction::where('event_type', 'customer.subscription.updated')
    ->latest()
    ->first();

if (!$transaction) {
    echo "Aucun webhook customer.subscription.updated trouvé\n";
    exit;
}

echo "Dernier webhook customer.subscription.updated:\n";
echo "  Event ID: {$transaction->stripe_event_id}\n";
echo "  Date: {$transaction->created_at}\n";
echo "  Processed: " . ($transaction->processed ? 'Oui' : 'Non') . "\n\n";

if ($transaction->raw_data) {
    $data = $transaction->raw_data['data']['object'];
    $subscriptionId = $data['id'] ?? null;
    
    echo "Données du webhook:\n";
    echo "  Subscription ID: {$subscriptionId}\n";
    echo "  Status: " . ($data['status'] ?? 'NULL') . "\n";
    echo "  cancel_at_period_end: " . (($data['cancel_at_period_end'] ?? false) ? 'true' : 'false') . "\n";
    echo "  cancel_at: " . (isset($data['cancel_at']) ? date('Y-m-d H:i:s', $data['cancel_at']) : 'NULL') . "\n";
    echo "  current_period_end: " . (isset($data['current_period_end']) ? date('Y-m-d H:i:s', $data['current_period_end']) : 'NULL') . "\n\n";
    
    if ($subscriptionId) {
        $subscription = Subscription::where('stripe_id', $subscriptionId)->first();
        
        if ($subscription) {
            echo "Abonnement dans la base de données:\n";
            echo "  ID: {$subscription->id}\n";
            echo "  Type: " . ($subscription->type ?? 'NULL') . "\n";
            echo "  Status: {$subscription->stripe_status}\n";
            echo "  ends_at: " . ($subscription->ends_at ? $subscription->ends_at->format('Y-m-d H:i:s') : 'NULL') . "\n";
            echo "  onGracePeriod: " . ($subscription->onGracePeriod() ? 'Oui' : 'Non') . "\n";
            echo "  valid: " . ($subscription->valid() ? 'Oui' : 'Non') . "\n";
            
            // Vérifier si la synchronisation est correcte
            $expectedEndsAt = null;
            if ($data['cancel_at_period_end'] ?? false) {
                if (isset($data['cancel_at'])) {
                    $expectedEndsAt = \Carbon\Carbon::createFromTimestamp($data['cancel_at']);
                } elseif (isset($data['current_period_end'])) {
                    $expectedEndsAt = \Carbon\Carbon::createFromTimestamp($data['current_period_end']);
                }
            }
            
            if ($expectedEndsAt) {
                echo "\nVérification:\n";
                echo "  ends_at attendu: {$expectedEndsAt->format('Y-m-d H:i:s')}\n";
                echo "  ends_at actuel: " . ($subscription->ends_at ? $subscription->ends_at->format('Y-m-d H:i:s') : 'NULL') . "\n";
                
                if ($subscription->ends_at && $subscription->ends_at->equalTo($expectedEndsAt)) {
                    echo "  ✅ Synchronisation correcte\n";
                } else {
                    echo "  ❌ Synchronisation incorrecte - Mise à jour nécessaire\n";
                    
                    // Corriger manuellement
                    $subscription->ends_at = $expectedEndsAt;
                    $subscription->save();
                    echo "  ✅ Corrigé manuellement\n";
                }
            }
        } else {
            echo "❌ Abonnement non trouvé dans la base de données pour stripe_id: {$subscriptionId}\n";
        }
    }
}
