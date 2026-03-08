<?php

namespace App\Services\Payments;

use App\Models\Echeance;
use App\Models\Entreprise;
use App\Models\PaymentAuditLog;
use App\Models\User;
use Carbon\Carbon;

class ManualDebtService
{
    public function createManualDebt(array $data, int $adminId): Echeance
    {
        $entreprise = null;
        $user = User::findOrFail((int) $data['user_id']);

        if (!empty($data['entreprise_id'])) {
            $entreprise = Entreprise::with('user')->findOrFail((int) $data['entreprise_id']);
            if ($entreprise->user_id !== $user->id) {
                $user = $entreprise->user;
            }
        }

        $periodeDebut = Carbon::parse($data['periode_debut'])->startOfDay();
        $periodeFin = Carbon::parse($data['periode_fin'])->endOfDay();
        $montantDu = (float) $data['montant_du'];

        $echeance = Echeance::create([
            'user_id' => $user->id,
            'entreprise_id' => $entreprise?->id,
            'subscription_type' => $data['subscription_type'],
            'payment_origin' => Echeance::ORIGIN_MANUAL,
            'payment_provider' => null,
            'auto_charge_eligible' => false,
            'periode_debut' => $periodeDebut,
            'periode_fin' => $periodeFin,
            'jour_facturation' => (int) ($data['jour_facturation'] ?? $periodeDebut->day),
            'montant_du' => $montantDu,
            'montant_final' => $montantDu,
            'reduction_promo' => 0,
            'reduction_manuel' => 0,
            'statut' => Echeance::STATUT_A_PAYER,
            'metadata' => array_filter([
                'manual' => true,
                'manual_note' => $data['note'] ?? null,
                'created_by_admin_id' => $adminId,
            ]),
        ]);

        PaymentAuditLog::log('manual_debt_created', $user->id, [
            'echeance_id' => $echeance->id,
            'amount' => $montantDu,
            'status' => 'a_payer',
            'context' => ['admin_id' => $adminId],
            'message' => 'Dette manuelle créée depuis l\'admin.',
        ]);

        return $echeance;
    }

    public function markManualPaid(Echeance $echeance, array $data, int $adminId): Echeance
    {
        $metadata = $echeance->metadata ?? [];
        $metadata['manual_payment'] = [
            'mode' => $data['payment_mode'],
            'note' => $data['note'] ?? null,
            'paid_amount' => (float) $data['paid_amount'],
            'paid_at' => Carbon::parse($data['paid_at'])->toIso8601String(),
            'admin_id' => $adminId,
        ];

        $echeance->update([
            'statut' => Echeance::STATUT_PAYE,
            'paye_at' => Carbon::parse($data['paid_at']),
            'montant_final' => (float) $data['paid_amount'],
            'payment_origin' => Echeance::ORIGIN_MANUAL,
            'payment_provider' => null,
            'auto_charge_eligible' => false,
            'metadata' => $metadata,
        ]);

        PaymentAuditLog::log('manual_debt_paid', $echeance->user_id, [
            'echeance_id' => $echeance->id,
            'amount' => (float) $data['paid_amount'],
            'status' => 'paye',
            'context' => ['admin_id' => $adminId, 'mode' => $data['payment_mode']],
            'message' => 'Dette marquée payée manuellement.',
        ]);

        return $echeance->refresh();
    }
}
