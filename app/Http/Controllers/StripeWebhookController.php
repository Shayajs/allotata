<?php

namespace App\Http\Controllers;

use App\Models\StripeTransaction;
use App\Services\PaymentVerificationService;
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
     *
     * Pour checkout.session.completed (paiement échéance) : 1er niveau de vérification.
     * Si le webhook échoue, le retour success (vérif directe Stripe) ou le CRON de
     * réconciliation rattraperont.
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

        // 1. Checkout session complétée (paiement ponctuel échéance) : marquer échéance payée
        if ($eventType === 'checkout.session.completed') {
            $this->handleCheckoutSessionCompletedForEcheance($payload);
        }

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
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Erreur Stripe lors de l\'enregistrement de la transaction', [
                'error' => $e->getMessage(),
                'event_type' => $eventType,
                'event_id' => $payload['id'] ?? null,
                'exception_type' => get_class($e),
                'raw_error' => json_encode($e->getError()),
            ]);
        } catch (\Exception $e) {
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

            if (isset($transaction)) {
                $transaction->markAsProcessed();
            }

            return $response;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Erreur Stripe lors du traitement du webhook par Cashier', [
                'error' => $e->getMessage(),
                'event_type' => $eventType,
                'event_id' => $payload['id'] ?? null,
                'exception_type' => get_class($e),
                'raw_error' => json_encode($e->getError()),
            ]);
            // Ne pas bloquer le webhook, Stripe réessayera
            return $this->successMethod();
        } catch (\Exception $e) {
            Log::error('Erreur lors du traitement du webhook Stripe par Cashier', [
                'error' => $e->getMessage(),
                'event_type' => $eventType,
                'event_id' => $payload['id'] ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Traiter checkout.session.completed pour nos paiements échéance (mode payment).
     * Appelle PaymentVerificationService (fetch Stripe + marquage). Idempotent.
     * N'interrompt pas le webhook en cas d'erreur : CRON / retour success rattraperont.
     */
    protected function handleCheckoutSessionCompletedForEcheance(array $payload): void
    {
        $data = $payload['data']['object'] ?? [];
        $sessionId = $data['id'] ?? null;
        if (!$sessionId || !str_starts_with((string) $sessionId, 'cs_')) {
            return;
        }
        $mode = $data['mode'] ?? null;
        $paymentStatus = $data['payment_status'] ?? null;
        if ($mode !== 'payment' || $paymentStatus !== 'paid') {
            return;
        }
        $metadata = $data['metadata'] ?? [];
        if (is_object($metadata)) {
            $metadata = (array) $metadata;
        }
        if (empty($metadata['echeance_id']) || empty($metadata['user_id'])) {
            return;
        }

        try {
            $result = PaymentVerificationService::verifyAndMarkPaid($sessionId);
            if ($result['ok']) {
                Log::info('Webhook checkout.session.completed : échéance marquée payée', [
                    'session_id' => $sessionId,
                    'echeance_id' => $result['echeance_id'],
                    'already' => $result['already'],
                ]);
            } else {
                $userId = (int) ($metadata['user_id'] ?? 0);
                $echeanceId = (int) ($metadata['echeance_id'] ?? 0);
                Log::warning('Webhook checkout.session.completed : vérification échec', [
                    'session_id' => $sessionId,
                    'message' => $result['message'],
                ]);
                if ($userId) {
                    try {
                        \App\Models\PaymentAuditLog::log('webhook_verify_fail', $userId, [
                            'echeance_id' => $echeanceId,
                            'stripe_checkout_session_id' => $sessionId,
                            'status' => 'failed',
                            'context' => ['message' => $result['message']],
                            'message' => 'Échec vérification webhook: ' . ($result['message'] ?? ''),
                        ]);
                    } catch (\Throwable $logEx) {
                        Log::warning('PaymentAuditLog webhook_verify_fail failed', ['error' => $logEx->getMessage()]);
                    }
                }
            }
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $userId = (int) ($metadata['user_id'] ?? 0);
            $echeanceId = (int) ($metadata['echeance_id'] ?? 0);
            Log::error('Webhook checkout.session.completed : ApiErrorException', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'exception_type' => get_class($e),
                'raw_error' => json_encode($e->getError()),
            ]);
            if ($userId) {
                try {
                    \App\Models\PaymentAuditLog::log('webhook_verify_fail', $userId, [
                        'echeance_id' => $echeanceId,
                        'stripe_checkout_session_id' => $sessionId,
                        'status' => 'failed',
                        'context' => [
                            'exception_type' => get_class($e),
                            'raw_error' => json_encode($e->getError()),
                        ],
                        'message' => 'Erreur API Stripe lors de la vérification webhook: ' . $e->getMessage(),
                    ]);
                } catch (\Throwable $logEx) {
                    Log::warning('PaymentAuditLog webhook_verify_fail failed', ['error' => $logEx->getMessage()]);
                }
            }
        } catch (\Throwable $e) {
            $userId = (int) ($metadata['user_id'] ?? 0);
            $echeanceId = (int) ($metadata['echeance_id'] ?? 0);
            Log::error('Webhook checkout.session.completed : exception', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            if ($userId) {
                try {
                    \App\Models\PaymentAuditLog::log('webhook_verify_fail', $userId, [
                        'echeance_id' => $echeanceId,
                        'stripe_checkout_session_id' => $sessionId,
                        'status' => 'failed',
                        'context' => [
                            'exception_type' => get_class($e),
                            'raw_error' => $e->getMessage(),
                        ],
                        'message' => 'Exception inattendue lors de la vérification webhook: ' . $e->getMessage(),
                    ]);
                } catch (\Throwable $logEx) {
                    Log::warning('PaymentAuditLog webhook_verify_fail failed', ['error' => $logEx->getMessage()]);
                }
            }
        }
    }

    /**
     * Gérer les événements de paiement réussis
     * 
     * Cette méthode est appelée automatiquement par Cashier pour les événements
     * payment_intent.succeeded
     * 
     * Protection contre les "transactions zombies" : si Stripe a débité mais que
     * le serveur a planté avant de mettre à jour l'échéance, le webhook rattrape.
     */
    protected function handlePaymentIntentSucceeded(array $payload)
    {
        $piId = $payload['data']['object']['id'] ?? null;
        $amount = $payload['data']['object']['amount'] ?? null;
        
        Log::info('Payment Intent réussi (webhook)', [
            'payment_intent_id' => $piId,
            'amount' => $amount,
        ]);
        
        // Vérifier si c'est un paiement d'échéance (via metadata)
        $metadata = $payload['data']['object']['metadata'] ?? [];
        if (is_object($metadata)) {
            $metadata = (array) $metadata;
        }
        
        $echeanceId = (int) ($metadata['echeance_id'] ?? 0);
        $userId = (int) ($metadata['user_id'] ?? 0);
        
        // Si c'est un paiement d'échéance, marquer l'échéance payée (idempotent)
        if ($echeanceId && $userId && $piId) {
            try {
                $result = PaymentVerificationService::markEcheancePaidFromPaymentIntent($piId);
                if ($result['ok']) {
                    Log::info('Webhook payment_intent.succeeded : échéance marquée payée', [
                        'payment_intent_id' => $piId,
                        'echeance_id' => $echeanceId,
                        'already' => $result['already'],
                    ]);
                } else {
                    Log::warning('Webhook payment_intent.succeeded : échec de marquage échéance', [
                        'payment_intent_id' => $piId,
                        'echeance_id' => $echeanceId,
                        'message' => $result['message'],
                    ]);
                    try {
                        \App\Models\PaymentAuditLog::log('webhook_pi_verify_fail', $userId, [
                            'echeance_id' => $echeanceId,
                            'stripe_payment_intent_id' => $piId,
                            'status' => 'failed',
                            'context' => ['message' => $result['message']],
                            'message' => 'Échec vérification PaymentIntent webhook: ' . ($result['message'] ?? ''),
                        ]);
                    } catch (\Throwable $logEx) {
                        Log::warning('PaymentAuditLog webhook_pi_verify_fail failed', ['error' => $logEx->getMessage()]);
                    }
                }
            } catch (\Stripe\Exception\ApiErrorException $e) {
                Log::error('Webhook payment_intent.succeeded : ApiErrorException lors du marquage', [
                    'payment_intent_id' => $piId,
                    'echeance_id' => $echeanceId,
                    'error' => $e->getMessage(),
                    'exception_type' => get_class($e),
                    'raw_error' => json_encode($e->getError()),
                ]);
                try {
                    \App\Models\PaymentAuditLog::log('webhook_pi_verify_fail', $userId, [
                        'echeance_id' => $echeanceId,
                        'stripe_payment_intent_id' => $piId,
                        'status' => 'failed',
                        'context' => [
                            'exception_type' => get_class($e),
                            'raw_error' => json_encode($e->getError()),
                        ],
                        'message' => 'Erreur API Stripe lors de la vérification PaymentIntent: ' . $e->getMessage(),
                    ]);
                } catch (\Throwable $logEx) {
                    Log::warning('PaymentAuditLog webhook_pi_verify_fail failed', ['error' => $logEx->getMessage()]);
                }
            } catch (\Throwable $e) {
                Log::error('Webhook payment_intent.succeeded : exception lors du marquage', [
                    'payment_intent_id' => $piId,
                    'echeance_id' => $echeanceId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                try {
                    \App\Models\PaymentAuditLog::log('webhook_pi_verify_fail', $userId, [
                        'echeance_id' => $echeanceId,
                        'stripe_payment_intent_id' => $piId,
                        'status' => 'failed',
                        'context' => [
                            'exception_type' => get_class($e),
                            'raw_error' => $e->getMessage(),
                        ],
                        'message' => 'Exception inattendue lors de la vérification PaymentIntent: ' . $e->getMessage(),
                    ]);
                } catch (\Throwable $logEx) {
                    Log::warning('PaymentAuditLog webhook_pi_verify_fail failed', ['error' => $logEx->getMessage()]);
                }
            }
        }
        
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
                $user = \App\Models\User::where('stripe_id', $data['customer'] ?? null)->first();
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
                        
                        if ($status === 'canceled') {
                            // Si le statut est annulé, on doit avoir une date de fin
                            Log::info('Abonnement annulé détecté (status=canceled)', [
                                'subscription_id' => $subscriptionId,
                            ]);
                            
                            $endsAt = null;
                            if (isset($data['ended_at']) && $data['ended_at']) {
                                $endsAt = \Carbon\Carbon::createFromTimestamp($data['ended_at']);
                            } else {
                                $endsAt = now();
                            }
                            
                            if (!$subscription->ends_at || !$subscription->ends_at->equalTo($endsAt)) {
                                $subscription->ends_at = $endsAt;
                                $subscription->save();
                            }
                        }
                        // Forcer la mise à jour de ends_at si cancel_at_period_end est true
                        // Le handler parent de Cashier utilise currentPeriodEnd() qui peut échouer,
                        // donc on force la mise à jour avec les données du webhook
                        elseif ($cancelAtPeriodEnd) {
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
                            // Vérifier si l'abonnement est vraiment actif (pas en période de grâce, pas canceled)
                            if ($subscription->stripe_status === 'active' && $status !== 'canceled') {
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
        $subscriptionId = $payload['data']['object']['id'] ?? null;
        $customerId = $payload['data']['object']['customer'] ?? null;
        
        Log::info('Abonnement supprimé', [
            'subscription_id' => $subscriptionId,
            'customer_id' => $customerId,
        ]);
        
        // Appeler le handler parent (cette méthode existe dans Cashier)
        $response = parent::handleCustomerSubscriptionDeleted($payload);
        
        // ⚠️ S'assurer que l'abonnement est bien marqué comme annulé/supprimé
        if ($subscriptionId) {
            try {
                // 1. Mettre à jour dans la table subscriptions (Cashier)
                $subscription = \Laravel\Cashier\Subscription::where('stripe_id', $subscriptionId)->first();
                
                if ($subscription) {
                    // Marquer l'abonnement comme définitivement annulé
                    $subscription->update([
                        'stripe_status' => 'canceled',
                        'ends_at' => now(), // L'abonnement a pris fin maintenant
                    ]);
                    
                    Log::info('Abonnement Cashier marqué comme annulé', [
                        'subscription_id' => $subscriptionId,
                        'subscription_type' => $subscription->type ?? $subscription->name ?? 'default',
                    ]);
                }
                
                // 2. Mettre à jour dans entreprise_subscriptions si c'est un abonnement entreprise
                $entrepriseSubscription = \App\Models\EntrepriseSubscription::where('stripe_id', $subscriptionId)->first();
                
                if ($entrepriseSubscription) {
                    $entrepriseSubscription->update([
                        'stripe_status' => 'canceled',
                        'ends_at' => now(),
                    ]);
                    
                    Log::info('Abonnement entreprise marqué comme annulé', [
                        'entreprise_subscription_id' => $entrepriseSubscription->id,
                        'entreprise_id' => $entrepriseSubscription->entreprise_id,
                        'type' => $entrepriseSubscription->type,
                    ]);
                }
                
            } catch (\Exception $e) {
                Log::error('Erreur lors du marquage de l\'abonnement comme supprimé', [
                    'subscription_id' => $subscriptionId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
        
        return $response;
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
