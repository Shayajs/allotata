<?php

namespace App\Services\BillingLab\Scenarios;

use App\Models\Echeance;
use App\Services\BillingLab\ScenarioContext;
use App\Services\BillingLab\ScenarioResult;
use Carbon\Carbon;

class JourFacturationUnsetScenario extends AbstractScenario
{
    public function id(): string
    {
        return 'jour_facturation_unset';
    }

    public function label(): string
    {
        return 'Garde : jour_facturation null saute le CRON';
    }

    public function group(): string
    {
        return 'renewal';
    }

    public function run(ScenarioContext $ctx): ScenarioResult
    {
        $user = $ctx->fixtures->user([
            'payment_provider' => Echeance::PROVIDER_STRIPE,
            'premium_actif_jusqu' => Carbon::create(2026, 5, 16),
            'jour_facturation' => null,
        ]);

        $ctx->clock->travelTo(Carbon::create(2026, 5, 17, 6, 0));
        $ctx->runCheckEcheances();

        $created = Echeance::query()
            ->where('user_id', $user->id)
            ->where('subscription_type', Echeance::TYPE_DEFAULT)
            ->exists();

        $details = [
            'jour_facturation' => $user->fresh()->jour_facturation,
            'echeance_created' => $created,
        ];

        if ($created) {
            return ScenarioResult::fail('jour_facturation null a quand même créé une échéance.', $details, [
                'unset_jour_skips_renewal' => false,
            ]);
        }

        return ScenarioResult::pass(
            'Sans jour_facturation, le CRON ne crée pas d’échéance (le champ est figé au 1er paiement).',
            $details,
            ['unset_jour_skips_renewal' => true]
        );
    }
}
