<?php

namespace App\Services\BillingLab\Scenarios;

use App\Models\Echeance;
use App\Services\BillingLab\ScenarioContext;
use App\Services\BillingLab\ScenarioResult;
use App\Services\PaymentVerificationService;
use Carbon\Carbon;

class PremiumSingleChargerScenario extends AbstractScenario
{
    public function id(): string
    {
        return 'premium_single_charger';
    }

    public function label(): string
    {
        return 'Premium web : 1er paiement = un seul préleveur (échéances)';
    }

    public function group(): string
    {
        return 'unlock';
    }

    public function run(ScenarioContext $ctx): ScenarioResult
    {
        $paidAt = Carbon::create(2026, 4, 17, 10, 0);
        $ctx->clock->travelTo($paidAt);

        $user = $ctx->fixtures->user();
        $debut = $paidAt->copy()->startOfDay();
        $fin = $debut->copy()->addMonth()->subDay();

        $echeance = $ctx->fixtures->echeance($user, [
            'subscription_type' => Echeance::TYPE_DEFAULT,
            'statut' => Echeance::STATUT_PAYE,
            'periode_debut' => $debut,
            'periode_fin' => $fin,
            'paye_at' => $paidAt,
            'montant_final' => 14,
        ]);

        PaymentVerificationService::ensurePremiumAccessForEcheance($echeance->fresh());
        $user->refresh();

        $cashierCount = $user->subscriptions()->count();
        $details = [
            'access' => $user->aAbonnementActif(),
            'jour_facturation' => $user->jour_facturation,
            'premium_actif_jusqu' => $user->premium_actif_jusqu?->toDateString(),
            'payment_provider' => $user->payment_provider,
            'cashier_count' => $cashierCount,
            'ledger' => $ctx->ledger->forUser($user),
        ];

        if (! $user->aAbonnementActif()) {
            return ScenarioResult::fail('Le 1er paiement n’a pas débloqué Premium.', $details);
        }

        if ((int) $user->jour_facturation !== 17) {
            return ScenarioResult::fail('jour_facturation n’est pas le jour du 1er paiement.', $details);
        }

        if ($cashierCount > 0) {
            return ScenarioResult::fail('Un abonnement Cashier a été créé (double moteur).', $details);
        }

        if ($user->payment_provider !== Echeance::PROVIDER_STRIPE) {
            return ScenarioResult::fail('payment_provider devrait être stripe.', $details);
        }

        return ScenarioResult::pass('Un seul préleveur : échéance payée, jour 17 figé, aucun Cashier.', $details);
    }
}
