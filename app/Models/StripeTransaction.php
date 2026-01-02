<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StripeTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'stripe_customer_id',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'stripe_invoice_id',
        'stripe_subscription_id',
        'stripe_checkout_session_id',
        'event_type',
        'stripe_event_id',
        'amount',
        'currency',
        'status',
        'metadata',
        'raw_data',
        'description',
        'processed',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'raw_data' => 'array',
        'processed' => 'boolean',
        'processed_at' => 'datetime',
    ];

    /**
     * Relation : Une transaction appartient à un utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Créer une transaction depuis un événement Stripe
     */
    public static function createFromStripeEvent(array $eventData): ?self
    {
        try {
            $event = $eventData['type'] ?? null;
            $data = $eventData['data']['object'] ?? [];
            $eventId = $eventData['id'] ?? null;
            
            // Si pas d'ID d'événement, on ne peut pas créer une transaction unique
            if (!$eventId) {
                \Log::warning('Tentative de création de transaction Stripe sans event_id', [
                    'event_type' => $event,
                ]);
                return null;
            }
            
            // Vérifier si la transaction existe déjà (éviter les doublons)
            $existing = self::where('stripe_event_id', $eventId)->first();
            if ($existing) {
                return $existing;
            }
            
            // Extraire les IDs selon le type d'événement
            $transaction = new self();
            $transaction->event_type = $event;
            $transaction->stripe_event_id = $eventId;
            $transaction->raw_data = $eventData;
        
        // Extraire les informations selon le type d'événement
        if (isset($data['customer'])) {
            $transaction->stripe_customer_id = $data['customer'];
        }
        
        if (isset($data['id'])) {
            // Identifier le type d'objet et stocker l'ID approprié
            if (str_starts_with($data['id'], 'pi_')) {
                $transaction->stripe_payment_intent_id = $data['id'];
            } elseif (str_starts_with($data['id'], 'ch_')) {
                $transaction->stripe_charge_id = $data['id'];
            } elseif (str_starts_with($data['id'], 'in_')) {
                $transaction->stripe_invoice_id = $data['id'];
            } elseif (str_starts_with($data['id'], 'sub_')) {
                $transaction->stripe_subscription_id = $data['id'];
            } elseif (str_starts_with($data['id'], 'cs_')) {
                $transaction->stripe_checkout_session_id = $data['id'];
            }
        }
        
        // Extraire payment_intent si présent
        if (isset($data['payment_intent'])) {
            $transaction->stripe_payment_intent_id = $data['payment_intent'];
        }
        
        // Extraire charge si présent
        if (isset($data['charge'])) {
            $transaction->stripe_charge_id = $data['charge'];
        }
        
        // Extraire subscription si présent
        if (isset($data['subscription'])) {
            $transaction->stripe_subscription_id = $data['subscription'];
        }
        
        // Extraire le montant
        if (isset($data['amount'])) {
            $transaction->amount = $data['amount'] / 100; // Convertir de centimes en euros
        } elseif (isset($data['amount_total'])) {
            $transaction->amount = $data['amount_total'] / 100;
        }
        
        // Extraire la devise
        if (isset($data['currency'])) {
            $transaction->currency = strtoupper($data['currency']);
        }
        
        // Extraire le statut
        if (isset($data['status'])) {
            $transaction->status = $data['status'];
        }
        
        // Extraire les métadonnées
        if (isset($data['metadata'])) {
            $transaction->metadata = $data['metadata'];
        }
        
        // Extraire la description
        if (isset($data['description'])) {
            $transaction->description = $data['description'];
        }
        
        // Trouver l'utilisateur via le customer_id
        if ($transaction->stripe_customer_id) {
            $user = User::where('stripe_id', $transaction->stripe_customer_id)->first();
            if ($user) {
                $transaction->user_id = $user->id;
            }
        }
        
            $transaction->save();
            
            return $transaction;
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la création de la transaction Stripe', [
                'error' => $e->getMessage(),
                'event_id' => $eventId ?? null,
                'event_type' => $event ?? null,
            ]);
            return null;
        }
    }

    /**
     * Marquer la transaction comme traitée
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'processed' => true,
            'processed_at' => now(),
        ]);
    }

    /**
     * Trouver une transaction par payment_intent_id
     */
    public static function findByPaymentIntent(string $paymentIntentId): ?self
    {
        return self::where('stripe_payment_intent_id', $paymentIntentId)->first();
    }

    /**
     * Trouver une transaction par charge_id
     */
    public static function findByCharge(string $chargeId): ?self
    {
        return self::where('stripe_charge_id', $chargeId)->first();
    }

    /**
     * Trouver toutes les transactions d'un utilisateur
     */
    public static function findByUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('user_id', $userId)->orderBy('created_at', 'desc')->get();
    }
}
