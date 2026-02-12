<?php

namespace App\Services;

use App\Models\Echeance;
use App\Models\PaymentAuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Refund;
use Stripe\Stripe;

/**
 * Service de remboursement Stripe pour les échéances payées.
 *
 * Supporte le remboursement total et partiel via PaymentIntent.
 * Idempotent : refuse un second remboursement si déjà effectué.
 */
class RefundService
{
    /**
     * Rembourser une échéance (total ou partiel).
     *
     * @param Echeance $echeance  L'échéance payée à rembourser
     * @param float|null $amount  Montant à rembourser (null = total)
     * @param string $reason      Raison Stripe : duplicate, fraudulent, requested_by_customer
     * @param string|null $notes  Notes internes admin
     * @param int|null $adminId   ID de l'admin qui effectue le remboursement
     *
     * @return array{ok: bool, message: string, refund_id: ?string}
     */
    public static function refund(
        Echeance $echeance,
        ?float $amount = null,
        string $reason = 'requested_by_customer',
        ?string $notes = null,
        ?int $adminId = null,
    ): array {
        // --- Garde-fous ---

        if (!$echeance->estPayee() && !$echeance->estPartielementRemboursee()) {
            return ['ok' => false, 'message' => 'Seules les échéances payées peuvent être remboursées.', 'refund_id' => null];
        }

        if (!$echeance->stripe_payment_intent_id) {
            return ['ok' => false, 'message' => 'Aucun PaymentIntent Stripe associé. Remboursement impossible via l\'API.', 'refund_id' => null];
        }

        // Déjà entièrement remboursée
        if ($echeance->statut === Echeance::STATUT_REMBOURSE) {
            return ['ok' => false, 'message' => 'Cette échéance a déjà été remboursée.', 'refund_id' => $echeance->stripe_refund_id];
        }

        // Montant
        $maxRefundable = (float) ($echeance->montant_final ?? $echeance->montant_du ?? 0);
        if ($echeance->refund_amount) {
            $maxRefundable -= (float) $echeance->refund_amount;
        }
        if ($maxRefundable <= 0) {
            return ['ok' => false, 'message' => 'Aucun montant restant à rembourser.', 'refund_id' => null];
        }

        $refundAmount = $amount ? min($amount, $maxRefundable) : $maxRefundable;
        if ($refundAmount <= 0) {
            return ['ok' => false, 'message' => 'Le montant de remboursement doit être supérieur à 0.', 'refund_id' => null];
        }

        $isFullRefund = abs($refundAmount - $maxRefundable) < 0.01;

        // --- Appel Stripe ---
        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $refundParams = [
                'payment_intent' => $echeance->stripe_payment_intent_id,
                'amount' => (int) round($refundAmount * 100), // Stripe attend des centimes
                'reason' => $reason,
                'metadata' => [
                    'echeance_id' => $echeance->id,
                    'user_id' => $echeance->user_id,
                    'admin_id' => $adminId,
                    'notes' => $notes,
                ],
            ];

            $stripeRefund = Refund::create($refundParams);

            Log::info('Remboursement Stripe effectué', [
                'refund_id' => $stripeRefund->id,
                'echeance_id' => $echeance->id,
                'amount' => $refundAmount,
                'status' => $stripeRefund->status,
                'is_full' => $isFullRefund,
            ]);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            Log::error('Refund Stripe InvalidRequestException', [
                'echeance_id' => $echeance->id,
                'pi' => $echeance->stripe_payment_intent_id,
                'error' => $e->getMessage(),
            ]);

            // Cas courant : "charge already refunded"
            if (str_contains($e->getMessage(), 'already been refunded')) {
                // Marquer localement comme remboursé (rattrapage)
                $echeance->update([
                    'statut' => Echeance::STATUT_REMBOURSE,
                    'refund_status' => 'succeeded',
                    'refund_amount' => $maxRefundable + (float) ($echeance->refund_amount ?? 0),
                    'refund_reason' => $reason,
                    'refund_notes' => ($notes ?? '') . ' [Rattrapage : déjà remboursé chez Stripe]',
                    'refunded_by' => $adminId,
                    'refunded_at' => now(),
                ]);
                return ['ok' => true, 'message' => 'Déjà remboursé chez Stripe. Base de données mise à jour.', 'refund_id' => null];
            }

            return ['ok' => false, 'message' => 'Erreur Stripe : ' . $e->getMessage(), 'refund_id' => null];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Refund Stripe ApiErrorException', [
                'echeance_id' => $echeance->id,
                'error' => $e->getMessage(),
            ]);
            return ['ok' => false, 'message' => 'Erreur API Stripe : ' . $e->getMessage(), 'refund_id' => null];
        }

        // --- Mise à jour BDD ---
        DB::transaction(function () use ($echeance, $stripeRefund, $refundAmount, $isFullRefund, $reason, $notes, $adminId) {
            $echeanceLocked = Echeance::where('id', $echeance->id)->lockForUpdate()->first();
            if (!$echeanceLocked) {
                return;
            }

            $totalRefunded = (float) ($echeanceLocked->refund_amount ?? 0) + $refundAmount;

            $echeanceLocked->update([
                'stripe_refund_id' => $stripeRefund->id,
                'refund_amount' => round($totalRefunded, 2),
                'refund_status' => $stripeRefund->status, // succeeded, pending
                'refund_reason' => $reason,
                'refund_notes' => $notes,
                'refunded_by' => $adminId,
                'refunded_at' => now(),
                'statut' => $isFullRefund ? Echeance::STATUT_REMBOURSE : $echeanceLocked->statut,
            ]);
        });

        // --- Audit log ---
        try {
            PaymentAuditLog::log('refund', $echeance->user_id, [
                'echeance_id' => $echeance->id,
                'stripe_payment_intent_id' => $echeance->stripe_payment_intent_id,
                'stripe_refund_id' => $stripeRefund->id ?? null,
                'amount' => $refundAmount,
                'currency' => 'eur',
                'status' => $stripeRefund->status ?? 'unknown',
                'context' => [
                    'reason' => $reason,
                    'notes' => $notes,
                    'admin_id' => $adminId,
                    'is_full' => $isFullRefund,
                ],
                'message' => ($isFullRefund ? 'Remboursement total' : 'Remboursement partiel') . " de {$refundAmount} € effectué.",
            ]);
        } catch (\Throwable $e) {
            Log::warning('PaymentAuditLog refund failed', ['error' => $e->getMessage()]);
        }

        $echeance->refresh();

        return [
            'ok' => true,
            'message' => ($isFullRefund ? 'Remboursement total' : 'Remboursement partiel')
                . " de " . number_format($refundAmount, 2, ',', ' ') . " € effectué avec succès.",
            'refund_id' => $stripeRefund->id,
        ];
    }
}
