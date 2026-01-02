<?php

namespace App\Http\Controllers;

use App\Models\StripeTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;

class StripeWebhookController extends CashierController
{
    /**
     * Gérer les webhooks Stripe
     * 
     * Cette méthode intercepte tous les webhooks Stripe avant qu'ils ne soient traités
     * par Laravel Cashier, pour les logger et les stocker dans la base de données.
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        $eventType = $payload['type'] ?? 'unknown';
        
        // Logger l'événement
        Log::info('Webhook Stripe reçu', [
            'event_type' => $eventType,
            'event_id' => $payload['id'] ?? null,
        ]);
        
        // Stocker la transaction dans la base de données (ne bloque pas si ça échoue)
        $transaction = null;
        try {
            $transaction = StripeTransaction::createFromStripeEvent($payload);
            
            if ($transaction) {
                Log::info('Transaction Stripe enregistrée', [
                    'transaction_id' => $transaction->id,
                    'event_type' => $eventType,
                    'stripe_event_id' => $transaction->stripe_event_id,
                ]);
            }
        } catch (\Exception $e) {
            // Logger l'erreur mais ne pas bloquer le traitement du webhook
            Log::error('Erreur lors de l\'enregistrement de la transaction Stripe', [
                'error' => $e->getMessage(),
                'event_type' => $eventType,
                'event_id' => $payload['id'] ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
        }
        
        // Appeler le handler parent de Cashier pour le traitement standard
        try {
            $response = parent::handleWebhook($request);
            
            // Marquer la transaction comme traitée si elle existe
            if (isset($transaction)) {
                $transaction->markAsProcessed();
            }
            
            return $response;
            
        } catch (\Exception $e) {
            // Logger l'erreur de traitement
            Log::error('Erreur lors du traitement du webhook Stripe par Cashier', [
                'error' => $e->getMessage(),
                'event_type' => $eventType,
                'event_id' => $payload['id'] ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Re-lancer l'exception pour que Stripe puisse réessayer
            throw $e;
        }
    }

    /**
     * Gérer les événements de paiement réussis
     * 
     * Cette méthode est appelée automatiquement par Cashier pour les événements
     * payment_intent.succeeded
     */
    protected function handlePaymentIntentSucceeded(array $payload)
    {
        Log::info('Payment Intent réussi', [
            'payment_intent_id' => $payload['data']['object']['id'] ?? null,
            'amount' => $payload['data']['object']['amount'] ?? null,
        ]);
        
        // Appeler le handler parent si la méthode existe
        if (method_exists(parent::class, 'handlePaymentIntentSucceeded')) {
            return parent::handlePaymentIntentSucceeded($payload);
        }
        
        return $this->successMethod();
    }

