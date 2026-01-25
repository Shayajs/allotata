<?php

namespace App\Services;

use App\Models\Echeance;
use App\Models\EntrepriseSubscription;
use App\Models\PromoCode;
use App\Models\StripeTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as StripeSession;
use Stripe\PaymentIntent;
use Stripe\Stripe;

/**
 * Vérification robuste des paiements Stripe (checkout session, mode payment).
 *
 * Utilisé par :
 * 1. Webhook checkout.session.completed (peut échouer)
 * 2. Retour utilisateur après paiement (vérification directe Stripe)
 * 3. CRON de réconciliation (une fois par mois ou quotidien)
 */
class PaymentVerificationService
{
    /**
     * Vérifie sur Stripe qu'une session est payée, puis marque l'échéance payée.
     * Idempotent : si déjà payée, ne fait rien et retourne ok.
     *
     * @return array{ok: bool, echeance_id: ?int, already: bool, message: string}
     */
    public static function verifyAndMarkPaid(string $sessionId): array
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $session = StripeSession::retrieve($sessionId, ['expand' => ['payment_intent']]);
        } catch (\Exception $e) {
            Log::warning('PaymentVerification: Stripe session retrieve failed', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
            return [
                'ok' => false,
                'echeance_id' => null,
                'already' => false,
                'message' => 'Session Stripe introuvable ou erreur API.',
            ];
        }

        if (($session->mode ?? '') !== 'payment') {
            return [
                'ok' => false,
                'echeance_id' => null,
                'already' => false,
                'message' => 'Session non payment (subscription, etc.).',
            ];
        }

        if (($session->payment_status ?? '') !== 'paid') {
            return [
                'ok' => false,
                'echeance_id' => null,
                'already' => false,
                'message' => 'Paiement non reçu (payment_status: ' . ($session->payment_status ?? 'null') . ').',
            ];
        }

        $metadata = $session->metadata ?? [];
        if (is_object($metadata)) {
            $metadata = (array) $metadata;
        }
        $userId = (int) ($metadata['user_id'] ?? 0);
        $echeanceId = (int) ($metadata['echeance_id'] ?? 0);
        if (!$echeanceId || !$userId) {
            Log::warning('PaymentVerification: metadata user_id/echeance_id manquants', [
                'session_id' => $sessionId,
                'metadata' => $metadata,
            ]);
            return [
                'ok' => false,
                'echeance_id' => null,
                'already' => false,
                'message' => 'Métadonnées échéance manquantes.',
            ];
        }

        $echeance = Echeance::where('user_id', $userId)->find($echeanceId);
        if (!$echeance) {
            return [
                'ok' => false,
                'echeance_id' => $echeanceId,
                'already' => false,
                'message' => 'Échéance introuvable.',
            ];
        }

        // Vérifier que l'échéance n'est pas annulée ou arrêtée
        if (in_array($echeance->statut, [Echeance::STATUT_ANNULE, Echeance::STATUT_ARRETE], true)) {
            return [
                'ok' => false,
                'echeance_id' => $echeanceId,
                'already' => false,
                'message' => 'Échéance annulée ou arrêtée, impossible de payer.',
            ];
        }

        // Vérifier le montant débité correspond au montant attendu
        $amountPaid = $session->amount_total ? $session->amount_total / 100 : 0;
        $expectedAmount = (float) ($echeance->montant_final ?? $echeance->montant_du ?? 0);
        
        // Tolérance de 0.01€ pour les arrondis
        if (abs($amountPaid - $expectedAmount) > 0.01) {
            Log::warning('PaymentVerification: montant débité ne correspond pas', [
                'session_id' => $sessionId,
                'echeance_id' => $echeanceId,
                'amount_paid' => $amountPaid,
                'expected_amount' => $expectedAmount,
            ]);
            return [
                'ok' => false,
                'echeance_id' => $echeanceId,
                'already' => false,
                'message' => 'Montant débité (' . number_format($amountPaid, 2) . '€) ne correspond pas au montant attendu (' . number_format($expectedAmount, 2) . '€).',
            ];
        }

        if ($echeance->estPayee()) {
            self::ensureStripeTransaction($session, $userId);
            self::ensureEntrepriseSubscriptionForEcheance($echeance);
            return [
                'ok' => true,
                'echeance_id' => $echeanceId,
                'already' => true,
                'message' => 'Déjà enregistré.',
            ];
        }

        $pi = $session->payment_intent;
        $piId = $pi ? (is_object($pi) ? ($pi->id ?? null) : $pi) : null;

        // Utiliser une transaction avec verrou pour éviter les doubles paiements
        \DB::transaction(function () use ($echeance, $session, $piId) {
            // Recharger l'échéance avec verrou pour éviter les race conditions
            $echeanceLocked = Echeance::where('id', $echeance->id)
                ->lockForUpdate()
                ->first();
            
            if (!$echeanceLocked || $echeanceLocked->estPayee()) {
                return; // Déjà payée ou introuvable, sortir de la transaction
            }

            $echeanceLocked->update([
                'statut' => Echeance::STATUT_PAYE,
                'stripe_checkout_session_id' => $session->id,
                'stripe_payment_intent_id' => $piId,
                'paye_at' => now(),
            ]);
            
            // Mettre à jour l'objet $echeance pour la suite
            $echeance->refresh();
        });

        if ($echeance->promo_code_id) {
            PromoCode::find($echeance->promo_code_id)?->use();
        }

        self::ensureStripeTransaction($session, $userId);
        self::ensureEntrepriseSubscriptionForEcheance($echeance);

        return [
            'ok' => true,
            'echeance_id' => $echeanceId,
            'already' => false,
            'message' => 'Paiement enregistré.',
        ];
    }

    /**
     * Crée une StripeTransaction pour ce paiement si pas déjà existante (idempotent).
     */
    public static function ensureStripeTransaction(object $session, int $userId): void
    {
        $eventId = 'checkout_success_' . $session->id;
        if (StripeTransaction::where('stripe_event_id', $eventId)->exists()) {
            return;
        }
        $pi = $session->payment_intent ?? null;
        $piId = $pi ? (is_object($pi) ? ($pi->id ?? null) : $pi) : null;

        try {
            StripeTransaction::create([
                'user_id' => $userId,
                'stripe_checkout_session_id' => $session->id,
                'stripe_payment_intent_id' => $piId,
                'event_type' => 'checkout.session.completed',
                'stripe_event_id' => $eventId,
                'amount' => $session->amount_total ? $session->amount_total / 100 : null,
                'currency' => $session->currency ?? 'eur',
                'status' => 'paid',
                'metadata' => $session->metadata ? (is_array($session->metadata) ? $session->metadata : (array) $session->metadata) : [],
                'processed' => true,
                'processed_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('PaymentVerification: could not create StripeTransaction', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Marque une échéance comme payée à partir d'un PaymentIntent (flux charge Elements).
     * Idempotent : si déjà payée, ne fait rien.
     *
     * @return array{ok: bool, echeance_id: int, already: bool, message: string}
     */
    public static function markEcheancePaidFromPaymentIntent(string $paymentIntentId): array
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $pi = PaymentIntent::retrieve($paymentIntentId);
        } catch (\Exception $e) {
            Log::warning('PaymentVerification: PI retrieve failed', ['pi' => $paymentIntentId, 'error' => $e->getMessage()]);
            return ['ok' => false, 'echeance_id' => 0, 'already' => false, 'message' => 'PaymentIntent introuvable.'];
        }

        if (($pi->status ?? '') !== 'succeeded') {
            return [
                'ok' => false,
                'echeance_id' => 0,
                'already' => false,
                'message' => 'Paiement non abouti (status: ' . ($pi->status ?? 'null') . ').',
            ];
        }

        $metadata = $pi->metadata ?? [];
        if (is_object($metadata)) {
            $metadata = (array) $metadata;
        }
        $echeanceId = (int) ($metadata['echeance_id'] ?? 0);
        $userId = (int) ($metadata['user_id'] ?? 0);
        if (!$echeanceId || !$userId) {
            Log::warning('PaymentVerification: PI metadata echeance_id/user_id manquants', ['pi' => $paymentIntentId]);
            return ['ok' => false, 'echeance_id' => 0, 'already' => false, 'message' => 'Métadonnées échéance manquantes.'];
        }

        $echeance = Echeance::where('user_id', $userId)->find($echeanceId);
        if (!$echeance) {
            return ['ok' => false, 'echeance_id' => $echeanceId, 'already' => false, 'message' => 'Échéance introuvable.'];
        }

        // Vérifier que l'échéance n'est pas annulée ou arrêtée
        if (in_array($echeance->statut, [Echeance::STATUT_ANNULE, Echeance::STATUT_ARRETE], true)) {
            return [
                'ok' => false,
                'echeance_id' => $echeanceId,
                'already' => false,
                'message' => 'Échéance annulée ou arrêtée, impossible de payer.',
            ];
        }

        // Vérifier le montant débité correspond au montant attendu
        $amountPaid = $pi->amount ? $pi->amount / 100 : 0;
        $expectedAmount = (float) ($echeance->montant_final ?? $echeance->montant_du ?? 0);
        
        // Tolérance de 0.01€ pour les arrondis
        if (abs($amountPaid - $expectedAmount) > 0.01) {
            Log::warning('PaymentVerification: montant débité ne correspond pas (PI)', [
                'payment_intent_id' => $paymentIntentId,
                'echeance_id' => $echeanceId,
                'amount_paid' => $amountPaid,
                'expected_amount' => $expectedAmount,
            ]);
            return [
                'ok' => false,
                'echeance_id' => $echeanceId,
                'already' => false,
                'message' => 'Montant débité (' . number_format($amountPaid, 2) . '€) ne correspond pas au montant attendu (' . number_format($expectedAmount, 2) . '€).',
            ];
        }

        if ($echeance->estPayee()) {
            self::ensureStripeTransactionFromPaymentIntent($pi, $userId);
            self::ensureEntrepriseSubscriptionForEcheance($echeance);
            return ['ok' => true, 'echeance_id' => $echeanceId, 'already' => true, 'message' => 'Déjà enregistré.'];
        }

        // Utiliser une transaction avec verrou pour éviter les doubles paiements
        \DB::transaction(function () use ($echeance, $pi) {
            // Recharger l'échéance avec verrou pour éviter les race conditions
            $echeanceLocked = Echeance::where('id', $echeance->id)
                ->lockForUpdate()
                ->first();
            
            if (!$echeanceLocked || $echeanceLocked->estPayee()) {
                return; // Déjà payée ou introuvable, sortir de la transaction
            }

            $echeanceLocked->update([
                'statut' => Echeance::STATUT_PAYE,
                'stripe_checkout_session_id' => null,
                'stripe_payment_intent_id' => $pi->id,
                'paye_at' => now(),
            ]);
            
            // Mettre à jour l'objet $echeance pour la suite
            $echeance->refresh();
        });

        if ($echeance->promo_code_id) {
            PromoCode::find($echeance->promo_code_id)?->use();
        }

        self::ensureStripeTransactionFromPaymentIntent($pi, $userId);
        self::ensureEntrepriseSubscriptionForEcheance($echeance);

        return ['ok' => true, 'echeance_id' => $echeanceId, 'already' => false, 'message' => 'Paiement enregistré.'];
    }

    /**
     * Crée une StripeTransaction depuis un PaymentIntent (flux charge).
     */
    public static function ensureStripeTransactionFromPaymentIntent(object $pi, int $userId): void
    {
        $eventId = 'charge_pi_' . $pi->id;
        if (StripeTransaction::where('stripe_event_id', $eventId)->exists()) {
            return;
        }
        $meta = $pi->metadata ? (is_array($pi->metadata) ? $pi->metadata : (array) $pi->metadata) : [];
        try {
            StripeTransaction::create([
                'user_id' => $userId,
                'stripe_payment_intent_id' => $pi->id,
                'event_type' => 'payment_intent.succeeded',
                'stripe_event_id' => $eventId,
                'amount' => $pi->amount ? $pi->amount / 100 : null,
                'currency' => $pi->currency ?? 'eur',
                'status' => 'paid',
                'metadata' => $meta,
                'processed' => true,
                'processed_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('PaymentVerification: StripeTransaction from PI failed', ['pi' => $pi->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Indique si une session Stripe correspond à notre checkout échéance (metadata echeance_id).
     */
    public static function isEcheanceCheckout(object $session): bool
    {
        $metadata = $session->metadata ?? [];
        if (is_object($metadata)) {
            $metadata = (array) $metadata;
        }
        return !empty($metadata['echeance_id']) && !empty($metadata['user_id']);
    }

    /**
     * Crée ou met à jour EntrepriseSubscription lorsqu'une échéance entreprise est payée.
     * Permet au CRON de générer les prochaines échéances et à l'app de considérer l'option active.
     */
    public static function ensureEntrepriseSubscriptionForEcheance(Echeance $echeance): void
    {
        if (!$echeance->entreprise_id || !$echeance->subscription_type) {
            return;
        }
        if (!in_array($echeance->subscription_type, [Echeance::TYPE_SITE_WEB, Echeance::TYPE_MULTI_PERSONNES], true)) {
            return;
        }

        $sub = EntrepriseSubscription::where('entreprise_id', $echeance->entreprise_id)
            ->where('type', $echeance->subscription_type)
            ->first();

        if ($sub) {
            if ($sub->actif_jusqu && $echeance->periode_fin && $echeance->periode_fin->gt($sub->actif_jusqu)) {
                $sub->update(['actif_jusqu' => $echeance->periode_fin]);
            }
            return;
        }

        $user = $echeance->user;
        $jour = $user ? ($user->jour_facturation ?? 1) : 1;

        try {
            EntrepriseSubscription::create([
                'entreprise_id' => $echeance->entreprise_id,
                'type' => $echeance->subscription_type,
                'name' => 'echeance_' . $echeance->subscription_type . '_' . $echeance->entreprise_id,
                'est_manuel' => false,
                'stripe_id' => null,
                'stripe_status' => null,
                'stripe_price' => null,
                'actif_jusqu' => $echeance->periode_fin,
                'jour_renouvellement' => $jour,
            ]);
        } catch (\Throwable $e) {
            Log::warning('PaymentVerification: ensureEntrepriseSubscription failed', [
                'echeance_id' => $echeance->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
