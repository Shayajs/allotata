<?php

namespace App\Services\BillingLab\Scenarios;

use App\Models\Echeance;
use App\Services\BillingLab\ScenarioContext;
use App\Services\BillingLab\ScenarioResult;
use App\Services\PlayBilling\PlayBillingFulfillment;
use Carbon\Carbon;

class PlayStripeIsolationScenario extends AbstractScenario
{
    public function id(): string
    {
        return 'play_stripe_isolation';
    }

    public function label(): string
    {
        return 'Preuve : isolation Play vs CRON Stripe';
    }

    public function group(): string
    {
        return 'collision';
    }

    public function run(ScenarioContext $ctx): ScenarioResult
    {
        if ($skip = $this->requirePlayTable()) {
            return $skip;
        }

        $ctx->useFakePlay();
        $day = (int) now()->day;
        $user = $ctx->fixtures->user(['jour_facturation' => $day]);
        $entreprise = $ctx->fixtures->entreprise($user);

        $premiumToken = 'play-iso-premium-'.uniqid();
        $ctx->fakePlay->setResponse($premiumToken, [
            'valid' => true,
            'expires_at' => Carbon::now()->addMonth(),
            'acknowledged' => true,
        ]);
        app(PlayBillingFulfillment::class)->fulfill(
            $user,
            (string) config('play.products.premium.id'),
            $premiumToken
        );

        $addonToken = 'play-iso-site-'.uniqid();
        $ctx->fakePlay->setResponse($addonToken, [
            'valid' => true,
            'expires_at' => Carbon::now()->addMonth(),
            'acknowledged' => true,
        ]);
        app(PlayBillingFulfillment::class)->fulfill(
            $user,
            (string) config('play.products.site_web.id'),
            $addonToken,
            'GPA.ISO.SITE',
            $entreprise->id
        );

        $sub = $entreprise->fresh()->abonnementSiteWeb();
        $sub?->update(['jour_renouvellement' => $day]);

        $ctx->runCheckEcheances();

        $premiumEcheance = Echeance::query()
            ->where('user_id', $user->id)
            ->whereNull('entreprise_id')
            ->where('subscription_type', Echeance::TYPE_DEFAULT)
            ->exists();

        $addonEcheance = Echeance::query()
            ->where('user_id', $user->id)
            ->where('entreprise_id', $entreprise->id)
            ->where('subscription_type', Echeance::TYPE_SITE_WEB)
            ->where('payment_provider', Echeance::PROVIDER_STRIPE)
            ->exists();

        $findings = [
            'play_premium_safe' => ! $premiumEcheance,
            'play_addon_leak' => $addonEcheance,
        ];

        $details = [
            'premium_stripe_echeance' => $premiumEcheance,
            'addon_stripe_echeance' => $addonEcheance,
            'jour_renouvellement' => $sub?->fresh()->jour_renouvellement,
        ];

        if ($addonEcheance) {
            return ScenarioResult::evidenceRisk(
                'PROUVÉ : un add-on Play avec jour_renouvellement reçoit une échéance Stripe auto. Premium Play sans Cashier est épargné.',
                $details,
                $findings
            );
        }

        if ($premiumEcheance) {
            return ScenarioResult::evidenceRisk(
                'PROUVÉ : un user Play Premium a reçu une échéance Stripe (Cashier ou trou dans le filtre).',
                $details,
                $findings
            );
        }

        return ScenarioResult::evidenceSafe(
            'Play isolé du CRON Stripe : Premium et add-on Play ne reçoivent pas d’échéance auto.',
            $details,
            $findings
        );
    }
}