    /**
     * Gérer les événements d'abonnement créés
     */
    protected function handleCustomerSubscriptionCreated(array $payload)
    {
        $subscriptionId = $payload['data']['object']['id'] ?? null;
        $customerId = $payload['data']['object']['customer'] ?? null;
        $metadata = $payload['data']['object']['metadata'] ?? [];
        
        Log::info('Abonnement créé', [
            'subscription_id' => $subscriptionId,
            'customer_id' => $customerId,
            'metadata' => $metadata,
            'type_from_metadata' => $metadata['name'] ?? $metadata['type'] ?? 'default',
        ]);
        
        // Appeler le handler parent (cette méthode existe dans Cashier)
        $response = parent::handleCustomerSubscriptionCreated($payload);
        
        // Si c'est un abonnement d'entreprise, créer l'entrée dans entreprise_subscriptions
        if (isset($metadata['entreprise_id']) && isset($metadata['type'])) {
            try {
                $entreprise = \App\Models\Entreprise::find($metadata['entreprise_id']);
                if ($entreprise) {
                    $subscriptionName = $metadata['name'] ?? 'entreprise_' . $metadata['type'] . '_' . $entreprise->id;
                    $user = \App\Models\User::where('stripe_id', $customerId)->first();
                    
                    if ($user) {
                        $subscription = $user->subscription($subscriptionName);
                        if ($subscription) {
                            \App\Models\EntrepriseSubscription::updateOrCreate(
                                [
                                    'entreprise_id' => $entreprise->id,
                                    'type' => $metadata['type'],
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
                            
                            Log::info('Abonnement d\'entreprise créé automatiquement', [
                                'entreprise_id' => $entreprise->id,
                                'type' => $metadata['type'],
                                'subscription_id' => $subscription->stripe_id,
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Erreur lors de la création de l\'abonnement d\'entreprise', [
                    'error' => $e->getMessage(),
                    'subscription_id' => $subscriptionId,
                    'metadata' => $metadata,
                ]);
            }
        }
        
        return $response;
    }

    /**
     * Gérer les événements d'abonnement mis à jour
     */
    protected function handleCustomerSubscriptionUpdated(array $payload)
    {
        $subscriptionId = $payload['data']['object']['id'] ?? null;
        $data = $payload['data']['object'];
        $status = $data['status'] ?? null;
        $cancelAtPeriodEnd = $data['cancel_at_period_end'] ?? false;
        $cancelAt = $data['cancel_at'] ?? null;
        $currentPeriodEnd = $data['current_period_end'] ?? null;
        
        Log::info('Abonnement mis à jour', [
            'subscription_id' => $subscriptionId,
            'status' => $status,
            'cancel_at_period_end' => $cancelAtPeriodEnd,
            'cancel_at' => $cancelAt,
            'current_period_end' => $currentPeriodEnd,
        ]);
        
        // Appeler le handler parent (cette méthode existe dans Cashier)
        $response = parent::handleCustomerSubscriptionUpdated($payload);
        
        // Vérifier que la mise à jour a bien été effectuée
        if ($subscriptionId) {
            try {
                $user = $this->getUserByStripeId($data['customer'] ?? null);
                if ($user) {
                    // Trouver l'abonnement par stripe_id (peu importe le type)
                    $subscription = \Laravel\Cashier\Subscription::where('stripe_id', $subscriptionId)->first();
                    
                    if (!$subscription) {
                        // Si l'abonnement n'existe pas encore, le créer
                        $subscriptionType = $data['metadata']['name'] ?? $data['metadata']['type'] ?? 'default';
                        $subscription = $user->subscriptions()->create([
                            'type' => $subscriptionType,
                            'stripe_id' => $subscriptionId,
                            'stripe_status' => $status ?? 'active',
                            'stripe_price' => $data['items']['data'][0]['price']['id'] ?? null,
                            'quantity' => $data['items']['data'][0]['quantity'] ?? 1,
                        ]);
                        
                        Log::info('Abonnement créé depuis webhook updated', [
                            'subscription_id' => $subscriptionId,
                            'type' => $subscriptionType,
                        ]);
                    }
                    
                    if ($subscription) {
                        Log::info('Vérification de la mise à jour de l\'abonnement', [
                            'subscription_id' => $subscriptionId,
                            'cancel_at_period_end' => $cancelAtPeriodEnd,
                            'cancel_at' => $cancelAt,
                            'current_period_end' => $currentPeriodEnd,
                            'ends_at_actuel' => $subscription->ends_at ? $subscription->ends_at->format('Y-m-d H:i:s') : 'NULL',
                        ]);
                        
                        // Forcer la mise à jour de ends_at si cancel_at_period_end est true
                        // Le handler parent de Cashier utilise currentPeriodEnd() qui peut échouer,
                        // donc on force la mise à jour avec les données du webhook
                        if ($cancelAtPeriodEnd) {
                            Log::info('Abonnement annulé détecté, mise à jour de ends_at', [
                                'subscription_id' => $subscriptionId,
                            ]);
                            $endsAt = null;
                            
                            // Priorité 1: Utiliser cancel_at si disponible (date exacte d'annulation)
                            if ($cancelAt) {
                                $endsAt = \Carbon\Carbon::createFromTimestamp($cancelAt);
                            }
                            // Priorité 2: Utiliser current_period_end depuis les données du webhook
                            elseif ($currentPeriodEnd) {
                                $endsAt = \Carbon\Carbon::createFromTimestamp($currentPeriodEnd);
                            }
                            // Priorité 3: Essayer de récupérer depuis Stripe directement
                            else {
                                try {
                                    $stripeSubscription = $subscription->asStripeSubscription();
                                    if (isset($stripeSubscription->current_period_end) && $stripeSubscription->current_period_end) {
                                        $endsAt = \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end);
                                    }
                                } catch (\Exception $e) {
                                    Log::warning('Impossible de récupérer current_period_end depuis Stripe', [
                                        'subscription_id' => $subscriptionId,
                                        'error' => $e->getMessage(),
                                    ]);
                                }
                            }
                            
                            if ($endsAt) {
                                // Ne mettre à jour que si la valeur est différente
                                if (!$subscription->ends_at || !$subscription->ends_at->equalTo($endsAt)) {
                                    $subscription->ends_at = $endsAt;
                                    $subscription->save();
                                    
                                    Log::info('Abonnement mis à jour avec ends_at', [
                                        'subscription_id' => $subscriptionId,
                                        'ends_at' => $endsAt->format('Y-m-d H:i:s'),
                                        'source' => $cancelAt ? 'cancel_at' : ($currentPeriodEnd ? 'current_period_end (webhook)' : 'current_period_end (stripe)'),
                                    ]);
                                }
                            } else {
                                Log::warning('Impossible de déterminer ends_at pour l\'abonnement annulé', [
                                    'subscription_id' => $subscriptionId,
                                    'cancel_at' => $cancelAt,
                                    'current_period_end' => $currentPeriodEnd,
                                ]);
                            }
                        }
                        // Si cancel_at_period_end est false mais qu'il y avait une date d'annulation, la supprimer
                        elseif (!$cancelAtPeriodEnd && $subscription->ends_at) {
                            // Vérifier si l'abonnement est vraiment actif (pas en période de grâce)
                            if ($subscription->stripe_status === 'active' && !$subscription->onGracePeriod()) {
                                $subscription->ends_at = null;
                                $subscription->save();
                                
                                Log::info('Date d\'annulation supprimée (abonnement réactivé)', [
                                    'subscription_id' => $subscriptionId,
                                ]);
                            }
                        }
                        
                        // Mettre à jour le statut si nécessaire
                        if ($status && $subscription->stripe_status !== $status) {
                            $subscription->stripe_status = $status;
                            $subscription->save();
                            
                            Log::info('Statut de l\'abonnement mis à jour', [
                                'subscription_id' => $subscriptionId,
                                'old_status' => $subscription->getOriginal('stripe_status'),
                                'new_status' => $status,
                            ]);
                        }
                        
                        // Mettre à jour aussi dans entreprise_subscriptions si c'est un abonnement d'entreprise
                        // On peut identifier un abonnement d'entreprise par :
                        // 1. Le stripe_id correspond
                        // 2. Le type de l'abonnement commence par "entreprise_"
                        $entrepriseSubscription = \App\Models\EntrepriseSubscription::where('stripe_id', $subscriptionId)->first();
                        
                        // Si pas trouvé par stripe_id, chercher par le type de l'abonnement
                        if (!$entrepriseSubscription && (str_starts_with($subscription->type ?? '', 'entreprise_') || str_starts_with($subscription->name ?? '', 'entreprise_'))) {
                            // Extraire entreprise_id et type depuis le nom (format: entreprise_{type}_{entreprise_id})
                            $name = $subscription->type ?? $subscription->name ?? '';
                            if (preg_match('/entreprise_(\w+)_(\d+)/', $name, $matches)) {
                                $type = $matches[1];
                                $entrepriseId = $matches[2];
                                
                                $entrepriseSubscription = \App\Models\EntrepriseSubscription::where('entreprise_id', $entrepriseId)
                                    ->where('type', $type)
                                    ->first();
                                
                                // Si trouvé, mettre à jour le stripe_id
                                if ($entrepriseSubscription && !$entrepriseSubscription->stripe_id) {
                                    $entrepriseSubscription->stripe_id = $subscriptionId;
                                }
                            }
                        }
                        
                        if ($entrepriseSubscription) {
                            $entrepriseSubscription->update([
                                'stripe_id' => $subscriptionId, // S'assurer que le stripe_id est bien défini
                                'stripe_status' => $subscription->stripe_status,
                                'stripe_price' => $subscription->stripe_price,
                                'ends_at' => $subscription->ends_at,
                                'trial_ends_at' => $subscription->trial_ends_at,
                            ]);
                            
                            Log::info('Abonnement d\'entreprise mis à jour', [
                                'entreprise_subscription_id' => $entrepriseSubscription->id,
                                'entreprise_id' => $entrepriseSubscription->entreprise_id,
                                'type' => $entrepriseSubscription->type,
                                'stripe_status' => $subscription->stripe_status,
                                'ends_at' => $subscription->ends_at ? $subscription->ends_at->format('Y-m-d H:i:s') : 'NULL',
                            ]);
                        } elseif (str_starts_with($subscription->type ?? '', 'entreprise_') || str_starts_with($subscription->name ?? '', 'entreprise_')) {
                            // Si c'est un abonnement d'entreprise mais pas trouvé dans entreprise_subscriptions, logger un avertissement
                            Log::warning('Abonnement d\'entreprise non trouvé dans entreprise_subscriptions', [
                                'subscription_id' => $subscriptionId,
                                'subscription_type' => $subscription->type ?? 'NULL',
                                'subscription_name' => $subscription->name ?? 'NULL',
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Erreur lors de la mise à jour de l\'abonnement après webhook', [
                    'subscription_id' => $subscriptionId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
        
        return $response;
    }

    /**
     * Gérer les événements d'abonnement supprimés
     */
    protected function handleCustomerSubscriptionDeleted(array $payload)
    {
        Log::info('Abonnement supprimé', [
            'subscription_id' => $payload['data']['object']['id'] ?? null,
            'customer_id' => $payload['data']['object']['customer'] ?? null,
        ]);
        
        // Appeler le handler parent (cette méthode existe dans Cashier)
        return parent::handleCustomerSubscriptionDeleted($payload);
    }

    /**
     * Gérer les événements de facture payée
     * Note: Cette méthode n'existe pas dans Cashier par défaut, donc on retourne juste success
     */
    protected function handleInvoicePaymentSucceeded(array $payload)
    {
        Log::info('Facture payée avec succès', [
            'invoice_id' => $payload['data']['object']['id'] ?? null,
            'subscription_id' => $payload['data']['object']['subscription'] ?? null,
            'amount_paid' => $payload['data']['object']['amount_paid'] ?? null,
        ]);
        
        // Cette méthode n'existe pas dans Cashier, donc on retourne juste success
        return $this->successMethod();
    }

    /**
     * Gérer les événements de facture en échec
     * Note: Cette méthode n'existe pas dans Cashier par défaut, donc on retourne juste success
     */
    protected function handleInvoicePaymentFailed(array $payload)
    {
        Log::warning('Échec du paiement de la facture', [
            'invoice_id' => $payload['data']['object']['id'] ?? null,
            'subscription_id' => $payload['data']['object']['subscription'] ?? null,
            'amount_due' => $payload['data']['object']['amount_due'] ?? null,
        ]);
        
        // Cette méthode n'existe pas dans Cashier, donc on retourne juste success
        return $this->successMethod();
    }
}
